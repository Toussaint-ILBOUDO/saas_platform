<?php

namespace App\Modules\Users\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Modification d'un parent du cabinet par l'admin (M3 scolarité).
 *
 * L'identité du compte (`users`), le mot de passe éventuel et les trois
 * champs du profil `parent_profils` voyagent dans le même appel : c'est
 * un seul formulaire côté écran Scolarité.
 *
 * L'anti-doublon de numéro (`ParentService::refuserSiDejaConnu`) est appelé
 * côté contrôleur avec `$sauf = parent` : la validation ne peut pas nommer
 * deux fois la même règle (unique maison sur la table + rôle parent de
 * Spatie, invisible pour `unique:users`).
 */
class UpdateParentApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Roles::estAdmin($this->user());
    }

    public function rules(): array
    {
        // `SubstituteBindings` a remplacé le paramètre par le modèle, mais la
        // valeur brute reste acceptée : le cast couvre les deux cas.
        $parent = $this->route('parent');
        $parentId = $parent instanceof \App\Models\User
            ? $parent->id
            : (int) $parent;

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($parentId),
            ],
            'telephone_whatsapp' => ['nullable', 'string', 'max:20'],
            'telephone_appel' => ['nullable', 'string', 'max:20'],
            // Réinitialisation de mot de passe : facultative, jamais renvoyée.
            'password' => ['nullable', 'string', 'min:8'],

            // Profil (parent_profils).
            'adresse_domicile' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'nombre_enfants' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du parent est obligatoire.',
            'prenom.required' => 'Le prénom du parent est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'nombre_enfants.min' => 'Le nombre d\'enfants ne peut pas être négatif.',
        ];
    }
}
