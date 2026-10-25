<?php

namespace App\Modules\Bibliotheque\Http\Requests;

use App\Models\DocumentSignalement;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Signalement d'un document depuis l'espace membre (connecté).
 * Les motifs sont codifiés — même référentiel que le signalement public.
 */
class StoreSignalementRequest extends FormRequest
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
        ];
    }
}
