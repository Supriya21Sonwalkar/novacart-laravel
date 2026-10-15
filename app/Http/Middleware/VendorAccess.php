<?php
namespace App\Http\Middleware;
use Closure;
use App\Vendor;
use App\StoreRecord;
class VendorAccess {
 public function handle($r,Closure $next){
  if(!$r->user())return redirect()->guest('/vendor/login');
  abort_unless($r->user()->active&&optional(StoreRecord::in('roles')->find($r->user()->role_id))->name==='vendor',403);
  $vendor=Vendor::where('user_id',$r->user()->id)->where('active',true)->first();abort_unless($vendor,403,'Your vendor store is inactive or not linked. Contact the store admin.');
  $r->attributes->set('vendor',$vendor);return $next($r);
 }
}
