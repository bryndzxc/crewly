<?php

use App\Http\Controllers\PayrollExportController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company'])->group(function () {
    Route::get('/payroll/payslip/{employee}/{period}', [PayslipController::class, 'show'])
        ->whereNumber('employee')
        ->name('payroll.payslip.show');
});

Route::middleware(['auth', 'ensure.company', 'can:access-payroll-summary'])->group(function () {
    Route::get('/payroll/summary', [PayrollRunController::class, 'index'])->name('payroll.summary.index');
    Route::get('/payroll/summary/export', [PayrollExportController::class, 'export'])
        ->middleware('can:export-payroll-summary')
        ->name('payroll.summary.export');

    Route::post('/payroll/runs/generate', [PayrollRunController::class, 'generate'])
        ->middleware('can:generate-payroll')
        ->name('payroll.runs.generate');

    Route::patch('/payroll/run-items/{item}', [PayrollRunController::class, 'updateItem'])
        ->whereNumber('item')
        ->middleware('can:edit-payroll-deductions')
        ->name('payroll.run-items.update');

    Route::post('/payroll/runs/{run}/review', [PayrollRunController::class, 'markReviewed'])
        ->whereNumber('run')
        ->middleware('can:review-payroll')
        ->name('payroll.runs.review');

    Route::post('/payroll/runs/{run}/finalize', [PayrollRunController::class, 'finalize'])
        ->whereNumber('run')
        ->middleware('can:finalize-payroll')
        ->name('payroll.runs.finalize');

    Route::post('/payroll/runs/{run}/release', [PayrollRunController::class, 'release'])
        ->whereNumber('run')
        ->middleware('can:release-payroll')
        ->name('payroll.runs.release');
});
