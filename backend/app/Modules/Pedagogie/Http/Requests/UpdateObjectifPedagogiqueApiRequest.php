<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Modification d'un objectif via l'API (T7A.6).
 *
 * Les champs sont tous optionnels : l'enseignant corrige ce qu'il a saisi,
 * l'admin ajoute son commentaire — un formulaire partiel ne doit jamais
 * effacer ce qu'il n'a pas touché. La ventilation `matieres` est ignorée si
 * absente, ce qui préserve les moyennes obtenues (D-050).
 */
class UpdateObjectifPedagogiqueApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode_id' => ['sometimes', 'required', 'integer', 'exists:periode_comptables,id'],
            'moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'moyenne_obtenue' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'materiel_disponible' => ['nullable', 'string'],
            'materiel_manquant' => ['nullable', 'string'],
            'commentaire_admin' => ['nullable', 'string'],

            'matieres' => ['sometimes', 'array', 'min:1'],
            'matieres.*.matiere_id' => ['required', 'integer', 'exists:matieres,id'],
            'matieres.*.moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'matieres.*.moyenne_obtenue' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'matieres.*.commentaire' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'periode_id.exists' => 'La période sélectionnée est invalide.',
            'moyenne_visee.min' => 'La moyenne visée doit être comprise entre 0 et 20.',
            'moyenne_visee.max' => 'La moyenne visée doit être comprise entre 0 et 20.',
            'moyenne_obtenue.min' => 'La moyenne obtenue doit être comprise entre 0 et 20.',
            'moyenne_obtenue.max' => 'La moyenne obtenue doit être comprise entre 0 et 20.',
            'matieres.min' => 'L\'objectif doit couvrir au moins une matière.',
            'matieres.*.matiere_id.required' => 'Chaque ligne doit préciser une matière.',
            'matieres.*.matiere_id.exists' => 'Une matière sélectionnée est invalide.',
            'matieres.*.moyenne_visee.min' => 'La moyenne visée doit être comprise entre 0 et 20.',
            'matieres.*.moyenne_visee.max' => 'La moyenne visée doit être comprise entre 0 et 20.',
            'matieres.*.moyenne_obtenue.min' => 'La moyenne obtenue doit être comprise entre 0 et 20.',
            'matieres.*.moyenne_obtenue.max' => 'La moyenne obtenue doit être comprise entre 0 et 20.',
        ];
    }
}
