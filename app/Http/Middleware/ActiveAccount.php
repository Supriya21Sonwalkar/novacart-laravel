<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
class ActiveAccount {public function handle($request,Closure $next){if(Auth::check()&&!Auth::user()->active){Auth::logout();$request->session()->invalidate();return redirect()->route('login')->withErrors(['email'=>'This account is inactive.']);}return $next($request);}}
