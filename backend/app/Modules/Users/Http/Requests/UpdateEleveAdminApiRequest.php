<?php

namespace App\Modules\Users\Http\Requests;

use App\Models\Eleve;
use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Modification complète d'un élève par l'admin du cabinet (M3 scolarité).
 *
 * L'admin est le SEUL à pouvoir toucher aux liens (`parent_id`,
 * `classe_id`) : la fiche partagée (`PUT /auth/eleves/{eleve}`, modèle
 * `UpdateEleveApiRequest`) les exclut explicitement. Le contrôle que le
 * compte ciblé porte bien le rôle « parent » ne passe pas par un
 * `exists:users,id` (la table pivot de Spatie y est invisible) — il est
 * fait dans le contrôleur, à l'identique du `store`.
 */
class UpdateEleveAdminApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Roles::estAdmin($this->user());
    }

    public function rules(): array
    {
        // unique:users.email s'ignore sur le COMPTE de l'élève (users.id),
        // pas sur eleves.id : d'où la résolution défensive ci-dessous.
        $eleve = $this->route('eleve');
        $userId = $eleve instanceof Eleve
            ? $eleve->user_id
            : Eleve::find((int) $eleve)?->user_id;

        return [
            'parent_id' => ['required', 'integer', 'exists:users,id'],
            'classe_id' => ['required', 'integer', 'exists:classes,id'],

            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'telephone_whatsapp' => ['nullable', 'string', 'max:20'],
            'telephone_appel' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],

            // Fiche scolaire — mêmes règles que la fiche partagée.
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'ecole' => ['nullable', 'string', 'max:255'],
            'parent_charge' => ['nullable', 'string', 'max:255'],
            'etablissement_origine' => ['nullable', 'string', 'max:255'],
            'profession_pere' => ['nullable', 'string', 'max:255'],
            'profession_mere' => ['nullable', 'string', 'max:255'],
            'regime_etude' => ['nullable', 'string', 'in:interne,externe'],
            'loisirs_sport' => ['nullable', 'string', 'max:255'],
            'religion_enfant' => ['nullable', 'string', 'max:255'],
            'maladies_allergies' => ['nullable', 'string', 'max:2000'],
            'interdits_familiaux' => ['nullable', 'string', 'max:2000'],
            'boisson_preferee' => ['nullable', 'string', 'max:255'],
            'nourriture_preferee' => ['nullable', 'string', 'max:255'],
            'autres_precautions' => ['nullable', 'string', 'max:2000'],
            'autres_observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.required' => 'Choisissez le parent : un élève doit être rattaché à un parent du cabinet.',
            'parent_id.exists' => 'Ce compte parent est introuvable.',
            'classe_id.required' => 'Choisissez la classe de l\'élève.',
            'prenom.required' => 'Le prénom de l\'élève est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'date_naissance.date' => 'La date de naissance est invalide.',
            'date_naissance.before' => 'La date de naissance doit être dans le passé.',
            'regime_etude.in' => 'Le régime d\'études doit être « interne » ou « externe ».',
        ];
    }
}
