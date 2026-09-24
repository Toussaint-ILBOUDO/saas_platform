<?php

namespace Tests\Feature;

use App\Models\ContratCours;
use App\Models\DemandeCours;
use App\Models\EvaluationCours;
use App\Models\Matiere;
use Database\Seeders\ClasseSeeder;
use Database\Seeders\MatiereSeeder;
use Database\Seeders\TypeCoursSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Partie 04 — Intégrité des données, des relations et des indexes :
 * relations Eloquent supprimées/corrigées, contraintes UNIQUE anti-doublon
 * et indexes manquants ajoutés par la migration d'intégrité.
 */
class IntegriteDonneesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_relation_morte_contrat_cours_matiere_supprimee(): void
    {
        $this->assertFalse(
            method_exists(ContratCours::class, 'matiere'),
            'La relation ContratCours::matiere() doit avoir été supprimée (FK matiere_id absente de contrat_cours).'
        );
    }

    public function test_relation_morte_demande_cours_matiere_supprimee(): void
    {
        $this->assertFalse(
            method_exists(DemandeCours::class, 'matiere'),
            'La relation DemandeCours::matiere() doit avoir été supprimée (FK matiere_id absente de demande_cours).'
        );
    }

    public function test_demande_cours_conserve_la_relation_matieres(): void
    {
        $this->assertTrue(
            method_exists(DemandeCours::class, 'matieres'),
            'La relation DemandeCours::matieres() (pivot demande_cours_matieres) doit être conservée.'
        );
    }

    public function test_evaluation_cours_enseignant_utilise_bonne_fk(): void
    {
        $relation = (new EvaluationCours)->enseignant();

        $this->assertSame(
            'enseignant_id',
            $relation->getForeignKeyName(),
            'La relation EvaluationCours::enseignant() doit utiliser la colonne enseignant_id (et non enseignant_profil_id).'
        );
    }

    public function test_contraintes_unique_et_indexes_ajoutes(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Vérification des indexes PostgreSQL uniquement.');
        }

        $indexes = static function (string $table): array {
            return collect(Schema::getIndexes($table))
                ->keyBy('name')
                ->all();
        };

        $unique = static fn (array $idx): array => array_filter($idx, fn ($i) => $i['unique']);

        $ligneFactures = $indexes('ligne_factures');
        $this->assertArrayHasKey('uniq_ligne_facture_affectation', $ligneFactures);
        $this->assertTrue($ligneFactures['uniq_ligne_facture_affectation']['unique']);

        $bulletinPaieLignes = $indexes('bulletin_paie_lignes');
        $this->assertArrayHasKey('uniq_bpl_bulletin_affectation', $bulletinPaieLignes);
        $this->assertTrue($bulletinPaieLignes['uniq_bpl_bulletin_affectation']['unique']);

        $demandeCoursMatieres = $unique($indexes('demande_cours_matieres'));
        $this->assertArrayHasKey('uniq_dcm_demande_matiere', $demandeCoursMatieres);

        $enseignantMatiere = $unique($indexes('enseignant_matiere'));
        $this->assertArrayHasKey('uniq_em_enseignant_matiere', $enseignantMatiere);

        $rapportMensuel = $indexes('rapport_mensuel_enseignants');
        foreach (['idx_rm_contrat_cours_id', 'idx_rm_enseignant_id', 'idx_rm_periode_id'] as $index) {
            $this->assertArrayHasKey($index, $rapportMensuel, "Index $index manquant sur rapport_mensuel_enseignants.");
        }

        $factures = $indexes('factures');
        foreach (['idx_factures_contrat_cours_id', 'idx_factures_parent_id', 'idx_factures_eleve_id', 'idx_factures_periode_id'] as $index) {
            $this->assertArrayHasKey($index, $factures, "Index $index manquant sur factures.");
        }

        $documents = $indexes('document_bibliotheques');
        foreach (['idx_doc_biblio_user', 'idx_doc_biblio_type', 'idx_doc_biblio_classe', 'idx_doc_biblio_matiere', 'idx_doc_biblio_periode'] as $index) {
            $this->assertArrayHasKey($index, $documents, "Index $index manquant sur document_bibliotheques.");
        }
    }

    public function test_contrainte_unique_demande_cours_matieres_bloque_le_doublon(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Vérification des contraintes PostgreSQL uniquement.');
        }

        $this->seed([ClasseSeeder::class, MatiereSeeder::class, TypeCoursSeeder::class]);

        $matiere = Matiere::firstOrFail();

        $demande = DemandeCours::create([
            'nom_parent' => 'Koffi',
            'prenom_parent' => 'Aya',
            'telephone' => '0102030405',
            'type_cours_id' => 1,
            'classe_id' => 1,
            'volume_horaire_estime' => 10,
            'statut' => 'nouvelle',
            'message' => 'Demande de cours test',
        ]);

        $demande->matieres()->attach($matiere->id);

        $this->expectException(QueryException::class);
        DB::table('demande_cours_matieres')->insert([
            'demande_cours_id' => $demande->id,
            'matiere_id' => $matiere->id,
        ]);
    }
}
