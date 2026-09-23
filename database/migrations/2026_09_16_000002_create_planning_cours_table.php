<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créneaux de cours récurrents planifiés par un enseignant.
     *
     * Un créneau est rattaché à une affectation (élève + matière + contrat) :
     * il se répète chaque semaine (jour_semaine 1 = Lundi … 7 = Dimanche).
     */
    public function up(): void
    {
        Schema::create('planning_cours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')
                ->constrained('enseignant_profils')
                ->cascadeOnDelete();

            $table->foreignId('affectation_enseignant_id')
                ->constrained('affectation_enseignants')
                ->cascadeOnDelete();

            $table->tinyInteger('jour_semaine');
            $table->time('heure_debut');
            $table->time('heure_fin');

            $table->timestamps();

            $table->unique(
                ['affectation_enseignant_id', 'jour_semaine', 'heure_debut'],
                'uq_planning_cours_affectation_jour_debut'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_cours');
    }
};