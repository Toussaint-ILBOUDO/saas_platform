<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Rôle canonique du cabinet (D-007) : les anciens rôles « admin » /
        // « super-admin » ont disparu du seed tenant.
        return auth()->user()->hasRole('admin_cabinet');
    }

    public function rules(): array
    {
        return [

            'nom' => [
                'required',
                'string',
                'max:255',
                'unique:matieres,nom'
            ],

            'sigle' => [
                'required',
                'string',
                'max:50',
                'unique:matieres,sigle'
            ],

            'description' => [
                'nullable',
                'string'
            ]

        ];
    }
}