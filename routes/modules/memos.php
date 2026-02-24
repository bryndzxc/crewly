<?php

use App\Http\Controllers\MemoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/memos/{memo}/download', [MemoController::class, 'download'])
        ->whereNumber('memo')
        ->name('memos.download');
});
