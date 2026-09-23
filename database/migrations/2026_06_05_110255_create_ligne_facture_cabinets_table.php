<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_facture_cabinets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('facture_cabinet_id')
                ->constrained('facture_cabinets')
                ->cascadeOnDelete();

            $table->foreignId('type_commission_id')
                ->constrained('type_commissions')
                ->cascadeOnDelete();

            $table->integer('quantite')->default(1);

            $table->integer('base_calcul')->default(0);

            $table->decimal('taux_commission', 5, 2)->default(0);

            $table->integer('montant')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_facture_cabinets');
    }
};