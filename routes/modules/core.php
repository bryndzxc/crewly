<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserTutorialController;

Route::middleware(['auth', 'ensure.company'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::post('/user/tutorial/complete', [UserTutorialController::class, 'markCompleted'])
        ->name('user.tutorial.complete');
});
