<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('sigle', 25)->nullable();
            $table->timestamps();
        });

        Schema::table('document_bibliotheques', function (Blueprint $table) {
            $table->foreignId('periode_id')->nullable()->after('matiere_id')->constrained('periode_documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_bibliotheques', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
        });

        Schema::dropIfExists('periode_documents');
    }
};
