<?php

use App\Http\Controllers\EmployeeDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company', 'can:access-my-portal'])->group(function () {
    Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'show'])->name('employee.dashboard');
});
