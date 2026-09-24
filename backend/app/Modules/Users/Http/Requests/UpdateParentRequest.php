<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParentRequest extends FormRequest
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

            'email' => ['nullable', 'email'],

            // IMPORTANT : password optionnel en update
            'password' => ['nullable', 'string', 'min:6'],

            'profession' => ['nullable', 'string'],
            'nombre_enfants' => ['nullable', 'integer'],
            'adresse_domicile' => ['nullable', 'string'],
        ];
    }
}