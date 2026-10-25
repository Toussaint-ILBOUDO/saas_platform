<?php

namespace App\Modules\Users\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Activation du compte élève (M3 scolarité).
 *
 * Aucun champ obligatoire : la réactivation d'un compte suspendu ne change
 * ni l'email ni le mot de passe. Le mot de passe n'est proposé que pour
 * remettre un enfant qui l'a oublié — il reste optionnel et n'est jamais
 * renvoyé dans la réponse.
 */
class ActiverEleveApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Roles::estAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
