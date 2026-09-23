<?php

namespace App\Modules\Bibliotheque\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'type_document_id' => 'nullable|exists:type_documents,id',
            'classe_id' => 'nullable|exists:classes,id',
            'matiere_id' => 'nullable|exists:matieres,id',
            'periode_id' => 'nullable|exists:periode_documents,id',
            'tag' => 'nullable|string|max:50',
            'statut' => 'nullable|in:brouillon,en_attente,publie,refuse,archive',
            'is_public' => 'nullable|boolean',
            'sort' => 'nullable|in:recents,populaires,telecharges,notes',
        ];
    }
}
