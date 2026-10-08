<?php

namespace App\Modules\Pedagogie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T7A.7 — Rapport mensuel enseignant.
 *
 * Le frontend doit pouvoir piloter ses actions à partir du statut sans
 * redéduire les règles : `actions` porte la liste des transitions autorisées
 * pour l'utilisateur connecté. C'est la seule façon d'éviter que l'écran
 * propose « Valider » à un enseignant ou « Re-soumettre » à un admin.
 *
 * Les `actions` sont dérivées de `RapportMensuelEnseignantPolicy` et des
 * statuts du modèle — jamais d'une liste recopiée dans le front.
 */
class RapportMensuelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contrat_cours_id' => (int) $this->contrat_cours_id,
            'periode_id' => (int) $this->periode_id,
            'periode' => $this->whenLoaded('periode', fn () => [
                'id' => $this->periode->id,
                'label' => $this->periode->label,
                'date_debut' => $this->periode->date_debut?->toDateString(),
                'date_fin' => $this->periode->date_fin?->toDateString(),
                'statut' => $this->periode->statut,
                'est_ouverte' => $this->periode->estOuverte(),
            ]),
            'enseignant_id' => (int) $this->enseignant_id,
            'enseignant' => $this->whenLoaded('enseignant', fn () => [
                'id' => $this->enseignant->id,
                'nom' => trim(($this->enseignant->user->prenom ?? '') . ' ' . ($this->enseignant->user->nom ?? '')),
            ]),
            'eleve' => $this->whenLoaded('contratCours', fn () => (
                $this->contratCours?->eleve
                    ? [
                        'id' => $this->contratCours->eleve->id,
                        'nom' => trim(
                            ($this->contratCours->eleve->user->prenom ?? '')
                            .' '.($this->contratCours->eleve->user->nom ?? '')
                        ),
                        'classe' => $this->contratCours->eleve->classe?->sigle,
                    ]
                    : null
            )),
            'type_cours' => $this->whenLoaded('contratCours', fn () => ($this->contratCours?->typeCours
                ? [
                    'id' => $this->contratCours->typeCours->id,
                    'libelle' => $this->contratCours->typeCours->libelle,
                ]
                : null
            )),

            'statut' => $this->statut,
            'volume_horaire_cumule' => (float) $this->volume_horaire_cumule,

            /*
             * D-049 — La ventilation fait foi pour les montants. Le total
             * horaire n'est qu'un cumul : c'est la somme des lignes qui est
             * comparée à la facture et au bulletin.
             */
            'lignes' => RapportMensuelLigneResource::collection(
                $this->whenLoaded('lignes')
            ),
            'total_seances' => (int) ($this->lignes?->sum('nombre_seances') ?? 0),
            'montant_estime' => (int) round(
                ($this->lignes ?? collect())->sum(
                    fn ($ligne) => (float) $ligne->nombre_heures
                        * (int) ($ligne->affectation?->taux_horaire_enseignant ?? 0)
                )
            ),

            /*
             * Volumétrie : `withSum` de la requête de liste si présente, sinon
             * recalcul depuis les lignes chargées (détail). Jamais « null » :
             * l'écran somme toujours la même clé.
             */
            'total_heures_lignes' => (float) (
                $this->resource->lignes_nombre_heures_sum
                ?? $this->lignes?->sum('nombre_heures')
                ?? 0
            ),

            /*
             * Modèle de rapport : sections et éléments administrés, avec la
             * réponse de l'enseignant pour chacun. Seul le DÉTAIL le porte
             * (`avecSections`) — les listes n'ont pas besoin de ce volume.
             */
            'sections' => $this->resource->avecSections
                ? $this->resource->sectionsRenseignees()
                : [],

            // Bilan automatique du cahier de texte (jamais saisi).
            'bilan_activites' => $this->bilan_activites,

            'motif_rejet' => $this->motif_rejet,
            'date_validation' => $this->date_validation?->toIso8601String(),
            'valide_par' => $this->valide_par,

            'actions' => $this->actionsAutorisees($request->user()),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transitions autorisées pour l'utilisateur connecté.
     *
     * Une seule source de vérité côté back : si la policy refuse, l'action
     * n'est pas proposée — et si elle est proposée, l'API l'autorise.
     */
    private function actionsAutorisees(?\App\Models\User $user): array
    {
        if (! $user) {
            return [];
        }

        $actions = ['pdf'];

        if ($user->can('update', $this->resource)) {
            $actions[] = 'modifier';

            // Un rapport rejeté se re-soumet ; un rapport soumis attend le
            // verdict de l'administration.
            if ($this->estRejete()) {
                $actions[] = 'resoumettre';
            }
        }

        if ($user->can('delete', $this->resource)) {
            $actions[] = 'supprimer';
        }

        /*
         * Valider et rejeter ne passent pas par la policy existante (qui n'a
         * pas ces abilities) : la route les réserve à `admin_cabinet`. Le
         * contrôleur API les ré-autorise explicitement.
         */
        if (\App\Support\Roles::estAdmin($user) && $this->estSoumis()) {
            $actions[] = 'valider';
            $actions[] = 'rejeter';
        }

        return $actions;
    }
}
