<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jeton d'impersonation (T2.6) — émis côté Landlord (connexion centrale),
 * consommé une seule fois sur le domaine du cabinet (durée de vie courte).
 * Schéma aligné sur la feature stancl (ImpersonationToken) + super_admin_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user_impersonation_tokens', function (Blueprint $table) {
            $table->string('token', 128)->primary();
            $table->string('tenant_id')->index();
            $table->string('user_id');
            $table->string('auth_guard');
            $table->string('redirect_url');
            $table->unsignedBigInteger('super_admin_id')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_impersonation_tokens');
    }
};