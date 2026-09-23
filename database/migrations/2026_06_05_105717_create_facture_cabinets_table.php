<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facture_cabinets', function (Blueprint $table) {
            $table->id();

            $table->date('periode_debut');
            $table->date('periode_fin');

            $table->integer('total_cours')->default(0);
            $table->integer('total_ventes')->default(0);
            $table->integer('total_inscriptions')->default(0);

            $table->integer('montant_commission')->default(0);
            $table->integer('montant_total_du')->default(0);

            $table->string('statut')->default('en_attente');

            $table->date('date_facture');

            $table->date('date_paiement')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_cabinets');
    }
};