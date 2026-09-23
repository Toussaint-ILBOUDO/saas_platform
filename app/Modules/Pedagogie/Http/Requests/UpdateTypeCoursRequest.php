<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeCours = $this->route('type_cour');

        return [
            'libelle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('type_cours', 'libelle')->ignore($typeCours),
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('type_cours', 'code')->ignore($typeCours),
            ],

            'description' => ['nullable', 'string'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}