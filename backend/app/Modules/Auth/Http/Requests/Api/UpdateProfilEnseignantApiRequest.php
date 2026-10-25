<?php

namespace App\Modules\Auth\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Profil enseignant éditable par le compte enseignant lui-même.
 * `frais_annuel_regle` reste réservé à l'administration (suivi de gain).
 */
class UpdateProfilEnseignantApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user !== null && $user->hasRole('enseignant');
    }

    public function rules(): array
    {
        return [
            'numero_orange_money' => ['nullable', 'string', 'max:40'],
            'diplome_max' => ['nullable', 'string', 'max:255'],
            'lieu_de_service' => ['nullable', 'string', 'max:255'],
            'domicile' => ['nullable', 'string', 'max:255'],
            'matieres' => ['nullable', 'array'],
            'matieres.*' => ['integer', 'exists:matieres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'matieres.array' => 'Les matières doivent être une liste.',
            'matieres.*.exists' => 'Une des matières sélectionnées est inconnue.',
        ];
    }
}
