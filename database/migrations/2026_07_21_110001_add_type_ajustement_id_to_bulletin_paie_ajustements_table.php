<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletin_paie_ajustements', function (Blueprint $table) {
            $table->foreignId('type_ajustement_id')
                ->nullable()
                ->after('bulletin_paie_id')
                ->constrained('type_ajustements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_paie_ajustements', function (Blueprint $table) {
            $table->dropForeign(['type_ajustement_id']);
            $table->dropColumn('type_ajustement_id');
        });
    }
};
