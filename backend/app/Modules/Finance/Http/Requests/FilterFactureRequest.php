<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.8 — Filtres de liste factures (parent et administration).
 *
 * Le statut `partiel` n'existe plus (CONCEPTION_FINANCE §2.3-10) : un filtre
 * qui l'exposerait serait un trou à inatteignable. La liste n'accepte que les
 * valeurs réellement écrites par le service (`en_attente`, `payee`).
 */
class FilterFactureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode_id' => ['nullable', 'integer', 'exists:periode_comptables,id'],
            'statut' => ['nullable', 'string', 'in:en_attente,payee'],
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