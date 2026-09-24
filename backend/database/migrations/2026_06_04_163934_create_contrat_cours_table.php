<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrat_cours', function (Blueprint $table) {
            $table->id();

            // Élève concerné
            $table->foreignId('eleve_id')
                ->constrained('eleves')
                ->cascadeOnDelete();

            // Type de cours (domicile, en ligne, etc.)
            $table->foreignId('type_cours_id')
                ->constrained('type_cours')
                ->cascadeOnDelete();

            // Suivi administratif
            $table->integer('autres_frais_suivi')->default(0);

            $table->string('statut')->default('actif');
            // actif, suspendu, termine

            $table->date('date_debut');
            $table->date('date_fin')->nullable();

            $table->text('notes_admin')->nullable();

            $table->timestamps();

            // INDEX
            $table->index('eleve_id', 'idx_contrat_eleve');
            $table->index('type_cours_id', 'idx_contrat_type');
            $table->index('statut', 'idx_contrat_statut');
            $table->index('date_debut', 'idx_contrat_date_debut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrat_cours');
    }
};