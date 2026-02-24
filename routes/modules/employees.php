<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeDocumentScanController;
use App\Http\Controllers\EmployeeIncidentController;
use App\Http\Controllers\EmployeeNoteController;
use App\Http\Controllers\EmployeePhotoController;
use App\Http\Controllers\EmployeeRelationAttachmentController;
use App\Http\Controllers\EmployeesProbationController;
use App\Http\Controllers\ExpiringDocumentsController;
use App\Http\Controllers\MemoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'can:access-employees'])->group(function () {
    // Route::get('/employees', fn () => Inertia::render('Employees/Index'))
    //     ->name('employees.index');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

    Route::get('/employees/probation', [EmployeesProbationController::class, 'index'])
        ->name('employees.probation');

    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->whereNumber('employee')->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->whereNumber('employee')->name('employees.edit');
    Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->whereNumber('employee')->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->whereNumber('employee')->name('employees.destroy');

    Route::get('/employees/{employee}/photo', [EmployeePhotoController::class, 'show'])->whereNumber('employee')->name('employees.photo');
    Route::post('/employees/{employee}/photo', [EmployeePhotoController::class, 'update'])->whereNumber('employee')->name('employees.photo.update');
    Route::delete('/employees/{employee}/photo', [EmployeePhotoController::class, 'destroy'])->whereNumber('employee')->name('employees.photo.destroy');

    Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->whereNumber('employee')
        ->middleware('can:employees-documents-upload')
        ->name('employees.documents.store');

    Route::post('/employees/scan-documents', EmployeeDocumentScanController::class)
        ->middleware('can:employees-documents-upload')
        ->name('employees.documents.scan');

    Route::get('/employees/{employee}/documents/{document}/download', [EmployeeDocumentController::class, 'download'])->whereNumber('employee')
        ->middleware('can:employees-documents-download')
        ->name('employees.documents.download');

    Route::delete('/employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->whereNumber('employee')
        ->middleware('can:employees-documents-delete')
        ->name('employees.documents.destroy');

    // Employee Relations (Notes & Incidents)
    Route::post('/employees/{employee}/notes', [EmployeeNoteController::class, 'store'])->whereNumber('employee')
        ->middleware('can:employees-relations-manage')
        ->name('employees.notes.store');

    Route::delete('/employees/{employee}/notes/{note}', [EmployeeNoteController::class, 'destroy'])->whereNumber('employee')
        ->middleware('can:employees-relations-manage')
        ->name('employees.notes.destroy');

    Route::post('/employees/{employee}/incidents', [EmployeeIncidentController::class, 'store'])->whereNumber('employee')
        ->middleware('can:employees-relations-manage')
        ->name('employees.incidents.store');

    Route::patch('/employees/{employee}/incidents/{incident}', [EmployeeIncidentController::class, 'update'])->whereNumber('employee')
        ->middleware('can:employees-relations-manage')
        ->name('employees.incidents.update');

    Route::post('/employees/{employee}/incidents/{incident}/memos/preview', [MemoController::class, 'previewForIncident'])
        ->whereNumber('employee')
        ->whereNumber('incident')
        ->middleware('can:generate-memos')
        ->name('employees.incidents.memos.preview');

    Route::post('/employees/{employee}/incidents/{incident}/memos', [MemoController::class, 'storeForIncident'])
        ->whereNumber('employee')
        ->whereNumber('incident')
        ->middleware('can:generate-memos')
        ->name('employees.incidents.memos.store');

    Route::delete('/employees/{employee}/incidents/{incident}', [EmployeeIncidentController::class, 'destroy'])->whereNumber('employee')
        ->middleware('can:employees-relations-manage')
        ->name('employees.incidents.destroy');

    Route::post('/relations/{attachableType}/{id}/attachments', [EmployeeRelationAttachmentController::class, 'store'])
        ->where('attachableType', 'notes|incidents')
        ->whereNumber('id')
        ->middleware('can:employees-relations-manage')
        ->name('relations.attachments.store');

    Route::get('/relations/attachments/{attachment}/download', [EmployeeRelationAttachmentController::class, 'download'])
        ->middleware('can:employees-relations-view')
        ->name('relations.attachments.download');

    Route::delete('/relations/attachments/{attachment}', [EmployeeRelationAttachmentController::class, 'destroy'])
        ->middleware('can:employees-relations-manage')
        ->name('relations.attachments.destroy');

    Route::get('/documents/expiring', [ExpiringDocumentsController::class, 'index'])
        ->name('documents.expiring');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
});
