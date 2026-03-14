<?php

use App\Http\Controllers\PayrollExportController;
use App\Http\Controllers\PayrollSummaryController;
use App\Http\Controllers\PayslipController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company'])->group(function () {
    Route::get('/payroll/payslip/{employee}/{period}', [PayslipController::class, 'show'])
        ->whereNumber('employee')
        ->name('payroll.payslip.show');
});

Route::middleware(['auth', 'ensure.company', 'can:access-payroll-summary'])->group(function () {
    Route::get('/payroll/summary', [PayrollSummaryController::class, 'index'])->name('payroll.summary.index');
    Route::get('/payroll/summary/export', [PayrollExportController::class, 'export'])
        ->middleware('can:export-payroll-summary')
        ->name('payroll.summary.export');
});
