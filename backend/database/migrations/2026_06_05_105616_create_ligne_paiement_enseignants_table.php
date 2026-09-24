<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_paiement_enseignants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paiement_enseignant_id')
                ->constrained('paiement_enseignants')
                ->cascadeOnDelete();

            $table->foreignId('affectation_enseignant_id')
                ->constrained('affectation_enseignants')
                ->cascadeOnDelete();

            $table->decimal('nombre_heures', 5, 2);

            $table->integer('montant');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_paiement_enseignants');
    }
};