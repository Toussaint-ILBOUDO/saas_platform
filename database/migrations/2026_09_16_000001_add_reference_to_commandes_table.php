<?php

use App\Models\Commande;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute une référence aléatoire publique à chaque commande.
     *
     * L'identifiant séquentiel (id) ne doit plus être exposé comme
     * « n° de commande » : une référence aléatoire « CMD-XXXXXXXX »
     * le remplace partout dans l'interface.
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('id');
        });

        DB::table('commandes')
            ->whereNull('reference')
            ->orderBy('id')
            ->chunkById(500, function ($commandes) {
                foreach ($commandes as $commande) {
                    DB::table('commandes')
                        ->where('id', $commande->id)
                        ->update(['reference' => Commande::genererReference()]);
                }
            });

        Schema::table('commandes', function (Blueprint $table) {
            $table->unique('reference');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
        });
    }
};