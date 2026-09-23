<?php

namespace App\Modules\Temoignages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemoignageSignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'motif' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif du signalement est obligatoire.',
            'motif.max' => 'Le motif ne doit pas dépasser 255 caractères.',
            'description.max' => 'La description ne doit pas dépasser 2000 caractères.',
        ];
    }
}
