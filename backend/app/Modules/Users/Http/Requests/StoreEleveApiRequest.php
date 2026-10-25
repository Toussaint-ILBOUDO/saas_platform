<?php

namespace App\Modules\Users\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Création directe d'un élève (sans demande de cours préalable).
 *
 * Contrairement à `StoreDemandeCoursEleveRequest` (où le parent vient de la
 * demande), ici `parent_id` est **obligatoire** : le formulaire exige la
 * sélection d'un parent du cabinet. `classe_id` est également obligatoire —
 * `EleveService::create` en a besoin et, hors demande, aucun repli n'existe.
 *
 * Le contrôle du rôle « parent » du compte choisi est fait côté service /
 * contrôleur : un `exists:users,id` ne peut pas vérifier la table pivot de
 * Spatie.
 */
class StoreEleveApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Roles::estAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['required', 'integer', 'exists:users,id'],
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['nullable', 'string', 'max:255'],

            'classe_id' => ['required', 'integer', 'exists:classes,id'],

            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'ecole' => ['nullable', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'telephone_whatsapp' => ['nullable', 'string', 'max:20'],
            'telephone_appel' => ['nullable', 'string', 'max:20'],

            // Facultatif : `EleveService` génère un mot de passe provisoire et
            // le compte reste désactivé jusqu'à activation.
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.required' => 'Choisissez le parent : un élève doit être rattaché à un parent du cabinet.',
            'parent_id.exists' => 'Ce compte parent est introuvable.',
            'prenom.required' => 'Le prénom de l\'élève est obligatoire.',
            'classe_id.required' => 'Choisissez la classe de l\'élève.',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
