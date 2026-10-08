<?php

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T7A.8 — Ligne d'une facture parent.
 *
 * D-049 : la ligne pointe vers l'affectation, qui porte l'enseignant, la
 * matière et le taux. On ne recopie jamais ces valeurs dans la table de
 * facturation — la ressource les expose telles quelles au client.
 */
class LigneFactureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'facture_id' => (int) $this->facture_id,
            'affectation_enseignant_id' => (int) $this->affectation_enseignant_id,
            'enseignant' => $this->whenLoaded('affectation', fn () => (
                $this->affectation?->enseignant?->user
                    ? trim(
                        ($this->affectation->enseignant->user->prenom ?? '')
                        .' '.($this->affectation->enseignant->user->nom ?? '')
                    )
                    : null
            )),
            'matiere' => $this->whenLoaded('affectation', fn () => (
                $this->affectation?->matiere?->nom ?? null
            )),
            'nombre_heures' => (float) $this->nombre_heures,
            'taux_horaire' => (int) $this->taux_horaire,
            'montant' => (int) $this->montant,
        ];
    }
}