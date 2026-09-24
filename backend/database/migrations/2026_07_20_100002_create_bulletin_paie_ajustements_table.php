<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_paie_ajustements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bulletin_paie_id')
                ->constrained('bulletins_paie')
                ->cascadeOnDelete()
                ->name('fk_bpa_bulletin')
                ->index();

            $table->string('type');
            $table->string('libelle');
            $table->integer('montant');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_paie_ajustements');
    }
};
