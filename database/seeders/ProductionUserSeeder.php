<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ParentProfil;
use App\Models\EnseignantProfil;
use App\Models\Eleve;
use App\Models\Classe;
use App\Models\Matiere;
use Illuminate\Support\Facades\Hash;

class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | CLASSES
        |--------------------------------------------------------------------------
        */
        $classes = Classe::all();

        /*
        |--------------------------------------------------------------------------
        | MATIERES
        |--------------------------------------------------------------------------
        */
        $matieres = Matiere::all();

        /*
        |--------------------------------------------------------------------------
        | 1. SUPER ADMIN
        |--------------------------------------------------------------------------
        */
        $superAdmin = User::create([
            'nom' => 'ILBOUDO',
            'prenom' => 'Sibri P Toussaint',
            'telephone_whatsapp' => '70000001',
            'telephone_appel' => '70000001',
            'email' => 'superadmin@keduc.bf',
            'password' => Hash::make('password'),
            'statut' => true,
        ]);

        $superAdmin->assignRole('super-admin');

        /*
        |--------------------------------------------------------------------------
        | 2. ADMIN + ENSEIGNANT SVT
        |--------------------------------------------------------------------------
        */
        $admin = User::create([
            'nom' => 'KIENO',
            'prenom' => 'Sibiri',
            'telephone_whatsapp' => '70000002',
            'telephone_appel' => '70000002',
            'email' => 'admin@keduc.bf',
            'password' => Hash::make('password'),
            'statut' => true,
        ]);

        $admin->assignRole('admin');
        $admin->assignRole('enseignant');

        $adminEnseignant = EnseignantProfil::create([
            'user_id' => $admin->id,
            'numero_orange_money' => '70000002',
            'diplome_max' => 'Licence SVT',
            'lieu_de_service' => 'Ouagadougou',
            'domicile' => 'Karpala',
            'frais_annuel_regle' => true,
        ]);

        // SVT assignation
        $svt = $matieres->where('sigle', 'SVT')->first();
        if ($svt) {
            $adminEnseignant->matieres()->attach($svt->id);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. ENSEIGNANTS
        |--------------------------------------------------------------------------
        */

        $enseignantsData = [
            [
                'nom' => 'OUEDRAOGO',
                'prenom' => 'Amadou',
                'matieres' => ['FR', 'ANG']
            ],
            [
                'nom' => 'TRAORE',
                'prenom' => 'Awa',
                'matieres' => ['MATH', 'PC', 'INFO']
            ],
            [
                'nom' => 'ZONGO',
                'prenom' => 'Mariam',
                'matieres' => ['ANG']
            ],
            [
                'nom' => 'KABORE',
                'prenom' => 'Issa',
                'matieres' => ['FR']
            ],
            [
                'nom' => 'SOME',
                'prenom' => 'Salif',
                'matieres' => ['PC']
            ],
        ];

        $enseignants = [];

        foreach ($enseignantsData as $data) {

            $user = User::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'telephone_whatsapp' => '70' . rand(100000, 999999),
                'telephone_appel' => '70' . rand(100000, 999999),
                'email' => strtolower($data['prenom']) . '@keduc.bf',
                'password' => Hash::make('password'),
                'statut' => true,
            ]);

            $user->assignRole('enseignant');

            $profil = EnseignantProfil::create([
                'user_id' => $user->id,
                'numero_orange_money' => '70' . rand(100000, 999999),
                'diplome_max' => 'Licence',
                'lieu_de_service' => 'Ouagadougou',
                'domicile' => 'BF',
                'frais_annuel_regle' => false,
            ]);

            foreach ($data['matieres'] as $sigle) {
                $matiere = $matieres->where('sigle', $sigle)->first();
                if ($matiere) {
                    $profil->matieres()->attach($matiere->id);
                }
            }

            $enseignants[] = $user;
        }

        /*
        |--------------------------------------------------------------------------
        | 4. PARENTS
        |--------------------------------------------------------------------------
        */

        $parents = [];

        for ($i = 1; $i <= 5; $i++) {

            $parent = User::create([
                'nom' => 'Parent' . $i,
                'prenom' => 'User' . $i,
                'telephone_whatsapp' => '70' . rand(100000, 999999),
                'telephone_appel' => '70' . rand(100000, 999999),
                'email' => 'parent' . $i . '@bf.com',
                'password' => Hash::make('password'),
                'statut' => true,
            ]);

            $parent->assignRole('parent');

            ParentProfil::create([
                'user_id' => $parent->id,
                'adresse_domicile' => 'Ouagadougou',
                'profession' => 'Commerçant',
                'nombre_enfants' => 1,
            ]);

            $parents[] = $parent;
        }

        /*
        |--------------------------------------------------------------------------
        | 5. ÉLÈVES
        |--------------------------------------------------------------------------
        */

        $eleves = [];

        // Parent 1 -> 2 enfants
        for ($i = 1; $i <= 2; $i++) {
            $eleves[] = $this->createEleve($parents[0], $classes);
        }

        // autres parents -> 1 enfant chacun
        for ($i = 1; $i <= 4; $i++) {
            $eleves[] = $this->createEleve($parents[$i], $classes);
        }

        /*
        |--------------------------------------------------------------------------
        | FIN
        |--------------------------------------------------------------------------
        */

        $this->command->info("Seed terminé avec succès !");
    }

    private function createEleve($parent, $classes)
    {
        $user = User::create([
            'nom' => 'Eleve',
            'prenom' => fake()->firstName(),
            'telephone_whatsapp' => '70' . rand(100000, 999999),
            'telephone_appel' => '70' . rand(100000, 999999),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'statut' => true,
        ]);

        $user->assignRole('eleve');

        return Eleve::create([
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'classe_id' => $classes->random()->id,
            'ecole' => 'Lycée Bogodogo - Ouagadougou',
            'date_naissance' => '2010-01-01',
            'lieu_naissance' => 'Ouagadougou',
            'parent_charge' => $parent->nom,
            'etablissement_origine' => 'École primaire publique',
            'profession_pere' => 'Fonctionnaire',
            'profession_mere' => 'Commerçante',
            'regime_etude' => 'externe',
            'loisirs_sport' => 'Football',
            'religion_enfant' => 'Islam',
        ]);
    }
}