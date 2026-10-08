<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeriodeComptableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('admin_cabinet');
    }

    public function rules(): array
    {
        return [
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique('periode_comptables', 'label')
                    ->ignore($this->route('periode')),
            ],

            'date_debut' => [
                'required',
                'date',
            ],

            'date_fin' => [
                'required',
                'date',
                'after_or_equal:date_debut',
            ],

            'type' => [
                'required',
                'string',
                'in:mensuel,trimestriel,annuel',
            ],
            // Le statut est géré uniquement par close/reopen, jamais par update.
        ];
    }
}