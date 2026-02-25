<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::middleware(['auth', 'ensure.company'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');
});
