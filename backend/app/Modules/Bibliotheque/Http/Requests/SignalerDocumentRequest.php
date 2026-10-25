<?php

namespace App\Modules\Bibliotheque\Http\Requests;

use App\Models\DocumentSignalement;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Signalement public (connecté ou anonyme) d'un document de la
 * bibliothèque. Motifs codifiés + description + précisions.
 */
class SignalerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'in:'.implode(',', array_keys(DocumentSignalement::MOTIFS))],
            'description' => ['nullable', 'string', 'max:2000'],
            'precisions' => ['nullable', 'string', 'max:2000'],
            'email_contact' => ['nullable', 'email', 'max:190'],
        ];
    }

    public function messages(): array
    {
        $motifs = collect(DocumentSignalement::MOTIFS)
            ->map(fn ($libelle, $valeur) => "{$libelle} ({$valeur})")
            ->implode(', ');

        return [
            'motif.required' => 'Le motif du signalement est obligatoire.',
            'motif.in' => "Motif invalide. Choisissez parmi : {$motifs}.",
            'description.max' => 'La description ne doit pas dépasser 2000 caractères.',
            'precisions.max' => 'Les précisions ne doivent pas dépasser 2000 caractères.',
            'email_contact.email' => 'L\'adresse e-mail de contact n\'est pas valide.',
        ];
    }
}
