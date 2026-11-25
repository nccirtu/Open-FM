<?php

use App\Http\Controllers\BimModelController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceCalendarPdfController;
use App\Http\Controllers\PropertyDossierPdfController;
use App\Http\Controllers\WasteDocumentationPdfController;
use App\Http\Middleware\ScopeRequestsToUserOrganizations;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/pm/login');
})->name('home');

Route::get('/login', function () {
    return redirect('/pm/login');
})->name('login');

Route::get('/sprache/{locale}', LocaleController::class)
    ->middleware('auth')
    ->name('locale.switch');

Route::middleware(['auth', ScopeRequestsToUserOrganizations::class])->group(function () {

    Route::get('/objekte/{property}/dossier', [PropertyDossierPdfController::class, 'download'])
        ->name('property.dossier.pdf');

    Route::get('/wartungskalender/pdf', [MaintenanceCalendarPdfController::class, 'download'])
        ->name('maintenance.calendar.pdf');

    Route::get('/abfall/dokumentation/{property}', [WasteDocumentationPdfController::class, 'download'])
        ->name('waste.documentation.pdf');

    Route::get('/bim-models/{bimModel}/file', [BimModelController::class, 'file'])
        ->name('bim-models.file');

    Route::post('/bim-models/{bimModel}/import-rooms', [BimModelController::class, 'importRooms'])
        ->name('bim-models.import');
});
