<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * T7A.7 — Filtres des listes de rapports mensuels.
 *
 * Les statuts sont bornés à la machine à états du modèle. Le contrôleur web
 * KEduc acceptait n'importe quelle valeur dans `?statut=` ; ici un filtre
 * inconnu renvoie 422 au lieu d'une liste vide attendue.
 */
class FilterRapportMensuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode_id' => ['nullable', 'integer', 'exists:periode_comptables,id'],
            'statut' => ['nullable', Rule::in(['soumis', 'valide', 'rejete'])],
            'contrat_cours_id' => ['nullable', 'integer', 'exists:contrat_cours,id'],
            'enseignant_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            // D-058 — taille de page réellement bornée.
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'par_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}