<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Models\RapportMensuelEnseignant;
use App\Modules\Pedagogie\Support\ValideReponsesRapport;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.7 — Modification d'un rapport mensuel par l'enseignant.
 *
 * Correction d'un rapport encore soumis (statut `soumis`), ou re-soumission
 * d'un rapport rejeté via les mêmes champs. L'état contrôle les transitions
 * dans le service (`corriger` / `resoumettre`), qui applique aussi la garde
 * de période : ici on ne valide que les champs modifiables.
 *
 * Les réponses au modèle de rapport sont facultatives en modification : une
 * correction peut ne toucher qu'un seul élément. Dès qu'elles sont fournies,
 * les éléments obligatoires sont revérifiés. La ventilation est toujours
 * recalculée côté service depuis le cahier de texte, jamais reçue du client.
 */
class UpdateRapportMensuelApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $rapport = $this->route('rapport');

        if (! $user || ! $rapport instanceof RapportMensuelEnseignant) {
            return false;
        }

        /*
         * On n'autorise que la PROPRIÉTÉ ici, pas l'état : « soumis » et
         * « rejete » sont des transitions que le service tranche (la machine
         * à états vit dans `corriger()` / `resoumettre()`). Un rapport validé
         * passe donc ce contrôle puis est refusé par le service avec une
         * erreur 422 claire — au lieu d'un 403 muet qui n'explique rien.
         */
        return $rapport->enseignant?->user_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'reponses' => ['sometimes', 'array'],
            'reponses.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            ValideReponsesRapport::valider($validator, $this->input('reponses'));
        });
    }
}