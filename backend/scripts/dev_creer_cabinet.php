<?php

/**
 * Script de développement : créer un cabinet et sa base PostgreSQL.
 *
 * Le propriétaire l'exécute LUI-MÊME (création de base = action DB) :
 *
 *   php artisan migrate                              # d'abord, tables centrales tenants + domains
 *   php artisan tinker scripts/dev_creer_cabinet.php  # ensuite, création du cabinet
 *
 * Variables d'environnement optionnelles :
 *   CABINET_SLUG (défaut c1), CABINET_NOM (défaut "Cabinet développement")
 *
 * Effets : INSERT dans `tenants` + `domains` (base centrale) ;
 * CREATE DATABASE cabinet_<slug> + exécution des migrations tenant.
 */

$slug = env('CABINET_SLUG', 'c1');
$nom = env('CABINET_NOM', 'Cabinet développement');

$cabinet = App\Models\Cabinet::find($slug);

if ($cabinet) {
    echo "Le cabinet [{$slug}] existe déjà (id={$cabinet->id}).\n";

    return;
}

$cabinet = App\Models\Cabinet::create([
    'id' => $slug,
    'nom' => $nom,
    'sous_domaine' => $slug,
]);

if (! $cabinet->domains()->where('domain', "{$slug}.localhost")->exists()) {
    $cabinet->domains()->create(['domain' => "{$slug}.localhost"]);
}

echo "Cabinet [{$slug}] créé : base « {$cabinet->database()->getName()} » sur domaine {$slug}.localhost\n";
echo "Résultat : ", ($cabinet->database()->getName() ? 'OK' : 'ECHEC'), "\n";