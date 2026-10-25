<?php

namespace App\Modules\Plateforme\Services;

use Illuminate\Support\Facades\DB;

/**
 * P8 — Numérotation de la plateforme : `CTA-YYYY-NNNNN` (contrats) et
 * `FC-YYYYMM-NNNNN` (factures). Sémantique identique à NumerotationDocuments
 * (module tenant) mais adaptée à la base centrale : verrou consultatif
 * PostgreSQL + compteur persistant par (prefixe, clé de période).
 */
final class NumerotationPlateformeService
{
    public function prochain(string $prefixe, string $clePeriode): string
    {
        return DB::transaction(function () use ($prefixe, $clePeriode) {
            DB::statement('SELECT pg_advisory_xact_lock(hashtext(?))', ['plateforme-numero-'.$prefixe.'-'.$clePeriode]);

            $existe = DB::table('numerotation_plateforme')
                ->where('prefixe', $prefixe)
                ->where('cle_periode', $clePeriode)
                ->lockForUpdate()
                ->first();

            if ($existe === null) {
                DB::table('numerotation_plateforme')->insert([
                    'prefixe' => $prefixe,
                    'cle_periode' => $clePeriode,
                    'dernier_numero' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return sprintf('%s-%s-%05d', $prefixe, $clePeriode, 1);
            }

            $suivant = ((int) $existe->dernier_numero) + 1;

            DB::table('numerotation_plateforme')
                ->where('id', $existe->id)
                ->update(['dernier_numero' => $suivant, 'updated_at' => now()]);

            return sprintf('%s-%s-%05d', $prefixe, $clePeriode, $suivant);
        });
    }
}
