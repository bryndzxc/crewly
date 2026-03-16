<?php

use App\Http\Controllers\Settings\ChatSettingsController;
use App\Http\Controllers\Settings\GovernmentDefaultsController;
use App\Http\Controllers\Settings\GovernmentContributions\PagibigContributionSettingController;
use App\Http\Controllers\Settings\GovernmentContributions\PhilhealthContributionSettingController;
use App\Http\Controllers\Settings\GovernmentContributions\SssContributionTableController;
use App\Http\Controllers\Settings\MemoTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.company'])->prefix('settings')->group(function () {
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

    Route::middleware(['can:manage-government-contributions'])->prefix('government-contributions')->group(function () {
        // SSS
        Route::get('/sss', [SssContributionTableController::class, 'index'])->name('settings.government_contributions.sss.index');
        Route::get('/sss/create', [SssContributionTableController::class, 'create'])->name('settings.government_contributions.sss.create');
        Route::post('/sss', [SssContributionTableController::class, 'store'])->name('settings.government_contributions.sss.store');
        Route::get('/sss/{rule}/edit', [SssContributionTableController::class, 'edit'])->whereNumber('rule')->name('settings.government_contributions.sss.edit');
        Route::put('/sss/{rule}', [SssContributionTableController::class, 'update'])->whereNumber('rule')->name('settings.government_contributions.sss.update');
        Route::patch('/sss/{rule}/archive', [SssContributionTableController::class, 'archive'])->whereNumber('rule')->name('settings.government_contributions.sss.archive');

        // PhilHealth
        Route::get('/philhealth', [PhilhealthContributionSettingController::class, 'index'])->name('settings.government_contributions.philhealth.index');
        Route::get('/philhealth/create', [PhilhealthContributionSettingController::class, 'create'])->name('settings.government_contributions.philhealth.create');
        Route::post('/philhealth', [PhilhealthContributionSettingController::class, 'store'])->name('settings.government_contributions.philhealth.store');
        Route::get('/philhealth/{setting}/edit', [PhilhealthContributionSettingController::class, 'edit'])->whereNumber('setting')->name('settings.government_contributions.philhealth.edit');
        Route::put('/philhealth/{setting}', [PhilhealthContributionSettingController::class, 'update'])->whereNumber('setting')->name('settings.government_contributions.philhealth.update');
        Route::patch('/philhealth/{setting}/archive', [PhilhealthContributionSettingController::class, 'archive'])->whereNumber('setting')->name('settings.government_contributions.philhealth.archive');

        // Pag-IBIG
        Route::get('/pagibig', [PagibigContributionSettingController::class, 'index'])->name('settings.government_contributions.pagibig.index');
        Route::get('/pagibig/create', [PagibigContributionSettingController::class, 'create'])->name('settings.government_contributions.pagibig.create');
        Route::post('/pagibig', [PagibigContributionSettingController::class, 'store'])->name('settings.government_contributions.pagibig.store');
        Route::get('/pagibig/{setting}/edit', [PagibigContributionSettingController::class, 'edit'])->whereNumber('setting')->name('settings.government_contributions.pagibig.edit');
        Route::put('/pagibig/{setting}', [PagibigContributionSettingController::class, 'update'])->whereNumber('setting')->name('settings.government_contributions.pagibig.update');
        Route::patch('/pagibig/{setting}/archive', [PagibigContributionSettingController::class, 'archive'])->whereNumber('setting')->name('settings.government_contributions.pagibig.archive');
    });

    Route::middleware(['can:manage-government-contributions'])->prefix('government-defaults')->group(function () {
        Route::post('/sss-2025', [GovernmentDefaultsController::class, 'loadSss2025Defaults'])->name('settings.government_defaults.sss_2025');
        Route::post('/philhealth-2025', [GovernmentDefaultsController::class, 'loadPhilHealth2025Defaults'])->name('settings.government_defaults.philhealth_2025');
        Route::post('/pagibig-2025', [GovernmentDefaultsController::class, 'loadPagibig2025Defaults'])->name('settings.government_defaults.pagibig_2025');
    });
});
