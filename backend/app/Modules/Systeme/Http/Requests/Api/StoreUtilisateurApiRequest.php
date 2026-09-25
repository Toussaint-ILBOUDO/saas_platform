<?php

namespace App\Modules\Systeme\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUtilisateurApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'telephone_whatsapp' => 'nullable|string|max:20',
            'telephone_appel' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'statut' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => [
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'roles.*.exists' => 'Un ou plusieurs rôles sélectionnés n\'existent pas.',
        ];
    }
}