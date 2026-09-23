<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_cours', function (Blueprint $table) {
            $table->id();

            // Élève évalué
            $table->foreignId('eleve_id')
                ->constrained('eleves')
                ->cascadeOnDelete();

            // Enseignant évalué
            $table->foreignId('enseignant_id')
                ->constrained('enseignant_profils')
                ->cascadeOnDelete();

            // Auteur de l’évaluation (parent, admin, etc.)
            $table->foreignId('auteur_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Note
            $table->decimal('note', 3, 1); 
            // ex: 4.5 / 5

            $table->text('commentaire')->nullable();

            // anonymisation optionnelle
            $table->boolean('anonyme')->default(true);

            $table->timestamps();

            // INDEX
            $table->index('eleve_id', 'idx_eval_eleve');
            $table->index('enseignant_id', 'idx_eval_enseignant');
            $table->index('auteur_id', 'idx_eval_auteur');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_cours');
    }
};