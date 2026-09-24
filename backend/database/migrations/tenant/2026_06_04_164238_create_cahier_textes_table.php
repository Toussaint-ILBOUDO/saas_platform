<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cahier_textes', function (Blueprint $table) {
            $table->id();

            // Identifiant métier stable côté client (UUID), unique par cabinet.
            $table->uuid('uuid_client')->nullable()->unique();

            // Affectation (cours réel du prof)
            $table->foreignId('affectation_enseignant_id')
                ->constrained('affectation_enseignants')
                ->cascadeOnDelete();

            // Séance
            $table->date('date_seance');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();

            $table->decimal('duree_heures', 5, 2)->default(0);

            // Contenu pédagogique
            $table->text('contenu_cours');
            $table->text('objectifs_atteints')->nullable();
            $table->text('observations')->nullable();

            // Validation admin (optionnel mais puissant)
            $table->boolean('valide_admin')->default(false);

            $table->timestamps();

            // INDEX
            $table->index('affectation_enseignant_id', 'idx_cahier_affectation');
            $table->index('date_seance', 'idx_cahier_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cahier_textes');
    }
};