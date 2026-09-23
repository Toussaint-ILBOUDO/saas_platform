<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnseignantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone_whatsapp' => ['nullable', 'string'],
            'telephone_appel' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],

            'numero_orange_money' => ['nullable', 'string'],
            'diplome_max' => ['nullable', 'string'],
            'lieu_de_service' => ['nullable', 'string'],
            'domicile' => ['nullable', 'string'],
            'frais_annuel_regle' => ['nullable', 'boolean'],

            'matieres' => ['nullable', 'array'],
            'matieres.*' => ['exists:matieres,id'],
        ];
    }
}
