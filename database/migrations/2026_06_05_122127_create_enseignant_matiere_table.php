<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignant_matiere', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_profil_id')
                ->constrained('enseignant_profils')
                ->onDelete('cascade');

            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignant_matiere');
    }
};