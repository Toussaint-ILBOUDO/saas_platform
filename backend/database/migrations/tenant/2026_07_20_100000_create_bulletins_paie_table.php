<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletins_paie', function (Blueprint $table) {
            $table->id();

            $table->string('numero')->unique();

            $table->foreignId('enseignant_id')
                ->constrained('enseignant_profils')
                ->cascadeOnDelete()
                ->name('fk_bp_enseignant')
                ->index();

            $table->foreignId('periode_id')
                ->constrained('periode_comptables')
                ->cascadeOnDelete()
                ->name('fk_bp_periode')
                ->index();

            $table->decimal('total_heures', 8, 2)->default(0);
            $table->integer('montant_brut')->default(0);
            $table->integer('frais_suivi')->default(5000);
            $table->integer('montant_net')->default(0);

            $table->string('statut')->default('brouillon')->index();

            $table->text('commentaire_enseignant')->nullable();

            $table->timestamp('date_consultation')->nullable();
            $table->timestamp('date_validation')->nullable();
            $table->timestamp('date_paiement')->nullable();

            $table->string('mode_paiement')->nullable();
            $table->string('reference_paiement')->nullable();

            $table->timestamps();

            $table->unique(
                ['enseignant_id', 'periode_id'],
                'uniq_bp_enseignant_periode'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletins_paie');
    }
};
