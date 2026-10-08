<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D-048 — Retrait de la chaîne de paiement enseignant concurrente.
 *
 * `paiement_enseignants` calculait ses heures depuis `cahier_textes` et créait
 * un paiement au statut `paye` immédiatement, sans aucun cycle de validation :
 * elle faisait double emploi avec `bulletins_paie` (heures du rapport mensuel
 * validé, cycle genere → consulte → valide → verse). Le versement est
 * désormais porté par le bulletin (`date_paiement`, `mode_paiement`,
 * `reference_paiement`, `statut = 'verse'`).
 *
 * `factures.statut_paiement_enseignants` disparaît avec elle : la valeur
 * `'paye'` n'y était écrite nulle part, donc `Facture::tousEnseignantsPayes()`
 * renvoyait toujours `false` (code mort). Les factures déjà payées conservent
 * leur propre `statut_paiement`.
 *
 * ── Conservation de l'historique ────────────────────────────────────────────
 * Les tables sont RENOMMÉES en `..._archive` et non supprimées : des paiements
 * au statut `paye` représentent de l'argent réellement versé, qui doit rester
 * justifiable. La migration journalise le nombre de lignes conservées. Leur
 * suppression définitive reste une décision explicite, à prendre après
 * vérification comptable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Journalisation AVANT toute modification : l'administrateur doit voir
        // ce qui part en archive, en particulier les paiements effectués.
        $paiements = DB::table('paiement_enseignants')->count();

        $paiementsVerses = Schema::hasTable('paiement_enseignants')
            ? DB::table('paiement_enseignants')
                ->whereIn('statut', ['paye', 'verse', 'partiel'])
                ->count()
            : 0;

        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn('statut_paiement_enseignants');
        });

        if (Schema::hasTable('ligne_paiement_enseignants')) {
            Schema::rename('ligne_paiement_enseignants', 'ligne_paiement_enseignants_archive');
        }

        if (Schema::hasTable('paiement_enseignants')) {
            Schema::rename('paiement_enseignants', 'paiement_enseignants_archive');
        }

        if ($paiements > 0) {
            fwrite(STDERR, sprintf(
                "D-048 : %d paiement(s) enseignant conservés dans "
                . "paiement_enseignants_archive (%d déjà versé(s) — à archiver "
                . "comptablement).\n",
                $paiements,
                $paiementsVerses
            ));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('paiement_enseignants_archive')) {
            Schema::rename('paiement_enseignants_archive', 'paiement_enseignants');
        }

        if (Schema::hasTable('ligne_paiement_enseignants_archive')) {
            Schema::rename('ligne_paiement_enseignants_archive', 'ligne_paiement_enseignants');
        }

        Schema::table('factures', function (Blueprint $table) {
            $table->string('statut_paiement_enseignants')->default('en_attente');
        });
    }
};