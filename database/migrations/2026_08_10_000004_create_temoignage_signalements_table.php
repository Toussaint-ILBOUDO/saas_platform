<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temoignage_signalements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('temoignage_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motif', 100);
            $table->text('description')->nullable();
            $table->string('statut', 20)
                ->default('en_attente')
                ->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temoignage_signalements');
    }
};
