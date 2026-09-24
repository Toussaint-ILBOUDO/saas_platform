<?php

namespace App\Modules\Bibliotheque\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'resume' => 'nullable|string|max:5000',
            'type_document_id' => 'required|exists:type_documents,id',
            'classe_id' => 'nullable|exists:classes,id',
            'matiere_id' => 'nullable|exists:matieres,id',
            'periode_id' => 'nullable|exists:periode_documents,id',
            'is_public' => 'sometimes|boolean',
            'statut' => 'sometimes|in:brouillon,en_attente',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'fichier' => 'required|file|max:51200|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,txt,zip',
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre est obligatoire.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'type_document_id.required' => 'Le type de document est obligatoire.',
            'type_document_id.exists' => 'Le type de document sélectionné est invalide.',
            'classe_id.exists' => 'La classe sélectionnée est invalide.',
            'matiere_id.exists' => 'La matière sélectionnée est invalide.',
            'fichier.required' => 'Le fichier est obligatoire.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 50 Mo.',
            'fichier.mimes' => 'Le format du fichier n\'est pas supporté.',
        ];
    }
}
