<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRapportSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}