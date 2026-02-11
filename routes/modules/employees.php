<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'can:access-employees'])->group(function () {
    Route::get('/employees', fn () => Inertia::render('Employees/Index'))
        ->name('employees.index');
});
