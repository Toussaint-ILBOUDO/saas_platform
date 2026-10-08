<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClasseRequest extends FormRequest
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
                'max:255'
            ],

            'sigle' => [
                'required',
                'string',
                'max:50',
                'unique:classes,sigle'
            ]
        ];
    }
}