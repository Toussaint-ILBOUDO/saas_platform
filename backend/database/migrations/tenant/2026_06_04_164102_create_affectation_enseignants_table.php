<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affectation_enseignants', function (Blueprint $table) {
            $table->id();

            // Contrat lié
            $table->foreignId('contrat_cours_id')
                ->constrained('contrat_cours')
                ->cascadeOnDelete();

            // Enseignant (profil)
            $table->foreignId('enseignant_id')
                ->constrained('enseignant_profils')
                ->cascadeOnDelete();

            // Matière enseignée
            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->cascadeOnDelete();

            // Logique métier
            $table->integer('taux_horaire_enseignant')->default(0);
            $table->decimal('nombre_heures_prevues', 5, 2)->default(0);

            $table->date('date_affectation');
            $table->date('date_fin')->nullable();

            $table->string('statut')->default('actif');
            // actif, suspendu, termine

            $table->timestamps();

            // INDEX
            $table->index('contrat_cours_id', 'idx_affectation_contrat');
            $table->index('enseignant_id', 'idx_affectation_enseignant');
            $table->index('matiere_id', 'idx_affectation_matiere');
            $table->index('statut', 'idx_affectation_statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectation_enseignants');
    }
};