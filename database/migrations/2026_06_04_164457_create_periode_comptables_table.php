<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_comptables', function (Blueprint $table) {
            $table->id();

            $table->string('label');
            $table->date('date_debut');
            $table->date('date_fin');

            $table->string('type')->default('mensuel');
            $table->string('statut')->default('ouverte');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_comptables');
    }
};