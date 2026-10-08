<?php

namespace App\Modules\Pedagogie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationEnseignantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contrat_cours_id' => $this->contrat_cours_id,
            'statut' => $this->statut,
            'date_affectation' => $this->date_affectation?->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),

            // Paramètres financiers : ce sont eux qui alimentent la facture
            // parent (D-049) et le bulletin de l'enseignant (D-048).
            'nombre_heures_prevues' => (float) $this->nombre_heures_prevues,
            'taux_horaire_enseignant' => (int) $this->taux_horaire_enseignant,
            'montant_prevu' => (float) $this->nombre_heures_prevues * (int) $this->taux_horaire_enseignant,

            'matiere' => $this->whenLoaded('matiere', fn () => $this->matiere ? [
                'id' => $this->matiere->id,
                'nom' => $this->matiere->nom,
                'sigle' => $this->matiere->sigle,
            ] : null),

            'enseignant' => $this->whenLoaded('enseignant', fn () => $this->enseignant ? [
                'id' => $this->enseignant->id,
                'user_id' => $this->enseignant->user_id,
                'nom' => $this->enseignant->user?->nom,
                'prenom' => $this->enseignant->user?->prenom,
            ] : null),
        ];
    }
}