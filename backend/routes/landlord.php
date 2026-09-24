<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Landlord (domaine central de la plateforme)
|--------------------------------------------------------------------------
|
| Réservées au super admin de la plateforme : connexion, gestion des
| cabinets, facturation plateforme (P2/P7). Aucune donnée de cabinet
| n'est exposée ici.
|
*/

Route::get('/', function () {
    return response()->json([
        'plateforme' => config('app.name'),
        'contexte' => 'landlord',
        'message' => 'Interface plateforme — en construction (P2).',
    ]);
});
