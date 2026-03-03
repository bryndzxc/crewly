<?php

use App\Http\Controllers\My\MyAttendanceController;
use App\Http\Controllers\My\MyCashAdvanceController;
use App\Http\Controllers\My\MyLeaveController;
use App\Http\Controllers\My\MyProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company', 'can:access-my-portal'])->prefix('my')->group(function () {
    Route::get('/profile', [MyProfileController::class, 'show'])->name('my.profile');

    Route::get('/leave', [MyLeaveController::class, 'index'])->name('my.leave.index');
    Route::get('/leave/create', [MyLeaveController::class, 'create'])->name('my.leave.create');
    Route::post('/leave', [MyLeaveController::class, 'store'])->name('my.leave.store');

    Route::get('/attendance/daily', [MyAttendanceController::class, 'daily'])->name('my.attendance.daily');
    Route::get('/attendance/monthly', [MyAttendanceController::class, 'monthly'])->name('my.attendance.monthly');

    Route::get('/cash-advances', [MyCashAdvanceController::class, 'index'])->name('my.cash_advances.index');
    Route::get('/cash-advances/create', [MyCashAdvanceController::class, 'create'])->name('my.cash_advances.create');
    Route::post('/cash-advances', [MyCashAdvanceController::class, 'store'])->name('my.cash_advances.store');
});
