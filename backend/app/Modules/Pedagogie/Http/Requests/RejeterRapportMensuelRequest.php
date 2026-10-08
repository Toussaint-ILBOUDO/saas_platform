<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Models\RapportMensuelEnseignant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.7 — Rejet motivé d'un rapport mensuel par l'administration.
 *
 * KEduc rejetait sans motif : l'enseignant ne savait pas quoi corriger. Le
 * motif est obligatoire et borné — il sera notifié à l'enseignant
 * (`reportRejected`) et affiché sur l'écran de re-soumission.
 */
class RejeterRapportMensuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && $user->can('rejeter', $this->route('rapport'))
            && $this->route('rapport') instanceof RapportMensuelEnseignant;
    }

    public function rules(): array
    {
        return [
            'motif_rejet' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rapport = $this->route('rapport');

            if ($rapport && ! $rapport->estSoumis()) {
                $validator->errors()->add(
                    'statut',
                    'Seul un rapport soumis peut être rejeté.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'motif_rejet.required' => 'Le motif du rejet est obligatoire.',
            'motif_rejet.min' => 'Précisez le motif en 10 caractères au minimum.',
            'motif_rejet.max' => 'Le motif ne peut pas dépasser 500 caractères.',
        ];
    }
}