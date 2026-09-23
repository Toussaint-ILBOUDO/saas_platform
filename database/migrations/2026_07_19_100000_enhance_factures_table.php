<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {

            // Modifier volume_horaire_total : integer → decimal(8,2)
            $table->decimal('volume_horaire_total', 8, 2)
                ->default(0)
                ->change();

            // Frais de suivi (distinct des autres_frais)
            $table->integer('frais_suivi')
                ->default(0)
                ->after('volume_horaire_total');

            // Remise éventuelle
            $table->integer('remise')
                ->default(0)
                ->after('autres_frais');

            // Commentaire administratif
            $table->text('commentaire')
                ->nullable()
                ->after('remise');

            // Date limite de paiement
            $table->date('date_limite_paiement')
                ->nullable()
                ->after('mode_paiement');

            // Référence du paiement
            $table->string('reference_paiement')
                ->nullable()
                ->after('date_limite_paiement');

            // Statut paiement enseignants (indépendant du parent)
            $table->string('statut_paiement_enseignants')
                ->default('en_attente')
                ->after('reference_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {

            $table->integer('volume_horaire_total')
                ->default(0)
                ->change();

            $table->dropColumn([
                'frais_suivi',
                'remise',
                'commentaire',
                'date_limite_paiement',
                'reference_paiement',
                'statut_paiement_enseignants',
            ]);
        });
    }
};
