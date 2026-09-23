<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actualites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('titre');
            $table->string('slug')->unique();
            $table->text('resume')->nullable();
            $table->longText('contenu');
            $table->string('video_url')->nullable();
            $table->string('lien_externe')->nullable();

            $table->string('statut', 20)->default('brouillon')->index();
            $table->timestamp('published_at')->nullable()->index();

            $table->json('destinataires')->nullable();
            $table->string('canal_notification', 20)->nullable();
            $table->boolean('notification_envoyee')->default(false);

            $table->boolean('is_active')->default(true)->index();

            $table->unsignedInteger('nb_vues')->default(0);
            $table->unsignedInteger('nb_reactions')->default(0);
            $table->unsignedInteger('nb_partages')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actualites');
    }
};
