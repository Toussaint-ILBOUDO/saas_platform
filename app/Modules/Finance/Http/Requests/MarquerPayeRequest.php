<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarquerPayeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_paiement' => [
                'required',
                'date',
            ],

            'mode_paiement' => [
                'required',
                'string',
                'in:especes,orange_money,moov_money,virement,cheque,autre',
            ],

            'reference_paiement' => [
                'nullable',
                'string',
                'max:100',
            ],

            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date_paiement.required' =>
                'La date de paiement est obligatoire.',
            'mode_paiement.required' =>
                'Le mode de paiement est obligatoire.',
            'mode_paiement.in' =>
                'Le mode de paiement sélectionné n\'est pas valide.',
        ];
    }
}
