<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Models\AffectationEnseignant;
use App\Support\Roles;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aperçu pédagogique du rapport avant dépôt : même couple contrat/période que
 * le dépôt, mêmes règles d'appartenance — mais lecture seule (aucune écriture).
 * Le serveur calcule les heures réalisées et le bilan depuis le cahier de
 * texte : l'enseignant voit ce qui sera figé avant de soumettre.
 */
class ApercuRapportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Roles::estEnseignant($this->user());
    }

    public function rules(): array
    {
        return [
            'contrat_cours_id' => [
                'required',
                'integer',
                Rule::exists('contrat_cours', 'id'),
            ],
            'periode_id' => [
                'required',
                'integer',
                Rule::exists('periode_comptables', 'id'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $profil = $this->user()?->enseignantProfil;
            $contratId = $this->input('contrat_cours_id');

            if (! $profil) {
                $validator->errors()->add(
                    'contrat_cours_id',
                    'Aucun profil enseignant n\'est associé à votre compte.'
                );

                return;
            }

            if (! $contratId) {
                return;
            }

            $affecte = AffectationEnseignant::query()
                ->where('contrat_cours_id', $contratId)
                ->where('enseignant_id', $profil->id)
                ->exists();

            if (! $affecte) {
                $validator->errors()->add(
                    'contrat_cours_id',
                    'Ce cours ne fait pas partie de vos affectations.'
                );
            }
        });
    }
}