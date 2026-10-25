<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enrichit les signalements de documents pour la modération complète :
 * signalement anonyme (user_id nullable), précisions complémentaires,
 * note administrative, traçabilité du traitement et empreinte IP pour
 * les protections anti-abus.
 */
return new class extends Migration
{
    public function up(): void
    {
        // user_id nullable : signalement anonyme (PostgreSQL — on relâche
        // d'abord la contrainte, puis on change la colonne, puis on la
        // recrée avec ON DELETE SET NULL).
        DB::statement('ALTER TABLE document_signalements DROP CONSTRAINT IF EXISTS document_signalements_user_id_foreign');
        DB::statement('ALTER TABLE document_signalements ALTER COLUMN user_id DROP NOT NULL');
        DB::statement(
            'ALTER TABLE document_signalements ADD CONSTRAINT document_signalements_user_id_foreign
             FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL'
        );

        Schema::table('document_signalements', function (Blueprint $table) {
            $table->text('precisions')->nullable();
            $table->text('note_admin')->nullable();
            $table->foreignId('traite_par_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('traite_at')->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('email_contact')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_signalements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('traite_par_user_id');
            $table->dropColumn(['precisions', 'note_admin', 'traite_at', 'ip_hash', 'email_contact']);
        });

        DB::statement('ALTER TABLE document_signalements DROP CONSTRAINT IF EXISTS document_signalements_user_id_foreign');
        DB::statement('ALTER TABLE document_signalements ALTER COLUMN user_id SET NOT NULL');
        DB::statement(
            'ALTER TABLE document_signalements ADD CONSTRAINT document_signalements_user_id_foreign
             FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE'
        );
    }
};
