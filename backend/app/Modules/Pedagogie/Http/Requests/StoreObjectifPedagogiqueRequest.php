<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObjectifPedagogiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'exists:eleves,id'],

            // D-050 : une PÉRIODE COMPTABLE, plus une chaîne libre.
            // L'unicité (élève, période, enseignant) est vérifiée par
            // `ObjectifPedagogiqueService::refuserDoublon()` : elle dépend de
            // l'enseignant, que le formulaire ne connaît pas toujours.
            'periode_id' => ['required', 'exists:periode_comptables,id'],

            // Optionnel côté admin ; déduit de l'utilisateur connecté ou de
            // l'affectation active de l'élève côté enseignant.
            'enseignant_id' => ['nullable', 'exists:enseignant_profils,id'],
            'moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'materiel_disponible' => ['nullable', 'string'],
            'materiel_manquant' => ['nullable', 'string'],

            'matieres' => ['required', 'array', 'min:1'],
            'matieres.*.matiere_id' => ['required', 'exists:matieres,id'],
            'matieres.*.moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }
}
