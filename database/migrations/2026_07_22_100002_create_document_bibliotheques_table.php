<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_bibliotheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->text('resume')->nullable();
            $table->foreignId('type_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('matiere_id')->nullable()->constrained()->nullOnDelete();
            $table->string('periode', 50)->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('statut', 20)->default('brouillon')->index();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('nb_vues')->default(0);
            $table->unsignedBigInteger('nb_telechargements')->default(0);
            $table->decimal('note_moyenne', 3, 2)->default(0);
            $table->unsignedInteger('nb_notes')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_bibliotheques');
    }
};
