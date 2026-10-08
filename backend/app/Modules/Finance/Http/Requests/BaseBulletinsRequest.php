<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * T7A.9 — Prérequis partagés par l'aperçu (preview) et la génération.
 *
 * La période doit exister ; `frais_suivi` et `ajustements` sont des tableaux
 * indexés par enseignant (`frais_suivi[enseignant_id]` = montant) puis, pour
 * les ajustements, par type (`ajustements[enseignant_id][type_id]` = montant).
 * Les règles métier (période ouverte, unicité, lignes non vides) restent dans
 * le service — ici on ne vérifie que la forme du transport.
 */
abstract class BaseBulletinsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'periode_id' => ['required', 'exists:periode_comptables,id'],
            'frais_suivi' => ['nullable', 'array'],
            'frais_suivi.*' => ['nullable', 'integer', 'min:0'],
            'ajustements' => ['nullable', 'array'],
            'ajustements.*' => ['nullable', 'array'],
            'ajustements.*.*' => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'periode_id.required' => 'Veuillez sélectionner une période.',
            'periode_id.exists' => 'La période sélectionnée n\'existe pas.',
            'frais_suivi.*.integer' => 'Les frais de suivi doivent être des montants.',
            'ajustements.*.*.integer' => 'Les ajustements doivent être des montants.',
        ];
    }
}