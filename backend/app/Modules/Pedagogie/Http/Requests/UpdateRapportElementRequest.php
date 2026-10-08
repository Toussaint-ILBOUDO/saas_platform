<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRapportElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['sometimes', 'required', 'string', 'max:200'],
            'type' => ['sometimes', Rule::in(['textarea', 'text'])],
            'obligatoire' => ['sometimes', 'boolean'],
            'aide' => ['nullable', 'string', 'max:255'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}