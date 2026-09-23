<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Ajoute un jeton d'accès aux commandes.
     *
     * Ce jeton, généré aléatoirement à la création de la commande, permet de
     * sécuriser l'accès aux pages de confirmation et au PDF des commandes
     * passées par un visiteur non connecté (user_id NULL). Il remplace le
     * simple identifiant séquentiel, trop prédictible (faible IDOR).
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('token', 64)
                ->nullable()
                ->index()
                ->after('frais_livraison');
        });

        // Rétro-remplissage : chaque commande existante reçoit un jeton unique.
        DB::table('commandes')->orderBy('id')->chunkById(200, function ($commandes) {
            foreach ($commandes as $commande) {
                DB::table('commandes')
                    ->where('id', $commande->id)
                    ->update(['token' => Str::random(64)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropIndex(['token']);
            $table->dropColumn('token');
        });
    }
};
