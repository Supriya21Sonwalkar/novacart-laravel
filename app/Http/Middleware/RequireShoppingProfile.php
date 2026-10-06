<?php
namespace App\Http\Middleware;
use Closure;
use App\Services\CustomerProfile;
class RequireShoppingProfile {
 public function handle($request,Closure $next){
  if($request->routeIs('cart','cart.add','cart.update','coupon','checkout','checkout.place','orders.reorder')){
   if(!auth()->check()){
    if($request->expectsJson())return response()->json(['message'=>'Sign in and complete your profile before shopping.'],401);
    return redirect()->guest(route('login'))->with('notice','Sign in and complete your profile before adding products or checking out.');
   }
   if(!CustomerProfile::complete(auth()->user())){
    if($request->expectsJson())return response()->json(['message'=>'Complete your profile before shopping.','profile_url'=>route('account')],403);
    return redirect()->route('account')->with('notice','Complete all required profile and address details before adding products or placing orders.');
   }
  }
  return $next($request);
 }
}
