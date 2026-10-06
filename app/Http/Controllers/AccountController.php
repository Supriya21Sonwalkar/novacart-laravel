<?php
namespace App\Http\Controllers;
use App\User;
use App\StoreRecord;
use App\Services\Commerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\Registered;
class AccountController extends Controller {
 public function loginForm(){return view('auth.form',['mode'=>'login']);}
 public function registerForm(){return view('auth.form',['mode'=>'register']);}
 public function login(Request $r){$v=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($v+['active'=>1],$r->boolean('remember')))return back()->withErrors(['email'=>'The email or password is incorrect.'])->withInput($r->only('email'));$r->session()->regenerate();if(!\App\Services\CustomerProfile::complete(Auth::user()))return redirect('/account')->with('notice','Complete your contact details and default address to start shopping.');Commerce::mergeGuestCart();return redirect()->intended('/account');}
 public function register(Request $r){$v=$r->validate(\App\Services\CustomerProfile::rules()+['password'=>'required|string|min:8|confirmed']);$u=User::create(['name'=>$v['name'],'email'=>$v['email'],'password'=>Hash::make($v['password'])]);$u->role_id=StoreRecord::in('roles')->where('name','customer')->value('id');$u->save();\DB::table('role_user')->insert(['role_id'=>$u->role_id,'user_id'=>$u->id]);\App\Services\CustomerProfile::save($u,$v);event(new Registered($u));Auth::login($u);$r->session()->regenerate();Commerce::mergeGuestCart();return redirect('/account')->with('success','Account created. A verification link has been sent through the configured mail driver.');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}
 public function profile(Request $r){$v=$r->validate(\App\Services\CustomerProfile::rules($r->user()->id));$changed=$r->user()->email!==$v['email'];\App\Services\CustomerProfile::save($r->user(),$v);Commerce::mergeGuestCart();if($changed)$r->user()->sendEmailVerificationNotification();return redirect('/account')->with('success','Profile saved. You can now add products and place orders.');}
 public function password(Request $r){$v=$r->validate(['current_password'=>'required','password'=>'required|string|min:8|confirmed']);if(!Hash::check($v['current_password'],$r->user()->password))return back()->withErrors(['current_password'=>'Current password is incorrect.']);$r->user()->password=Hash::make($v['password']);$r->user()->setRememberToken(\Illuminate\Support\Str::random(60));$r->user()->save();$r->session()->regenerate();Commerce::log('Password changed');return back()->with('success','Password changed.');}
 public function forgotForm(){return view('auth.form',['mode'=>'forgot']);}
 public function sendReset(Request $r){$r->validate(['email'=>'required|email']);Password::sendResetLink($r->only('email'));return back()->with('success','If an account exists, a reset link has been sent through the configured mail driver.');}
 public function resetForm(Request $r,$token){return view('auth.form',['mode'=>'reset','token'=>$token,'email'=>$r->query('email')]);}
 public function reset(Request $r){$r->validate(['email'=>'required|email','token'=>'required','password'=>'required|min:8|confirmed']);$status=Password::reset($r->only('email','token','password','password_confirmation'),function($u,$p){$u->password=Hash::make($p);$u->setRememberToken(\Illuminate\Support\Str::random(60));$u->save();});return $status===Password::PASSWORD_RESET?redirect('/login')->with('success','Password reset. Sign in with your new password.'):back()->withErrors(['email'=>trans($status)]);}
 public function verify(Request $r,$id,$hash){abort_unless((string)$r->user()->id===(string)$id&&hash_equals(sha1($r->user()->getEmailForVerification()),$hash),403);$r->user()->markEmailAsVerified();return redirect('/account')->with('success','Email verified.');}
 public function resend(Request $r){if(!$r->user()->hasVerifiedEmail())$r->user()->sendEmailVerificationNotification();return back()->with('success','Verification link sent through the configured mail driver.');}
}
