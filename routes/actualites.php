<?php

use App\Modules\Communication\Http\Controllers\AdminActualiteController;
use App\Modules\Communication\Http\Controllers\PanelActualiteController;
use App\Modules\Communication\Http\Controllers\PublicActualiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Actualités — Public
|--------------------------------------------------------------------------
*/

Route::get('/actualites', [PublicActualiteController::class, 'index'])
    ->name('actualites.index');

Route::get('/actualites/{actualite:slug}', [PublicActualiteController::class, 'show'])
    ->name('actualites.show');

Route::post('/actualites/{actualite:slug}/reaction', [PublicActualiteController::class, 'reaction'])
    ->name('actualites.reaction');

Route::post('/actualites/{actualite:slug}/partager', [PublicActualiteController::class, 'partager'])
    ->name('actualites.partager');

/*
|--------------------------------------------------------------------------
| Routes Actualités — Espace interne (panel)
|--------------------------------------------------------------------------
| Flux d'actualités internes destinées à la communauté scolaire connectée :
| chaque utilisateur ne voit que les actualités publiées qui le ciblent
| (champ « destinataires »), ainsi que les actualités globales.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:eleve|enseignant|parent'])
    ->get('/mes-actualites', [PanelActualiteController::class, 'index'])
    ->name('actualites.internes');

Route::middleware(['auth', 'role:eleve|enseignant|parent'])
    ->get('/mes-actualites/{slug}', [PanelActualiteController::class, 'show'])
    ->name('actualites.internes.show');

/*
|--------------------------------------------------------------------------
| Routes Actualités — Administration (CMS)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|super-admin'])
    ->prefix('admin/cms/actualites')
    ->name('admin.actualites.')
    ->group(function () {

        Route::get('/', [AdminActualiteController::class, 'index'])
            ->name('index');

        Route::get('/creer', [AdminActualiteController::class, 'create'])
            ->name('create');
        Route::post('/', [AdminActualiteController::class, 'store'])
            ->name('store');

        Route::get('/{actualite}/modifier', [AdminActualiteController::class, 'edit'])
            ->name('edit');
        Route::put('/{actualite}', [AdminActualiteController::class, 'update'])
            ->name('update');

        Route::get('/{actualite}/publier', [AdminActualiteController::class, 'publishForm'])
            ->name('publier.form');
        Route::post('/{actualite}/publier', [AdminActualiteController::class, 'publish'])
            ->name('publier');

        Route::post('/{actualite}/depublier', [AdminActualiteController::class, 'unpublish'])
            ->name('depublier');

        Route::patch('/{actualite}/toggle', [AdminActualiteController::class, 'toggle'])
            ->name('toggle');

        Route::delete('/{actualite}', [AdminActualiteController::class, 'destroy'])
            ->name('destroy');
    });
