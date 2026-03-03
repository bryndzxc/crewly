<?php

use App\Http\Controllers\BillingRequiredController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/billing-required', BillingRequiredController::class)
        ->name('billing.required');
});
