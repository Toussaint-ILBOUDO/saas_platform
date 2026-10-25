<?php

namespace App\Modules\Auth\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Fiche élève éditable par l'élève lui-même, son parent, un enseignant
 * affecté à son cours ou l'administration du cabinet — le contrôle exact
 * est appliqué dans le contrôleur (il dépend de l'élève ciblé).
 *
 * Les liens (`user_id`, `parent_id`, `classe_id`) ne sont pas modifiables
 * ici : ils structurent l'inscription et restent du ressort de l'admin.
 */
class UpdateEleveApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    public function rules(): array
    {
        return [
            'ecole' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
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
            'date_naissance.date' => 'La date de naissance est invalide.',
            'date_naissance.before' => 'La date de naissance doit être dans le passé.',
            'regime_etude.in' => 'Le régime d\'études doit être « interne » ou « externe ».',
        ];
    }
}
