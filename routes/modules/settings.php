<?php

use App\Http\Controllers\Settings\ChatSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('settings')->group(function () {
    Route::patch('/chat/sound', [ChatSettingsController::class, 'updateSound'])->name('settings.chat.sound');
});
