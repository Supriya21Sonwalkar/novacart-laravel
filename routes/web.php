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


/*
|--------------------------------------------------------------------------
| Store Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/store.php';
