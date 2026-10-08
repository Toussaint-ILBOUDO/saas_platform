<?php

namespace App\Modules\Finance\Http\Requests;

use App\Models\BulletinPaie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * T7A.9 — Contestation d'un bulletin (D-052).
 *
 * Motif structuré : une catégorie tirée de la liste fermée du modèle, plus un
 * détail libre d'au moins 20 caractères. Le service revalide les deux — l'API
 * et le web passent par le même garde.
 */
class ContesterBulletinApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bulletin'));
    }

    public function rules(): array
    {
        return [
            'motif_contestation' => [
                'required',
                Rule::in(array_keys(BulletinPaie::MOTIFS_CONTESTATION)),
            ],
            'commentaire_enseignant' => ['required', 'string', 'min:20', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif_contestation.required' =>
                'Indiquez ce que vous contestez sur ce bulletin.',
            'motif_contestation.in' =>
                'Motif de contestation inconnu.',
            'commentaire_enseignant.required' =>
                'Décrivez la contestation : l\'administration a besoin de savoir '
                . 'ce qui est contesté pour vous répondre.',
            'commentaire_enseignant.min' =>
                'Décrivez la contestation en 20 caractères au minimum : '
                . 'l\'administration doit comprendre ce qui est contesté.',
        ];
    }
}