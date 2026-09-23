<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObjectifPedagogiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode' => ['sometimes', 'required', 'string'],
            'moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'moyenne_obtenue' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'materiel_disponible' => ['nullable', 'string'],
            'materiel_manquant' => ['nullable', 'string'],
            'commentaire_admin' => ['nullable', 'string'],

            'matieres' => ['sometimes', 'array', 'min:1'],
            'matieres.*.matiere_id' => ['required', 'exists:matieres,id'],
            'matieres.*.moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'matieres.*.moyenne_obtenue' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'matieres.*.commentaire' => ['nullable', 'string'],
        ];
    }
}
