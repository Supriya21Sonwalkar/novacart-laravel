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

Route::get('/admin/login','AccountController@adminLoginForm')->middleware(\App\Http\Middleware\ActiveAccount::class);
Route::get('/admin/outward', function () {
    return view('admin.outward.index');
})->middleware('auth')->name('admin.outward');
Route::get('/admin/outward/create', function () {
    return view('admin.outward.create');
})->name('admin.outward.create');
Route::get('/admin/inward', function () {
    return view('admin.inward.index');
})->name('admin.inward');
Route::get('/admin/inward/create', function () {
    return view('admin.inward.create');
})->name('admin.inward.create');
Route::get('/admin/inward/import', function () {
    return view('admin.inward.import');
})->name('admin.inward.import');
Route::post('/admin/login','AccountController@adminLogin')->middleware([\App\Http\Middleware\ActiveAccount::class,'throttle:6,1']);
require __DIR__.'/delivery.php';
require __DIR__.'/store.php';
