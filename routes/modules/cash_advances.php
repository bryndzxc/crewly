<?php

use App\Http\Controllers\CashAdvanceAttachmentController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\CashAdvanceDecisionController;
use App\Http\Controllers\CashAdvanceDeductionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company', 'can:access-cash-advances'])->group(function () {
    Route::get('/cash-advances', [CashAdvanceController::class, 'index'])->name('cash_advances.index');
    Route::get('/cash-advances/{cashAdvance}', [CashAdvanceController::class, 'show'])
        ->whereNumber('cashAdvance')
        ->name('cash_advances.show');

    Route::get('/cash-advances/{cashAdvance}/attachment', [CashAdvanceAttachmentController::class, 'download'])
        ->whereNumber('cashAdvance')
        ->name('cash_advances.attachment');

    Route::middleware(['can:manage-cash-advances'])->group(function () {
        Route::post('/cash-advances/{cashAdvance}/approve', [CashAdvanceDecisionController::class, 'approve'])
            ->whereNumber('cashAdvance')
            ->name('cash_advances.approve');

        Route::post('/cash-advances/{cashAdvance}/reject', [CashAdvanceDecisionController::class, 'reject'])
            ->whereNumber('cashAdvance')
            ->name('cash_advances.reject');

        Route::post('/cash-advances/{cashAdvance}/deductions', [CashAdvanceDeductionController::class, 'store'])
            ->whereNumber('cashAdvance')
            ->name('cash_advances.deductions.store');
    });
});
