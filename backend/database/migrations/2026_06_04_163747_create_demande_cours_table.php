<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demande_cours', function (Blueprint $table) {
            $table->id();

            // Infos visiteur (pas forcément user connecté)
            $table->string('nom_parent');
            $table->string('prenom_parent')->nullable();
            $table->string('telephone');

            // Relations pédagogiques
            $table->foreignId('type_cours_id')
                ->constrained('type_cours')
                ->cascadeOnDelete();

            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->cascadeOnDelete();

            $table->foreignId('classe_id')
                ->constrained('classes')
                ->cascadeOnDelete();

            // Logique métier
            $table->integer('volume_horaire_estime')->nullable();

            $table->string('statut')->default('en_attente');
            // en_attente, traitee, annulee

            $table->text('message')->nullable(); // besoin libre du parent

            $table->timestamps();

            // INDEX
            $table->index('statut', 'idx_demande_statut');
            $table->index('type_cours_id', 'idx_demande_type_cours');
            $table->index('matiere_id', 'idx_demande_matiere');
            $table->index('classe_id', 'idx_demande_classe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_cours');
    }
};