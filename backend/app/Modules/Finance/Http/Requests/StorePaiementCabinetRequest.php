<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaiementCabinetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant_paye'       => 'required|integer|min:1',
            'mode_paiement'      => 'required|string|in:especes,orange_money,moov_money,virement,cheque,autre',
            'reference_paiement' => 'nullable|string|max:100',
            'date_paiement'      => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'montant_paye.required'  => 'Le montant payé est obligatoire.',
            'montant_paye.integer'   => 'Le montant payé doit être un nombre entier.',
            'montant_paye.min'       => 'Le montant payé doit être supérieur à 0.',
            'mode_paiement.required' => 'Le mode de paiement est obligatoire.',
            'mode_paiement.in'       => 'Le mode de paiement sélectionné n\'est pas valide.',
            'date_paiement.required' => 'La date de paiement est obligatoire.',
            'date_paiement.date'     => 'La date de paiement n\'est pas valide.',
        ];
    }
}
