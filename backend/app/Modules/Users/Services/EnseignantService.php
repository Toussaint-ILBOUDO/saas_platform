<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnseignantService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'telephone_whatsapp' => $data['telephone_whatsapp'] ?? null,
                'telephone_appel' => $data['telephone_appel'] ?? null,
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('enseignant');

            $user->enseignantProfil()->create([
                'numero_orange_money' => $data['numero_orange_money'] ?? null,
                'diplome_max' => $data['diplome_max'] ?? null,
                'lieu_de_service' => $data['lieu_de_service'] ?? null,
                'domicile' => $data['domicile'] ?? null,
                'frais_annuel_regle' => $data['frais_annuel_regle'] ?? false,
            ]);

            if (!empty($data['matieres'])) {
                $user->enseignantProfil->matieres()->sync($data['matieres']);
            }

            return $user->load('enseignantProfil.matieres');
        });
    }

    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {

            $user = User::findOrFail($id);

            $userData = [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'telephone_whatsapp' => $data['telephone_whatsapp'] ?? null,
                'telephone_appel' => $data['telephone_appel'] ?? null,
                'email' => $data['email'] ?? null,
            ];

            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $user->update($userData);

            $user->enseignantProfil()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'numero_orange_money' => $data['numero_orange_money'] ?? null,
                    'diplome_max' => $data['diplome_max'] ?? null,
                    'lieu_de_service' => $data['lieu_de_service'] ?? null,
                    'domicile' => $data['domicile'] ?? null,
                    'frais_annuel_regle' => $data['frais_annuel_regle'] ?? false,
                ]
            );

            if (isset($data['matieres'])) {
                $user->enseignantProfil->matieres()->sync($data['matieres']);
            }

            return $user->load('enseignantProfil.matieres');
        });
    }
}
