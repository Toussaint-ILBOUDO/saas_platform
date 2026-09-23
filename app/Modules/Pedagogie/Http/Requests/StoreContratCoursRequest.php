<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContratCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function rules(): array
    {
        return [
            'eleve_id' => 'required|integer|exists:eleves,id',
            'type_cours_id' => 'required|integer|exists:type_cours,id',

            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',

            'autres_frais_suivi' => 'nullable|numeric|min:0',
            'notes_admin' => 'nullable|string',

            'affectations' => 'required|array|min:1',

            'affectations.*.enseignant_id' => 'required|integer|exists:enseignant_profils,id',
            'affectations.*.matiere_id' => 'required|integer|exists:matieres,id',
            'affectations.*.taux_horaire_enseignant' => 'required|numeric|min:0',
            'affectations.*.nombre_heures_prevues' => 'required|integer|min:1',
            'affectations.*.date_affectation' => 'nullable|date',
        ];
    }
}