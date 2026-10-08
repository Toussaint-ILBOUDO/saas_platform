<?php

namespace App\Modules\Finance\Http\Requests;

use App\Models\Facture;
use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.8 — Génération d'une facture (API admin).
 *
 * Mêmes règles que `GenerateFactureRequest` (web), bornées par la policy : le
 * rôle `admin_cabinet` est déjà imposé par le groupe de routes, mais la
 * permission fine `facture.create` reste vérifiée ici.
 */
class GenererFactureApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Facture::class);
    }

    public function rules(): array
    {
        return [
            'contrat_cours_id' => ['required', 'exists:contrat_cours,id'],
            'periode_id' => ['required', 'exists:periode_comptables,id'],
            'frais_suivi' => ['nullable', 'integer', 'min:0'],
            'autres_frais' => ['nullable', 'integer', 'min:0'],
            'remise' => ['nullable', 'integer', 'min:0'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
            'date_limite_paiement' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'contrat_cours_id.required' => 'Veuillez sélectionner un contrat.',
            'contrat_cours_id.exists' => 'Le contrat sélectionné n\'existe pas.',
            'periode_id.required' => 'Veuillez sélectionner une période.',
            'periode_id.exists' => 'La période sélectionnée n\'existe pas.',
            'date_limite_paiement.after' => 'La date limite doit être future.',
        ];
    }
}