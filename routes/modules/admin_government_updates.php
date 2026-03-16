<?php

use App\Http\Controllers\Admin\GovernmentUpdates\GovernmentUpdatesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company', 'can:manage-government-updates'])
    ->prefix('admin/government-updates')
    ->name('admin.government_updates.')
    ->group(function () {
        Route::get('/', [GovernmentUpdatesController::class, 'index'])->name('index');

        Route::post('/check-all', [GovernmentUpdatesController::class, 'checkAll'])->name('check_all');
        Route::post('/{sourceType}/check', [GovernmentUpdatesController::class, 'checkOne'])
            ->whereIn('sourceType', ['sss', 'philhealth', 'pagibig'])
            ->name('check_one');

        Route::get('/drafts/{draft}', [GovernmentUpdatesController::class, 'showDraft'])->whereNumber('draft')->name('drafts.show');
        Route::post('/drafts/{draft}/approve', [GovernmentUpdatesController::class, 'approveDraft'])->whereNumber('draft')->name('drafts.approve');
        Route::post('/drafts/{draft}/reject', [GovernmentUpdatesController::class, 'rejectDraft'])->whereNumber('draft')->name('drafts.reject');
    });
