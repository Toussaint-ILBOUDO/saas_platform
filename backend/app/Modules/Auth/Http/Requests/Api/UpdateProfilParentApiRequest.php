<?php

namespace App\Modules\Auth\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/** Profil parent éditable par le compte parent lui-même. */
class UpdateProfilParentApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user !== null && $user->hasRole('parent');
    }

    public function rules(): array
    {
        return [
            'adresse_domicile' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'nombre_enfants' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_enfants.integer' => 'Le nombre d\'enfants doit être un nombre.',
            'nombre_enfants.min' => 'Le nombre d\'enfants ne peut pas être négatif.',
        ];
    }
}
