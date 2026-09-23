<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_bibliotheques', function (Blueprint $table) {
            $table->unsignedInteger('nombre_favoris')->default(0)->after('nb_notes');
        });
    }

    public function down(): void
    {
        Schema::table('document_bibliotheques', function (Blueprint $table) {
            $table->dropColumn('nombre_favoris');
        });
    }
};
