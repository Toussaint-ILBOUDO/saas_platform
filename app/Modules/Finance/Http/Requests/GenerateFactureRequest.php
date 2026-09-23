<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateFactureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contrat_cours_id' => [
                'required',
                'exists:contrat_cours,id',
            ],

            'periode_id' => [
                'required',
                'exists:periode_comptables,id',
            ],

            'frais_suivi' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'autres_frais' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'remise' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'date_limite_paiement' => [
                'nullable',
                'date',
                'after:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'contrat_cours_id.required' =>
                'Veuillez sélectionner un contrat.',
            'contrat_cours_id.exists' =>
                'Le contrat sélectionné n\'existe pas.',
            'periode_id.required' =>
                'Veuillez sélectionner une période.',
            'periode_id.exists' =>
                'La période sélectionnée n\'existe pas.',
            'date_limite_paiement.after' =>
                'La date limite doit être future.',
        ];
    }
}
