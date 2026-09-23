<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Models\Eleve;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EleveService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {

            // ======================
            // USER ELEVE
            // ======================
            $user = User::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'] ?? null,
                'telephone_whatsapp' => $data['telephone_whatsapp'] ?? null,
                'telephone_appel' => $data['telephone_appel'] ?? null,
                'password' => isset($data['password'])
                    ? Hash::make($data['password'])
                    : Hash::make('password123'), // temporaire
            ]);

            $user->assignRole('eleve');

            // ======================
            // PROFIL ELEVE
            // ======================
            $user->eleve()->create([
                'parent_id' => $data['parent_id'],
                'classe_id' => $data['classe_id'],
                'ecole' => $data['ecole'] ?? null,
                'date_naissance' => $data['date_naissance'] ?? null,
                'lieu_naissance' => $data['lieu_naissance'] ?? null,
                'parent_charge' => $data['parent_charge'] ?? null,
                'etablissement_origine' => $data['etablissement_origine'] ?? null,

                'profession_pere' => $data['profession_pere'] ?? null,
                'profession_mere' => $data['profession_mere'] ?? null,

                'regime_etude' => $data['regime_etude'] ?? null,
                'loisirs_sport' => $data['loisirs_sport'] ?? null,
                'religion_enfant' => $data['religion_enfant'] ?? null,

                'maladies_allergies' => $data['maladies_allergies'] ?? null,
                'interdits_familiaux' => $data['interdits_familiaux'] ?? null,

                'boisson_preferee' => $data['boisson_preferee'] ?? null,
                'nourriture_preferee' => $data['nourriture_preferee'] ?? null,

                'autres_precautions' => $data['autres_precautions'] ?? null,
                'autres_observations' => $data['autres_observations'] ?? null,
                'statut' => false,
                
            ]);

            return $user->load('eleve');
        });
    }

    public function activateAccount(
        Eleve $eleve,
        array $data
    ): void {

        /*
        |--------------------------------------------------------------------------
        | 🔐 ACTIVATION DU COMPTE ÉLÈVE
        |--------------------------------------------------------------------------
        | Le blocage de connexion (AuthService) s'appuie sur le flag
        | `eleves.statut` ET `users.statut`. Les DEUX doivent passer à true,
        | sinon l'élève « activé » reste bloqué au login
        | (« Compte élève désactivé. »).
        |--------------------------------------------------------------------------
        */

        $eleve->update([
            'statut' => true,
        ]);

        $eleve->user->update([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'statut' => true,
        ]);
    }
}