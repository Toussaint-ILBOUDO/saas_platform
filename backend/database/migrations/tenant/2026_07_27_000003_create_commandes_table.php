<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nom_client');
            $table->string('telephone_client');
            $table->text('adresse_livraison');
            $table->string('quartier')->nullable();
            $table->boolean('is_livraison')->default(false);
            $table->decimal('montant_total', 12, 2);
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->string('statut', 30)->default('en_attente')->index();
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference_transaction')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
