<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_paie_lignes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bulletin_paie_id')
                ->constrained('bulletins_paie')
                ->cascadeOnDelete()
                ->name('fk_bpl_bulletin')
                ->index();

            $table->foreignId('affectation_enseignant_id')
                ->constrained('affectation_enseignants')
                ->cascadeOnDelete()
                ->name('fk_bpl_affectation');

            $table->foreignId('contrat_cours_id')
                ->constrained('contrat_cours')
                ->cascadeOnDelete()
                ->name('fk_bpl_contrat');

            $table->foreignId('eleve_id')
                ->constrained('eleves')
                ->cascadeOnDelete()
                ->name('fk_bpl_eleve');

            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->cascadeOnDelete()
                ->name('fk_bpl_matiere');

            $table->decimal('nombre_heures', 8, 2);
            $table->integer('taux_horaire');
            $table->integer('montant');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_paie_lignes');
    }
};
