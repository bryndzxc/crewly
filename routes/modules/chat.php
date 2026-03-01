<?php

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chat\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company_or_developer'])->prefix('chat')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread_count');
    Route::get('/conversations/{id}', [ChatController::class, 'show'])->whereNumber('id')->name('chat.conversations.show');
    Route::post('/dm', [ChatController::class, 'createOrOpenDm'])->name('chat.dm');
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store'])->whereNumber('id')->name('chat.messages.store');
    Route::patch('/conversations/{id}/read', [ChatController::class, 'markRead'])->whereNumber('id')->name('chat.conversations.read');
});
