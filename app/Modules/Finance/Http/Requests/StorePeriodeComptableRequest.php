<?php


namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePeriodeComptableRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'type' => ['required', 'string', 'in:mensuel,trimestriel,annuel'],
            'statut' => ['nullable', 'string', 'in:ouverte,cloturee'],
        ];
    }
}