<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cible d'impersonation du cabinet (T2.6) : id (BIGINT tenant) de l'admin
 * cabinet créé par le pipeline, renseigné par CreerAdminCabinetEtNotifier.
 * Stocké à plat (colonne réelle des tenants) via Cabinet::getCustomColumns().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_utilisateur_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('admin_utilisateur_id');
        });
    }
};