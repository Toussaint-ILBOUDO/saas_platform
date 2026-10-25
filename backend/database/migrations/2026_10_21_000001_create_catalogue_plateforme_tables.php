<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P8 — Catalogue éditable de la PLATEFORME (console super admin) :
 *
 * - `services_plateforme` : chaque fonctionnalité facturable de la plateforme
 *   avec son libellé et son explication, modifiables à tout moment.
 *   `colonne_tarif` relie le service à la colonne du contrat
 *   (`contrats_cabinet`) ; un service sans colonne tarif n'entre pas dans
 *   la grille du contrat (il se facture en ligne manuelle).
 *
 * - `lignes_types_plateforme` : briques de facturation réutilisables
 *   (libellé, prix de départ, texte explicatif, sens débit/crédit), insérées
 *   en un clic dans une facture manuelle.
 *
 * La société éditrice de la plateforme (Magis Plus Center) est paramétrée
 * via `parametres_plateforme` (cle = 'plateforme') : nom, tagline, contact.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('services_plateforme', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 60)->unique();       // ex : frais_creation_contrat
            $table->string('categorie', 40)->index();  // ouverture|abonnement|annuel|commission|ponctuel
            $table->string('libelle', 150);
            $table->text('description')->nullable();   // explication visible et modifiable
            $table->string('colonne_tarif', 60)->nullable(); // colonne du contrat pilotée
            $table->boolean('actif')->default(true);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('lignes_types_plateforme', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 200);
            $table->string('sens', 8)->default('debit'); // debit|credit
            $table->integer('montant')->nullable();      // prix de départ (entier FCFA)
            $table->text('description')->nullable();     // texte explicatif
            $table->boolean('actif')->default(true);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_types_plateforme');
        Schema::dropIfExists('services_plateforme');
    }
};
