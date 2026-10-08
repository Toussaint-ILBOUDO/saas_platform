<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D-049 — Ventilation des heures par matière du rapport mensuel.
 *
 * Défaut KEduc corrigé : le rapport mensuel ne stockait qu'un volume global
 * (`volume_horaire_cumule`). `FacturationService` indexait ensuite les rapports
 * par `keyBy('enseignant_id')` puis bouclait sur les AFFECTATIONS : les heures
 * d'un enseignant étaient donc comptées autant de fois qu'il enseignait de
 * matières au même élève (sur-facturation), et la facture comme le bulletin ne
 * pouvaient pas garantir la même somme.
 *
 * Cette table porte le détail par affectation (matière). Source des heures :
 * `cahier_textes` sur la période (voir `RapportMensuelCalculator`). La facture
 * ET le bulletin consomment ces lignes — une seule vérité, deux consommateurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapport_mensuel_enseignant_lignes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rapport_mensuel_enseignant_id')
                ->constrained('rapport_mensuel_enseignants')
                ->cascadeOnDelete();

            $table->foreignId('affectation_enseignant_id')
                ->constrained('affectation_enseignants')
                ->cascadeOnDelete();

            $table->foreignId('matiere_id')
                ->constrained('matieres')
                ->cascadeOnDelete();

            $table->integer('nombre_seances')->default(0);
            $table->decimal('nombre_heures', 5, 2)->default(0);

            $table->timestamps();

            // Une seule ligne par affectation et par rapport : c'est ce qui
            // empêche le double comptage des heures.
            $table->unique(
                ['rapport_mensuel_enseignant_id', 'affectation_enseignant_id'],
                'uq_rmel_rapport_affectation'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapport_mensuel_enseignant_lignes');
    }
};