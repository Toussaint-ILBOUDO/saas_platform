<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapport_mensuel_enseignants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contrat_cours_id')
                ->constrained('contrat_cours')
                ->cascadeOnDelete();

            $table->foreignId('enseignant_id');
            $table->foreign('enseignant_id')
                ->references('id')
                ->on('enseignant_profils')
                ->cascadeOnDelete();

            $table->foreignId('periode_id');
            $table->foreign('periode_id')
                ->references('id')
                ->on('periode_comptables')
                ->cascadeOnDelete();

            $table->decimal('volume_horaire_cumule', 5, 2)->default(0);

            $table->text('point_notes_matieres')->nullable();
            $table->text('point_notes_autres_matieres')->nullable();
            $table->text('bilan_activites')->nullable();
            $table->text('difficultes_rencontrees')->nullable();
            $table->text('solutions_trouvees')->nullable();
            $table->text('attentes_parents_eleve')->nullable();
            $table->text('attentes_administration')->nullable();
            $table->text('appreciation_evolution')->nullable();
            $table->text('observations')->nullable();

            $table->string('statut')->default('soumis');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapport_mensuel_enseignants');
    }
};