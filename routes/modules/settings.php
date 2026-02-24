<?php

use App\Http\Controllers\Settings\ChatSettingsController;
use App\Http\Controllers\Settings\MemoTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('settings')->group(function () {
    Route::patch('/chat/sound', [ChatSettingsController::class, 'updateSound'])->name('settings.chat.sound');

    Route::middleware(['can:manage-memo-templates'])->group(function () {
        Route::get('/memo-templates', [MemoTemplateController::class, 'index'])->name('settings.memo_templates.index');
        Route::get('/memo-templates/create', [MemoTemplateController::class, 'create'])->name('settings.memo_templates.create');
        Route::post('/memo-templates', [MemoTemplateController::class, 'store'])->name('settings.memo_templates.store');
        Route::get('/memo-templates/{template}/edit', [MemoTemplateController::class, 'edit'])
            ->whereNumber('template')
            ->name('settings.memo_templates.edit');
        Route::put('/memo-templates/{template}', [MemoTemplateController::class, 'update'])
            ->whereNumber('template')
            ->name('settings.memo_templates.update');
        Route::patch('/memo-templates/{template}/toggle', [MemoTemplateController::class, 'toggle'])
            ->whereNumber('template')
            ->name('settings.memo_templates.toggle');
    });
});
