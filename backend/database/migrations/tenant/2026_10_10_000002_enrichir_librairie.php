<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enrichissement du module librairie (mission finale).
 *
 * Commandes : coordonnées client complètes (prénom, point de repère,
 * géolocalisation), note interne du personnel, demande d'annulation tracée,
 * clé d'idempotence pour empêcher les doubles commandes (clics répétés).
 *
 * Produits : référence commerciale unique, prix promotionnel, stock géré,
 * archivage (un produit commandé ne disparaît jamais de l'historique).
 *
 * parametres_librairie : réglages propres à la structure commerciale
 * (sigle de commande, frais de livraison, délégation d'accès de
 * l'administrateur général aux données commerciales).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Le retrait sur place n'a pas d'adresse : la colonne était
            // NOT NULL dans la migration d'origine.
            $table->text('adresse_livraison')->nullable()->change();
            $table->string('prenom_client', 100)->nullable()->after('nom_client');
            $table->string('point_de_repere', 255)->nullable()->after('quartier');
            $table->string('lien_geolocalisation', 500)->nullable()->after('point_de_repere');
            $table->text('note_interne')->nullable()->after('notes');
            $table->timestamp('annulation_demandee_at')->nullable()->after('note_interne');
            $table->string('annulation_motif', 500)->nullable()->after('annulation_demandee_at');
            $table->string('cle_idempotence', 64)->nullable()->after('token');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->unique('cle_idempotence');
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->string('reference', 60)->nullable()->after('slug');
            $table->decimal('prix_promo', 10, 2)->nullable()->after('prix');
            $table->unsignedInteger('stock')->nullable()->after('frais_livraison');
            $table->boolean('est_archive')->default(false)->after('is_active');
        });

        Schema::table('produits', function (Blueprint $table) {
            $table->unique('reference');
            $table->index('est_archive');
        });

        Schema::create('parametres_librairie', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 100)->unique();
            $table->text('valeur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_librairie');

        Schema::table('produits', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropIndex(['est_archive']);
            $table->dropColumn(['reference', 'prix_promo', 'stock', 'est_archive']);
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropUnique(['cle_idempotence']);
            $table->dropColumn([
                'prenom_client',
                'point_de_repere',
                'lien_geolocalisation',
                'note_interne',
                'annulation_demandee_at',
                'annulation_motif',
                'cle_idempotence',
            ]);
            $table->text('adresse_livraison')->nullable(false)->change();
        });
    }
};
