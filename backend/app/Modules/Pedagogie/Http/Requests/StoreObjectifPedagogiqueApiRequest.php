<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Création d'un objectif via l'API (T7A.6).
 *
 * Le triplet d'identité (élève, période comptable, enseignant) est validé par
 * le service : l'unicité dépend de l'enseignant, résolu depuis l'utilisateur
 * connecté, et la période doit être ouverte (D-051), ce qu'aucune règle de
 * formulaire ne peut trancher seul.
 */
class StoreObjectifPedagogiqueApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('enseignant') === true
            && $this->user()?->enseignantProfil !== null;
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'integer', 'exists:eleves,id'],
            'periode_id' => ['required', 'integer', 'exists:periode_comptables,id'],
            'moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'materiel_disponible' => ['nullable', 'string'],
            'materiel_manquant' => ['nullable', 'string'],

            'matieres' => ['required', 'array', 'min:1'],
            'matieres.*.matiere_id' => ['required', 'integer', 'exists:matieres,id'],
            'matieres.*.moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'eleve_id.required' => 'Veuillez choisir l\'élève concerné.',
            'eleve_id.exists' => 'L\'élève sélectionné est invalide.',
            'periode_id.required' => 'La période comptable est obligatoire.',
            'periode_id.exists' => 'La période sélectionnée est invalide.',
            'moyenne_visee.min' => 'La moyenne visée doit être comprise entre 0 et 20.',
            'moyenne_visee.max' => 'La moyenne visée doit être comprise entre 0 et 20.',
            'matieres.required' => 'Ajoutez au moins une matière.',
            'matieres.min' => 'Ajoutez au moins une matière.',
            'matieres.*.matiere_id.required' => 'Chaque ligne doit préciser une matière.',
            'matieres.*.matiere_id.exists' => 'Une matière sélectionnée est invalide.',
            'matieres.*.moyenne_visee.min' => 'La moyenne visée doit être comprise entre 0 et 20.',
            'matieres.*.moyenne_visee.max' => 'La moyenne visée doit être comprise entre 0 et 20.',
        ];
    }
}
