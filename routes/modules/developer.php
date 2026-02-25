<?php

use App\Http\Controllers\Developer\CompanyController;
use App\Http\Controllers\Developer\DemoRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.developer'])->prefix('developer')->name('developer.')->group(function () {
    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

    Route::get('demo-requests', [DemoRequestController::class, 'index'])->name('demo_requests.index');
    Route::post('demo-requests/{lead}/approve', [DemoRequestController::class, 'approve'])->name('demo_requests.approve');
    Route::post('demo-requests/{lead}/decline', [DemoRequestController::class, 'decline'])->name('demo_requests.decline');
});
