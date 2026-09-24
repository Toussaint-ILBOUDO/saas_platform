<?php

namespace Database\Seeders;

use App\Models\ParametrePublic;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder exécuté dans chaque base cabinet (provigionnement §6.6) :
 * rôles tenant (D-007), permissions, référentiels et thème/footer par défaut.
 */
class TenantDatabaseSeeder extends Seeder
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
        | Rôles tenant — D-007 (5 rôles, guard 'web', sans super-admin)
        |--------------------------------------------------------------------------
        */
        $adminCabinet = Role::findOrCreate('admin_cabinet', 'web');
        $enseignant = Role::findOrCreate('enseignant', 'web');
        $parent = Role::findOrCreate('parent', 'web');
        $eleve = Role::findOrCreate('eleve', 'web');
        $gestionnaireLibrairie = Role::findOrCreate('gestionnaire_librairie', 'web');

        /*
        |--------------------------------------------------------------------------
        | Admin cabinet — pilotage complet (anciens rôles « admin » + « gestionnaire »)
        |--------------------------------------------------------------------------
        */
        $adminCabinet->givePermissionTo(Permission::all());

        /*
        |--------------------------------------------------------------------------
        | Enseignant
        |--------------------------------------------------------------------------
        */
        $enseignant->givePermissionTo([
            'dashboard.view',
            'rapport.create',
            'bibliotheque.create',
            'bibliotheque.view',
            'temoignage.create',
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
        | Elève
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
        | Gestionnaire librairie
        |--------------------------------------------------------------------------
        */
        $gestionnaireLibrairie->givePermissionTo([
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

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Référentiels par défaut
        |--------------------------------------------------------------------------
        */
        $this->call(TypeCoursSeeder::class);
        $this->call(TypeDocumentSeeder::class);

        /*
        |--------------------------------------------------------------------------
        | Thème / footer / paramètres publics par défaut
        |--------------------------------------------------------------------------
        */
        ParametrePublic::firstOrCreate(
            ['id' => 1],
            [
                'theme' => [
                    'couleurs' => [
                        '--couleur-primaire' => '#1b7f5c',
                        '--couleur-secondaire' => '#f4a261',
                        '--couleur-fond' => '#ffffff',
                        '--couleur-texte' => '#2b2b2b',
                        '--couleur-accent' => '#e9ecef',
                    ],
                    'polices' => [
                        'titres' => null,
                        'texte' => null,
                    ],
                ],
                'footer' => [],
                'data' => [],
            ]
        );
    }
}