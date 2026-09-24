<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal de la plateforme (base centrale Landlord).
 * Trace les événements d'administration (création, échec, impersonation…).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_plateforme', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('info');
            $table->string('action', 100);
            $table->string('cabinet_id')->nullable();
            $table->unsignedBigInteger('super_admin_id')->nullable();
            $table->json('contexte')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index('cabinet_id');
            $table->index('created_at');

            // Pas de FK vers tenants : le journal doit survivre au rollback
            // d'un cabinet (T2.4) et à sa suppression définitive (T2.5).
            $table->foreign('super_admin_id')
                ->references('id')
                ->on('super_admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_plateforme');
    }
};