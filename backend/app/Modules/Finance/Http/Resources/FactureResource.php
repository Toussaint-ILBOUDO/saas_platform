<?php

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T7A.8 — Facture parent.
 *
 * La facture est un document d'argent : le parent la consulte (PDF) et
 * l'administration la génère et enregistre le règlement. Comme pour le rapport
 * mensuel, `actions` porte les transitions autorisées pour l'utilisateur
 * connecté — le frontend n'a qu'à afficher les boutons correspondants.
 */
class FactureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_facture' => $this->numero_facture,
            'statut_paiement' => $this->statut_paiement,
            'est_payee' => $this->estPayee(),
            'est_en_attente' => $this->estEnAttente(),

            'volume_horaire_total' => (float) $this->volume_horaire_total,
            'frais_suivi' => (int) $this->frais_suivi,
            'autres_frais' => (int) $this->autres_frais,
            'remise' => (int) $this->remise,
            'montant_total' => (int) $this->montant_total,

            'commentaire' => $this->commentaire,
            'date_limite_paiement' => $this->date_limite_paiement?->toDateString(),
            'date_paiement' => $this->date_paiement?->toDateString(),
            'mode_paiement' => $this->mode_paiement,
            'reference_paiement' => $this->reference_paiement,

            'periode' => $this->whenLoaded('periode', fn () => [
                'id' => $this->periode->id,
                'label' => $this->periode->label,
                'date_debut' => $this->periode->date_debut?->toDateString(),
                'date_fin' => $this->periode->date_fin?->toDateString(),
                'statut' => $this->periode->statut,
                'est_ouverte' => $this->periode->estOuverte(),
            ]),

            'eleve' => $this->whenLoaded('contrat', fn () => (
                $this->contrat?->eleve
                    ? [
                        'id' => $this->contrat->eleve->id,
                        'nom' => trim(
                            ($this->contrat->eleve->user->prenom ?? '')
                            .' '.($this->contrat->eleve->user->nom ?? '')
                        ),
                        'classe' => $this->contrat->eleve->classe?->sigle,
                    ]
                    : null
            )),

            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->id,
                'nom' => trim(
                    ($this->parent->prenom ?? '')
                    .' '.($this->parent->nom ?? '')
                ),
            ]),

            'type_cours' => $this->whenLoaded('contrat', fn () => (
                $this->contrat?->typeCours
                    ? [
                        'id' => $this->contrat->typeCours->id,
                        'libelle' => $this->contrat->typeCours->libelle,
                    ]
                    : null
            )),

            'lignes' => LigneFactureResource::collection(
                $this->whenLoaded('lignes')
            ),

            /*
             * Ajustements (bonus/débit) ajoutés après génération : ils font
             * partie du montant total. Comme pour le bulletin, le détail porte
             * les totaux crédit/débit pour l'affichage.
             */
            'ajustements' => FactureAjustementResource::collection(
                $this->whenLoaded('ajustements')
            ),
            'total_credits' => $this->resource->relationLoaded('ajustements')
                ? (int) $this->ajustements->where('type', 'credit')->sum('montant')
                : 0,
            'total_debits' => $this->resource->relationLoaded('ajustements')
                ? (int) $this->ajustements->where('type', 'debit')->sum('montant')
                : 0,

            'actions' => $this->actionsAutorisees($request->user()),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transitions autorisées pour l'utilisateur connecté.
     *
     * Un parent ne fait que consulter (PDF) ; un admin enregistre le règlement
     * tant que la facture est en attente. Une facture payée est figée.
     */
    private function actionsAutorisees(?\App\Models\User $user): array
    {
        if (! $user) {
            return [];
        }

        $actions = ['pdf'];

        if ($this->estEnAttente() && $user->can('payer', $this->resource)) {
            $actions[] = 'payer';
        }

        // Une facture non réglée accepte des ajustements de l'administration
        // (bonus/débit) tant que le montant n'est pas figé par le paiement.
        if (! $this->estPayee() && $user->can('update', $this->resource)) {
            $actions[] = 'ajuster';
        }

        return $actions;
    }
}