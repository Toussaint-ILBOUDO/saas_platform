<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contraintes d'intégrité de la facturation et de la paie.
 *
 * 1. `factures` : unicité `(contrat_cours_id, periode_id)`. KEduc ne faisait qu'un
 *    `exists()` applicatif **non verrouillé** — deux requêtes concurrentes
 *    pouvaient créer deux factures pour le même contrat et la même période. La
 *    contrainte en base rend l'opération idempotente ; le service garde son
 *    pré-check pour un message d'erreur lisible.
 * 2. `bulletin_paie_lignes` : unicité `(bulletin_paie_id, affectation_enseignant_id)`.
 *    Une même affectation ne peut être payée qu'une fois dans un bulletin — c'est
 *    l'invariant anti-double-comptage de la paie.
 *
 * Déjà en place, donc NON recréées ici :
 *   - `bulletins_paie.numero` unique et `(enseignant_id, periode_id)` unique
 *     (migration `2026_07_20_100000`) ;
 *   - `factures.numero_facture` unique (migration `2026_06_04_164933`).
 *
 * NB : deux matières du même élève sur le même contrat produisent bien DEUX
 * lignes de bulletin (D-049) : l'unicité porte sur l'affectation, pas sur le
 * couple contrat/élève.
 */
return new class extends Migration
{
    public function up(): void
    {
        // AVANT la création des index : si des doublons existent, l'index échoue
        // et l'administrateur doit d'abord arbitrer. On journalise, on ne
        // supprime aucune donnée.
        $this->journaliserAnomalies();

        Schema::table('factures', function (Blueprint $table) {
            $table->unique(
                ['contrat_cours_id', 'periode_id'],
                'uq_factures_contrat_periode'
            );
        });

        Schema::table('bulletin_paie_lignes', function (Blueprint $table) {
            $table->unique(
                ['bulletin_paie_id', 'affectation_enseignant_id'],
                'uq_bpl_bulletin_affectation'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_paie_lignes', function (Blueprint $table) {
            $table->dropUnique('uq_bpl_bulletin_affectation');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropUnique('uq_factures_contrat_periode');
        });
    }

    /**
     * Signale les données existantes qui empêcheront la création des index.
     */
    private function journaliserAnomalies(): void
    {
        $doublonsFactures = DB::table('factures')
            ->selectRaw('contrat_cours_id, periode_id, COUNT(*) AS n')
            ->groupBy('contrat_cours_id', 'periode_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($doublonsFactures as $doublon) {
            fwrite(STDERR, sprintf(
                "Anomalie : %d factures pour le contrat %d et la période %d — "
                . "uq_factures_contrat_periode ne pourra pas être créée.\n",
                $doublon->n,
                $doublon->contrat_cours_id,
                $doublon->periode_id
            ));
        }

        $doublonsBulletins = DB::table('bulletins_paie')
            ->selectRaw('enseignant_id, periode_id, COUNT(*) AS n')
            ->groupBy('enseignant_id', 'periode_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($doublonsBulletins as $doublon) {
            fwrite(STDERR, sprintf(
                "Anomalie : %d bulletins pour l'enseignant %d et la période %d — "
                . "uniq_bp_enseignant_periode ne pourra pas être créée.\n",
                $doublon->n,
                $doublon->enseignant_id,
                $doublon->periode_id
            ));
        }
    }
};