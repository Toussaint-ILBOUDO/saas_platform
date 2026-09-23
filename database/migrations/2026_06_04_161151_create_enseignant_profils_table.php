<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignant_profils', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('numero_orange_money')->nullable();

            $table->string('diplome_max')->nullable();
            $table->string('lieu_de_service')->nullable();
            $table->string('domicile')->nullable();

            $table->boolean('frais_annuel_regle')
                ->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignant_profils');
    }
};