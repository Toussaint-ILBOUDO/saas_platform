<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_cours', function (Blueprint $table) {
            $table->id();

            $table->string('libelle'); // A domicile, en ligne, renforcement
            $table->string('code')->nullable(); // optionnel (DOM, ONLINE, etc.)
            $table->text('description')->nullable();

            $table->boolean('actif')->default(true);

            $table->timestamps();

            // indexes
            $table->index('libelle', 'idx_type_cours_libelle');
            $table->index('code', 'idx_type_cours_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_cours');
    }
};