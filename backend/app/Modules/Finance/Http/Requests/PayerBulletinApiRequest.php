<?php

namespace App\Modules\Finance\Http\Requests;

use App\Models\BulletinPaie;
use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.9 — Enregistrement du versement d'un bulletin (API admin).
 *
 * Le versement est un acte administratif, borné par la ability `payer`. La
 * machine à états exige que le bulletin soit `valide` (validé par
 * l'enseignant) avant tout paiement — le service la fait respecter.
 */
class PayerBulletinApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payer', $this->route('bulletin'));
    }

    public function rules(): array
    {
        return [
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', 'string', 'in:especes,orange_money,moov_money,virement,cheque,autre'],
            'reference_paiement' => ['nullable', 'string', 'max:255'],
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