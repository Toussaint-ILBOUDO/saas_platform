<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();

            $table->string('nom');
            $table->string('sigle', 25)->nullable();

            $table->text('description')->nullable();

            $table->boolean('actif')->default(true);

            $table->timestamps();

            // indexes
            $table->index('nom', 'idx_matieres_nom');
            $table->index('sigle', 'idx_matieres_sigle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matieres');
    }
};