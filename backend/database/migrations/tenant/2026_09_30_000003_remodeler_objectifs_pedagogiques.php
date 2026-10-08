<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D-050 — Objectifs pédagogiques remodelés.
 *
 * KEduc stockait l'objectif par `élève × période` où `periode` était une
 * chaîne libre du type « Trimestre 1 » :
 *   - deux enseignants du même élève écrivaient sur le même objectif ;
 *   - la période n'était pas une période comptable, donc rien ne garantissait
 *     la cohérence avec le rapport mensuel ni avec le gel des périodes (D-051) ;
 *   - pas d'unicité, donc des doublons possibles.
 *
 * Nouveau modèle : une ligne par (élève, PÉRIODE COMPTABLE, enseignant).
 * Backfill : chaque objectif existant est rattaché à la période comptable qui
 * contient sa date de création ; à défaut, la première période créée. Les
 * objectifs qui ne reçoivent aucune période sont supprimés (ils ne seraient
 * de toute façon pas exploitables par la nouvelle API) — le rapport des lignes
 * supprimées est journalisé dans la sortie de la migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectif_pedagogiques', function (Blueprint $table) {
            $table->foreignId('periode_id')
                ->nullable()
                ->after('eleve_id')
                ->constrained('periode_comptables')
                ->cascadeOnDelete();

            $table->foreignId('enseignant_id')
                ->nullable()
                ->after('periode_id')
                ->constrained('enseignant_profils')
                ->cascadeOnDelete();
        });

        // Backfill
        $periodes = DB::table('periode_comptables')
            ->orderBy('date_debut')
            ->get(['id', 'date_debut', 'date_fin']);

        $orphelins = 0;

        DB::table('objectif_pedagogiques')
            ->orderBy('id')
            ->chunk(200, function ($objectifs) use ($periodes, &$orphelins) {
                foreach ($objectifs as $objectif) {
                    $periodeId = null;

                    if ($periodes->isNotEmpty()) {
                        $reference = $objectif->created_at
                            ? substr((string) $objectif->created_at, 0, 10)
                            : null;

                        if ($reference) {
                            $contenue = $periodes->first(function ($periode) use ($reference) {
                                return $periode->date_debut <= $reference
                                    && $periode->date_fin >= $reference;
                            });

                            if ($contenue) {
                                $periodeId = $contenue->id;
                            }
                        }

                        $periodeId ??= $periodes->first()->id;
                    }

                    if (! $periodeId) {
                        $orphelins++;

                        continue;
                    }

                    DB::table('objectif_pedagogiques')
                        ->where('id', $objectif->id)
                        ->update([
                            'periode_id' => $periodeId,
                            // Enseignant : première affectation active de l'élève.
                            'enseignant_id' => $this->enseignantDeLEleve($objectif->eleve_id),
                        ]);
                }
            });

        // Suppression des lignes restées sans période comptable
        DB::table('objectif_pedagogiques')
            ->whereNull('periode_id')
            ->delete();

        // Unicité : un objectif par élève, période et enseignant
        DB::statement('DELETE FROM objectif_pedagogiques WHERE id NOT IN (
            SELECT MIN(id) FROM objectif_pedagogiques
            GROUP BY eleve_id, periode_id, COALESCE(enseignant_id, 0)
        )');

        // La colonne texte libre n'a plus de sens
        Schema::table('objectif_pedagogiques', function (Blueprint $table) {
            $table->dropColumn('periode');
            $table->index(['eleve_id', 'periode_id'], 'idx_obj_eleve_periode');
            $table->index('enseignant_id', 'idx_obj_enseignant');
        });

        // Après backfill, plus aucune ligne n'est sans période comptable.
        Schema::table('objectif_pedagogiques', function (Blueprint $table) {
            $table->foreignId('periode_id')
                ->nullable(false)
                ->change();
        });

        // Unicité : un objectif par élève, période et enseignant.
        //
        // `enseignant_id` reste nullable (un élève peut n'avoir aucun enseignant
        // affecté), mais un index unique PostgreSQL considère plusieurs NULL
        // comme distincts : deux objectifs du même élève et de la même période
        // SANS enseignant passeraient donc l'unique. `COALESCE(..., 0)` fait
        // participant le NULL à l'unicité, l'id 0 n'existant pas en base.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_obj_eleve_periode_enseignant
            ON objectif_pedagogiques (eleve_id, periode_id, COALESCE(enseignant_id, 0))
        SQL);

        if ($orphelins > 0) {
            fwrite(STDERR, sprintf(
                "D-050 : %d objectif(s) pédagogique(s) supprimé(s) — aucune période comptable disponible.\n",
                $orphelins
            ));
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_obj_eleve_periode_enseignant');

        Schema::table('objectif_pedagogiques', function (Blueprint $table) {
            $table->index('eleve_id', 'idx_obj_temp');
        });

        Schema::table('objectif_pedagogiques', function (Blueprint $table) {
            $table->dropIndex('idx_obj_eleve_periode');
            $table->dropIndex('idx_obj_enseignant');
            $table->dropIndex('idx_obj_temp');
            $table->dropColumn('periode');
        });

        Schema::table('objectif_pedagogiques', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropForeign(['enseignant_id']);
            $table->dropColumn(['periode_id', 'enseignant_id']);
            $table->string('periode')->nullable()->after('eleve_id');
        });
    }

    /**
     * Premier enseignant affecté à l'élève (via un contrat actif).
     */
    private function enseignantDeLEleve(?int $eleveId): ?int
    {
        if (! $eleveId) {
            return null;
        }

        $ligne = DB::table('affectation_enseignants as a')
            ->join('contrat_cours as c', 'c.id', '=', 'a.contrat_cours_id')
            ->where('c.eleve_id', $eleveId)
            ->where('a.statut', 'actif')
            ->orderBy('a.id')
            ->select('a.enseignant_id')
            ->first();

        return $ligne?->enseignant_id;
    }
};