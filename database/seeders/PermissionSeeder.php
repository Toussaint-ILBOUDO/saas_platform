<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // élèves
            'eleve.view',
            'eleve.create',
            'eleve.update',
            'eleve.delete',

            // enseignants
            'enseignant.view',
            'enseignant.create',
            'enseignant.update',
            'enseignant.delete',

            // contrats
            'contrat.view',
            'contrat.create',
            'contrat.update',
            'contrat.delete',

            // factures
            'facture.view',
            'facture.create',
            'facture.update',

            // paiements
            'paiement.view',
            'paiement.create',

            // rapports
            'rapport.view',
            'rapport.create',

            // bibliothèque
            'bibliotheque.view',
            'bibliotheque.create',
            'bibliotheque.update',
            'bibliotheque.delete',
            'bibliotheque.moderate',

            // librairie
            'librairie.view',
            'categorie.create',
            'categorie.update',
            'categorie.delete',
            'produit.create',
            'produit.update',
            'produit.delete',
            'commande.update',
            'commande.cancel',

            // faq (CMS)
            'faq.view',
            'faq.create',
            'faq.update',
            'faq.delete',

            // actualités (CMS)
            'actualite.view',
            'actualite.create',
            'actualite.update',
            'actualite.delete',

            // témoignages (CMS)
            'temoignage.view',
            'temoignage.create',
            'temoignage.moderate',

            // dashboard
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
    }
}