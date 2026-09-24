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