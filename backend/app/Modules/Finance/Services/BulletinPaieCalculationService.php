<?php

namespace App\Modules\Finance\Services;

use App\Models\BulletinPaie;
use App\Models\EnseignantProfil;
use App\Models\RapportMensuelEnseignant;

class BulletinPaieCalculationService
{
    /**
     * Enseignants dont les rapports mensuels sont VALIDÉS pour la période.
     *
     * D-051 : on ne paie que des heures validées par l'administration.
     * KEduc incluait les rapports « soumis », ce qui permettait de générer un
     * bulletin sur des heures que le compte n'avait pas encore validées.
     */
    public function getEnseignantsConcernes(int $periodeId): \Illuminate\Support\Collection
    {
        return EnseignantProfil::query()
            ->whereHas('rapportsMensuels', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId)
                    ->where('statut', 'valide')
                    ->whereHas('lignes', fn ($l) => $l->where('nombre_heures', '>', 0));
            })
            ->with('user')
            ->orderBy('id')
            ->get();
    }

    /**
     * Calcule les lignes de paie pour un enseignant donné sur une période.
     *
     * D-049 — Source des heures : `rapport_mensuel_enseignant_lignes`
     * (ventilation par affectation/matière issue du cahier de texte).
     * D-049 — Source du taux : `affectations_enseignants.taux_horaire_enseignant`.
     *
     * KEduc indexait les rapports par enseignant, lisait le CUMULATIF
     * `volume_horaire_cumule` puis rattachait la ligne à la PREMIÈRE affectation
     * trouvée (`firstWhere`) : les heures d'une deuxième matière étaient
     * payées au taux de la première, et le cumul était compté en double dès
     * qu'un enseignant intervenait sur plusieurs contrats.
     *
     * @return array{lignes: array<int, array<string, mixed>>, total_heures: float, montant_brut: int}
     */
    public function calculerLignes(
        EnseignantProfil $enseignant,
        int $periodeId
    ): array {

        $ventilations = RapportMensuelEnseignant::query()
            ->where('enseignant_id', $enseignant->id)
            ->where('periode_id', $periodeId)
            ->where('statut', 'valide')
            ->whereHas('lignes', fn ($q) => $q->where('nombre_heures', '>', 0))
            ->with([
                'lignes' => fn ($q) => $q->where('nombre_heures', '>', 0),
                'lignes.affectation',
                'lignes.affectation.matiere',
                'contratCours.eleve.user',
            ])
            ->orderBy('contrat_cours_id')
            ->get();

        $lignes = [];
        $totalHeures = 0.0;
        $montantBrut = 0;

        foreach ($ventilations as $rapport) {
            $eleve = $rapport->contratCours?->eleve;

            foreach ($rapport->lignes as $ventilation) {
                $affectation = $ventilation->affectation;

                if (! $affectation || $affectation->statut !== 'actif') {
                    continue;
                }

                $heures = (float) $ventilation->nombre_heures;

                if ($heures <= 0) {
                    continue;
                }

                $tauxHoraire = (int) $affectation->taux_horaire_enseignant;
                $montantLigne = (int) round($heures * $tauxHoraire);

                $totalHeures += $heures;
                $montantBrut += $montantLigne;

                $lignes[] = [
                    'affectation_enseignant_id' => (int) $affectation->id,
                    'contrat_cours_id'          => (int) $rapport->contrat_cours_id,
                    'eleve_id'                  => (int) $rapport->contratCours->eleve_id,
                    'matiere_id'                => (int) $ventilation->matiere_id,
                    'eleve_nom'                 => trim(
                        ($eleve?->user?->prenom ?? '')
                        . ' '
                        . ($eleve?->user?->nom ?? '')
                    ),
                    'matiere_nom'               => $affectation->matiere?->nom ?? '—',
                    'nombre_heures'             => round($heures, 2),
                    'taux_horaire'              => $tauxHoraire,
                    'montant'                   => $montantLigne,
                ];
            }
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
        $bulletin->loadMissing('lignes', 'ajustements');

        $bulletin->update([
            'total_heures' => round(
                $bulletin->lignes->sum('nombre_heures'),
                2
            ),
            'montant_brut' => (int) $bulletin->lignes->sum('montant'),
            'montant_net'  => $this->calculerMontantNet($bulletin),
        ]);
    }
}