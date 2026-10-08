<?php

namespace App\Modules\Pedagogie\Support;

use App\Models\RapportElement;
use Illuminate\Contracts\Validation\Validator;

/**
 * Validation partagée des réponses du modèle de rapport.
 *
 * Les réponses sont indexées par l'id d'un `rapport_elements` actif ; les
 * éléments marqués « obligatoire » doivent recevoir une réponse non vide.
 * Utilisé par Store et Update rapport — la règle vaut au dépôt comme à la
 * re-soumission : un rapport incomplet ne part jamais.
 */
class ValideReponsesRapport
{
    /**
     * @param  array<string, mixed>|null  $reponses
     */
    public static function valider(Validator $validator, ?array $reponses): void
    {
        if ($reponses === null) {
            return;
        }

        $elementsActifs = RapportElement::query()
            ->where('actif', true)
            ->get();

        $ids = $elementsActifs->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        foreach (array_keys($reponses) as $cle) {
            if (! in_array((string) $cle, $ids, true)) {
                $validator->errors()->add(
                    'reponses',
                    "L'élément « {$cle} » n'existe pas dans le modèle de rapport."
                );

                return;
            }
        }

        foreach ($elementsActifs->where('obligatoire', true) as $element) {
            $valeur = $reponses[(string) $element->id] ?? null;

            if ($valeur === null || trim((string) $valeur) === '') {
                $validator->errors()->add(
                    'reponses',
                    "Le champ « {$element->libelle} » est obligatoire."
                );
            }
        }
    }
}