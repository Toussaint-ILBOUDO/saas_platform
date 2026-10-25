<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Réactions « J'aime / Je n'aime pas » sur les documents de la bibliothèque.
 *
 * Une réaction est rattachée à une IDENTITÉ, pas forcément à un compte :
 *  - `user_id` renseigné  → membre connecté ;
 *  - `visiteur_id` renseigné → visiteur anonyme (identifiant de session,
 *    généré côté serveur, conservé 120 min comme la session).
 *
 * La colonne `identifiant` (« u:12 » / « v:<uuid> ») matérialise cette
 * identité en une seule chaîne : une contrainte d'unicité unique et simple
 * garantit qu'une même identité ne peut porter qu'une seule réaction par
 * document (remplacement ou retrait possibles, doublons impossibles).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_bibliotheque_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Identité « u:<user_id> » ou « v:<uuid de session> ».
            $table->string('identifiant', 64);

            $table->string('reaction', 20); // aime | n_aime_pas

            $table->timestamps();

            $table->unique(['document_bibliotheque_id', 'identifiant'], 'document_reactions_doc_identite_unique');
            $table->index(['document_bibliotheque_id', 'reaction'], 'document_reactions_doc_reaction_index');
        });

        // Compteurs de tête lus par les cartes publiques : toujours
        // recalculés par le service, jamais incrémentés depuis le client.
        Schema::table('document_bibliotheques', function (Blueprint $table) {
            $table->unsignedInteger('nb_aimes')->default(0)->after('nombre_favoris');
            $table->unsignedInteger('nb_n_aimes')->default(0)->after('nb_aimes');
        });
    }

    public function down(): void
    {
        Schema::table('document_bibliotheques', function (Blueprint $table) {
            $table->dropColumn(['nb_aimes', 'nb_n_aimes']);
        });

        Schema::dropIfExists('document_reactions');
    }
};
