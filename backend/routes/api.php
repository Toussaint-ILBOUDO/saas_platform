<?php

use App\Modules\Auth\Http\Controllers\Api\AuthApiController;
use App\Modules\Bibliotheque\Http\Controllers\Api\PublicDocumentApiController;
use App\Modules\Communication\Http\Controllers\Api\PublicActualiteApiController;
use App\Modules\Communication\Http\Controllers\Api\PublicFaqApiController;
use App\Modules\Librairie\Http\Controllers\Api\PublicCommandeApiController;
use App\Modules\Librairie\Http\Controllers\Api\PublicProduitApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\PublicDemandeCoursApiController;
use App\Modules\Public\Http\Controllers\Api\CabinetPublicApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API cabinets (P3)
|--------------------------------------------------------------------------
| Préfixe /api, JSON, tenancy par domaine (appliqué au groupe api dans
| bootstrap/app.php) : chaque <slug>.localhost fait sa propre base.
| Conventions T3.1 : {message, code, erreurs}, 422/401/403/404/429, français.
*/

// ---------- Public (aucune connexion) — T3.3 ----------
Route::prefix('public')->group(function () {
    Route::get('cabinet', [CabinetPublicApiController::class, 'index']);

    Route::get('actualites', [PublicActualiteApiController::class, 'index']);
    Route::get('actualites/{slug}', [PublicActualiteApiController::class, 'show']);

    Route::get('faq', [PublicFaqApiController::class, 'index']);

    Route::get('documents', [PublicDocumentApiController::class, 'index']);
    Route::get('documents/{slug}', [PublicDocumentApiController::class, 'show']);

    Route::get('produits', [PublicProduitApiController::class, 'index']);

    Route::post('demandes-cours', [PublicDemandeCoursApiController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::post('commandes', [PublicCommandeApiController::class, 'store'])
        ->middleware('throttle:10,1');
});

// ---------- Authentification par session (guard web) — T3.2 ----------
Route::prefix('auth')->group(function () {
    Route::post('connexion', [AuthApiController::class, 'connexion'])
        ->middleware('throttle:5,1');
    Route::post('mot-de-passe-oublie', [AuthApiController::class, 'motDePasseOublie'])
        ->middleware('throttle:5,1');
    Route::post('reinitialiser-mot-de-passe', [AuthApiController::class, 'reinitialiserMotDePasse'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:web')->group(function () {
        Route::post('deconnexion', [AuthApiController::class, 'deconnexion']);
        Route::get('moi', [AuthApiController::class, 'moi']);
        Route::post('changer-mot-de-passe', [AuthApiController::class, 'changerMotDePasse']);
        Route::post('role-actif', [AuthApiController::class, 'roleActif']);
    });
});

// ---------- Backoffice MVP (authentifié) — T3.5 ----------
Route::middleware(['auth:web', 'role:admin_cabinet'])->prefix('admin')->group(function () {
    // Contenu public (thème, pied de page, fonctionnalités)
    // Utilisateurs, actualités, FAQ — voir tâches T3.5.
});