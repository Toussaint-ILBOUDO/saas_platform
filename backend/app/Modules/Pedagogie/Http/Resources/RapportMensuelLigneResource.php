<?php

namespace App\Modules\Pedagogie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T7A.7 — Ligne de ventilation d'un rapport mensuel (D-049).
 *
 * Ces lignes sont la source unique des heures : la facture parent et le
 * bulletin de paie consomment exactement les mêmes lignes. C'est pourquoi le
 * montant n'est PAS envoyé ici — il dépend du taux d'une affectation, pas du
 * rapport — mais pourquoi `taux_horaire` l'est : le frontend doit pouvoir
 * afficher l'estimation avant validation, comme le fait le PDF KEduc.
 */
class RapportMensuelLigneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $taux = (int) ($this->affectation?->taux_horaire_enseignant ?? 0);
        $heures = (float) $this->nombre_heures;

        return [
            'id' => $this->id,
            'affectation_enseignant_id' => (int) $this->affectation_enseignant_id,
            'matiere_id' => (int) $this->matiere_id,
            'matiere_nom' => $this->affectation?->matiere?->nom
                ?? $this->matiere?->nom,
            'nombre_seances' => (int) $this->nombre_seances,
            'nombre_heures' => (float) $this->nombre_heures,
            'taux_horaire' => $taux,
            'montant_estime' => (int) round($heures * $taux),
        ];
    }
}
