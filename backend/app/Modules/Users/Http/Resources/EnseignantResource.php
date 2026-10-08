<?php

namespace App\Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * L'enseignant est un `User` porteur du rôle « enseignant » + un profil.
 * Le mot de passe et l'email de connexion ne sont jamais sérialisés ici
 * (le mot de passe ne l'est de toute façon pas, mais on reste explicite).
 */
class EnseignantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profil = $this->enseignantProfil;

        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone_whatsapp' => $this->telephone_whatsapp,
            'telephone_appel' => $this->telephone_appel,
            'statut' => (bool) $this->statut,
            'profil' => $profil ? [
                'id' => $profil->id,
                'numero_orange_money' => $profil->numero_orange_money,
                'diplome_max' => $profil->diplome_max,
                'lieu_de_service' => $profil->lieu_de_service,
                'domicile' => $profil->domicile,
                'frais_annuel_regle' => (bool) $profil->frais_annuel_regle,
                'matieres' => $profil->relationLoaded('matieres')
                    ? $profil->matieres->map(fn ($m) => [
                        'id' => $m->id,
                        'nom' => $m->nom,
                        'sigle' => $m->sigle,
                    ])->values()
                    : null,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}