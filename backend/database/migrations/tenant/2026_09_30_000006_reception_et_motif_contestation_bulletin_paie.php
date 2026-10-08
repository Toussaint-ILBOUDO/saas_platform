<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D-052 — Fin du cycle de paie enseignant : contestation motivée et
 * confirmation de réception du paiement.
 *
 * KEduc s'arrêtait au statut `verse` : l'enseignant était notifié du versement
 * (texte mort, sans lien) mais ne pouvait ni expliquer ce qu'il conteste ni
 * confirmer avoir reçu l'argent. Le paiement hors plateforme restait donc
 * adossé à une déclaration unilatérale de l'administration, sans clôture.
 *
 * 1. `motif_contestation` : catégorie de contestation, contrôlée par l'
 *    application (liste fermée sur le modèle). Un commentaire libre de 1 000
 *    caractères ne permet ni de trier les contestations ni de voir d'où
 *    viennent les litiges récurrents.
 * 2. `date_reception` + `recu_par` : l'enseignant confirme avoir reçu son
 *    paiement. Tant que cette confirmation manque, le bulletin payé reste
 *    « en attente de réception » — visible dans la liste de paie de
 *    l'administration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletins_paie', function (Blueprint $table) {
            $table->string('motif_contestation', 50)
                ->nullable()
                ->after('commentaire_enseignant');

            $table->timestamp('date_reception')
                ->nullable()
                ->after('reference_paiement');

            $table->foreignId('recu_par')
                ->nullable()
                ->after('date_reception')
                ->constrained('users')
                ->nullOnDelete();

            // Les bulletins payés dont l'enseignant n'a pas encore confirmé la
            // réception : c'est le suivi des versements en attente.
            $table->index(
                ['statut', 'date_reception'],
                'idx_bulletins_attente_reception'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bulletins_paie', function (Blueprint $table) {
            $table->dropIndex('idx_bulletins_attente_reception');
            $table->dropForeign(['recu_par']);
            $table->dropColumn(['motif_contestation', 'date_reception', 'recu_par']);
        });
    }
};