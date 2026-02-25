<?php

use App\Http\Controllers\PayrollSummaryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company', 'can:access-payroll-summary'])->group(function () {
    Route::get('/payroll/summary', [PayrollSummaryController::class, 'index'])->name('payroll.summary.index');
    Route::get('/payroll/summary/export', [PayrollSummaryController::class, 'export'])
        ->middleware('can:export-payroll-summary')
        ->name('payroll.summary.export');
});
