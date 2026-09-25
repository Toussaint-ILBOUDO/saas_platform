<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Point d'entrée de l'application cabinet (web KEduc). Ces routes ne sont
| accessibles que sur un domaine de cabinet (<slug>.localhost en dev) :
| InitializeTenancyByDomain sélectionne le tenant, puis la base cabinet.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'cabinet.actif',
])->group(function () {
    // Impersonation (T2.6) : consommation du jeton Landlord — toujours
    // disponible, indépendante de l'interrupteur web KEduc (T2.10).
    Route::get('/impersonation/{jeton}', [App\Modules\Impersonation\Http\Controllers\ImpersonationController::class, 'entrer'])
        ->name('impersonation.entrer');
    Route::post('/impersonation/sortir', [App\Modules\Impersonation\Http\Controllers\ImpersonationController::class, 'sortir'])
        ->name('impersonation.sortir');
});

// Web KEduc (référence fonctionnelle — décision B4) : servi uniquement si
// l'interrupteur du Landlord est activé (par défaut désactivé, T2.10).
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'cabinet.actif',
    'keduc.web',
])->group(function () {
    require __DIR__ . '/web.php';
    require __DIR__ . '/auth.php';
    require __DIR__ . '/pedagogie.php';
    require __DIR__ . '/notifications.php';
    require __DIR__ . '/finance.php';
    require __DIR__ . '/bibliotheque.php';
    require __DIR__ . '/librairie.php';
    require __DIR__ . '/cms.php';
    require __DIR__ . '/actualites.php';
    require __DIR__ . '/temoignages.php';
});