<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objectif_pedagogiques', function (Blueprint $table) {
            $table->id();

            // Élève concerné
            $table->foreignId('eleve_id')
                ->constrained('eleves')
                ->cascadeOnDelete();

            // Période (Trimestre, semestre, etc.)
            $table->string('periode'); 
            // ex: Trimestre 1, Trimestre 2...

            // Objectifs globaux
            $table->decimal('moyenne_visee', 6, 2)->default(0);
            $table->decimal('moyenne_obtenue', 6, 2)->nullable();

            // Ressources pédagogiques
            $table->text('materiel_disponible')->nullable();
            $table->text('materiel_manquant')->nullable();

            $table->text('commentaire_admin')->nullable();

            $table->timestamps();

            // INDEX
            $table->index('eleve_id', 'idx_obj_eleve');
            $table->index('periode', 'idx_obj_periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objectif_pedagogiques');
    }
};