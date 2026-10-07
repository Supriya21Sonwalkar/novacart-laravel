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

Route::get('/admin/outward', function () {
    return view('admin.outward.index');
})->middleware('auth')->name('admin.outward');

Route::get('/admin/outward/create', function () {
    return view('admin.outward.create');
})->name('admin.outward.create');


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