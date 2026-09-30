<?php

use App\Modules\Auth\Http\Controllers\Api\AuthApiController;
use App\Modules\Bibliotheque\Http\Controllers\Api\PublicDocumentApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminActualiteApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminFaqQuestionApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminFaqSectionApiController;
use App\Modules\Communication\Http\Controllers\Api\PublicActualiteApiController;
use App\Modules\Communication\Http\Controllers\Api\PublicFaqApiController;
use App\Modules\Librairie\Http\Controllers\Api\PublicCommandeApiController;
use App\Modules\Librairie\Http\Controllers\Api\PublicProduitApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\PublicDemandeCoursApiController;
use App\Modules\Public\Http\Controllers\Api\AdminContenuPublicApiController;
use App\Modules\Public\Http\Controllers\Api\CabinetPublicApiController;
use App\Modules\Public\Http\Controllers\Api\PublicEnseignantApiController;
use App\Modules\Public\Http\Controllers\Api\PublicReferencesApiController;
use App\Modules\Public\Http\Controllers\Api\PublicStatsApiController;
use App\Modules\Systeme\Http\Controllers\Api\AdminUtilisateurApiController;
use App\Modules\Systeme\Http\Controllers\Api\NotificationApiController;
use App\Modules\Systeme\Http\Controllers\Api\PushSubscriptionApiController;
use App\Modules\Temoignages\Http\Controllers\Api\PublicTemoignageApiController;
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
    Route::get('logo', [CabinetPublicApiController::class, 'logo']);

    Route::get('actualites', [PublicActualiteApiController::class, 'index']);
    Route::get('actualites/{slug}', [PublicActualiteApiController::class, 'show']);

    Route::get('faq', [PublicFaqApiController::class, 'index']);

    Route::get('documents', [PublicDocumentApiController::class, 'index']);
    Route::get('documents/{slug}', [PublicDocumentApiController::class, 'show']);

    Route::get('produits', [PublicProduitApiController::class, 'index']);

    // Statistiques, enseignants, témoignages — P2 (page publique frontend).
    Route::get('stats', [PublicStatsApiController::class, 'index']);
    Route::get('enseignants', [PublicEnseignantApiController::class, 'index']);
    Route::get('temoignages', [PublicTemoignageApiController::class, 'index']);
    Route::get('temoignages/{slug}', [PublicTemoignageApiController::class, 'show']);
    Route::get('references', [PublicReferencesApiController::class, 'index']);

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

// ---------- Notifications (base) + abonnement push — T3.6 ----------
Route::middleware('auth:web')->group(function () {
    Route::get('notifications', [NotificationApiController::class, 'index']);
    Route::post('notifications/lire-toutes', [NotificationApiController::class, 'lireToutes']);
    Route::post('notifications/{notification}/lue', [NotificationApiController::class, 'marquerLue']);
    Route::delete('notifications/{notification}', [NotificationApiController::class, 'destroy']);

    Route::get('abonnement-push', [PushSubscriptionApiController::class, 'index']);
    Route::post('abonnement-push', [PushSubscriptionApiController::class, 'store']);
    Route::delete('abonnement-push', [PushSubscriptionApiController::class, 'destroy']);
});

// ---------- Backoffice MVP (authentifié, staff cabinet) — T3.5 ----------
Route::middleware(['auth:web', 'role:admin_cabinet'])->prefix('admin')->name('admin.')->group(function () {
    // Contenu public (thème, pied de page, données)
    Route::get('contenu-public', [AdminContenuPublicApiController::class, 'show']);
    Route::put('contenu-public', [AdminContenuPublicApiController::class, 'update']);
    // POST en plus de PUT : PHP < 8.4 ne peuple pas $_POST/$_FILES pour un
    // multipart en PUT — le navigateur envoie donc l'upload en POST.
    Route::match(['put', 'post'], 'contenu-public/logo', [AdminContenuPublicApiController::class, 'mettreAJourLogo']);

    // Actualités
    Route::get('actualites', [AdminActualiteApiController::class, 'index']);
    Route::post('actualites', [AdminActualiteApiController::class, 'store']);
    Route::get('actualites/{actualite}', [AdminActualiteApiController::class, 'show']);
    Route::match(['put', 'post'], 'actualites/{actualite}', [AdminActualiteApiController::class, 'update']);
    Route::delete('actualites/{actualite}', [AdminActualiteApiController::class, 'destroy']);

    // FAQ
    Route::get('faq/sections', [AdminFaqSectionApiController::class, 'index']);
    Route::post('faq/sections', [AdminFaqSectionApiController::class, 'store']);
    Route::get('faq/sections/{faqSection}', [AdminFaqSectionApiController::class, 'show']);
    Route::put('faq/sections/{faqSection}', [AdminFaqSectionApiController::class, 'update']);
    Route::delete('faq/sections/{faqSection}', [AdminFaqSectionApiController::class, 'destroy']);

    Route::get('faq/sections/{faqSection}/questions', [AdminFaqQuestionApiController::class, 'index']);
    Route::post('faq/questions', [AdminFaqQuestionApiController::class, 'store']);
    Route::get('faq/questions/{faqQuestion}', [AdminFaqQuestionApiController::class, 'show']);
    Route::put('faq/questions/{faqQuestion}', [AdminFaqQuestionApiController::class, 'update']);
    Route::delete('faq/questions/{faqQuestion}', [AdminFaqQuestionApiController::class, 'destroy']);

    // Utilisateurs
    Route::get('utilisateurs', [AdminUtilisateurApiController::class, 'index']);
    Route::post('utilisateurs', [AdminUtilisateurApiController::class, 'store']);
    Route::get('utilisateurs/{utilisateur}', [AdminUtilisateurApiController::class, 'show']);
    Route::put('utilisateurs/{utilisateur}', [AdminUtilisateurApiController::class, 'update']);
    Route::patch('utilisateurs/{utilisateur}/activer', [AdminUtilisateurApiController::class, 'activer']);
    Route::patch('utilisateurs/{utilisateur}/suspendre', [AdminUtilisateurApiController::class, 'suspendre']);
});