<?php

namespace App\Modules\Users\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Création directe d'un parent (sans demande de cours préalable).
 *
 * Même contrat que `StoreDemandeCoursParentRequest` — le mot de passe est
 * exigé et définitif (min:8) car il ouvre un espace parent porteur de
 * factures. La détection d'un parent déjà connu de ce numéro vit dans
 * `ParentService::refuserSiDejaConnu`, appelée par le contrôleur : les
 * numéros effectifs y sont confrontés aux comptes existants.
 */
class StoreParentApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Roles::estAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],

            'telephone_whatsapp' => ['nullable', 'string', 'max:20'],
            'telephone_appel' => ['nullable', 'string', 'max:20'],

            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],

            'profession' => ['nullable', 'string', 'max:255'],
            'adresse_domicile' => ['nullable', 'string', 'max:255'],
            'nombre_enfants' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du parent est obligatoire.',
            'prenom.required' => 'Le prénom du parent est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
