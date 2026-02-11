<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'can:access-recruitment'])->group(function () {
    Route::get('/recruitment', fn () => Inertia::render('Recruitment/Index'))
        ->name('recruitment.index');
});
