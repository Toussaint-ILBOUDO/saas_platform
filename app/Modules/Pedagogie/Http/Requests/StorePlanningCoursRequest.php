<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Modules\Pedagogie\Enums\PlanningJourSemaine;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanningCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->hasRole('enseignant')
            && auth()->user()->enseignantProfil !== null;
    }

    public function rules(): array
    {
        return [
            'affectation_enseignant_id' => [
                'required',
                'integer',
                'exists:affectation_enseignants,id',
            ],

            'jour_semaine' => [
                'required',
                'integer',
                'in:'.implode(',', PlanningJourSemaine::VALIDES),
            ],

            'heure_debut' => [
                'required',
                'date_format:H:i',
            ],

            'heure_fin' => [
                'required',
                'date_format:H:i',
                'after:heure_debut',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'affectation_enseignant_id.required' => 'Veuillez choisir le cours concerné.',
            'affectation_enseignant_id.exists' => 'Le cours sélectionné est invalide.',
            'jour_semaine.required' => 'Veuillez choisir le jour de la semaine.',
            'jour_semaine.in' => 'Le jour sélectionné est invalide.',
            'heure_debut.required' => 'L\'heure de début est obligatoire.',
            'heure_debut.date_format' => 'L\'heure de début doit être au format HH:MM.',
            'heure_fin.required' => 'L\'heure de fin est obligatoire.',
            'heure_fin.date_format' => 'L\'heure de fin doit être au format HH:MM.',
            'heure_fin.after' => 'L\'heure de fin doit être après l\'heure de début.',
        ];
    }
}