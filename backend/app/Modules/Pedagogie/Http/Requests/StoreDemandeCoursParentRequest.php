<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Création du parent depuis une demande de cours.
 *
 * Le contrat de données reprend celui de `StoreParentRequest` (nom, prénom,
 * téléphones, email unique, mot de passe) avec une différence consciente :
 * `min:8` sur le mot de passe au lieu de 6, car celui saisi ici est définitif —
 * il ouvre un espace parent qui porte ses factures.
 *
 * La détection d'un parent déjà connu de ce numéro n'est **pas** ici mais dans
 * `DemandeCoursAdminService::creerParent` : les numéros effectifs sont résolus
 * après repli sur ceux de la demande, donc la validation en ignorerait la
 * plupart des cas.
 */
class StoreDemandeCoursParentRequest extends FormRequest
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

            // Repli sur le numéro de la demande : c'est le numéro que le
            // parent a lui-même donné en appelant.
            'telephone_whatsapp' => ['nullable', 'string', 'max:20'],
            'telephone_appel' => ['nullable', 'string', 'max:20'],

            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            // Définitif dès la création (le parent ouvre un espace qui porte ses
            // factures) : 8 caractères minimum, pas 6.
            'password' => ['required', 'string', 'min:8'],

            'profession' => ['nullable', 'string', 'max:255'],
            'adresse_domicile' => ['nullable', 'string', 'max:255'],
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