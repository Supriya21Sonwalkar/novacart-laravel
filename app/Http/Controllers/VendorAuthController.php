<?php
namespace App\Http\Controllers;
use App\Vendor;
use App\StoreRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class VendorAuthController extends Controller {
 public function form(){
        try {
            return view('vendor.login');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
 public function login(Request $r){
        try {
            \App\Services\CustomerProfile::normalizeRequest($r);
            $v = $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string']);
            if (!Auth::attempt($v + ['active' => true], $r->boolean('remember'))) {
                return back()->withErrors(['email' => 'The email or password is incorrect.'])->withInput($r->only('email'));
            }
            $user = $r->user();
            if (optional(StoreRecord::in('roles')->find($user->role_id))->name !== 'vendor' || !Vendor::where('user_id', $user->id)->where('active', true)->exists()) {
                Auth::logout();
                return back()->withErrors(['email' => 'This account is not linked to an active vendor store.']);
            }
            $r->session()->regenerate();
            return redirect('/vendor');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
}
