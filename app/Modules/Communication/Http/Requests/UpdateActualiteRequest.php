<?php

namespace App\Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActualiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:actualites,slug,'.$this->route('actualite')->id,
            'resume' => 'nullable|string|max:1000',
            'contenu' => 'required|string',
            'video_url' => 'nullable|url|max:255',
            'lien_externe' => 'nullable|url|max:255',
            'image_principale' => 'nullable|image|mimes:jpeg,png,webp,gif|max:10240',
            'galerie' => 'nullable|array',
            'galerie.*' => 'image|mimes:jpeg,png,webp,gif|max:10240',
            'document' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx|max:10240',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre de l\'actualité est obligatoire.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'slug.unique' => 'Ce slug est déjà utilisé par une autre actualité.',
            'resume.max' => 'Le résumé ne doit pas dépasser 1000 caractères.',
            'contenu.required' => 'Le contenu de l\'actualité est obligatoire.',
            'video_url.url' => 'L\'URL de la vidéo n\'est pas valide.',
            'video_url.max' => 'L\'URL de la vidéo ne doit pas dépasser 255 caractères.',
            'lien_externe.url' => 'Le lien externe n\'est pas valide.',
            'lien_externe.max' => 'Le lien externe ne doit pas dépasser 255 caractères.',
            'image_principale.image' => 'L\'image principale doit être une image.',
            'image_principale.mimes' => 'L\'image principale doit être au format JPEG, PNG, WEBP ou GIF.',
            'image_principale.max' => 'L\'image principale ne doit pas dépasser 10 Mo.',
            'galerie.*.image' => 'Chaque image de la galerie doit être une image.',
            'galerie.*.mimes' => 'Les images de la galerie doivent être au format JPEG, PNG, WEBP ou GIF.',
            'galerie.*.max' => 'Chaque image de la galerie ne doit pas dépasser 10 Mo.',
            'document.file' => 'Le document doit être un fichier.',
            'document.mimes' => 'Le document doit être au format PDF, DOC, DOCX, PPT ou PPTX.',
            'document.max' => 'Le document ne doit pas dépasser 10 Mo.',
        ];
    }
}
