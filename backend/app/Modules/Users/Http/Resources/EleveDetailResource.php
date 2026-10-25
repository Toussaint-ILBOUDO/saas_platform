<?php

namespace App\Modules\Users\Http\Resources;

use Illuminate\Http\Request;

/**
 * @mixin \App\Models\Eleve
 *
 * Fiche élève ADMIN (M3 scolarité) : `EleveResource` (ce que tous les écrans
 * partagent) + ce qui n'appartient qu'au rattachement et à la fiche complète.
 *
 * `EleveResource` reste le format des listes (contrats, objectifs, demandes)
 * : la fiche détaillée n'apparaît que sur `GET|PUT
 * /pedagogie/eleves/{eleve}` et ses suites (activation/suspension).
 */
class EleveDetailResource extends EleveResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),

            // Rattachements en clair : les sélecteurs du formulaire admin ont
            // besoin des identifiants bruts (le bloc `parent`/`classe` ci-dessus
            // n'est présent que si la relation est chargée).
            'parent_id' => $this->parent_id,
            'classe_id' => $this->classe_id,

            // Fiche scolaire complète — `ecole`, `date_naissance` et
            // `lieu_naissance` sont déjà en haut de champ via la ressource mère.
            'fiche' => [
                'parent_charge' => $this->parent_charge,
                'etablissement_origine' => $this->etablissement_origine,
                'profession_pere' => $this->profession_pere,
                'profession_mere' => $this->profession_mere,
                'regime_etude' => $this->regime_etude,
                'loisirs_sport' => $this->loisirs_sport,
                'religion_enfant' => $this->religion_enfant,
                'maladies_allergies' => $this->maladies_allergies,
                'interdits_familiaux' => $this->interdits_familiaux,
                'boisson_preferee' => $this->boisson_preferee,
                'nourriture_preferee' => $this->nourriture_preferee,
                'autres_precautions' => $this->autres_precautions,
                'autres_observations' => $this->autres_observations,
            ],
        ];
    }
}
