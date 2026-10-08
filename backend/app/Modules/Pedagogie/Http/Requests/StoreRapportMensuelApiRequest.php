<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Models\AffectationEnseignant;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Modules\Pedagogie\Support\ValideReponsesRapport;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * T7A.7 — Dépôt d'un rapport mensuel par l'enseignant.
 *
 * Le volume horaire n'est PAS saisissable : il vient du cahier de texte via
 * `RapportMensuelCalculator`. Les seuls champs libres sont les réponses aux
 * éléments du modèle de rapport (`reponses`), indexées par `rapport_elements.id`
 * — les informations générales et le bilan des activités sont générés
 * automatiquement. C'est ce qui rend la facture et le bulletin justes : ils
 * repartent des heures réellement constatées, pas d'un chiffre saisi.
 *
 * Le contrat et la période sont validés ici pour l'ergonomie (422 lisible),
 * mais le service revérifie l'unicité, l'appartenance du contrat à
 * l'enseignant et la période ouverte : la policy et le garde-fou serveur
 * restent la référence, quel que soit le point d'entrée.
 */
class StoreRapportMensuelApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RapportMensuelEnseignant::class) ?? false;
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

            // Réponses au modèle de rapport : indexées par élément, textuelles.
            // L'intégrité des clés et les éléments obligatoires sont vérifiés
            // dans `withValidator` (nécessite une lecture du modèle).
            'reponses' => ['sometimes', 'array'],
            'reponses.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Le contrat doit appartenir à l'enseignant connecté et être actif.
     *
     * Sans ce contrôle, un enseignant pourrait déposer un rapport sur le
     * contrat d'un collègue : le calculator ramènerait alors zéro affectation
     * à son nom, et le rapport serait un rapport vide.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            ValideReponsesRapport::valider($validator, $this->input('reponses'));

            $user = $this->user();
            $contratId = $this->input('contrat_cours_id');
            $periodeId = $this->input('periode_id');

            if (! $user || ! $contratId) {
                return;
            }

            $profil = $user->enseignantProfil;

            if (! $profil) {
                $validator->errors()->add(
                    'contrat_cours_id',
                    'Aucun profil enseignant n\'est associé à votre compte.'
                );

                return;
            }

            $contractualise = AffectationEnseignant::query()
                ->where('contrat_cours_id', $contratId)
                ->where('enseignant_id', $profil->id)
                ->exists();

            if (! $contractualise) {
                $validator->errors()->add(
                    'contrat_cours_id',
                    'Ce cours ne fait pas partie de vos affectations.'
                );
            }

            /*
             * Un rapport déjà déposé sur ce couple contrat/période serait
             * silencieusement écrasé : on le dit au client plutôt que de le
             * laisser découvrir une 409. La clé d'erreur est celle du service
             * (`rapport`), pour que le formulaire n'ait qu'une seule source de
             * vérité quelle que soit l'entrée par l'API ou par un job.
             */
            if ($periodeId && $contractualise) {
                $existe = RapportMensuelEnseignant::query()
                    ->where('contrat_cours_id', $contratId)
                    ->where('periode_id', $periodeId)
                    ->where('enseignant_id', $profil->id)
                    ->exists();

                if ($existe) {
                    $validator->errors()->add(
                        'rapport',
                        'Un rapport existe déjà pour cette période.'
                    );
                }
            }

            // D-051 — gel : une période close ne peut plus accueillir de dépôt.
            if ($periodeId) {
                $periode = PeriodeComptable::find($periodeId);

                if ($periode && $periode->statut !== PeriodeComptable::OUVERTE) {
                    $validator->errors()->add(
                        'periode_id',
                        'La période est clôturée : le dépôt du rapport est impossible.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'contrat_cours_id.required' => 'Sélectionnez le cours concerné par ce rapport.',
            'periode_id.required' => 'Sélectionnez la période du rapport.',
        ];
    }
}
