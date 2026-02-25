<?php

use App\Http\Controllers\Recruitment\ApplicantController;
use App\Http\Controllers\Recruitment\ApplicantDocumentController;
use App\Http\Controllers\Recruitment\ApplicantHireController;
use App\Http\Controllers\Recruitment\ApplicantInterviewController;
use App\Http\Controllers\Recruitment\ApplicantStageController;
use App\Http\Controllers\Recruitment\RecruitmentPositionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company', 'can:access-recruitment'])->group(function () {
    Route::get('/recruitment', fn () => redirect()->route('recruitment.applicants.index'))
        ->name('recruitment.index');

    // Positions (recommended)
    Route::get('/recruitment/positions', [RecruitmentPositionController::class, 'index'])
        ->name('recruitment.positions.index');
    Route::get('/recruitment/positions/create', [RecruitmentPositionController::class, 'create'])
        ->middleware('can:recruitment-manage')
        ->name('recruitment.positions.create');
    Route::post('/recruitment/positions', [RecruitmentPositionController::class, 'store'])
        ->middleware('can:recruitment-manage')
        ->name('recruitment.positions.store');
    Route::get('/recruitment/positions/{position}/edit', [RecruitmentPositionController::class, 'edit'])
        ->whereNumber('position')
        ->middleware('can:recruitment-manage')
        ->name('recruitment.positions.edit');
    Route::patch('/recruitment/positions/{position}', [RecruitmentPositionController::class, 'update'])
        ->whereNumber('position')
        ->middleware('can:recruitment-manage')
        ->name('recruitment.positions.update');
    Route::delete('/recruitment/positions/{position}', [RecruitmentPositionController::class, 'destroy'])
        ->whereNumber('position')
        ->middleware('can:recruitment-manage')
        ->name('recruitment.positions.destroy');

    // Applicants
    Route::get('/recruitment/applicants', [ApplicantController::class, 'index'])
        ->name('recruitment.applicants.index');
    Route::get('/recruitment/applicants/create', [ApplicantController::class, 'create'])
        ->middleware('can:recruitment-manage')
        ->name('recruitment.applicants.create');
    Route::post('/recruitment/applicants', [ApplicantController::class, 'store'])
        ->middleware('can:recruitment-manage')
        ->name('recruitment.applicants.store');
    Route::get('/recruitment/applicants/{applicant}', [ApplicantController::class, 'show'])
        ->whereNumber('applicant')
        ->name('recruitment.applicants.show');
    Route::get('/recruitment/applicants/{applicant}/edit', [ApplicantController::class, 'edit'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-manage')
        ->name('recruitment.applicants.edit');
    Route::patch('/recruitment/applicants/{applicant}', [ApplicantController::class, 'update'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-manage')
        ->name('recruitment.applicants.update');
    Route::delete('/recruitment/applicants/{applicant}', [ApplicantController::class, 'destroy'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-manage')
        ->name('recruitment.applicants.destroy');

    Route::patch('/recruitment/applicants/{applicant}/stage', [ApplicantStageController::class, 'update'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-stage-update')
        ->name('recruitment.applicants.stage.update');

    Route::post('/recruitment/applicants/{applicant}/documents', [ApplicantDocumentController::class, 'store'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-documents-upload')
        ->name('recruitment.applicants.documents.store');

    Route::get('/recruitment/applicants/{applicant}/documents/{document}/download', [ApplicantDocumentController::class, 'download'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-documents-download')
        ->name('recruitment.applicants.documents.download');

    Route::delete('/recruitment/applicants/{applicant}/documents/{document}', [ApplicantDocumentController::class, 'destroy'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-documents-delete')
        ->name('recruitment.applicants.documents.destroy');

    Route::post('/recruitment/applicants/{applicant}/interviews', [ApplicantInterviewController::class, 'store'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-interviews-create')
        ->name('recruitment.applicants.interviews.store');

    Route::patch('/recruitment/applicants/{applicant}/interviews/{interview}', [ApplicantInterviewController::class, 'update'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-interviews-manage')
        ->name('recruitment.applicants.interviews.update');

    Route::delete('/recruitment/applicants/{applicant}/interviews/{interview}', [ApplicantInterviewController::class, 'destroy'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-interviews-manage')
        ->name('recruitment.applicants.interviews.destroy');

    Route::post('/recruitment/applicants/{applicant}/hire', [ApplicantHireController::class, 'store'])
        ->whereNumber('applicant')
        ->middleware('can:recruitment-hire')
        ->name('recruitment.applicants.hire');
});
