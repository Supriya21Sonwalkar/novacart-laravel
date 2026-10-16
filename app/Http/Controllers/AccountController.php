<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use App\User;
use App\StoreRecord;
use App\Services\Commerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\Registered;

class AccountController extends Controller
{
    public function adminLoginForm()
    {
        try {
            return view('auth.admin-login');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function adminLogin(Request $r)
    {
        try {
            \App\Services\CustomerProfile::normalizeRequest($r);
            $v = $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string']);
            $user = User::where('email', $v['email'])->where('active', true)->first();
            $destination = null;
            if ($user) {
                foreach (array_merge(['dashboard'], array_keys(config('store.modules'))) as $module) {
                    if ($user->canManage($module)) {
                        $destination = $module === 'dashboard' ? '/admin' : '/admin/' . $module;
                        break;
                    }
                }
            }
            if (!$destination || !Hash::check($v['password'], $user->password)) {
                return back()->withErrors(['email' => 'The admin email or password is incorrect.'])->withInput($r->only('email'));
            }
            Auth::login($user);
            $r->session()->regenerate();
            return redirect($destination);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    private function paymentMethods()
    {
        try {
            return \App\Services\PaymentFlow::methods();
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function paymentForm()
    {
        try {
            return view('shop.payments', ['methods' => $this->paymentMethods()]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function paymentSave(Request $r)
    {
        try {
            $v = $r->validate([
                'payment_preference' => [
                    'required',
                    'string',
                    \Illuminate\Validation\Rule::in($this->paymentMethods()),
                ],
            ]);
            $r->user()->payment_preference = $v['payment_preference'];
            $r->user()->save();
            return back()->with('success', 'Preferred test payment method saved.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function loginForm()
    {
        try {
            return view('auth.form', ['mode' => 'login']);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function registerForm()
    {
        try {
            return view('auth.form', ['mode' => 'register']);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function login(Request $r)
    {
        try {
            \App\Services\CustomerProfile::normalizeRequest($r);
            $v = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
            if (!Auth::attempt($v + ['active' => 1], $r->boolean('remember'))) {
                return back()->withErrors(['email' => 'The email or password is incorrect.'])->withInput($r->only('email'));
            }
            $r->session()->regenerate();
            if (config('store_management.vendor_portal') && optional(StoreRecord::in('roles')->find(Auth::user()->role_id))->name === 'vendor') {
                return redirect('/vendor');
            }
            if (optional(StoreRecord::in('roles')->find(Auth::user()->role_id))->name === 'delivery partner') {
                return redirect('/rider');
            }
            if (Auth::user()->canManage('dashboard')) {
                return redirect('/admin');
            }
            if (!\App\Services\CustomerProfile::complete(Auth::user())) {
                return redirect('/account')->with('notice', 'Complete your contact details and default address to start shopping.');
            }
            Commerce::mergeGuestCart();
            return session('shopping_intent') ? redirect('/shopping/resume') : redirect()->intended('/account');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function register(Request $r)
    {
        try {
            \App\Services\CustomerProfile::normalizeRequest($r);
            $v = $r->validate(\App\Services\CustomerProfile::rules() + [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:72',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/s',
                ],
            ], \App\Services\CustomerProfile::messages());
            try {
                $u = DB::transaction(function () use ($v) {
                    $u = User::create([
                        'name' => $v['name'],
                        'email' => $v['email'],
                        'password' => Hash::make($v['password']),
                    ]);
                    $u->role_id = StoreRecord::in('roles')->where('name', 'customer')->value('id');
                    $u->save();
                    \DB::table('role_user')->insert(['role_id' => $u->role_id, 'user_id' => $u->id]);
                    \App\Services\CustomerProfile::save($u, $v);
                    return $u;
                });
            } catch (\Illuminate\Database\QueryException $e) {
                \App\Services\CustomerProfile::duplicateError($e);
            }
            $mailSent = \App\Support\PostCommitNotification::attempt(function () use ($u) {
                event(new Registered($u));
            });
            Auth::login($u);
            $r->session()->regenerate();
            Commerce::mergeGuestCart();
            return redirect(session('shopping_intent') ? '/shopping/resume' : '/account')->with('success', $mailSent ? 'Account created. A verification link has been sent through the configured mail driver.' : 'Account created, but the verification message could not be sent. Use Resend verification from your account.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function logout(Request $r)
    {
        try {
            Auth::logout();
            $r->session()->invalidate();
            $r->session()->regenerateToken();
            return redirect('/');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function profile(Request $r)
    {
        try {
            \App\Services\CustomerProfile::normalizeRequest($r);
            $v = $r->validate(\App\Services\CustomerProfile::rules($r->user()->id), \App\Services\CustomerProfile::messages());
            $changed = $r->user()->email !== $v['email'];
            \App\Services\CustomerProfile::save($r->user(), $v);
            Commerce::mergeGuestCart();
            $mailSent = !$changed || \App\Support\PostCommitNotification::attempt(function () use ($r) {
                $r->user()->sendEmailVerificationNotification();
            });
            return redirect(session('shopping_intent') ? '/shopping/resume' : '/account')->with('success', $mailSent ? 'Profile saved. You can now add products and place orders.' : 'Profile saved, but verification mail could not be sent. Use Resend verification from your account.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function password(Request $r)
    {
        try {
            $v = $r->validate([
                'current_password' => 'required',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:72',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/s',
                ],
            ], \App\Services\CustomerProfile::messages());
            if (!Hash::check($v['current_password'], $r->user()->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            DB::transaction(function () use ($r, $v) {
                $user = User::where('id', $r->user()->id)->lockForUpdate()->firstOrFail();
                if (!Hash::check($v['current_password'], $user->password)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
                }
                $user->password = Hash::make($v['password']);
                $user->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
                Commerce::log('Password changed');
            });
            $r->session()->regenerate();
            return back()->with('success', 'Password changed.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function forgotForm()
    {
        try {
            return view('auth.form', ['mode' => 'forgot']);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function sendReset(Request $r)
    {
        try {
            \App\Services\CustomerProfile::normalizeRequest($r);
            $r->validate(['email' => 'required|email']);
            try {
                // The broker creates the token and sends mail: do not put external mail in a retryable transaction.
                Password::sendResetLink($r->only('email'));
            } catch (\Throwable $e) {
                report($e);
                return back()->withErrors(['email' => 'We could not confirm sending the reset message. Please try again later.']);
            }
            return back()->with('success', 'If an account exists, a reset link has been sent through the configured mail driver.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function resetForm(Request $r, $token)
    {
        try {
            return view('auth.form', [
                'mode' => 'reset',
                'token' => $token,
                'email' => $r->query('email'),
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function reset(Request $r)
    {
        try {
            \App\Services\CustomerProfile::normalizeRequest($r);
            $r->validate([
                'email' => 'required|email',
                'token' => 'required',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:72',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/s',
                ],
            ], \App\Services\CustomerProfile::messages());
            $status = DB::transaction(function () use ($r) {
                // Serialize token consumption with the password change on the same connection.
                User::where('email', $r->input('email'))->lockForUpdate()->first();
                \DB::table('password_resets')->where('email', $r->input('email'))->lockForUpdate()->first();
                return Password::reset($r->only('email', 'token', 'password', 'password_confirmation'), function ($u, $p) {
                    $u->password = Hash::make($p);
                    $u->setRememberToken(\Illuminate\Support\Str::random(60));
                    $u->save();
                });
            }, 3);
            return $status === Password::PASSWORD_RESET ? redirect('/login')->with('success', 'Password reset. Sign in with your new password.') : back()->withErrors(['email' => trans($status)]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function verify(Request $r, $id, $hash)
    {
        try {
            abort_unless((string) $r->user()->id === (string) $id && hash_equals(sha1($r->user()->getEmailForVerification()), $hash), 403);
            $r->user()->markEmailAsVerified();
            return redirect('/account')->with('success', 'Email verified.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function resend(Request $r)
    {
        try {
            if ($r->user()->hasVerifiedEmail()) {
                return back()->with('notice', 'Your email is already verified.');
            }
            $sent = \App\Support\PostCommitNotification::attempt(function () use ($r) {
                $r->user()->sendEmailVerificationNotification();
            });
            return $sent ? back()->with('success', 'Verification link sent through the configured mail driver.') : back()->withErrors(['email' => 'The verification message could not be sent. Please try again later.']);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
}
