<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favori_bibliotheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_bibliotheque_id')->constrained('document_bibliotheques')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'document_bibliotheque_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favori_bibliotheques');
    }
};
