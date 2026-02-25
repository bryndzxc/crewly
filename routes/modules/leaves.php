<?php

use App\Http\Controllers\LeaveRequestApprovalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company', 'can:access-leaves'])->group(function () {
    Route::get('/leave/requests', [LeaveRequestController::class, 'index'])->name('leave.requests.index');
    Route::get('/leave/requests/create', [LeaveRequestController::class, 'create'])
        ->middleware('can:create,App\\Models\\LeaveRequest')
        ->name('leave.requests.create');
    Route::post('/leave/requests', [LeaveRequestController::class, 'store'])
        ->middleware('can:create,App\\Models\\LeaveRequest')
        ->name('leave.requests.store');
    Route::get('/leave/requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->whereNumber('leaveRequest')->name('leave.requests.show');
    Route::post('/leave/requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->whereNumber('leaveRequest')->name('leave.requests.cancel');

    Route::post('/leave/requests/{leaveRequest}/approve', [LeaveRequestApprovalController::class, 'approve'])->whereNumber('leaveRequest')->name('leave.requests.approve');
    Route::post('/leave/requests/{leaveRequest}/deny', [LeaveRequestApprovalController::class, 'deny'])->whereNumber('leaveRequest')->name('leave.requests.deny');

    Route::middleware(['can:manage-leave-types'])->group(function () {
        Route::get('/leave/types', [LeaveTypeController::class, 'index'])->name('leave.types.index');
        Route::get('/leave/types/create', [LeaveTypeController::class, 'create'])->name('leave.types.create');
        Route::post('/leave/types', [LeaveTypeController::class, 'store'])->name('leave.types.store');
        Route::get('/leave/types/{type}/edit', [LeaveTypeController::class, 'edit'])->whereNumber('type')->name('leave.types.edit');
        Route::patch('/leave/types/{type}', [LeaveTypeController::class, 'update'])->whereNumber('type')->name('leave.types.update');
    });
});
