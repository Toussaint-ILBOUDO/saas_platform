<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partie 06 — Index de performance.
 *
 * Justifiés par des requêtes fréquentes filtrant sur ces colonnes
 * (FK non indexées sur PostgreSQL) :
 *  - notifications(user_id)            : composer panel + NotificationController
 *                                        (paginate / unreadCount / markAllAsRead) à chaque page.
 *  - commandes(user_id)                : "mes commandes" (LibrairieService::getCommandesForUser).
 *  - temoignages(user_id)              : témoignages d'un utilisateur (profil).
 *  - temoignage_commentaires(temoignage_id) : liste des commentaires d'un témoignage.
 *  - document_commentaires(document_bibliotheque_id) : liste des commentaires d'un document.
 *  - document_notes(document_bibliotheque_id)        : statistiques de notes par document.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id', 'idx_notifications_user_id');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->index('user_id', 'idx_commandes_user_id');
        });

        Schema::table('temoignages', function (Blueprint $table) {
            $table->index('user_id', 'idx_temoignages_user_id');
        });

        Schema::table('temoignage_commentaires', function (Blueprint $table) {
            $table->index('temoignage_id', 'idx_temoignage_commentaires_temoignage_id');
        });

        Schema::table('document_commentaires', function (Blueprint $table) {
            $table->index('document_bibliotheque_id', 'idx_document_commentaires_document_id');
        });

        Schema::table('document_notes', function (Blueprint $table) {
            $table->index('document_bibliotheque_id', 'idx_document_notes_document_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_id');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropIndex('idx_commandes_user_id');
        });

        Schema::table('temoignages', function (Blueprint $table) {
            $table->dropIndex('idx_temoignages_user_id');
        });

        Schema::table('temoignage_commentaires', function (Blueprint $table) {
            $table->dropIndex('idx_temoignage_commentaires_temoignage_id');
        });

        Schema::table('document_commentaires', function (Blueprint $table) {
            $table->dropIndex('idx_document_commentaires_document_id');
        });

        Schema::table('document_notes', function (Blueprint $table) {
            $table->dropIndex('idx_document_notes_document_id');
        });
    }
};
