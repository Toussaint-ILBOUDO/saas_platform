<?php

namespace Tests\Feature;

use App\Models\Actualite;
use App\Models\CategorieProduit;
use App\Models\EnseignantProfil;
use App\Models\FaqQuestion;
use App\Models\FaqSection;
use App\Models\Produit;
use App\Models\Temoignage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Routes publiques API (T3.3) : cabinet, actualités, FAQ, documents,
 * produits, demandes de cours, commandes.
 */
class ApiPublicTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function init(string $slug): void
    {
        $this->makeCabinet($slug);
        tenancy()->initialize($slug);
    }

    public function test_cabinet_public_theme_et_fonctionnalites(): void
    {
        $this->init('c1');

        $response = $this->getJson('http://c1.localhost/api/public/cabinet')->assertOk();

        $response->assertJsonPath('nom', 'Cabinet c1');
        $response->assertJsonPath('slug', 'c1');
        $response->assertJsonPath('theme.couleurs.--couleur-primaire', '#1b7f5c');
        $response->assertJsonPath('fonctionnalites_actives.0', 'pedagogie');
    }

    public function test_actualites_liste_et_detail(): void
    {
        $this->init('c1');

        Actualite::create([
            'user_id' => 1,
            'titre' => 'Rentrée scolaire',
            'slug' => 'rentree-scolaire',
            'contenu' => 'La rentrée est prévue le 15 septembre.',
            'statut' => 'publie',
            'published_at' => now(),
            'is_active' => true,
        ]);

        $this->getJson('http://c1.localhost/api/public/actualites')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'rentree-scolaire')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('http://c1.localhost/api/public/actualites/rentree-scolaire')
            ->assertOk()
            ->assertJsonPath('titre', 'Rentrée scolaire')
            ->assertJsonPath('contenu', 'La rentrée est prévue le 15 septembre.');

        tenancy()->end();
    }

    public function test_actualite_non_publiee_introuvable(): void
    {
        $this->init('c1');

        Actualite::create([
            'user_id' => 1,
            'titre' => 'Brouillon',
            'slug' => 'brouillon',
            'contenu' => 'Pas encore publié.',
            'statut' => 'brouillon',
            'is_active' => true,
        ]);

        $this->getJson('http://c1.localhost/api/public/actualites/brouillon')->assertNotFound();
        $this->getJson('http://c1.localhost/api/public/actualites')->assertJsonPath('meta.total', 0);
    }

    public function test_faq_public(): void
    {
        $this->init('c1');

        $section = FaqSection::create([
            'title' => 'Inscriptions',
            'slug' => 'inscriptions',
            'description' => 'Questions fréquentes',
            'order_index' => 1,
            'is_active' => true,
        ]);
        FaqQuestion::create([
            'faq_section_id' => $section->id,
            'question' => 'Quels documents fournir ?',
            'answer' => 'Une pièce d\'identité.',
            'order_index' => 1,
            'is_active' => true,
        ]);

        $this->getJson('http://c1.localhost/api/public/faq')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Inscriptions')
            ->assertJsonPath('data.0.questions.0.question', 'Quels documents fournir ?');

        tenancy()->end();
    }

    public function test_documents_publics(): void
    {
        $this->init('c1');

        DB::table('document_bibliotheques')->insert([
            'user_id' => 1,
            'type_document_id' => DB::table('type_documents')->value('id'),
            'titre' => 'Programme officiel',
            'slug' => 'programme-officiel',
            'description' => 'Programme du cycle primaire.',
            'is_public' => true,
            'statut' => 'publie',
            'nb_vues' => 0,
            'nb_telechargements' => 0,
            'nombre_favoris' => 0,
            'nb_notes' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('http://c1.localhost/api/public/documents')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'programme-officiel');

        $this->getJson('http://c1.localhost/api/public/documents/programme-officiel')->assertOk();

        tenancy()->end();
    }

    public function test_produits_publics(): void
    {
        $this->init('c1');

        $categorie = CategorieProduit::create(['nom' => 'Fournitures', 'slug' => 'fournitures']);
        Produit::create([
            'categorie_id' => $categorie->id,
            'nom' => 'Cahier',
            'slug' => 'cahier',
            'description' => 'Cahier 96 pages.',
            'prix' => 1500,
            'frais_livraison' => 0,
            'is_active' => true,
        ]);

        $this->getJson('http://c1.localhost/api/public/produits')
            ->assertOk()
            ->assertJsonPath('data.0.nom', 'Cahier');

        tenancy()->end();
    }

    public function test_stats_publics(): void
    {
        $this->init('c1');

        $this->getJson('http://c1.localhost/api/public/stats')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['nb_enseignants', 'nb_eleves', 'nb_contrats', 'nb_familles'],
            ])
            ->assertJsonPath('data.nb_enseignants', 0);

        tenancy()->end();
    }

    public function test_enseignants_publics_actifs(): void
    {
        $this->init('c1');

        $actif = User::create([
            'nom' => 'Ouédraogo',
            'prenom' => 'Alice',
            'email' => 'alice@c1.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $actif->assignRole('enseignant');
        EnseignantProfil::create([
            'user_id' => $actif->id,
            'diplome_max' => 'Licence en mathématiques',
            'lieu_de_service' => 'Ouagadougou',
        ]);

        $inactif = User::create([
            'nom' => 'Kaboré',
            'prenom' => 'Boris',
            'email' => 'boris@c1.local',
            'password' => Hash::make('Secret1234'),
            'statut' => false,
        ]);
        $inactif->assignRole('enseignant');

        $this->getJson('http://c1.localhost/api/public/enseignants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nom_complet', 'Alice Ouédraogo')
            ->assertJsonPath('data.0.diplome_max', 'Licence en mathématiques')
            ->assertJsonPath('data.0.lieu_de_service', 'Ouagadougou');

        tenancy()->end();
    }

    public function test_temoignages_publics_classement(): void
    {
        $this->init('c1');

        $auteur = User::create([
            'nom' => 'Diallo',
            'prenom' => 'Mariam',
            'email' => 'mariam@c1.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $auteur->assignRole('parent');

        Temoignage::create([
            'user_id' => $auteur->id,
            'slug' => 'vraiment-top',
            'contenu' => 'Des progrès remarquables en mathématiques.',
            'role' => 'parent',
            'anonyme' => false,
            'statut' => 'publie',
            'published_at' => now(),
            'is_active' => true,
        ]);

        $this->getJson('http://c1.localhost/api/public/temoignages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.auteur', 'Mariam D.')
            ->assertJsonPath('data.0.role_label', 'Parent d\'élève')
            ->assertJsonPath('data.0.contenu', 'Des progrès remarquables en mathématiques.');

        $this->getJson('http://c1.localhost/api/public/temoignages/vraiment-top')
            ->assertOk()
            ->assertJsonPath('auteur', 'Mariam D.');

        tenancy()->end();
    }

    public function test_demande_cours_validated(): void
    {
        $this->init('c1');

        $matiereId = DB::table('matieres')->insertGetId(['nom' => 'Mathématiques']);
        $classeId = DB::table('classes')->insertGetId(['nom' => '6e', 'sigle' => '6e']);
        $typeCoursId = DB::table('type_cours')->value('id');

        $this->postJson('http://c1.localhost/api/public/demandes-cours', [
            'nom_parent' => 'Alpha',
            'prenom_parent' => 'Awa',
            'telephone' => '+226 70 00 00 00',
            'type_cours_id' => $typeCoursId,
            'classe_id' => $classeId,
            'volume_horaire_estime' => 4,
            'matieres' => [$matiereId],
        ])
            ->assertStatus(201)
            ->assertJsonPath('demande.statut', 'en_attente');

        tenancy()->end();
    }

    public function test_references_publiques_pour_formulaire(): void
    {
        $this->init('c1');

        $matiereId = DB::table('matieres')->insertGetId(['nom' => 'Mathématiques', 'sigle' => 'MATH', 'actif' => true]);
        $inactifId = DB::table('matieres')->insertGetId(['nom' => 'Latin', 'sigle' => 'LAT', 'actif' => false]);
        $classeId = DB::table('classes')->insertGetId(['nom' => '6e', 'sigle' => '6e']);
        $typeCoursId = DB::table('type_cours')->insertGetId(['libelle' => 'Cours à domicile', 'code' => 'DOM', 'actif' => true]);
        $typeInactifId = DB::table('type_cours')->insertGetId(['libelle' => 'Atelier', 'code' => 'ATL', 'actif' => false]);

        $response = $this->getJson('http://c1.localhost/api/public/references');
        $response->assertOk();

        $data = $response->json('data');
        $matiereIds = array_column($data['matieres'], 'id');
        $typeIds = array_column($data['type_cours'], 'id');
        $classeIds = array_column($data['classes'], 'id');

        $this->assertContains($matiereId, $matiereIds);
        $this->assertNotContains($inactifId, $matiereIds, 'Les matières inactives ne doivent pas remonter.');

        $this->assertContains($typeCoursId, $typeIds);
        $this->assertNotContains($typeInactifId, $typeIds, 'Les types de cours inactifs ne doivent pas remonter.');

        $this->assertContains($classeId, $classeIds);

        tenancy()->end();
    }

    public function test_commande_invite(): void
    {
        $this->init('c1');

        $categorie = CategorieProduit::create(['nom' => 'Fournitures', 'slug' => 'fournitures']);
        $produit = Produit::create([
            'categorie_id' => $categorie->id,
            'nom' => 'Cahier',
            'slug' => 'cahier',
            'description' => 'Cahier 96 pages.',
            'prix' => 1500,
            'frais_livraison' => 500,
            'is_active' => true,
        ]);

        $this->postJson('http://c1.localhost/api/public/commandes', [
            'nom_client' => 'Invité',
            'telephone_client' => '+226 70 00 00 00',
            'whatsapp' => '+226 70 00 00 00',
            'adresse_livraison' => 'Quartier Gounghin, Ouagadougou',
            'is_livraison' => true,
            'frais_livraison' => 500,
            'panier' => [
                ['produit_id' => $produit->id, 'quantite' => 2],
            ],
        ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'commande' => ['id', 'token', 'montant_total', 'statut', 'lien_suivi'],
            ]);

        tenancy()->end();
    }
}