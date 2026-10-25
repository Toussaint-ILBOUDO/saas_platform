<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit du cabinet (base tenant) — M13.
 *
 * Trace les actions sensibles (factures, bulletins, contrats, utilisateurs,
 * modération…). Aligné sur `journal_plateforme` : `created_at` seul, pas de FK
 * cassante côté entité (une entité supprimée laisse sa trace, le libellé est
 * figé dans `contexte`), FK utilisateur nulle à la suppression du compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_activites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('action', 100);
            $table->string('description', 255)->nullable();
            $table->string('level', 20)->default('info');
            $table->string('entite_type', 100)->nullable();
            $table->unsignedBigInteger('entite_id')->nullable();
            $table->json('contexte')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index('level');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['entite_type', 'entite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_activites');
    }
};
