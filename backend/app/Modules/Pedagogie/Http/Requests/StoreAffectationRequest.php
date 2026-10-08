<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAffectationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('contrat.update');
    }

    public function rules(): array
    {
        return [
            'enseignant_id' => ['required', 'integer', 'exists:enseignant_profils,id'],
            'matiere_id' => ['required', 'integer', 'exists:matieres,id'],
            'taux_horaire_enseignant' => ['required', 'integer', 'min:0'],
            'nombre_heures_prevues' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'date_affectation' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_heures_prevues.numeric' => 'Le nombre d\'heures prévues doit être un nombre.',
            'nombre_heures_prevues.max' => 'Le nombre d\'heures prévues ne peut pas dépasser 999,99.',
        ];
    }
}