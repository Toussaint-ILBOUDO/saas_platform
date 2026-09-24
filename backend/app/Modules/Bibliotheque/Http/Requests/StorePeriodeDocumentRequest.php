<?php

namespace App\Modules\Bibliotheque\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePeriodeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:25',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la période est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'sigle.max' => 'Le sigle ne doit pas dépasser 25 caractères.',
        ];
    }
}
