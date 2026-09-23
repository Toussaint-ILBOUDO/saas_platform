<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaiementEnseignantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enseignant_id' => [
                'required',
                'exists:enseignant_profils,id'
            ],

            'contrat_cours_id' => [
                'required',
                'exists:contrat_cours,id'
            ],

            'periode_id' => [
                'required',
                'exists:periode_comptables,id'
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];
    }
}