<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


/*
|--------------------------------------------------------------------------
| Admin Login
|--------------------------------------------------------------------------
*/

Route::get(
    '/admin/login',
    'AccountController@adminLoginForm'
)->middleware(
    \App\Http\Middleware\ActiveAccount::class
);

Route::post(
    '/admin/login',
    'AccountController@adminLogin'
)->middleware([
    \App\Http\Middleware\ActiveAccount::class,
    'throttle:6,1'
]);


/*
|--------------------------------------------------------------------------
| Outward
|--------------------------------------------------------------------------
|
| Existing Outward routes — unchanged.
|
*/

Route::get('/admin/batches/{id}/edit','BatchController@edit')->name('admin.batches.edit');
Route::put('/admin/batches/{id}','BatchController@update')->name('admin.batches.update');
Route::get('/admin/batches','BatchController@index')->name('admin.batches');
Route::get('/admin/returns','ReturnController@index')->name('admin.returns');
Route::get('/admin/returns/{id}','ReturnController@show')->name('admin.returns.show');
Route::post('/admin/returns/{id}/action','ReturnController@action')->name('admin.returns.action');
Route::post('/orders/{order}/returns','ReturnController@store')->name('orders.returns');
Route::post('/admin/inward','InwardController@store')->name('admin.inward.store');
Route::get('/admin/inward/template/batches','BatchController@template')->name('admin.inward.batch-template');
Route::post('/admin/workspace/bulk-pack','AdminWorkspaceController@bulkPack')->name('admin.workspace.bulk-pack');
Route::get('/admin/workspace/search','AdminWorkspaceController@search')->name('admin.workspace.search');
Route::get('/admin/store-operations','StoreOperationsController@report')->name('admin.store-operations');
Route::post('/admin/store-operations/expenses','StoreOperationsController@expense')->name('admin.store-operations.expense');
Route::get('/admin/order-workspace/{order}','OrderWorkspaceController@show')->name('admin.order-workspace');
Route::post('/admin/order-workspace/{order}/prepare','OrderWorkspaceController@prepare')->name('admin.order-workspace.prepare');
Route::get('/admin/team-profile','AdminWorkspaceController@profile')->name('admin.team-profile');
Route::post('/admin/team-profile','AdminWorkspaceController@saveProfile')->name('admin.team-profile.save');
Route::post('/admin/team-profile/password','AccountController@password')->middleware(['auth',\App\Http\Middleware\ActiveAccount::class,\App\Http\Middleware\AdminAccess::class,'throttle:6,1'])->name('admin.team-profile.password');
Route::prefix('admin/outward')->group(function(){
 Route::get('/','OutwardController@index')->name('admin.outward');
 Route::get('/create','OutwardController@create')->name('admin.outward.create');
 Route::get('/export','OutwardController@export')->name('admin.outward.export');
 Route::post('/','OutwardController@store')->name('admin.outward.store');
 Route::get('/{outward}/edit','OutwardController@edit')->name('admin.outward.edit');
 Route::put('/{outward}','OutwardController@update')->name('admin.outward.update');
 Route::get('/{outward}/print','OutwardController@printSlip')->name('admin.outward.print');
 Route::post('/{outward}/action','OutwardController@action')->name('admin.outward.action');
 Route::post('/{outward}/dispatch-details','OutwardController@dispatchDetails')->name('admin.outward.dispatch-details');
 Route::get('/{outward}','OutwardController@show')->name('admin.outward.show');
});
/*
|--------------------------------------------------------------------------
| Inward
|--------------------------------------------------------------------------
|
| Inward pages are now handled by InwardController.
|
*/

/*
 * Inward list
 */
Route::get(
    '/admin/inward',
    'InwardController@index'
)->name('admin.inward');


/*
 * Manual Inward
 */
Route::get(
    '/admin/inward/create',
    'InwardController@create'
)->name('admin.inward.create');


/*
 * Bulk Import page
 */
Route::get(
    '/admin/inward/import',
    'InwardController@importForm'
)->name('admin.inward.import');


/*
 * Bulk Import → Validate Excel
 *
 * IMPORTANT:
 * This only reads and validates the Excel file.
 * It does NOT update product stock.
 */
Route::post(
    '/admin/inward/import/validate',
    'InwardController@validateImport'
)->name('admin.inward.import.validate');
Route::post('/admin/inward/import/confirm', 'InwardController@confirmImport')
    ->name('admin.inward.import.confirm');
    

/*
|--------------------------------------------------------------------------
| Delivery Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/delivery.php';
Route::post('/shopping/intent','ShoppingIntentController@remember')->middleware('throttle:30,1');
Route::get('/shopping/resume','ShoppingIntentController@resume')->name('shopping.resume');
Route::post('/shopping/resume','ShoppingIntentController@confirm')->middleware(['auth',\App\Http\Middleware\ActiveAccount::class])->name('shopping.confirm');
Route::post('/orders/{order}/sandbox-payment','PaymentController@sandbox')->middleware('throttle:20,1')->name('orders.sandbox-payment');


/*
|--------------------------------------------------------------------------
| Store Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/vendor.php';
require __DIR__.'/purchase-list.php';
require __DIR__.'/store.php';
