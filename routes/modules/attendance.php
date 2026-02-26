<?php

use App\Http\Controllers\AttendanceDailyController;
use App\Http\Controllers\AttendanceMonthlyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company', 'can:access-attendance'])->group(function () {
    Route::get('/attendance/daily', [AttendanceDailyController::class, 'index'])->name('attendance.daily');
    Route::put('/attendance/daily', [AttendanceDailyController::class, 'upsertAll'])
        ->middleware('can:manage-attendance')
        ->name('attendance.daily.upsert_all');
    Route::put('/attendance/daily/{employee}', [AttendanceDailyController::class, 'upsert'])
        ->whereNumber('employee')
        ->middleware('can:manage-attendance')
        ->name('attendance.daily.upsert');

    Route::put('/attendance/schedule', [AttendanceDailyController::class, 'updateSchedule'])
        ->middleware('can:manage-attendance')
        ->name('attendance.schedule.update');

    Route::get('/attendance/monthly', [AttendanceMonthlyController::class, 'index'])->name('attendance.monthly');
});
