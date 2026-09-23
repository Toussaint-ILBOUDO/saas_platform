<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeriodeComptableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole([
            'admin',
            'super-admin'
        ]);
    }

    public function rules(): array
    {
        return [
            'label' => [
                'required',
                Rule::unique('periode_comptables', 'label')
                    ->ignore($this->periode->id),
            ],

            'date_debut' => [
                'required',
                'date'
            ],

            'date_fin' => [
                'required',
                'date',
                'after_or_equal:date_debut'
            ],

            'type' => [
                'required',
                'string',
                'in:mensuel,trimestriel,annuel'
            ],

            'statut' => [
                'required',
                'string',
                'in:ouverte,cloturee'
            ],
        ];
    }
}