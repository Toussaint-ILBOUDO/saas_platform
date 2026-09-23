<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiement_enseignants', function (Blueprint $table) {

            $table->foreignId('contrat_cours_id')
                ->after('enseignant_id')
                ->constrained('contrat_cours')
                ->cascadeOnDelete();

            $table->unique(
                [
                    'enseignant_id',
                    'contrat_cours_id',
                    'periode_id'
                ],
                'paiement_enseignant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('paiement_enseignants', function (Blueprint $table) {

            $table->dropUnique(
                'paiement_enseignant_unique'
            );

            $table->dropConstrainedForeignId(
                'contrat_cours_id'
            );
        });
    }
};