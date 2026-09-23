<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Bibliotheque\Http\Controllers\PublicBibliothequeController;
use App\Modules\Bibliotheque\Http\Controllers\BibliothequeController;
use App\Modules\Bibliotheque\Http\Controllers\AdminBibliothequeController;
use App\Modules\Bibliotheque\Http\Controllers\PeriodeDocumentController;
use App\Modules\Bibliotheque\Http\Controllers\TypeDocumentController;

/*
|--------------------------------------------------------------------------
| Routes Bibliothèque — Public
|--------------------------------------------------------------------------
*/

Route::get('/bibliotheque', [PublicBibliothequeController::class, 'index'])
    ->name('bibliothequepub.index');

Route::get('/bibliotheque/recherche', [PublicBibliothequeController::class, 'search'])
    ->name('bibliothequepub.search');

Route::get('/bibliotheque/telecharger/{document}', [PublicBibliothequeController::class, 'download'])
    ->name('bibliothequepub.download')
    ->whereNumber('document');

Route::get('/bibliotheque/voir/{document}', [PublicBibliothequeController::class, 'view'])
    ->name('bibliothequepub.view')
    ->whereNumber('document');

Route::post('/bibliotheque/{document}/noter', [PublicBibliothequeController::class, 'note'])
    ->name('bibliothequepub.note')
    ->middleware('auth')
    ->whereNumber('document');

/*
|--------------------------------------------------------------------------
| Routes Bibliothèque — Espace Privé
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('bibliotheque')->name('bibliotheque.')->group(function () {

    Route::get('/dashboard', [BibliothequeController::class, 'dashboard'])
        ->name('dashboard');

    Route::get('/mes-documents', [BibliothequeController::class, 'index'])
        ->name('index');

    Route::get('/creer', [BibliothequeController::class, 'create'])
        ->name('create');
    Route::post('/', [BibliothequeController::class, 'store'])
        ->name('store');

    Route::get('/favoris', [BibliothequeController::class, 'favoris'])
        ->name('favoris');

    Route::post('/{document}/favori', [BibliothequeController::class, 'toggleFavori'])
        ->name('toggle-favori')
        ->whereNumber('document');

    Route::post('/{document}/favori-ajax', [BibliothequeController::class, 'toggleFavoriAjax'])
        ->name('toggle-favori-ajax')
        ->whereNumber('document');

    Route::post('/{document}/commentaire', [BibliothequeController::class, 'comment'])
        ->name('comment')
        ->whereNumber('document');

    Route::post('/{document}/signalement', [BibliothequeController::class, 'report'])
        ->name('report')
        ->whereNumber('document');

    Route::get('/{document}', [BibliothequeController::class, 'show'])
        ->name('show')
        ->whereNumber('document');
    Route::get('/{document}/modifier', [BibliothequeController::class, 'edit'])
        ->name('edit')
        ->whereNumber('document');
    Route::put('/{document}', [BibliothequeController::class, 'update'])
        ->name('update')
        ->whereNumber('document');
    Route::delete('/{document}', [BibliothequeController::class, 'destroy'])
        ->name('destroy')
        ->whereNumber('document');
});

/*
|--------------------------------------------------------------------------
| Route publique {slug} — APRÈS les routes privées pour éviter l'ombre
|--------------------------------------------------------------------------
*/

Route::get('/bibliotheque/{slug}', [PublicBibliothequeController::class, 'show'])
    ->name('bibliothequepub.show')
    ->where('slug', '[a-z0-9\-]+');

/*
|--------------------------------------------------------------------------
| Routes Bibliothèque — Admin Modération + Référentiels
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|super-admin'])->prefix('admin/bibliotheque')->name('admin.bibliotheque.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | RÉFÉRENTIELS — AVANT les routes dynamiques /{document}
    |--------------------------------------------------------------------------
    */

    Route::resource('type-documents', TypeDocumentController::class)
        ->parameters(['type-documents' => 'typeDocument'])
        ->except(['show']);

    Route::resource('periodes', PeriodeDocumentController::class)
        ->parameters(['periodes' => 'periode'])
        ->except(['show']);

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTS
    |--------------------------------------------------------------------------
    */

    Route::get('/', [AdminBibliothequeController::class, 'index'])
        ->name('index');
    Route::get('/signalements', [AdminBibliothequeController::class, 'signalements'])
        ->name('signalements');
    Route::post('/signalements/{signalement}/traiter', [AdminBibliothequeController::class, 'traiterSignalement'])
        ->name('signalements.traiter');

    Route::get('/{document}', [AdminBibliothequeController::class, 'show'])
        ->name('show')
        ->whereNumber('document');
    Route::patch('/{document}/valider', [AdminBibliothequeController::class, 'valider'])
        ->name('valider')
        ->whereNumber('document');
    Route::patch('/{document}/refuser', [AdminBibliothequeController::class, 'refuser'])
        ->name('refuser')
        ->whereNumber('document');
    Route::patch('/{document}/archiver', [AdminBibliothequeController::class, 'archiver'])
        ->name('archiver')
        ->whereNumber('document');
    Route::delete('/{document}', [AdminBibliothequeController::class, 'destroy'])
        ->name('destroy')
        ->whereNumber('document');
});
