<?php

namespace App\Modules\Finance\Http\Requests;

use App\Models\Facture;
use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.8 — Aperçu (preview) d'une facture : rien n'est écrit.
 *
 * Le client peut saisir les frais dès l'aperçu pour voir le total avant de
 * générer la facture — le service recalcule tout, il ne fait confiance à
 * rien d'autre que ces entiers.
 */
class PreviewFactureApiRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'contrat_cours_id.required' => 'Veuillez sélectionner un contrat.',
            'contrat_cours_id.exists' => 'Le contrat sélectionné n\'existe pas.',
            'periode_id.required' => 'Veuillez sélectionner une période.',
            'periode_id.exists' => 'La période sélectionnée n\'existe pas.',
        ];
    }
}