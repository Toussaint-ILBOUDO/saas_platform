<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demande_cours', function (Blueprint $table) {
            $table->dropForeign(['matiere_id']);
            $table->dropColumn('matiere_id');
        });
    }

    public function down(): void
    {
        Schema::table('demande_cours', function (Blueprint $table) {
            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->cascadeOnDelete();
        });
    }
};