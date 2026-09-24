<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();

            // CONTRAT
            $table->foreignId('contrat_cours_id');
            $table->foreign('contrat_cours_id', 'factures_contrat_fk')
                ->references('id')
                ->on('contrat_cours')
                ->cascadeOnDelete();

            // PARENT (USER)
            $table->foreignId('parent_id');
            $table->foreign('parent_id', 'factures_parent_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // ELEVE
            $table->foreignId('eleve_id');
            $table->foreign('eleve_id', 'factures_eleve_fk')
                ->references('id')
                ->on('eleves')
                ->cascadeOnDelete();

            // PERIODE
            $table->foreignId('periode_id');
            $table->foreign('periode_id', 'factures_periode_fk')
                ->references('id')
                ->on('periode_comptables')
                ->cascadeOnDelete();

            // DATA
            $table->string('numero_facture')->unique();

            $table->integer('volume_horaire_total')->default(0);
            $table->integer('autres_frais')->default(0);
            $table->integer('montant_total')->default(0);

            $table->string('statut_paiement')->default('en_attente')->index();

            $table->timestamp('date_paiement')->nullable();
            $table->string('mode_paiement')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};