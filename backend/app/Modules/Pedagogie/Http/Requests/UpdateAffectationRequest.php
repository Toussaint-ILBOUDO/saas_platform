<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAffectationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('contrat.update');
    }

    public function rules(): array
    {
        return [
            // La matière est renvoyée pour que le formulaire reste complet,
            // mais elle n'est pas modifiable : le service la rejette.
            'matiere_id' => ['nullable', 'integer', 'exists:matieres,id'],
            'taux_horaire_enseignant' => ['nullable', 'integer', 'min:0'],
            'nombre_heures_prevues' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'date_fin' => ['nullable', 'date'],
            'statut' => ['nullable', 'string', Rule::in(['actif', 'suspendu', 'termine'])],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.in' => 'Statut d\'affectation inconnu.',
            'nombre_heures_prevues.numeric' => 'Le nombre d\'heures prévues doit être un nombre.',
        ];
    }
}