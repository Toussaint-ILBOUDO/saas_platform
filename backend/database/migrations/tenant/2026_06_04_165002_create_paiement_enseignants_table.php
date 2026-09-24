<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiement_enseignants', function (Blueprint $table) {
            $table->id();

            // FK: enseignant
            $table->foreignId('enseignant_id')
                ->constrained('enseignant_profils')
                ->cascadeOnDelete()
                ->index();

            // FK: période (VERSION EXPLICITE COMME TU AS DEMANDÉ)
            $table->foreignId('periode_id')->index();

            $table->foreign('periode_id', 'paiement_enseignants_periode_fk')
                ->references('id')
                ->on('periode_comptables')
                ->cascadeOnDelete();

            // données métier
            $table->decimal('total_heures_effectuees', 8, 2)->default(0);
            $table->integer('montant_total')->default(0);

            $table->string('statut')->default('en_attente')->index();

            $table->string('transaction_reference')->nullable();
            $table->timestamp('date_paiement')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiement_enseignants');
    }
};