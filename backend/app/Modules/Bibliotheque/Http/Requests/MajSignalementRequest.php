<?php

namespace App\Modules\Bibliotheque\Http\Requests;

use App\Models\DocumentSignalement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mise à jour d'un signalement par l'administrateur (statut + note).
 */
class MajSignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['sometimes', 'string', Rule::in(array_keys(DocumentSignalement::STATUTS))],
            'note_admin' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.in' => 'Statut de signalement invalide.',
            'note_admin.max' => 'La note ne doit pas dépasser 4000 caractères.',
        ];
    }
}
