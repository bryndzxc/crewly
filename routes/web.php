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

Route::group([], base_path('routes/modules/public.php'));
Route::group([], base_path('routes/modules/core.php'));
Route::group([], base_path('routes/modules/employees.php'));
Route::group([], base_path('routes/modules/recruitment.php'));
Route::group([], base_path('routes/modules/account.php'));
Route::group([], base_path('routes/modules/leaves.php'));
Route::group([], base_path('routes/modules/attendance.php'));
Route::group([], base_path('routes/modules/payroll.php'));
Route::group([], base_path('routes/modules/audit_logs.php'));
Route::group([], base_path('routes/modules/notifications.php'));
Route::group([], base_path('routes/modules/my.php'));
Route::group([], base_path('routes/modules/chat.php'));
Route::group([], base_path('routes/modules/feedback.php'));
Route::group([], base_path('routes/modules/settings.php'));
Route::group([], base_path('routes/modules/memos.php'));
Route::group([], base_path('routes/modules/developer.php'));
