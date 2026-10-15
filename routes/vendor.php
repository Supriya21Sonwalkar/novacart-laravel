<?php
use Illuminate\Support\Facades\Route;
if (!config('store_management.vendor_portal')) {
 Route::get('/vendor/{path?}', function () { return redirect('/admin/login')->with('notice', 'Use the store administrator login to manage NovaCart.'); })->where('path', '.*');
 Route::post('/vendor/{path?}', function () { abort(403, 'The separate vendor portal is disabled. Use the store administrator account.'); })->where('path', '.*');
 return;
}
Route::get('/vendor/login','VendorAuthController@form')->name('vendor.login');
Route::post('/vendor/login','VendorAuthController@login')->middleware('throttle:6,1');
Route::prefix('vendor')->name('vendor.')->group(function(){
 Route::get('/','VendorController@home')->name('home');
 Route::get('/products','VendorController@products')->name('products');Route::get('/products/create','VendorController@productForm')->name('products.create');Route::post('/products','VendorController@productSave')->name('products.store');Route::get('/products/{id}/edit','VendorController@productForm')->name('products.edit');Route::put('/products/{id}','VendorController@productSave')->name('products.update');Route::post('/products/{id}/deactivate','VendorController@deactivate')->name('products.deactivate');Route::get('/subcategories','VendorController@subcategories')->name('subcategories');
 Route::get('/inventory','VendorController@inventory')->name('inventory');Route::post('/inventory/requests','VendorController@stockRequest')->name('stock-request');
 Route::get('/orders','VendorController@orders')->name('orders');Route::get('/orders/{id}','VendorController@order')->name('orders.show');Route::post('/orders/{id}/action','VendorController@orderAction')->name('orders.action');
 Route::get('/purchases/{id}','VendorController@purchase')->name('purchases.show');Route::post('/purchases/{id}/action','VendorController@purchaseAction')->name('purchases.action');
 Route::get('/shipping','VendorController@shipping')->name('shipping');Route::post('/shipping/{id}','VendorController@shippingSave')->name('shipping.save');
 Route::get('/analytics','VendorController@analytics')->name('analytics');Route::get('/analytics/export','VendorController@export')->name('export');Route::get('/payments','VendorController@payments')->name('payments');Route::get('/categories','VendorController@categories')->name('categories');
 Route::get('/reviews','VendorController@reviews')->name('reviews');Route::post('/reviews/{id}','VendorController@reviewResponse')->name('reviews.response');Route::get('/notifications','VendorController@notifications')->name('notifications');Route::post('/notifications/read','VendorController@readNotifications')->name('notifications.read');
 Route::get('/profile','VendorController@profile')->name('profile');Route::post('/profile','VendorController@profileSave')->name('profile.save');Route::get('/settings','VendorController@settings')->name('settings');Route::post('/settings','VendorController@settingsSave')->name('settings.save');Route::post('/settings/password','AccountController@password')->middleware(\App\Http\Middleware\VendorAccess::class)->name('password');
});
Route::get('/admin/vendor-stock-requests','AdminVendorController@requests')->name('admin.vendor-stock-requests');
Route::post('/admin/vendor-stock-requests/announcement','AdminVendorController@announce')->name('admin.vendor-stock-requests.announcement');
Route::post('/admin/vendor-stock-requests/{id}','AdminVendorController@review')->name('admin.vendor-stock-requests.review');
