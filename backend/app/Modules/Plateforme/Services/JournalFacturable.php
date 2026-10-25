<?php

namespace App\Modules\Plateforme\Services;

use App\Models\EvenementFacturable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * P8 — Capture d'événements facturables des cabinets.
 *
 * Appelé depuis les services tenant (contrats de cours, rapports mensuels,
 * librairie, comptes) — toujours APRÈS le commit de la transaction tenant
 * (DB::afterCommit), toujours silencieux : la facturation ne doit jamais
 * casser le métier. Idempotent grâce à l'index unique (Q-A13/Q-A18).
 */
final class JournalFacturable
{
    /**
     * @param  string  $type  Constante EvenementFacturable::TYPE_*
     * @param  string  $refType  contrat|rapport|commande|eleve|enseignant|parent|user
     * @param  int|string|null  $refId
     * @param  string|null  $libelle
     * @param  int|null  $montantAssiette  Assiette pour commissions (sous-total vente)
     * @param  array  $meta
     * @param  string|null  $cabinetId  Identifiant du cabinet source ; si null, cabinet courant
     * @param  Carbon|null  $date  Date retenue (par défaut : maintenant)
     */
    public static function capturer(
        string $type,
        string $refType,
        int|string $refId,
        ?string $libelle = null,
        ?int $montantAssiette = null,
        array $meta = [],
        ?string $cabinetId = null,
        ?Carbon $date = null,
    ): void {
        $cabinetId = $cabinetId ?? (tenancy()->initialized ? (string) tenancy()->tenant?->getKey() : null);

        if ($cabinetId === null) {
            return;
        }

        $data = [
            'cabinet_id' => $cabinetId,
            'type' => $type,
            'reference_type' => $refType,
            'reference_id' => (string) $refId,
            'date_evenement' => ($date ?? now())->toDateString(),
            'libelle' => $libelle,
            'montant_assiette' => $montantAssiette,
            'meta' => $meta,
            'statut' => EvenementFacturable::STATUT_DISPONIBLE,
        ];

        $ecrire = static function () use ($data): void {
            try {
                EvenementFacturable::query()->firstOrCreate([
                    'cabinet_id' => $data['cabinet_id'],
                    'type' => $data['type'],
                    'reference_type' => $data['reference_type'],
                    'reference_id' => $data['reference_id'],
                    'date_evenement' => $data['date_evenement'],
                ], $data + ['created_at' => now(), 'updated_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('JournalFacturable: capture impossible', ['erreur' => $e->getMessage()] + $data);
            }
        };

        DB::afterCommit($ecrire);
    }

    /** Rattrapage idempotent : événements déjà enregistrés passent sans écrire. */
    public static function compteEvenements(string $cabinetId, ?string $type = null): int
    {
        return EvenementFacturable::query()
            ->where('cabinet_id', $cabinetId)
            ->when($type !== null, fn ($q) => $q->where('type', $type))
            ->count();
    }
}
