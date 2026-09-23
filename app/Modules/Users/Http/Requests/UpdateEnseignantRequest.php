<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnseignantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('enseignant');

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone_whatsapp' => ['nullable', 'string'],
            'telephone_appel' => ['nullable', 'string'],
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:6'],

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
