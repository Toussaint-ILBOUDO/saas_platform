<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportCahierTextePdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'eleve_id' => [
                'required',
                'exists:eleves,id',
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

        ];
    }

    public function attributes(): array
    {
        return [

            'eleve_id' => 'élève',

            'date_debut' => 'date de début',

            'date_fin' => 'date de fin',

        ];
    }
}