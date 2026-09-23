<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();

            // Si l'élève a un compte utilisateur
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            // Parent (obligatoire dans ton système métier)
            $table->foreignId('parent_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Classe
            $table->foreignId('classe_id')
                ->constrained('classes')
                ->cascadeOnDelete();

            // Infos scolaires
            $table->string('ecole')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();

            $table->string('parent_charge')->nullable();
            $table->string('etablissement_origine')->nullable();

            $table->string('profession_pere')->nullable();
            $table->string('profession_mere')->nullable();

            $table->string('regime_etude')->nullable(); // interne / externe
            $table->string('loisirs_sport')->nullable();
            $table->string('religion_enfant')->nullable();

            $table->text('maladies_allergies')->nullable();
            $table->text('interdits_familiaux')->nullable();

            $table->string('boisson_preferee')->nullable();
            $table->string('nourriture_preferee')->nullable();

            $table->text('autres_precautions')->nullable();
            $table->text('autres_observations')->nullable();

            $table->timestamps();

            // =========================
            // INDEX (important perf)
            // =========================
            $table->index('parent_id', 'idx_eleves_parent');
            $table->index('classe_id', 'idx_eleves_classe');
            $table->index('user_id', 'idx_eleves_user');

            // =========================
            // FOREIGN KEYS NOMMÉES
            // =========================

            $table->foreign('user_id', 'fk_eleves_user')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('parent_id', 'fk_eleves_parent')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('classe_id', 'fk_eleves_classe')
                ->references('id')
                ->on('classes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eleves');
    }
};