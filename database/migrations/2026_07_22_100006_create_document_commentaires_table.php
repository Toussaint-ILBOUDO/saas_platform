<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_commentaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_bibliotheque_id')->constrained('document_bibliotheques')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('document_commentaires')->cascadeOnDelete();
            $table->text('contenu');
            $table->boolean('signale')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_commentaires');
    }
};
