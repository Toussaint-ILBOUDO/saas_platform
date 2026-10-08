<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.9 — Filtres de liste bulletins de paie (enseignant et administration).
 *
 * Le statut est un instantané de la machine à états du service : aucune autre
 * valeur ne peut être écrite, un filtre qui l'exposerait serait un menteur.
 */
class FilterBulletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode_id' => ['nullable', 'integer', 'exists:periode_comptables,id'],
            'statut' => ['nullable', 'string', 'in:genere,consulte,valide,conteste,corrige,verse'],
            'search' => ['nullable', 'string', 'max:254'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'par_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.in' => 'Le statut demandé n\'est pas reconnu.',
            'search.max' => 'La recherche est trop longue.',
            'per_page.between' => 'Le nombre d\'éléments par page est hors bornes.',
            'par_page.between' => 'Le nombre d\'éléments par page est hors bornes.',
        ];
    }
}