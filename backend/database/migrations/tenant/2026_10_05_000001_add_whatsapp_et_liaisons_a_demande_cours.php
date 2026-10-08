<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D-051 — Enchaînement commercial d'une demande de cours.
 *
 * Trois besoins de l'écran « Demandes de cours » :
 *
 *  1. **Contact WhatsApp.** `demande_cours` n'avait qu'un `telephone` unique.
 *     `users` distingue déjà `telephone_whatsapp` / `telephone_appel` : on
 *     aligne la demande sur la même convention, en nullable. Le numéro n'est
 *     pas rendu obligatoire à la saisie (le parent peut n'avoir qu'un fixe) et
 *     l'écran d'administration retombe sur `telephone` quand il est absent —
 *     sinon toutes les demandes déjà enregistrées deviendraient injoignables.
 *
 *  2. **Traçabilité des entités créées.** Une demande doit pouvoir devenir un
 *     dossier réel : parent → élève → contrat. On mémorise les clés créées sur
 *     la demande elle-même. C'est ce qui rend les trois actions idempotentes
 *     (un double-clic ne doit pas créer deux parents) et ce qui permet à la
 *     fiche d'afficher « parent déjà créé » au lieu de reproposer l'action.
 *
 *  3. `parent_id` référence `users.id` et non `parent_profils.id` : les deux
 *     sont des séquences indépendantes, et c'est `users.id` que
 *     `eleves.parent_id` référence (cf. `ContratCoursPolicy::view`).
 *     `nullOnDelete` : une demande ne doit pas être supprimée parce qu'un
 *     parent l'a été.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demande_cours', function (Blueprint $table): void {
            $table->string('telephone_whatsapp')->nullable()->after('telephone');

            $table->foreignId('parent_id')
                ->nullable()
                ->after('classe_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('eleve_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('eleves')
                ->nullOnDelete();

            $table->foreignId('contrat_cours_id')
                ->nullable()
                ->after('eleve_id')
                ->constrained('contrat_cours')
                ->nullOnDelete();

            $table->index('parent_id', 'idx_demande_parent');
            $table->index('eleve_id', 'idx_demande_eleve');
        });
    }

    public function down(): void
    {
        Schema::table('demande_cours', function (Blueprint $table): void {
            $table->dropIndex('idx_demande_parent');
            $table->dropIndex('idx_demande_eleve');

            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('eleve_id');
            $table->dropConstrainedForeignId('contrat_cours_id');

            $table->dropColumn('telephone_whatsapp');
        });
    }
};