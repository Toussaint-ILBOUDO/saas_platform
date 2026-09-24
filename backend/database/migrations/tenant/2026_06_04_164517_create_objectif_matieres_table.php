<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objectif_matieres', function (Blueprint $table) {
            $table->id();

            // Lien objectif global
            $table->foreignId('objectif_pedagogique_id')
                ->constrained('objectif_pedagogiques')
                ->cascadeOnDelete();

            // Matière concernée
            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->cascadeOnDelete();

            // Objectifs
            $table->decimal('moyenne_visee', 6, 2)->default(0);
            $table->decimal('moyenne_obtenue', 6, 2)->nullable();

            $table->text('commentaire')->nullable();

            $table->timestamps();

            // INDEX
            $table->index('objectif_pedagogique_id', 'idx_obj_mat_objglobal');
            $table->index('matiere_id', 'idx_obj_mat_matiere');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objectif_matieres');
    }
};