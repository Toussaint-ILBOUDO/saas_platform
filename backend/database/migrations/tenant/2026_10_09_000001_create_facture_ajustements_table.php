<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facture_ajustements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('facture_id')
                ->constrained('factures')
                ->cascadeOnDelete()
                ->name('fk_facture_ajustements_facture')
                ->index();

            $table->foreignId('type_ajustement_id')
                ->nullable()
                ->after('facture_id')
                ->constrained('type_ajustements')
                ->nullOnDelete();

            // Direction du mouvement : 'credit' (bonus, remise supplémentaire)
            // ou 'debit' (frais, retenue). Dénormalisé depuis le type pour
            // rester lisible même si le type est supprimé.
            $table->string('type');
            $table->string('libelle');
            $table->integer('montant');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_ajustements');
    }
};
