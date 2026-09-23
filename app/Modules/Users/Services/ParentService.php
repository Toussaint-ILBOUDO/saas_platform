<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ParentService
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

            $user->assignRole('parent');

            $user->parentProfil()->create([
                'adresse_domicile' => $data['adresse_domicile'] ?? null,
                'profession' => $data['profession'] ?? null,
                'nombre_enfants' => $data['nombre_enfants'] ?? 0,
            ]);

            return $user->load('parentProfil');
        });
    }
}