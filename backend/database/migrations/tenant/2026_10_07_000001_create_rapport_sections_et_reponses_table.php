<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rapport mensuel — modèle de rapport administrable.
 *
 * Les informations générales et le bilan des activités viennent
 * AUTOMATIQUEMENT du cahier de texte et de la ventilation (heures). Tout le
 * reste du rapport (« Évaluation pédagogique », « Analyse et accompagnement »…)
 * est composé de SECTIONS et d'ÉLÉMENTS que l'administration configure : c'est
 * ce modèle que l'enseignant remplit au moment du dépôt, et que le PDF reproduit.
 *
 * 1. `rapport_sections`  : blocs du rapport (titre, ordre, actif).
 * 2. `rapport_elements`  : champs à remplir dans une section (libellé,
 *    obligatoire, ordre, actif).
 * 3. `reponses` (json)   : réponses de l'enseignant, indexées par
 *    `rapport_elements.id`.
 *
 * Les anciennes colonnes texte restent en base (dépréciées, compatibilité du
 * monolithe web) ; leurs valeurs sont REPLIÉES dans `reponses` à la montée, et
 * le nouveau flux (API + PDF) ne lit plus que `reponses`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapport_sections', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('description')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('rapport_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('rapport_sections')
                ->cascadeOnDelete();
            $table->string('libelle');
            $table->string('type')->default('textarea');
            $table->boolean('obligatoire')->default(false);
            $table->string('aide')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index('section_id', 'idx_rapport_elements_section');
        });

        Schema::table('rapport_mensuel_enseignants', function (Blueprint $table) {
            $table->json('reponses')->nullable()->after('bilan_activites');
        });

        $this->insererModeleParDefaut();
        $this->replierLesAnciensChamps();
    }

    /**
     * Modèle par défaut calqué sur le rapport KEduc : les sections et éléments
     * sont administrables mais, à la création d'un cabinet, ceux-là existent
     * déjà pour que le premier dépôt soit possible sans configuration.
     */
    protected function insererModeleParDefaut(): void
    {
        $maintenant = now()->toDateTimeString();

        $sections = [
            ['libelle' => 'Évaluation pédagogique', 'description' => 'Niveau pédagogique atteint sur la période, matière par matière.', 'ordre' => 1],
            ['libelle' => 'Analyse et accompagnement', 'description' => 'Difficultés rencontrées et solutions mises en place.', 'ordre' => 2],
            ['libelle' => 'Attentes', 'description' => 'Attentes des parents et de l\'élève, et de l\'administration.', 'ordre' => 3],
            ['libelle' => 'Appréciation générale', 'description' => 'Synthèse sur l\'évolution de l\'élève sur la période.', 'ordre' => 4],
            ['libelle' => 'Observations', 'description' => 'Remarques complémentaires éventuelles.', 'ordre' => 5],
        ];

        $elements = [
            'Évaluation pédagogique' => [
                ['libelle' => 'Points notables sur la matière', 'obligatoire' => true],
                ['libelle' => 'Autres matières', 'obligatoire' => false],
            ],
            'Analyse et accompagnement' => [
                ['libelle' => 'Difficultés rencontrées', 'obligatoire' => true],
                ['libelle' => 'Solutions proposées', 'obligatoire' => true],
            ],
            'Attentes' => [
                ['libelle' => 'Attentes parents / élève', 'obligatoire' => false],
                ['libelle' => 'Attentes administration', 'obligatoire' => false],
            ],
            'Appréciation générale' => [
                ['libelle' => 'Appréciation générale', 'obligatoire' => false],
            ],
            'Observations' => [
                ['libelle' => 'Observations', 'obligatoire' => false],
            ],
        ];

        $ordreGlobale = 0;
        foreach ($sections as $section) {
            $sectionId = DB::table('rapport_sections')->insertGetId([
                'libelle' => $section['libelle'],
                'description' => $section['description'],
                'ordre' => $section['ordre'],
                'actif' => true,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);

            foreach ($elements[$section['libelle']] as $element) {
                $ordreGlobale++;
                DB::table('rapport_elements')->insert([
                    'section_id' => $sectionId,
                    'libelle' => $element['libelle'],
                    'type' => 'textarea',
                    'obligatoire' => $element['obligatoire'],
                    'aide' => null,
                    'ordre' => $ordreGlobale,
                    'actif' => true,
                    'created_at' => $maintenant,
                    'updated_at' => $maintenant,
                ]);
            }
        }
    }

    /**
     * Replie les anciennes colonnes texte dans `reponses` (indexées par
     * l'id de l'élément de même libellé) : les rapports existants restent
     * intacts et s'affichent dans le nouveau modèle sans ressaisie.
     */
    protected function replierLesAnciensChamps(): void
    {
        $correspondances = [
            'point_notes_matieres' => 'Points notables sur la matière',
            'point_notes_autres_matieres' => 'Autres matières',
            'difficultes_rencontrees' => 'Difficultés rencontrées',
            'solutions_trouvees' => 'Solutions proposées',
            'attentes_parents_eleve' => 'Attentes parents / élève',
            'attentes_administration' => 'Attentes administration',
            'appreciation_evolution' => 'Appréciation générale',
            'observations' => 'Observations',
        ];

        $ids = DB::table('rapport_elements')
            ->whereIn('libelle', array_values($correspondances))
            ->pluck('id', 'libelle');

        if ($ids->isEmpty()) {
            return;
        }

        $colonnes = array_keys($correspondances);

        $rapports = DB::table('rapport_mensuel_enseignants')
            ->get(array_merge(['id'], $colonnes));

        foreach ($rapports as $rapport) {
            $reponses = [];

            foreach ($correspondances as $colonne => $libelle) {
                $valeur = $rapport->{$colonne};

                if ($valeur !== null && trim((string) $valeur) !== '' && $ids->has($libelle)) {
                    $reponses[(string) $ids[$libelle]] = $valeur;
                }
            }

            if ($reponses !== []) {
                DB::table('rapport_mensuel_enseignants')
                    ->where('id', $rapport->id)
                    ->update([
                        'reponses' => json_encode($reponses, JSON_UNESCAPED_UNICODE),
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('rapport_mensuel_enseignants', function (Blueprint $table) {
            $table->dropColumn('reponses');
        });

        Schema::dropIfExists('rapport_elements');
        Schema::dropIfExists('rapport_sections');
    }
};