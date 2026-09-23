<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParentRequest extends FormRequest
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
            'telephone_whatsapp' => ['nullable', 'string'],
            'telephone_appel' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'unique:users,email'],

            'adresse_domicile' => ['nullable', 'string'],
            'profession' => ['nullable', 'string'],
            'nombre_enfants' => ['nullable', 'integer'],
            'password' => 'required|string|min:6',
        ];
    }
}