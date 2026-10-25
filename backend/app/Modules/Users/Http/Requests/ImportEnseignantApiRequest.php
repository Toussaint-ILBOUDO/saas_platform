<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Import CSV d'enseignants (M1).
 *
 * `password_defaut` s'applique à toutes les lignes qui n'ont pas leur propre
 * colonne `password` : il n'apparaît jamais dans la réponse.
 */
class ImportEnseignantApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fichier' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'password_defaut' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'fichier.required' => 'Un fichier CSV est obligatoire.',
            'fichier.mimes' => 'Le fichier doit être un CSV (ou un .txt).',
            'fichier.max' => 'Le fichier ne doit pas dépasser 2 Mo.',
            'password_defaut.required' => 'Le mot de passe par défaut est obligatoire.',
            'password_defaut.min' => 'Le mot de passe par défaut doit faire au moins 6 caractères.',
        ];
    }
}
