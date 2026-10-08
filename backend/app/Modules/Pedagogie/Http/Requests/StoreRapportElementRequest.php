<?php

namespace App\Modules\Pedagogie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRapportElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', Rule::exists('rapport_sections', 'id')],
            'libelle' => ['required', 'string', 'max:200'],
            'type' => ['sometimes', Rule::in(['textarea', 'text'])],
            'obligatoire' => ['sometimes', 'boolean'],
            'aide' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'section_id.required' => 'La section d\'accueil de l\'élément est obligatoire.',
            'libelle.required' => 'Le libellé de la question est obligatoire.',
            'libelle.max' => 'Le libellé de la question est limité à 200 caractères.',
        ];
    }
}