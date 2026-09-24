<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Permissions — garanties présentes avant toute affectation (idempotent)
        |--------------------------------------------------------------------------
        */
        $this->call(PermissionSeeder::class);

        /*
        |--------------------------------------------------------------------------
        | Rôles — créés s'ils n'existent pas (idempotent, garde 'web')
        |--------------------------------------------------------------------------
        */
        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $parent = Role::findOrCreate('parent', 'web');
        $enseignant = Role::findOrCreate('enseignant', 'web');
        $eleve = Role::findOrCreate('eleve', 'web');
        $gestionnaire = Role::findOrCreate('gestionnaire', 'web');

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        $superAdmin->givePermissionTo(
            Permission::all()
        );

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        $admin->givePermissionTo([
            'dashboard.view',

            'eleve.view',
            'eleve.create',
            'eleve.update',

            'enseignant.view',
            'enseignant.create',
            'enseignant.update',

            'contrat.view',
            'contrat.create',
            'contrat.update',

            'facture.view',
            'facture.create',

            'paiement.view',
            'paiement.create',

            'rapport.view',

            'bibliotheque.view',
            'bibliotheque.create',
            'bibliotheque.update',
            'bibliotheque.delete',
            'bibliotheque.moderate',

            'librairie.view',
            'categorie.create',
            'categorie.update',
            'categorie.delete',
            'produit.create',
            'produit.update',
            'produit.delete',
            'commande.update',
            'commande.cancel',

            'faq.view',
            'faq.create',
            'faq.update',
            'faq.delete',

            'actualite.view',
            'actualite.create',
            'actualite.update',
            'actualite.delete',

            'temoignage.view',
            'temoignage.moderate',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Parent
        |--------------------------------------------------------------------------
        */

        $parent->givePermissionTo([
            'dashboard.view',
            'facture.view',
            'rapport.view',
            'bibliotheque.view',
            'bibliotheque.create',
            'temoignage.create',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Enseignant
        |--------------------------------------------------------------------------
        */

        $enseignant->givePermissionTo(
            Permission::whereIn('name', [
                'dashboard.view',
                'rapport.create',
                'bibliotheque.create',
                'bibliotheque.view',
                'temoignage.create',
            ])->get()
        );

        /*
        |--------------------------------------------------------------------------
        | Eleve
        |--------------------------------------------------------------------------
        */

        $eleve->givePermissionTo([
            'dashboard.view',
            'bibliotheque.view',
            'bibliotheque.create',
            'temoignage.create',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Gestionnaire
        |--------------------------------------------------------------------------
        */

        $gestionnaire->givePermissionTo([
            'dashboard.view',
            'librairie.view',
            'categorie.create',
            'categorie.update',
            'categorie.delete',
            'produit.create',
            'produit.update',
            'produit.delete',
            'commande.update',
            'commande.cancel',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cache des permissions — purge obligatoire (CACHE_STORE=database)
        |--------------------------------------------------------------------------
        */
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}