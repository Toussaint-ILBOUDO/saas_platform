<?php

namespace App\Modules\Finance\Services;

use App\Models\BulletinPaie;
use App\Models\BulletinPaieAjustement;
use App\Models\BulletinPaieLigne;
use App\Models\EnseignantProfil;
use App\Models\PeriodeComptable;
use App\Models\TypeAjustement;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

class BulletinPaieGenerationService
{
    public function __construct(
        private BulletinPaieCalculationService $calculator,
        private NotificationDispatcher $notifier,
    ) {}

    /**
     * Preview pour tous les enseignants d'une periode.
     *
     * @param  int  $periodeId
     * @param  array<int, int>  $fraisSuivisPerEnseignant  [enseignant_id => montant]
     * @param  array<int, array<int, int>>  $ajustementsPerEnseignant  [enseignant_id => [type_ajustement_id => montant]]
     * @return array<int, array{
     *     enseignant: EnseignantProfil,
     *     lignes: array,
     *     total_heures: float,
     *     montant_brut: int,
     *     frais_suivi: int,
     *     ajustements: array<int, array{type_ajustement_id: int, libelle: string, direction: string, montant: int}>,
     *     total_credits: int,
     *     total_debits: int,
     *     montant_net: int,
     * }>
     */
    public function preview(
        int $periodeId,
        array $fraisSuivisPerEnseignant = [],
        array $ajustementsPerEnseignant = []
    ): array {
        $periode = PeriodeComptable::findOrFail($periodeId);
        $enseignants = $this->calculator->getEnseignantsConcernes($periodeId);
        $typesAjustement = TypeAjustement::where('is_active', true)
            ->orderBy('direction')
            ->orderBy('libelle')
            ->get();

        $preview = [];

        foreach ($enseignants as $enseignant) {

            $result = $this->calculator->calculerLignes(
                $enseignant,
                $periode->id
            );

            if (empty($result['lignes'])) {
                continue;
            }

            $fraisSuivi = $fraisSuivisPerEnseignant[$enseignant->id] ?? 5000;

            $ajustements = $this->buildAjustements(
                $enseignant->id,
                $typesAjustement,
                $ajustementsPerEnseignant[$enseignant->id] ?? []
            );

            $totalCredits = $ajustements
                ->where('direction', 'credit')
                ->sum('montant');

            $totalDebits = $ajustements
                ->where('direction', 'debit')
                ->sum('montant');

            $montantNet = $result['montant_brut']
                - $fraisSuivi
                + $totalCredits
                - $totalDebits;

            $preview[] = [
                'enseignant'     => $enseignant,
                'lignes'         => $result['lignes'],
                'total_heures'   => $result['total_heures'],
                'montant_brut'   => $result['montant_brut'],
                'frais_suivi'    => $fraisSuivi,
                'ajustements'    => $ajustements->values(),
                'total_credits'  => $totalCredits,
                'total_debits'   => $totalDebits,
                'montant_net'    => $montantNet,
            ];
        }

        return $preview;
    }

    /**
     * Genere les bulletins avec ajustements pour tous les enseignants.
     *
     * @param  int  $periodeId
     * @param  array<int, int>  $fraisSuivisPerEnseignant
     * @param  array<int, array<int, int>>  $ajustementsPerEnseignant
     */
    public function generer(
        int $periodeId,
        array $fraisSuivisPerEnseignant = [],
        array $ajustementsPerEnseignant = []
    ): array {
        $periode = PeriodeComptable::findOrFail($periodeId);
        $enseignants = $this->calculator->getEnseignantsConcernes($periodeId);
        $typesAjustement = TypeAjustement::where('is_active', true)->get();
        $bulletins = [];

        return DB::transaction(function () use (
            $enseignants,
            $periode,
            &$bulletins,
            $fraisSuivisPerEnseignant,
            $ajustementsPerEnseignant,
            $typesAjustement
        ) {
            foreach ($enseignants as $enseignant) {

                $result = $this->calculator->calculerLignes(
                    $enseignant,
                    $periode->id
                );

                if (empty($result['lignes'])) {
                    continue;
                }

                // Verifier unicite
                $existant = BulletinPaie::query()
                    ->where('enseignant_id', $enseignant->id)
                    ->where('periode_id', $periode->id)
                    ->first();

                if ($existant) {
                    if ($existant->statut === 'brouillon') {
                        $existant->delete();
                    } else {
                        $bulletins[] = $existant;
                        continue;
                    }
                }

                $fraisSuivi = $fraisSuivisPerEnseignant[$enseignant->id] ?? 5000;

                // Calculer les totaux des ajustements
                $ajustementsData = $ajustementsPerEnseignant[$enseignant->id] ?? [];
                $totalCredits = 0;
                $totalDebits = 0;

                foreach ($ajustementsData as $typeId => $montant) {
                    $montant = (int) $montant;
                    if ($montant <= 0) {
                        continue;
                    }
                    $type = $typesAjustement->firstWhere('id', $typeId);
                    if ($type && $type->direction === 'credit') {
                        $totalCredits += $montant;
                    } else {
                        $totalDebits += $montant;
                    }
                }

                $montantNet = $result['montant_brut']
                    - $fraisSuivi
                    + $totalCredits
                    - $totalDebits;

                $bulletin = BulletinPaie::create([
                    'numero'        => $this->genererNumero(),
                    'enseignant_id' => $enseignant->id,
                    'periode_id'    => $periode->id,
                    'total_heures'  => $result['total_heures'],
                    'montant_brut'  => $result['montant_brut'],
                    'frais_suivi'   => $fraisSuivi,
                    'montant_net'   => $montantNet,
                    'statut'        => 'genere',
                ]);

                // Creer les lignes de paie
                foreach ($result['lignes'] as $ligne) {
                    BulletinPaieLigne::create([
                        'bulletin_paie_id'           => $bulletin->id,
                        'affectation_enseignant_id'  => $ligne['affectation_enseignant_id'],
                        'contrat_cours_id'           => $ligne['contrat_cours_id'],
                        'eleve_id'                   => $ligne['eleve_id'],
                        'matiere_id'                 => $ligne['matiere_id'],
                        'nombre_heures'              => $ligne['nombre_heures'],
                        'taux_horaire'               => $ligne['taux_horaire'],
                        'montant'                    => $ligne['montant'],
                    ]);
                }

                // Creer les ajustements
                foreach ($ajustementsData as $typeId => $montant) {
                    $montant = (int) $montant;
                    if ($montant <= 0) {
                        continue;
                    }

                    $type = $typesAjustement->firstWhere('id', $typeId);

                    BulletinPaieAjustement::create([
                        'bulletin_paie_id'    => $bulletin->id,
                        'type_ajustement_id'  => $type?->id,
                        'type'                => $type?->direction === 'debit'
                            ? 'retenue'
                            : 'prime',
                        'libelle'             => $type?->libelle ?? 'Ajustement',
                        'montant'             => $montant,
                    ]);
                }

                $this->notifier->bulletinGenere($bulletin);

                $bulletins[] = $bulletin->load([
                    'enseignant.user',
                    'periode',
                    'lignes.eleve.user',
                    'lignes.matiere',
                    'ajustements',
                ]);
            }

            return $bulletins;
        });
    }

    /**
     * Construit la collection d'ajustements pour un enseignant.
     *
     * Charge tous les types actifs avec la valeur soumise (defaut 0).
     */
    private function buildAjustements(
        int $enseignantId,
        $typesAjustement,
        array $submittedAjustements
    ): \Illuminate\Support\Collection {
        return $typesAjustement->map(function ($type) use ($submittedAjustements) {
            return [
                'type_ajustement_id'  => $type->id,
                'libelle'             => $type->libelle,
                'direction'           => $type->direction,
                'montant'             => $submittedAjustements[$type->id] ?? 0,
            ];
        });
    }

    /**
     * Genere un numero unique.
     * Format : BP-YYYYMM-NNNNN
     */
    private function genererNumero(): string
    {
        $prefixe = 'BP-' . now()->format('Ym') . '-';

        $dernierNumero = BulletinPaie::query()
            ->where('numero', 'like', $prefixe . '%')
            ->count();

        return $prefixe
            . str_pad($dernierNumero + 1, 5, '0', STR_PAD_LEFT);
    }
}
