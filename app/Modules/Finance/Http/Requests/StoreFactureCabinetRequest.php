<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFactureCabinetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_debut'         => 'required|date',
            'date_fin'           => 'required|date|after_or_equal:date_debut',
            'taux_cours'         => 'nullable|integer|min:0',
            'taux_inscription'   => 'nullable|integer|min:0',
            'taux_vente'         => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'date_debut.required'         => 'La date de début est obligatoire.',
            'date_debut.date'             => 'La date de début n\'est pas valide.',
            'date_fin.required'           => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal'     => 'La date de fin doit être égale ou postérieure à la date de début.',
            'taux_cours.integer'          => 'Le taux par cours doit être un nombre entier.',
            'taux_cours.min'              => 'Le taux par cours ne peut pas être négatif.',
            'taux_inscription.integer'    => 'Le taux par inscription doit être un nombre entier.',
            'taux_inscription.min'        => 'Le taux par inscription ne peut pas être négatif.',
            'taux_vente.numeric'          => 'Le taux de commission sur ventes doit être un nombre.',
            'taux_vente.min'              => 'Le taux de commission sur ventes ne peut pas être négatif.',
        ];
    }
}
