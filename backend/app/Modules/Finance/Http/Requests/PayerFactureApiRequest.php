<?php

namespace App\Modules\Finance\Http\Requests;

use App\Models\Facture;
use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.8 — Enregistrement du règlement d'une facture.
 *
 * Mêmes règles que `MarquerPayeRequest` (web), bornées par la ability
 * `payer` : un parent ne peut jamais enregistrer le paiement de sa propre
 * facture — c'est un acte administratif.
 */
class PayerFactureApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payer', $this->route('facture'));
    }

    public function rules(): array
    {
        return [
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', 'string', 'in:especes,orange_money,moov_money,virement,cheque,autre'],
            'reference_paiement' => ['nullable', 'string', 'max:100'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_paiement.required' => 'La date de paiement est obligatoire.',
            'mode_paiement.required' => 'Le mode de paiement est obligatoire.',
            'mode_paiement.in' => 'Le mode de paiement sélectionné n\'est pas valide.',
        ];
    }
}