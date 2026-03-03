<?php

use App\Http\Controllers\Admin\Billing\CompanyBillingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.developer'])->prefix('admin/billing')->name('admin.billing.')->group(function () {
    Route::get('/companies', [CompanyBillingController::class, 'index'])->name('companies.index');
    Route::get('/companies/{company}', [CompanyBillingController::class, 'show'])->name('companies.show');

    Route::post('/companies/{company}/activate', [CompanyBillingController::class, 'activate'])->name('companies.activate');
    Route::post('/companies/{company}/grant-trial-30', [CompanyBillingController::class, 'grantTrial30'])->name('companies.grant_trial_30');
    Route::post('/companies/{company}/mark-paid', [CompanyBillingController::class, 'markPaid'])->name('companies.mark_paid');
    Route::post('/companies/{company}/set-past-due', [CompanyBillingController::class, 'setPastDue'])->name('companies.set_past_due');
    Route::post('/companies/{company}/suspend', [CompanyBillingController::class, 'suspend'])->name('companies.suspend');
    Route::post('/companies/{company}/send-invoice-email', [CompanyBillingController::class, 'sendInvoiceEmail'])->name('companies.send_invoice_email');
    Route::patch('/companies/{company}', [CompanyBillingController::class, 'update'])->name('companies.update');
});
