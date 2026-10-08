<?php

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T7A.9 — Ligne de paie d'un bulletin.
 *
 * La ligne est copiée au moment de la génération (édition figée) : elle porte
 * l'élève, la matière, les heures et le taux d'origine. La relation
 * `affectation` permet au frontend d'aller plus loin si besoin.
 */
class BulletinPaieLigneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'bulletin_paie_id' => (int) $this->bulletin_paie_id,
            'affectation_enseignant_id' => (int) $this->affectation_enseignant_id,
            'eleve' => $this->whenLoaded('eleve', fn () => (
                $this->eleve?->user
                    ? trim(
                        ($this->eleve->user->prenom ?? '')
                        .' '.($this->eleve->user->nom ?? '')
                    )
                    : null
            )),
            'matiere' => $this->whenLoaded('matiere', fn () => (
                $this->matiere?->nom ?? null
            )),
            'nombre_heures' => (float) $this->nombre_heures,
            'taux_horaire' => (int) $this->taux_horaire,
            'montant' => (int) $this->montant,
        ];
    }
}