<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContratCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La route est déjà derrière `role:admin_cabinet`, mais le FormRequest
        // ne doit pas dépendre du seul middleware : c'est lui qui est réutilisé
        // si la route bouge (D-053).
        return auth()->user()->can('contrat.create');
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'integer', 'exists:eleves,id'],
            'type_cours_id' => ['required', 'integer', 'exists:type_cours,id'],

            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],

            'autres_frais_suivi' => ['nullable', 'numeric', 'min:0'],
            'notes_admin' => ['nullable', 'string'],

            'affectations' => ['required', 'array', 'min:1'],

            'affectations.*.enseignant_id' => ['required', 'integer', 'exists:enseignant_profils,id'],
            'affectations.*.matiere_id' => ['required', 'integer', 'exists:matieres,id'],
            // Le taux est un entier : les montants du projet sont stockés en
            // entier (FCFA), et `FacturationService` fait `(int)` sur ce champ.
            'affectations.*.taux_horaire_enseignant' => ['required', 'integer', 'min:0'],
            // Colonne `decimal(5,2)` : les heures prévues peuvent être fractionnaires
            // (1,5 h par exemple). Une validation `integer` rejetait ces saisies
            // alors que la colonne les accepte.
            'affectations.*.nombre_heures_prevues' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'affectations.*.date_affectation' => ['nullable', 'date'],
        ];
    }

    /**
     * Le statut n'est pas saisissable : un contrat naît actif et se change par
     * l'action dédiée. `statut` reste toléré en entrée mais ignoré, sans quoi
     * l'API pourrait créer un contrat « terminé » sans affectation active.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['statut' => 'actif']);
    }

    public function messages(): array
    {
        return [
            'affectations.required' => 'Un contrat doit comporter au moins une affectation enseignant.',
            'affectations.*.nombre_heures_prevues.numeric' => 'Le nombre d\'heures prévues doit être un nombre.',
            'affectations.*.nombre_heures_prevues.max' => 'Le nombre d\'heures prévues ne peut pas dépasser 999,99.',
        ];
    }
}