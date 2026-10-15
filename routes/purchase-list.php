<?php
use Illuminate\Support\Facades\Route;
Route::prefix('admin/purchase-list')->group(function(){
 Route::get('/','PurchaseListController@index')->name('admin.purchase-list');
 Route::get('/vendors/{vendor}','PurchaseListController@vendor')->name('admin.purchase-list.vendor');
 Route::get('/subcategories','PurchaseListController@subcategories')->name('admin.purchase-list.subcategories');
 Route::get('/products','PurchaseListController@products')->name('admin.purchase-list.products');
 Route::post('/','PurchaseListController@store')->name('admin.purchase-list.store');

Route::get(
    '/vendors/{vendor}/purchase-orders',
    'InwardController@vendorPurchaseOrders'
)->name('admin.purchase-list.vendor-orders');

Route::get(
    '/purchase-orders/{purchaseOrder}/items',
    'InwardController@purchaseOrderItems'
)->name('admin.purchase-list.order-items');

 Route::get('/{purchaseOrder}','PurchaseListController@show')->name('admin.purchase-list.show');
});
