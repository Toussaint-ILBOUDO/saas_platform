<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramètres globaux de la plateforme (T2.10) — clé/valeur (connexion centrale).
 * Ex. acces_ecrans_web_keduc (interrupteur B4 de délivrance du web KEduc).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_plateforme', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->json('valeur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_plateforme');
    }
};