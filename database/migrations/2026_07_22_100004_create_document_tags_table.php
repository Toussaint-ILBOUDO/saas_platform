<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_bibliotheque_id')->constrained('document_bibliotheques')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['document_bibliotheque_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_tags');
    }
};
