<?php

namespace App\Modules\Finance\Services;

use App\Models\AffectationEnseignant;
use App\Models\BulletinPaie;
use App\Models\BulletinPaieLigne;
use App\Models\EnseignantProfil;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;

class BulletinPaieCalculationService
{
    /**
     * Récupère les enseignants ayant des rapports soumis/validés pour la période.
     */
    public function getEnseignantsConcernes(int $periodeId): \Illuminate\Support\Collection
    {
        return EnseignantProfil::query()
            ->whereHas('rapportsMensuels', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId)
                    ->whereIn('statut', ['soumis', 'valide']);
            })
            ->with('user')
            ->get();
    }

    /**
     * Calcule les lignes de paie pour un enseignant donné sur une période.
     *
     * Source des heures : RapportMensuelEnseignant.volume_horaire_cumule
     * Source du taux   : AffectationEnseignant.taux_horaire_enseignant
     */
    public function calculerLignes(
        EnseignantProfil $enseignant,
        int $periodeId
    ): array {

        $rapports = RapportMensuelEnseignant::query()
            ->where('enseignant_id', $enseignant->id)
            ->where('periode_id', $periodeId)
            ->whereIn('statut', ['soumis', 'valide'])
            ->with([
                'contratCours.eleve.user',
                'contratCours.affectations' => function ($q) use ($enseignant) {
                    $q->where('enseignant_id', $enseignant->id)
                        ->with('matiere');
                },
            ])
            ->get();

        $lignes = [];
        $totalHeures = 0;
        $montantBrut = 0;

        foreach ($rapports as $rapport) {

            $heures = (float) $rapport->volume_horaire_cumule;

            if ($heures <= 0) {
                continue;
            }

            $affectation = $rapport->contratCours->affectations
                ->firstWhere('enseignant_id', $enseignant->id);

            if (!$affectation) {
                continue;
            }

            $tauxHoraire = (int) $affectation->taux_horaire_enseignant;
            $montantLigne = (int) ($heures * $tauxHoraire);

            $totalHeures += $heures;
            $montantBrut += $montantLigne;

            $lignes[] = [
                'affectation_enseignant_id' => $affectation->id,
                'contrat_cours_id'          => $rapport->contrat_cours_id,
                'eleve_id'                  => $rapport->contratCours->eleve_id,
                'matiere_id'                => $affectation->matiere_id,
                'eleve_nom'                 => trim(
                    ($rapport->contratCours->eleve?->user?->prenom ?? '')
                    . ' '
                    . ($rapport->contratCours->eleve?->user?->nom ?? '')
                ),
                'matiere_nom'               => $affectation->matiere?->nom ?? '—',
                'nombre_heures'             => round($heures, 2),
                'taux_horaire'              => $tauxHoraire,
                'montant'                   => $montantLigne,
            ];
        }

        return [
            'lignes'        => $lignes,
            'total_heures'  => round($totalHeures, 2),
            'montant_brut'  => $montantBrut,
        ];
    }

    /**
     * Calcule le montant net final avec ajustements.
     */
    public function calculerMontantNet(BulletinPaie $bulletin): int
    {
        $totalPrimes = $bulletin->ajustements
            ->where('type', 'prime')
            ->sum('montant');

        $totalRetenues = $bulletin->ajustements
            ->where('type', 'retenue')
            ->sum('montant');

        return $bulletin->montant_brut
            - $bulletin->frais_suivi
            + $totalPrimes
            - $totalRetenues;
    }

    /**
     * Re-calcule montant_brut + total_heures à partir des lignes existantes.
     */
    public function recalculerTotaux(BulletinPaie $bulletin): void
    {
        $bulletin->update([
            'total_heures' => $bulletin->lignes->sum('nombre_heures'),
            'montant_brut' => $bulletin->lignes->sum('montant'),
            'montant_net'  => $this->calculerMontantNet($bulletin),
        ]);
    }
}
