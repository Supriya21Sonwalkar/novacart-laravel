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
Route::post('/admin/login','AccountController@adminLogin')->middleware([\App\Http\Middleware\ActiveAccount::class,'throttle:6,1']);
require __DIR__.'/delivery.php';
require __DIR__.'/store.php';
