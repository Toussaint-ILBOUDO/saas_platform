<?php

use App\Modules\Temoignages\Http\Controllers\AdminTemoignageController;
use App\Modules\Temoignages\Http\Controllers\PublicTemoignageController;
use App\Modules\Temoignages\Http\Controllers\TemoignageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Témoignages — Public
|--------------------------------------------------------------------------
*/

Route::get('/temoignages', [PublicTemoignageController::class, 'index'])
    ->name('temoignages.index');

Route::post('/temoignages/{temoignage:slug}/reaction', [PublicTemoignageController::class, 'reaction'])
    ->name('temoignages.reaction');

/*
|--------------------------------------------------------------------------
| Routes Témoignages — Espace privé (connecté)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/temoignages/mes-temoignages', [TemoignageController::class, 'mes'])
        ->name('temoignages.mes.index');

    Route::get('/temoignages/creer', [TemoignageController::class, 'create'])
        ->name('temoignages.create');

    Route::post('/temoignages', [TemoignageController::class, 'store'])
        ->name('temoignages.store');

    Route::get('/temoignages/{temoignage}/modifier', [TemoignageController::class, 'edit'])
        ->name('temoignages.edit');

    Route::put('/temoignages/{temoignage}', [TemoignageController::class, 'update'])
        ->name('temoignages.update');

    Route::delete('/temoignages/{temoignage}', [TemoignageController::class, 'destroy'])
        ->name('temoignages.destroy');

    Route::post('/temoignages/{temoignage:slug}/commentaire', [PublicTemoignageController::class, 'commentaire'])
        ->name('temoignages.commentaire');

    Route::post('/temoignages/{temoignage:slug}/signalement', [PublicTemoignageController::class, 'signalement'])
        ->name('temoignages.signalement');
});

/*
|--------------------------------------------------------------------------
| Route publique {slug} — APRÈS les routes privées pour éviter l'ombre
|--------------------------------------------------------------------------
*/

Route::get('/temoignages/{temoignage:slug}', [PublicTemoignageController::class, 'show'])
    ->name('temoignages.show');

/*
|--------------------------------------------------------------------------
| Routes Témoignages — Administration (modération)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|super-admin'])
    ->prefix('admin/cms/temoignages')
    ->name('admin.temoignages.')
    ->group(function () {

        Route::get('/', [AdminTemoignageController::class, 'index'])
            ->name('index');

        Route::get('/signalements', [AdminTemoignageController::class, 'signalements'])
            ->name('signalements');

        Route::post('/signalements/{signalement}/traiter', [AdminTemoignageController::class, 'traiterSignalement'])
            ->name('signalements.traiter');

        Route::get('/{temoignage}', [AdminTemoignageController::class, 'show'])
            ->name('show');

        Route::patch('/{temoignage}/masquer', [AdminTemoignageController::class, 'masquer'])
            ->name('masquer');

        Route::patch('/{temoignage}/restaurer', [AdminTemoignageController::class, 'restaurer'])
            ->name('restaurer');

        Route::delete('/{temoignage}', [AdminTemoignageController::class, 'destroy'])
            ->name('destroy');
    });
