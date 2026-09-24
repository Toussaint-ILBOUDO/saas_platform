<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramètres de facturation (+ fonctionnalités actives) d'un cabinet
 * (base centrale Landlord). Un tuple par cabinet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_cabinet', function (Blueprint $table) {
            $table->id();
            $table->string('cabinet_id')->unique();
            $table->decimal('tarif_abonnement', 10, 2)->default(0);
            $table->decimal('tarif_par_eleve', 10, 2)->default(0);
            $table->json('fonctionnalites_activees')->nullable();
            $table->timestamps();

            $table->foreign('cabinet_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_cabinet');
    }
};