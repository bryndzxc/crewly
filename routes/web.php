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

require __DIR__.'/auth.php';

Route::group([], base_path('routes/modules/core.php'));
Route::group([], base_path('routes/modules/employees.php'));
Route::group([], base_path('routes/modules/recruitment.php'));
Route::group([], base_path('routes/modules/account.php'));
Route::group([], base_path('routes/modules/leaves.php'));
