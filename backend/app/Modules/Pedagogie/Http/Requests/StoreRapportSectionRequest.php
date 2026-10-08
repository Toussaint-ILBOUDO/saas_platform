<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRapportSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route réservée au rôle `admin_cabinet` (garde en amont).
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le titre de la section est obligatoire.',
            'libelle.max' => 'Le titre de la section est limité à 150 caractères.',
        ];
    }
}