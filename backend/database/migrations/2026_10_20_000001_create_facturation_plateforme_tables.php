<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P8 — Facturation de la plateforme (base centrale Landlord).
 *
 * Tables (§7.5/§7.8, adapté aux décisions Q-A13..Q-A20) :
 * - contrats_cabinet        : contrats commerciaux versionnés par cabinet ;
 * - factures_cabinet        : factures par cabinet et par période ;
 * - evenements_facturables  : journal évènementiel central (bases tenant) ;
 * - lignes_facture_cabinet  : lignes générées (auto) ou manuelles ;
 * - paiements_cabinet       : encaissements partiels/entiers.
 *
 * Conventions (projet) :
 * - `cabinet_id` sans FK vers `tenants` (comme journal_plateforme) : les
 *   données financieres/légales survivent au rollback/suppression du cabinet ;
 * - montants en entiers FCFA ; `sens` = debit(+) / credit(-) ;
 * - statut facture : brouillon|emise|partiellement_payee|payee|annulee.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Contrats commerciaux versionnés (par cabinet).
        Schema::create('contrats_cabinet', function (Blueprint $table) {
            $table->id();
            $table->string('cabinet_id');
            $table->string('numero')->unique();        // CTA-YYYY-NNNNN
            $table->unsignedInteger('version')->default(1);
            $table->string('statut', 20)->default('brouillon')->index(); // brouillon|valide|archive

            // Tarifs négociés (entiers FCFA ; null = non négocié ≠ 0 = gratuit).
            $table->integer('frais_creation_contrat')->nullable();
            $table->integer('frais_contrat_actif')->nullable();
            $table->integer('abonnement_mensuel_enseignant')->nullable();
            $table->integer('abonnement_mensuel_parent')->nullable();
            $table->integer('abonnement_mensuel_eleve')->nullable();
            $table->integer('frais_rapport_mensuel')->nullable();
            $table->integer('abonnement_annuel_admin')->nullable();
            $table->integer('maintenance_annuelle_site')->nullable();
            $table->decimal('taux_commission_librairie', 5, 2)->nullable();
            $table->decimal('taux_commission_autres', 5, 2)->nullable();

            $table->json('exonerations')->nullable();
            $table->text('conditions_particulieres')->nullable();
            $table->text('modalites_paiement')->nullable();
            $table->string('responsable_nom', 120)->nullable();
            $table->date('date_effet')->nullable();
            $table->date('date_fin_validite')->nullable();
            $table->string('chemin_pdf')->nullable();

            $table->unsignedBigInteger('cree_par_user')->nullable();
            $table->unsignedBigInteger('valide_par_user')->nullable();
            $table->timestamp('valide_le')->nullable();
            $table->timestamps();

            $table->unique(['cabinet_id', 'version']);
            $table->index('cabinet_id');

            $table->foreign('cree_par_user')->references('id')->on('super_admins')->nullOnDelete();
            $table->foreign('valide_par_user')->references('id')->on('super_admins')->nullOnDelete();
        });

        // 2) Factures par cabinet et par période.
        Schema::create('factures_cabinet', function (Blueprint $table) {
            $table->id();
            $table->string('cabinet_id')->index();
            $table->string('numero_facture')->nullable()->unique(); // FC-YYYYMM-NNNNN, posé à l'émission
            $table->date('periode_debut');
            $table->date('periode_fin')->index();
            $table->string('statut', 30)->default('brouillon')->index(); // brouillon|emise|partiellement_payee|payee|annulee
            $table->integer('montant_total')->default(0);
            $table->integer('montant_paye')->default(0);
            $table->text('conditions_paiement')->nullable();
            $table->text('note_interne')->nullable();
            $table->timestamp('emise_le')->nullable();
            $table->unsignedBigInteger('emise_par_user')->nullable();
            $table->date('date_echeance')->nullable();
            $table->timestamp('annulee_le')->nullable();
            $table->unsignedBigInteger('annulee_par_user')->nullable();
            $table->text('motif_annulation')->nullable();
            $table->timestamps();

            // Une seule facture (non annulée) par cabinet et période : le
            // contrôle fort de non chevauchement est du ressort du service
            // (Q-A13) ; la régularisation (Q-A16) d'une facture annulée
            // ré-ouvre la même période → l'index partiel est créé APRÈS la
            // table (voir l'index `uq_facture_cabinet_periode` plus bas).

            $table->foreign('emise_par_user')->references('id')->on('super_admins')->nullOnDelete();
            $table->foreign('annulee_par_user')->references('id')->on('super_admins')->nullOnDelete();
        });

        // 3) Journal évènementiel des activités facturables des cabinets
        //    (alimenté par les bases tenant via JournalFacturable::capturer).
        Schema::create('evenements_facturables', function (Blueprint $table) {
            $table->id();
            $table->string('cabinet_id')->index();
            $table->string('type', 50)->index();       // contrat_cree, rapport_valide, vente_librairie, compte_*_active…
            $table->string('reference_type', 50);      // 'contrat', 'rapport', 'commande', 'eleve'…
            $table->string('reference_id');
            $table->date('date_evenement')->index();
            $table->text('libelle')->nullable();
            $table->integer('montant_assiette')->nullable(); // p.ex. sous-total d'une vente (pour commissions)
            $table->json('meta')->nullable();
            $table->string('statut', 20)->default('disponible')->index(); // disponible|facturee
            $table->unsignedBigInteger('facture_cabinet_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Idempotence de la capture.
            $table->unique(['cabinet_id', 'type', 'reference_type', 'reference_id', 'date_evenement'], 'uq_evfact_marqueur');

            $table->foreign('facture_cabinet_id')
                ->references('id')
                ->on('factures_cabinet')
                ->nullOnDelete();
        });

        // 4) Lignes de facture (auto ou manuelle).
        Schema::create('lignes_facture_cabinet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facture_cabinet_id')->index();
            $table->string('type', 50)->index();
            $table->string('sens', 10);                // debit|credit
            $table->string('libelle');
            $table->integer('quantite')->default(1);
            $table->integer('prix_unitaire')->nullable();
            $table->integer('montant')->default(0);
            $table->string('source', 10)->default('auto')->index(); // auto|manuelle
            $table->unsignedBigInteger('evenement_facturable_id')->nullable()->index();
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->json('detail')->nullable();        // p.ex. liste des comptes d'un abonnement
            $table->text('motif')->nullable();         // justification d'une ligne manuelle
            $table->boolean('fiable')->default(true);  // false = « à vérifier » (reconstitution)
            $table->json('calcul')->nullable();        // traçabilité : tarif, période couverte, taux…
            $table->unsignedBigInteger('enregistre_par_user')->nullable();
            $table->timestamps();

            $table->unique(['facture_cabinet_id', 'evenement_facturable_id'], 'uq_lfc_evenement');

            $table->foreign('evenement_facturable_id')
                ->references('id')
                ->on('evenements_facturables')
                ->nullOnDelete();
            $table->foreign('facture_cabinet_id')
                ->references('id')
                ->on('factures_cabinet')
                ->cascadeOnDelete();
            $table->foreign('enregistre_par_user')->references('id')->on('super_admins')->nullOnDelete();
        });

        // 5) Paiements (encaissements d'une facture).
        Schema::create('paiements_cabinet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facture_cabinet_id')->index();
            $table->integer('montant');
            $table->string('mode', 50);                // espèces, transfert, Orange Money…
            $table->string('reference')->nullable();
            $table->timestamp('date_paiement');
            $table->text('commentaire')->nullable();
            $table->unsignedBigInteger('enregistre_par_user')->nullable();
            $table->timestamps();

            $table->foreign('facture_cabinet_id')
                ->references('id')
                ->on('factures_cabinet')
                ->cascadeOnDelete();
            $table->foreign('enregistre_par_user')->references('id')->on('super_admins')->nullOnDelete();
        });

        // Numérotation plateforme (compteurs par (prefixe, clé de période) :
        // contrat `CTA-YYYY-NNNNN`, facture `FC-YYYYMM-NNNNN`). La ligne de
        // compteur n'est jamais supprimée : un numéro n'est jamais réutilisé.
        Schema::create('numerotation_plateforme', function (Blueprint $table) {
            $table->id();
            $table->string('prefixe', 10);             // FC, C…
            $table->string('cle_periode', 10);         // 2026 ou 202610
            $table->unsignedInteger('dernier_numero')->default(0);
            $table->timestamps();

            $table->unique(['prefixe', 'cle_periode']);
        });

        // Idempotence des lignes auto « marquées » par la référence métier
        // (un même événement ne peut pas produire 2 lignes auto dans une
        // même facture). Les lignes manuelles (référence nulle) passent.
        DB::statement(
            'CREATE UNIQUE INDEX uq_lfc_marqueur ON lignes_facture_cabinet '
            .'(facture_cabinet_id, type, reference_type, reference_id) '
            .'WHERE reference_type IS NOT NULL AND reference_id IS NOT NULL'
        );

        // Factures : une seule (non annulée) par cabinet et période —
        // les factures annulées libèrent leur période (Q-A16).
        DB::statement(
            'CREATE UNIQUE INDEX uq_facture_cabinet_periode ON factures_cabinet '
            .'(cabinet_id, periode_debut, periode_fin) '
            ."WHERE statut <> 'annulee'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_cabinet');
        Schema::dropIfExists('lignes_facture_cabinet');
        Schema::dropIfExists('evenements_facturables');
        Schema::dropIfExists('factures_cabinet');
        Schema::dropIfExists('contrats_cabinet');
        Schema::dropIfExists('numerotation_plateforme');
    }
};
