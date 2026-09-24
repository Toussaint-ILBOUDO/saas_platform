<?php

use App\Modules\Temoignages\Enums\TemoignageStatut;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temoignages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('slug', 190)->unique();
            $table->text('contenu');
            $table->string('role', 40)->nullable();
            $table->boolean('anonyme')->default(false);
            $table->string('statut', 20)
                ->default(TemoignageStatut::PUBLIE)
                ->index();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('nb_reactions')->default(0);
            $table->unsignedInteger('nb_commentaires')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temoignages');
    }
};
