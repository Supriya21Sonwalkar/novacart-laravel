<?php
use Illuminate\Support\Facades\Route;
Route::middleware([\App\Http\Middleware\ActiveAccount::class])->group(function(){
 Route::post('/delivery/location','DeliveryController@location');
 Route::get('/search/suggestions','DeliveryController@suggestions')->middleware('throttle:90,1');
 Route::middleware('auth')->group(function(){
  Route::get('/delivery/options','DeliveryController@slots');Route::get('/orders/{order}/tracking','DeliveryController@tracking');
  Route::get('/rider','DeliveryController@riderPanel');Route::post('/rider/orders/{order}','DeliveryController@update')->middleware('throttle:120,1');
  Route::get('/admin/delivery','DeliveryController@admin');Route::post('/admin/delivery/zones','DeliveryController@zoneSave');Route::post('/admin/delivery/slots','DeliveryController@slotSave');Route::post('/admin/delivery/orders/{order}/assign','DeliveryController@assign');
 });
});
