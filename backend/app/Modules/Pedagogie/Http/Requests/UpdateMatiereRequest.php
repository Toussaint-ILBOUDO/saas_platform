<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Rôle canonique du cabinet (D-007) : les anciens rôles « admin » /
        // « super-admin » ont disparu du seed tenant.
        return auth()->user()->hasRole('admin_cabinet');
    }

    public function rules(): array
    {
        $matiere = $this->route('matiere');

        return [

            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('matieres', 'nom')
                    ->ignore($matiere->id)
            ],

            'sigle' => [
                'required',
                'string',
                'max:50',
                Rule::unique('matieres', 'sigle')
                    ->ignore($matiere->id)
            ],

            'description' => [
                'nullable',
                'string'
            ]

        ];
    }
}