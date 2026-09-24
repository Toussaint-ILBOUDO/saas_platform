<?php

namespace App\Modules\Temoignages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemoignageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('temoignage')) ?? false;
    }

    public function rules(): array
    {
        return [
            'contenu' => 'required|string|min:20|max:2000',
            'anonyme' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'contenu.required' => 'Le témoignage ne peut pas être vide.',
            'contenu.min' => 'Votre témoignage doit contenir au moins 20 caractères.',
            'contenu.max' => 'Votre témoignage ne doit pas dépasser 2000 caractères.',
            'anonyme.boolean' => 'Le choix d\'anonymat est invalide.',
        ];
    }
}
