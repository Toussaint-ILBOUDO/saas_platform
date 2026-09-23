<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Matiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    public function edit()
    {
        $user = auth()->user()->load([
            'parentProfil',
            'enseignantProfil.matieres',
            'eleve.classe',
        ]);

        $role = $user->getRoleNames()->first();

        return match ($role) {
            'enseignant' => view('profil.edit-enseignant', [
                'user' => $user,
                'allMatieres' => Matiere::where('actif', true)
                    ->orderBy('nom')
                    ->get(),
            ]),
            'parent' => view('profil.edit-parent', compact('user')),
            'eleve' => view('profil.edit-eleve', compact('user')),
            default => view('profil.edit-admin', compact('user')),
        };
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone_whatsapp' => ['nullable', 'string'],
            'telephone_appel' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'password' => ['nullable', 'string', 'min:6'],

            'numero_orange_money' => ['nullable', 'string'],
            'diplome_max' => ['nullable', 'string'],
            'lieu_de_service' => ['nullable', 'string'],
            'domicile' => ['nullable', 'string'],
            'frais_annuel_regle' => ['nullable', 'boolean'],
            'matieres' => ['nullable', 'array'],
            'matieres.*' => ['exists:matieres,id'],

            'profession' => ['nullable', 'string'],
            'nombre_enfants' => ['nullable', 'integer'],
            'adresse_domicile' => ['nullable', 'string'],
        ]);

        $userData = [
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'telephone_whatsapp' => $validated['telephone_whatsapp'] ?? null,
            'telephone_appel' => $validated['telephone_appel'] ?? null,
            'email' => $validated['email'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        match ($role) {
            'enseignant' => $this->updateEnseignant($user, $validated),
            'parent' => $this->updateParent($user, $validated),
            default => null,
        };

        return redirect()
            ->route('profil.edit')
            ->with('success', 'Profil mis à jour avec succès');
    }

    private function updateEnseignant($user, array $data): void
    {
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
    }

    private function updateParent($user, array $data): void
    {
        $user->parentProfil()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'profession' => $data['profession'] ?? null,
                'nombre_enfants' => $data['nombre_enfants'] ?? 0,
                'adresse_domicile' => $data['adresse_domicile'] ?? null,
            ]
        );
    }
}
