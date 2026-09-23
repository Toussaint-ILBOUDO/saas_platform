<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEleveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string'],
            'prenom' => ['required', 'string'],

            'email' => ['nullable', 'email', 'unique:users,email'],
            'telephone_whatsapp' => ['nullable', 'string'],
            'telephone_appel' => ['nullable', 'string'],

            'parent_id' => ['required', 'exists:users,id'],
            'classe_id' => ['required', 'exists:classes,id'],

            'ecole' => ['nullable', 'string'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string'],
            'parent_charge' => ['nullable', 'string'],
            'etablissement_origine' => ['nullable', 'string'],
            'profession_pere' => ['nullable', 'string'],
            'profession_mere' => ['nullable', 'string'],
            'regime_etude' => ['nullable', 'string'],
            'loisirs_sport' => ['nullable', 'string'],
            'religion_enfant' => ['nullable', 'string'],
            'maladies_allergies' => ['nullable', 'string'],
            'interdits_familiaux' => ['nullable', 'string'],
            'boisson_preferee' => ['nullable', 'string'],
            'nourriture_preferee' => ['nullable', 'string'],
            'autres_precautions' => ['nullable', 'string'],
            'autres_observations' => ['nullable', 'string'],
        ];
    }
}
