<?php

namespace App\Modules\Plateforme\Services;

use App\Models\Cabinet;
use App\Models\ContratCabinet;
use App\Models\EvenementFacturable;
use App\Models\FactureCabinet;
use App\Models\LigneFactureCabinet;
use App\Models\JournalPlateforme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * P8 — Facturation par cabinet : brouillon → émission → paiements,
 * avec anti double facturation (Q-A13) et fidélité des montants.
 *
 * Invariants :
 * - une facture ne mélange jamais deux cabinets (cabinet_id) ;
 * - un événement facturable est imputé au plus une fois (statut
 *   `facturee` + FK `facture_cabinet_id`) ; un événement imputé à une
 *   facture annulée ou brouillon supprimé est relâché (« disponible ») ;
 * - les périodes de factures non annulées d'un même cabinet ne se
 *   chevauchent pas ;
 * - montant_total = Σ débits − Σ crédits ; montant_paye = Σ paiements.
 */
final class FacturationCabinetService
{
    public function calculerApercu(
        string $cabinetId,
        Carbon $debut,
        Carbon $fin,
        ?ContratCabinet $contrat = null,
    ): array {
        $this->validePeriode($debut, $fin);

        [$lignes, $manquants] = app(CalculFacturationCabinet::class)->calculer($cabinetId, $debut, $fin, $contrat);

        $evenements = $this->evenementsDisponibles($cabinetId, $debut, $fin);

        return [
            'lignes' => $lignes,
            'evenements' => $evenements,
            'tarifs_manquants' => $manquants,
        ];
    }

    /** Contrat validé courant (null si jamais négocié). */
    public function contratValide(string $cabinetId): ?ContratCabinet
    {
        return app(ContratCabinetService::class)->enCours($cabinetId);
    }

    /** Ajoute une ligne manuelle à un brouillon (persistance + recalcul). */
    public function ajouterLigneManuelle(FactureCabinet $facture, array $data, ?int $superAdminId): LigneFactureCabinet
    {
        if (! $facture->estBrouillon()) {
            // Montants figés à l'émission (Q-A16) : toute correction passe
            // par annulation puis régularisation par nouvelle facture.
            throw ValidationException::withMessages(['facture' => 'Les lignes ne sont modifiables que sur un brouillon.']);
        }

        return DB::transaction(function () use ($facture, $data, $superAdminId) {
            $ligne = $facture->lignes()->create($this->normaliserLigne($data, 'manuelle', $superAdminId));
            $this->recalculerMontant($facture);

            return $ligne->refresh();
        });
    }

    /** Supprime une ligne auto ou manuelle d'un brouillon (recalcul). */
    public function supprimerLigne(FactureCabinet $facture, int $ligneId): void
    {
        if (! $facture->estBrouillon()) {
            throw ValidationException::withMessages(['facture' => 'Les lignes ne sont modifiables que sur un brouillon.']);
        }

        DB::transaction(function () use ($facture, $ligneId): void {
            // Une ligne auto : son événement repasse « disponible »
            // et peut donc être facturé ailleurs (brouillon suivant).
            $ligne = LigneFactureCabinet::query()->where('facture_cabinet_id', $facture->getKey())->where('id', $ligneId)->first();

            if ($ligne === null) {
                throw ValidationException::withMessages(['ligne' => 'Ligne introuvable sur cette facture.']);
            }

            if ($ligne->evenement_facturable_id !== null) {
                $ligne->evenement()->update([
                    'facture_cabinet_id' => null,
                    'statut' => EvenementFacturable::STATUT_DISPONIBLE,
                    'updated_at' => now(),
                ]);
            }

            $ligne->delete();
            $this->recalculerMontant($facture);
        });
    }

    /** Crée une facture au statut `brouillon` (ponent, puis figée à l'émission). */
    public function creerFactureBrouillon(
        string $cabinetId,
        Carbon $debut,
        Carbon $fin,
        ?ContratCabinet $contrat,
        array $lignesManuelles = [],
        ?string $conditionsPaiement = null,
        ?string $noteInterne = null,
        ?int $superAdminId = null,
    ): FactureCabinet {
        $this->validePeriode($debut, $fin);

        return DB::transaction(function () use ($cabinetId, $debut, $fin, $contrat, $lignesManuelles, $conditionsPaiement, $noteInterne, $superAdminId) {
            DB::statement('SELECT pg_advisory_xact_lock(hashtext(?))', ['facture-cabinet-'.$cabinetId]);
            $this->refuseChevauchement($cabinetId, $debut, $fin, null);

            [$lignes, ] = app(CalculFacturationCabinet::class)->calculer($cabinetId, $debut, $fin, $contrat);

            $facture = FactureCabinet::create([
                'cabinet_id' => $cabinetId,
                'periode_debut' => $debut,
                'periode_fin' => $fin,
                'statut' => FactureCabinet::STATUT_BROUILLON,
                'conditions_paiement' => $conditionsPaiement,
                'note_interne' => $noteInterne,
            ]);

            foreach ($lignes as $ligne) {
                $facture->lignes()->create($this->normaliserLigne($ligne, 'auto', $superAdminId));
            }

            // Les événements couverts par la facture (période) sont attachés
            // au brouillon : leurs lignes sont figées avant émission (docs).
            EvenementFacturable::query()
                ->where('cabinet_id', $cabinetId)
                ->where('statut', EvenementFacturable::STATUT_DISPONIBLE)
                ->whereNull('facture_cabinet_id')
                ->whereBetween('date_evenement', [$debut->copy()->startOfDay(), $fin->copy()->endOfDay()])
                ->update(['facture_cabinet_id' => $facture->getKey(), 'updated_at' => now()]);

            // Lignes manuelles visibles par le super-admin.
            foreach ($lignesManuelles as $ligne) {
                $facture->lignes()->create($this->normaliserLigne($ligne, 'manuelle', $superAdminId));
            }

            $this->recalculerMontant($facture);

            return $facture->refresh();
        });
    }

    /** Émission : facture numérotée, montants figés, événements marqués facturés. */
    public function emettre(FactureCabinet $facture, ?int $superAdminId): FactureCabinet
    {
        return DB::transaction(function () use ($facture, $superAdminId) {
            if (! $facture->estBrouillon()) {
                throw ValidationException::withMessages(['facture' => 'Seul un brouillon peut être émis.']);
            }

            DB::statement('SELECT pg_advisory_xact_lock(hashtext(?))', ['facture-cabinet-'.$facture->cabinet_id]);
            $this->refuseChevauchement($facture->cabinet_id, $facture->periode_debut, $facture->periode_fin, $facture);

            $total = $this->recalculerMontant($facture);

            if ($total <= 0) {
                throw ValidationException::withMessages(['facture' => 'Refusé : le montant de la facture est nul ou négatif — vérifiez les lignes.']);
            }

            $facture->numero_facture = app(NumerotationPlateformeService::class)->prochain('FC', now()->format('Ym'));
            $facture->statut = FactureCabinet::STATUT_EMISE;
            $facture->montant_total = $total;
            $facture->emise_le = now();
            $facture->emise_par_user = $superAdminId;
            $facture->save();

            EvenementFacturable::query()
                ->where('facture_cabinet_id', $facture->getKey())
                ->update(['statut' => EvenementFacturable::STATUT_FACTUREE, 'updated_at' => now()]);

            JournalPlateforme::ecrire('facture.emise', 'info', Cabinet::find($facture->cabinet_id), [
                'facture' => $facture->numero_facture,
                'montant' => $total,
            ]);

            return $facture->refresh();
        });
    }

    /** Suppression du brouillon ; les événements repassent disponibles. */
    public function supprimerBrouillon(FactureCabinet $facture): void
    {
        DB::transaction(function () use ($facture): void {
            if (! $facture->estBrouillon()) {
                throw ValidationException::withMessages(['facture' => 'Seul un brouillon peut être supprimé.']);
            }

            $this->relacherEvenements($facture);

            $facture->lignes()->delete();
            $facture->delete();

            JournalPlateforme::ecrire('facture.brouillon_supprime', 'info', Cabinet::find($facture->cabinet_id));
        });
    }

    /** Annulation d'une facture émise non payée (Q-A16). */
    public function annuler(FactureCabinet $facture, string $motif, ?int $superAdminId): FactureCabinet
    {
        return DB::transaction(function () use ($facture, $motif, $superAdminId) {
            if (! $facture->estEmise()) {
                throw ValidationException::withMessages(['facture' => 'Seule une facture émise non payée peut être annulée.']);
            }

            if ($facture->montant_paye > 0) {
                throw ValidationException::withMessages(['facture' => 'Impossible d’annuler une facture payée – passez par un crédit.']);
            }

            $motif = trim($motif);

            if ($motif === '') {
                throw ValidationException::withMessages(['motif' => 'Motif d’annulation requis.']);
            }

            $facture->statut = FactureCabinet::STATUT_ANNULEE;
            $facture->annulee_le = now();
            $facture->annulee_par_user = $superAdminId;
            $facture->motif_annulation = mb_substr($motif, 0, 1000);
            $facture->save();

            $this->relacherEvenements($facture);

            JournalPlateforme::ecrire('facture.annulee', 'info', Cabinet::find($facture->cabinet_id), ['motif' => $facture->motif_annulation]);

            return $facture->refresh();
        });
    }

    /**
     * Paiement partiel ou complet d'une facture émise.
     * Le dépassement du montant_total est interdit (Q-A16/D-048).
     * Délégué à PaiementCabinetService (même module).
     */

    // ----------------------------------------------------------------

    /** Événements disponibles pour une période (non rattachés à une facture). */
    private function evenementsDisponibles(string $cabinetId, Carbon $debut, Carbon $fin)
    {
        return EvenementFacturable::query()
            ->where('cabinet_id', $cabinetId)
            ->where('statut', EvenementFacturable::STATUT_DISPONIBLE)
            ->whereNull('facture_cabinet_id')
            ->whereBetween('date_evenement', [$debut->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->orderBy('date_evenement')
            ->orderBy('id')
            ->get();
    }

    private function relacherEvenements(FactureCabinet $facture): void
    {
        EvenementFacturable::query()
            ->where('facture_cabinet_id', $facture->getKey())
            ->update([
                'facture_cabinet_id' => null,
                'statut' => EvenementFacturable::STATUT_DISPONIBLE,
                'updated_at' => now(),
            ]);
    }

    private function refuseChevauchement(string $cabinetId, Carbon $debut, Carbon $fin, ?FactureCabinet $sauf): void
    {
        $existante = FactureCabinet::query()
            ->where('cabinet_id', $cabinetId)
            ->where('statut', '!=', FactureCabinet::STATUT_ANNULEE)
            ->whereDate('periode_debut', '<=', $fin->toDateString())
            ->whereDate('periode_fin', '>=', $debut->toDateString())
            ->when($sauf !== null, fn ($q) => $q->where('id', '!=', $sauf->getKey()))
            ->first();

        if ($existante !== null) {
            throw ValidationException::withMessages([
                'periode' => sprintf(
                    'Franchissement refusé : la période %s → %s couvre la facture "%s" (%s → %s)',
                    $debut->format('Y-m-d'),
                    $fin->format('Y-m-d'),
                    $existante->numero_facture ?? ('brouillon #'.$existante->id),
                    $existante->periode_debut,
                    $existante->periode_fin,
                ),
            ]);
        }
    }

    private function validePeriode(Carbon $debut, Carbon $fin): void
    {
        if ($debut->isAfter($fin)) {
            throw ValidationException::withMessages(['periode' => 'La période de facturation doit aller de la plus ancienne à la plus récente.']);
        }

        if ($debut->diffInDays($fin) > 731) {
            throw ValidationException::withMessages(['periode' => 'Période trop longue (> 2 ans) — vérifiez les dates.']);
        }

        if ($fin->isAfter(now()->addDays(64))) {
            throw ValidationException::withMessages(['periode' => 'Période future déraisonnable (fin dans 64 jours max).']);
        }
    }

    /** Normalisation d'une ligne (auto ou manuelle) avec garanties fortes. */
    private function normaliserLigne(array $ligne, string $source, ?int $superAdminId): array
    {
        $montant = (int) ($ligne['montant'] ?? 0);
        $quantite = (int) ($ligne['quantite'] ?? 1);
        $libelle = mb_substr((string) ($ligne['libelle'] ?? ''), 0, 250);
        $motif = mb_substr((string) ($ligne['motif'] ?? ''), 0, 300);
        $type = mb_substr((string) ($ligne['type'] ?? LigneFactureCabinet::TYPE_LIBRE), 0, 50);

        if ($libelle === '') {
            if ($source === 'manuelle') {
                throw ValidationException::withMessages(['lignes_manuelles' => 'Chaque ligne manuelle doit avoir un libellé.']);
            }
            $libelle = '(libellé auto)';
        }

        if ($source === 'manuelle' && $montant < 0) {
            throw ValidationException::withMessages(['lignes_manuelles' => 'Le montant (crédit négatif) ne peut être négatif : utilisez la ligne sens credit.']);
        }

        return [
            'type' => $type,
            'sens' => $ligne['sens'] === LigneFactureCabinet::SENS_CREDIT
                ? LigneFactureCabinet::SENS_CREDIT
                : LigneFactureCabinet::SENS_DEBIT,
            'libelle' => $libelle,
            'quantite' => max(1, min($quantite, 999999)),
            'prix_unitaire' => ($ligne['prix_unitaire'] ?? null) === null
                ? null
                : max(0, (int) $ligne['prix_unitaire']),
            'montant' => max(0, $montant),
            'source' => $source,
            'evenement_facturable_id' => $ligne['evenement_facturable_id'] ?? null,
            'reference_type' => $ligne['reference_type'] ?? null,
            'reference_id' => ($ligne['reference_id'] ?? null) === null ? null : mb_substr((string) $ligne['reference_id'], 0, 100),
            'detail' => $ligne['detail'] ?? null,
            'motif' => $motif ?: null,
            'fiable' => (bool) ($ligne['fiable'] ?? true),
            'detail' => $ligne['detail'] ?? null,
            'motif' => $motif ?: null,
            'fiable' => (bool) ($ligne['fiable'] ?? true),
            'calcul' => $ligne['calcul'] ?? null,
            'enregistre_par_user' => $superAdminId,
        ];
    }

    private function recalculerMontant(FactureCabinet $facture): int
    {
        $total = 0;

        foreach ($facture->lignes()->get(['sens', 'montant']) as $ligne) {
            $total += $ligne->sens === LigneFactureCabinet::SENS_DEBIT ? $ligne->montant : -$ligne->montant;
        }

        $facture->montant_total = $total;
        $facture->save();

        return $total;
    }
}
