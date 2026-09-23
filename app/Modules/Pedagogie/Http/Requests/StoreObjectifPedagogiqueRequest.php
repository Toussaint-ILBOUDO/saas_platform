<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObjectifPedagogiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'exists:eleves,id'],
            'periode' => ['required', 'string'],
            'moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'materiel_disponible' => ['nullable', 'string'],
            'materiel_manquant' => ['nullable', 'string'],

            'matieres' => ['required', 'array', 'min:1'],
            'matieres.*.matiere_id' => ['required', 'exists:matieres,id'],
            'matieres.*.moyenne_visee' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }
}
