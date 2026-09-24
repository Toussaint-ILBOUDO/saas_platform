<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiement_cabinets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('facture_cabinet_id')
                ->constrained('facture_cabinets')
                ->cascadeOnDelete();

            $table->integer('montant_paye');

            $table->string('mode_paiement');

            $table->string('reference_paiement')
                ->nullable();

            $table->date('date_paiement');

            $table->enum('statut', [
                'en_attente',
                'valide',
                'annule'
            ])->default('en_attente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiement_cabinets');
    }
};