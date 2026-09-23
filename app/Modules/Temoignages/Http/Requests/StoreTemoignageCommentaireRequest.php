<?php

namespace App\Modules\Temoignages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemoignageCommentaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'contenu' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:temoignage_commentaires,id',
        ];
    }

    public function messages(): array
    {
        return [
            'contenu.required' => 'Le commentaire ne peut pas être vide.',
            'contenu.max' => 'Le commentaire ne doit pas dépasser 2000 caractères.',
            'parent_id.exists' => 'Le commentaire parent est invalide.',
        ];
    }
}
