<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_bibliotheque_id')->constrained('document_bibliotheques')->cascadeOnDelete();
            $table->tinyInteger('note')->unsigned();
            $table->timestamps();

            $table->unique(['user_id', 'document_bibliotheque_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_notes');
    }
};
