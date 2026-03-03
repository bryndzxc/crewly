<?php

use App\Http\Controllers\Developer\CompanyController;
use App\Http\Controllers\Developer\AccessRequestController;
use App\Http\Controllers\Developer\DemoRequestController;
use App\Http\Controllers\Developer\FeedbackController;
use App\Http\Controllers\Developer\FeedbackAttachmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.developer'])->prefix('developer')->name('developer.')->group(function () {
    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::post('companies/{company}/users', [CompanyController::class, 'storeUser'])->name('companies.users.store');
    Route::post('companies/{company}/convert-from-demo', [CompanyController::class, 'convertFromDemo'])->name('companies.convert_from_demo');

    Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('feedback/attachments/{attachment}/download', [FeedbackAttachmentController::class, 'download'])
        ->whereNumber('attachment')
        ->name('feedback_attachments.download');
    Route::get('feedback/attachments/{attachment}/view', [FeedbackAttachmentController::class, 'view'])
        ->whereNumber('attachment')
        ->name('feedback_attachments.view');
    Route::delete('feedback/attachments/{attachment}', [FeedbackAttachmentController::class, 'destroy'])
        ->whereNumber('attachment')
        ->name('feedback_attachments.destroy');

    Route::get('demo-requests', [DemoRequestController::class, 'index'])->name('demo_requests.index');
    Route::post('demo-requests/{lead}/approve', [DemoRequestController::class, 'approve'])->name('demo_requests.approve');
    Route::post('demo-requests/{lead}/decline', [DemoRequestController::class, 'decline'])->name('demo_requests.decline');

    Route::get('access-requests', [AccessRequestController::class, 'index'])->name('access_requests.index');
    Route::post('access-requests/{lead}/approve', [AccessRequestController::class, 'approve'])->name('access_requests.approve');
    Route::post('access-requests/{lead}/decline', [AccessRequestController::class, 'decline'])->name('access_requests.decline');
});
