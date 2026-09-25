<?php

namespace App\Modules\Systeme\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUtilisateurApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('utilisateur');

        return [
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
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
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'roles.*.exists' => 'Un ou plusieurs rôles sélectionnés n\'existent pas.',
        ];
    }
}