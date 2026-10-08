<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D-051 — Gel des périodes comptables + traçabilité du cycle de validation.
 *
 * 1. `periode_comptables` : qui a clos et quand, pour que la clôture soit
 *    traçable, plus les index manquants (les écrans trient par `date_debut` et
 *    filtrent par `statut`).
 * 2. `rapport_mensuel_enseignants` : motif de rejet et auteur/date de
 *    validation. KEduc faisait `update(['statut' => 'valide'])` sans rien
 *    tracer, et ne donnait aucun motif à l'enseignant qui était rejeté.
 * 3. `cahier_textes.valide_admin` : suppression. Cette case n'était ni lue ni
 *    écrite nulle part (hors `$fillable`) — elle suggérait une validation des
 *    séances par l'administration qui n'a jamais existé. D-051 reporte la
 *    validation au RAPPORT MENSUEL, où elle est réelle et tracée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periode_comptables', function (Blueprint $table) {
            $table->foreignId('cloturee_par')
                ->nullable()
                ->after('statut')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cloturee_at')->nullable()->after('cloturee_par');
        });

        Schema::table('periode_comptables', function (Blueprint $table) {
            $table->index('statut', 'idx_periodes_statut');
            $table->index('date_debut', 'idx_periodes_date_debut');
        });

        Schema::table('rapport_mensuel_enseignants', function (Blueprint $table) {
            $table->text('motif_rejet')->nullable()->after('statut');
            $table->timestamp('date_validation')->nullable()->after('motif_rejet');
            $table->foreignId('valide_par')
                ->nullable()
                ->after('date_validation')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('statut', 'idx_rapports_statut');
        });

        Schema::table('cahier_textes', function (Blueprint $table) {
            $table->dropColumn('valide_admin');
        });
    }

    public function down(): void
    {
        Schema::table('cahier_textes', function (Blueprint $table) {
            $table->boolean('valide_admin')->default(false);
        });

        Schema::table('rapport_mensuel_enseignants', function (Blueprint $table) {
            $table->dropIndex('idx_rapports_statut');
            $table->dropForeign(['valide_par']);
            $table->dropColumn(['motif_rejet', 'date_validation', 'valide_par']);
        });

        Schema::table('periode_comptables', function (Blueprint $table) {
            $table->dropIndex('idx_periodes_statut');
            $table->dropIndex('idx_periodes_date_debut');
            $table->dropForeign(['cloturee_par']);
            $table->dropColumn(['cloturee_par', 'cloturee_at']);
        });
    }
};