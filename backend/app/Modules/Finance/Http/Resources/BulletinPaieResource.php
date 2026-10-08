<?php

namespace App\Modules\Finance\Http\Resources;

use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T7A.9 — Bulletin de paie.
 *
 * Le bulletin est payé par l'administration et réceptionné par l'enseignant.
 * `actions` porte les transitions autorisées par la machine à états pour
 * l'utilisateur connecté, calculées depuis l'état du bulletin (le « qui peut »
 * est la policy, le « quand » est le statut) :
 *
 *  - `genere` / `corrige` → l'enseignant consulte ;
 *  - `consulte` → l'enseignant **valide** ou **conteste** ;
 *  - `conteste` → l'administration **corrige** ;
 *  - `valide` → l'administration **paie** ;
 *  - `verse` → l'enseignant **confirme la réception** tant qu'elle manque.
 *  - l'administration peut ajouter/retirer un ajustement tant que le bulletin
 *    n'est pas versé (`ajuster`).
 */
class BulletinPaieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => (int) $this->id,
            'numero' => $this->numero,
            'statut' => $this->statut,

            'est_genere' => $this->estGenere(),
            'est_consulte' => $this->estConsulte(),
            'est_valide' => $this->estValide(),
            'est_conteste' => $this->estConteste(),
            'est_corrige' => $this->estCorrige(),
            'est_verse' => $this->estVerse(),
            'est_recu' => $this->estRecu(),
            'en_attente_reception' => $this->enAttenteReception(),

            'total_heures' => (float) $this->total_heures,
            'montant_brut' => (int) $this->montant_brut,
            'frais_suivi' => (int) $this->frais_suivi,
            'montant_net' => (int) $this->montant_net,

            'total_primes' => $this->whenLoaded('ajustements', fn () => (int) $this->total_primes),
            'total_retenues' => $this->whenLoaded('ajustements', fn () => (int) $this->total_retenues),
            'montant_net_final' => $this->whenLoaded('ajustements', fn () => (int) $this->montant_net_final),

            'commentaire_enseignant' => $this->commentaire_enseignant,
            'motif_contestation' => $this->motif_contestation,
            'libelle_motif_contestation' => $this->motif_contestation
                ? $this->libelle_motif_contestation
                : null,

            'date_consultation' => $this->date_consultation?->toIso8601String(),
            'date_validation' => $this->date_validation?->toIso8601String(),
            'date_paiement' => $this->date_paiement?->toDateString(),
            'mode_paiement' => $this->mode_paiement,
            'reference_paiement' => $this->reference_paiement,
            'date_reception' => $this->date_reception?->toIso8601String(),
            'recu_par' => $this->whenLoaded('recaperePar', fn () => (
                $this->recaperePar?->nom ?? null
            )),

            'enseignant' => $this->whenLoaded('enseignant', fn () => [
                'id' => (int) $this->enseignant->id,
                'nom' => $this->enseignant->user
                    ? trim(
                        ($this->enseignant->user->prenom ?? '')
                        .' '.($this->enseignant->user->nom ?? '')
                    )
                    : null,
                'email' => $this->enseignant->user?->email,
            ]),

            'periode' => $this->whenLoaded('periode', fn () => [
                'id' => (int) $this->periode->id,
                'label' => $this->periode->label,
                'date_debut' => $this->periode->date_debut?->toDateString(),
                'date_fin' => $this->periode->date_fin?->toDateString(),
                'statut' => $this->periode->statut,
                'est_ouverte' => $this->periode->estOuverte(),
            ]),

            'lignes' => BulletinPaieLigneResource::collection(
                $this->whenLoaded('lignes')
            ),

            'ajustements' => BulletinPaieAjustementResource::collection(
                $this->whenLoaded('ajustements')
            ),

            'actions' => $this->actionsAutorisees($user),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transitions autorisées, déduites du statut (jamais de libre PATCH).
     *
     * Le groupe de routes a déjà restreint le rôle : dans le groupe
     * enseignant, tous les bulletins indexés sont les siens ; dans le groupe
     * admin, l'utilisateur est du staff. Pas besoin de re-tester l'appartenance
     * ici — c'est le périmètre du query service.
     */
    private function actionsAutorisees(?\App\Models\User $user): array
    {
        if (! $user) {
            return [];
        }

        $actions = ['pdf'];
        $admin = Roles::estAdmin($user);

        if ($admin) {
            if ($this->statut === 'conteste') {
                $actions[] = 'corriger';
            }
            if ($this->statut === 'valide') {
                $actions[] = 'payer';
            }
            if ($this->statut !== 'verse') {
                $actions[] = 'ajuster';
            }

            return array_values($actions);
        }

        // Enseignant (groupes `mes-bulletins`) : transitions de son cycle.
        if ($this->statut === 'genere' || $this->statut === 'corrige') {
            $actions[] = 'consulter';
        } elseif ($this->statut === 'consulte') {
            $actions[] = 'valider';
            $actions[] = 'contester';
        } elseif ($this->statut === 'verse' && $this->date_reception === null) {
            $actions[] = 'confirmer-reception';
        }

        return $actions;
    }
}