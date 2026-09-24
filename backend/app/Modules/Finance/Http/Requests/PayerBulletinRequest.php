<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayerBulletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_paiement'      => 'required|date',
            'mode_paiement'      => 'required|string|in:especes,orange_money,moov_money,virement,cheque',
            'reference_paiement' => 'nullable|string|max:255',
        ];
    }
}
