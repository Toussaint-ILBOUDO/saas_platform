<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->index('contrat_cours_id', 'idx_factures_contrat_cours_id');
            $table->index('parent_id', 'idx_factures_parent_id');
            $table->index('eleve_id', 'idx_factures_eleve_id');
            $table->index('periode_id', 'idx_factures_periode_id');
        });

        Schema::table('rapport_mensuel_enseignants', function (Blueprint $table) {
            $table->index('contrat_cours_id', 'idx_rm_contrat_cours_id');
            $table->index('enseignant_id', 'idx_rm_enseignant_id');
            $table->index('periode_id', 'idx_rm_periode_id');
        });

        Schema::table('ligne_factures', function (Blueprint $table) {
            $table->unique(
                ['facture_id', 'affectation_enseignant_id'],
                'uniq_ligne_facture_affectation'
            );
        });

        Schema::table('bulletin_paie_lignes', function (Blueprint $table) {
            $table->unique(
                ['bulletin_paie_id', 'affectation_enseignant_id'],
                'uniq_bpl_bulletin_affectation'
            );
        });

        Schema::table('paiement_cabinets', function (Blueprint $table) {
            $table->index('facture_cabinet_id', 'idx_paiement_cabinet_facture');
        });

        Schema::table('ligne_facture_cabinets', function (Blueprint $table) {
            $table->index('facture_cabinet_id', 'idx_lfc_facture_cabinet');
        });

        Schema::table('document_bibliotheques', function (Blueprint $table) {
            $table->index('user_id', 'idx_doc_biblio_user');
            $table->index('type_document_id', 'idx_doc_biblio_type');
            $table->index('classe_id', 'idx_doc_biblio_classe');
            $table->index('matiere_id', 'idx_doc_biblio_matiere');
            $table->index('periode_id', 'idx_doc_biblio_periode');
        });

        Schema::table('document_commentaires', function (Blueprint $table) {
            $table->index('document_bibliotheque_id', 'idx_doc_commentaires_document');
        });

        Schema::table('document_signalements', function (Blueprint $table) {
            $table->index('document_bibliotheque_id', 'idx_doc_signalements_document');
        });

        Schema::table('demande_cours_matieres', function (Blueprint $table) {
            $table->unique(
                ['demande_cours_id', 'matiere_id'],
                'uniq_dcm_demande_matiere'
            );
        });

        Schema::table('enseignant_matiere', function (Blueprint $table) {
            $table->unique(
                ['enseignant_profil_id', 'matiere_id'],
                'uniq_em_enseignant_matiere'
            );
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropIndex('idx_factures_contrat_cours_id');
            $table->dropIndex('idx_factures_parent_id');
            $table->dropIndex('idx_factures_eleve_id');
            $table->dropIndex('idx_factures_periode_id');
        });

        Schema::table('rapport_mensuel_enseignants', function (Blueprint $table) {
            $table->dropIndex('idx_rm_contrat_cours_id');
            $table->dropIndex('idx_rm_enseignant_id');
            $table->dropIndex('idx_rm_periode_id');
        });

        Schema::table('ligne_factures', function (Blueprint $table) {
            $table->dropUnique('uniq_ligne_facture_affectation');
        });

        Schema::table('bulletin_paie_lignes', function (Blueprint $table) {
            $table->dropUnique('uniq_bpl_bulletin_affectation');
        });

        Schema::table('paiement_cabinets', function (Blueprint $table) {
            $table->dropIndex('idx_paiement_cabinet_facture');
        });

        Schema::table('ligne_facture_cabinets', function (Blueprint $table) {
            $table->dropIndex('idx_lfc_facture_cabinet');
        });

        Schema::table('document_bibliotheques', function (Blueprint $table) {
            $table->dropIndex('idx_doc_biblio_user');
            $table->dropIndex('idx_doc_biblio_type');
            $table->dropIndex('idx_doc_biblio_classe');
            $table->dropIndex('idx_doc_biblio_matiere');
            $table->dropIndex('idx_doc_biblio_periode');
        });

        Schema::table('document_commentaires', function (Blueprint $table) {
            $table->dropIndex('idx_doc_commentaires_document');
        });

        Schema::table('document_signalements', function (Blueprint $table) {
            $table->dropIndex('idx_doc_signalements_document');
        });

        Schema::table('demande_cours_matieres', function (Blueprint $table) {
            $table->dropUnique('uniq_dcm_demande_matiere');
        });

        Schema::table('enseignant_matiere', function (Blueprint $table) {
            $table->dropUnique('uniq_em_enseignant_matiere');
        });
    }
};
