<?php

namespace Tests\Feature;

use App\Models\FaqSection;
use App\Models\ParametrePublic;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Backoffice API cabinet (T3.5) : contenu public, actualités, FAQ, utilisateurs.
 * Toutes les routes sont murées par « auth:web » + « role:admin_cabinet ».
 */
class ApiBackofficeTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function prepareAdmin(string $slug): void
    {
        $this->makeCabinet($slug);
        tenancy()->initialize($slug);

        User::where('email', "admin@{$slug}.local")->update([
            'password' => Hash::make('Secret1234'),
        ]);

        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => "admin@{$slug}.local",
            'password' => 'Secret1234',
        ])->assertOk();
    }

    public function test_admin_requiert_connexion_et_role_admin_cabinet(): void
    {
        $this->makeCabinet('c1');

        // Non connecté → 401.
        $this->getJson('http://c1.localhost/api/admin/contenu-public')
            ->assertStatus(401)
            ->assertJson(['code' => 'NON_CONNECTE']);

        // Utilisateur connecté sans rôle admin_cabinet → 403.
        tenancy()->initialize('c1');
        $enseignant = User::create([
            'nom' => 'Enseignant',
            'prenom' => 'Paul',
            'email' => 'paul@c1.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $enseignant->assignRole('enseignant');
        tenancy()->end();

        $this->postJson('http://c1.localhost/api/auth/connexion', [
            'email' => 'paul@c1.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->getJson('http://c1.localhost/api/admin/contenu-public')
            ->assertStatus(403);
    }

    public function test_contenu_public_lecture_et_mise_a_jour(): void
    {
        $this->prepareAdmin('c1');

        $this->getJson('http://c1.localhost/api/admin/contenu-public')
            ->assertOk()
            ->assertJsonStructure(['data' => ['theme', 'footer', 'data']]);

        $this->putJson('http://c1.localhost/api/admin/contenu-public', [
            'theme' => ['couleur_primaire' => '#123456', 'logo' => 'logo-c1.svg'],
            'footer' => ['adresse' => '1 rue de Test'],
        ])->assertOk();

        tenancy()->initialize('c1');
        $public = ParametrePublic::query()->first();
        $this->assertSame('#123456', $public->theme['couleur_primaire']);
        $this->assertSame('1 rue de Test', $public->footer['adresse']);
        tenancy()->end();
    }

    public function test_fiche_cabinet_mise_a_jour_via_api(): void
    {
        $this->prepareAdmin('c1');

        $this->putJson('http://c1.localhost/api/admin/contenu-public', [
            'data' => [
                'fiche' => [
                    'identite' => ['slogan' => 'Visez l\'excellence'],
                    'contact' => ['adresse' => 'Avenue de la Liberté', 'horaires' => '24h/24'],
                    'zones' => ['devise' => 'FCFA', 'localites' => ['Ouagadougou']],
                ],
            ],
        ])->assertOk();

        $this->getJson('http://c1.localhost/api/admin/contenu-public')
            ->assertOk()
            ->assertJsonPath('data.data.fiche.identite.slogan', 'Visez l\'excellence')
            ->assertJsonPath('data.data.fiche.contact.adresse', 'Avenue de la Liberté')
            ->assertJsonPath('data.data.fiche.zones.localites', ['Ouagadougou']);
    }

    public function test_actualites_crud(): void
    {
        $this->prepareAdmin('c1');

        // Liste vide.
        $this->getJson('http://c1.localhost/api/admin/actualites')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        // Création.
        $this->postJson('http://c1.localhost/api/admin/actualites', [
            'titre' => 'Rentrée 2026',
            'contenu' => 'La rentrée est prévue le 5 octobre.',
            'resume' => 'Dates de rentrée.',
        ])->assertStatus(201)->assertJsonPath('data.titre', 'Rentrée 2026');

        tenancy()->initialize('c1');
        $actualite = \App\Models\Actualite::first();
        tenancy()->end();

        // Lecture et mise à jour.
        $this->getJson("http://c1.localhost/api/admin/actualites/{$actualite->id}")
            ->assertOk()
            ->assertJsonPath('data.slug', $actualite->slug);

        $this->putJson("http://c1.localhost/api/admin/actualites/{$actualite->id}", [
            'titre' => 'Rentrée 2026 (màj)',
            'contenu' => 'La rentrée est prévue le 6 octobre.',
            'is_active' => true,
        ])->assertOk()->assertJsonPath('data.titre', 'Rentrée 2026 (màj)');

        // Suppression.
        $this->deleteJson("http://c1.localhost/api/admin/actualites/{$actualite->id}")
            ->assertOk();

        $this->getJson("http://c1.localhost/api/admin/actualites/{$actualite->id}")
            ->assertStatus(404);
    }

    public function test_actualite_statut_et_publication_via_api(): void
    {
        $this->prepareAdmin('c1');

        // Sans statut → brouillon par défaut (invisible publiquement).
        $this->postJson('http://c1.localhost/api/admin/actualites', [
            'titre' => 'Création sans statut',
            'contenu' => 'Contenu.',
        ])->assertStatus(201)->assertJsonPath('data.statut', 'brouillon');

        tenancy()->initialize('c1');
        $idBrouillon = \App\Models\Actualite::where('slug', 'creation-sans-statut')->firstOrFail()->id;
        tenancy()->end();

        // Création publiée → statut publish + published_at défini + active.
        $this->postJson('http://c1.localhost/api/admin/actualites', [
            'titre' => 'Rentrée publiable',
            'contenu' => 'Informations de rentrée.',
            'statut' => 'publie',
        ])->assertStatus(201)
            ->assertJsonPath('data.statut', 'publie')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.est_publiee', true)
            ->assertJsonStructure(['data' => ['published_at']]);

        tenancy()->initialize('c1');
        $idPubliee = \App\Models\Actualite::where('slug', 'rentree-publiable')->firstOrFail()->id;
        tenancy()->end();

        // La publiée remonte publiquement, le brouillon reste caché.
        $this->getJson('http://c1.localhost/api/public/actualites')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        // Repasser en brouillon → plus visible publiquement.
        $this->putJson("http://c1.localhost/api/admin/actualites/{$idPubliee}", [
            'titre' => 'Rentrée publiable',
            'contenu' => 'Informations de rentrée.',
            'statut' => 'brouillon',
        ])->assertOk()->assertJsonPath('data.statut', 'brouillon');

        $this->getJson('http://c1.localhost/api/public/actualites')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->deleteJson("http://c1.localhost/api/admin/actualites/{$idBrouillon}")->assertOk();
    }

    public function test_logo_cabinet_upload_et_publication(): void
    {
        $this->prepareAdmin('c1');

        // Aucun logo au départ.
        $this->getJson('http://c1.localhost/api/public/cabinet')
            ->assertOk()
            ->assertJsonPath('logo_url', null);

        // Upload via le backoffice (multipart POST — comme le navigateur ;
        // PHP < 8.4 ignore les fichiers d'un multipart en PUT).
        $this->call(
            'POST',
            'http://c1.localhost/api/admin/contenu-public/logo',
            [],
            [],
            ['logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 200)],
            ['HTTP_ACCEPT' => 'application/json']
        )->assertOk()->assertJsonPath('message', 'Logo mis à jour.');

        // Flux public et URL exposée au frontend.
        $repCab = $this->getJson('http://c1.localhost/api/public/cabinet')
            ->assertOk()
            ->assertJsonStructure(['logo_url']);
        $urlLogo = $repCab->json('logo_url');
        $this->assertIsString($urlLogo);
        $this->assertStringStartsWith('/api/public/logo', $urlLogo);

        $this->get('http://c1.localhost/api/public/logo')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        // Une sauvegarde de fiche (data.fiche uniquement) ne doit pas écraser le logo.
        $this->putJson('http://c1.localhost/api/admin/contenu-public', [
            'data' => ['fiche' => ['identite' => ['slogan' => 'Votre avenir']]],
        ])->assertOk();

        $this->getJson('http://c1.localhost/api/public/cabinet')
            ->assertOk()
            ->assertJsonPath('logo_url', $urlLogo);
    }

    public function test_actualite_mise_a_jour_multipart_post(): void
    {
        $this->prepareAdmin('c1');

        // Création (brouillon).
        $this->postJson('http://c1.localhost/api/admin/actualites', [
            'titre' => 'Sujet à mettre à jour',
            'contenu' => 'Version initiale.',
            'resume' => 'Résumé.',
        ])->assertStatus(201);

        tenancy()->initialize('c1');
        $id = \App\Models\Actualite::where('slug', 'sujet-a-mettre-a-jour')->firstOrFail()->id;
        tenancy()->end();

        // Mise à jour multipart POST (comme le navigateur) : champs texte +
        // fichier image + passage en « publie » en une seule requête.
        $this->call(
            'POST',
            "http://c1.localhost/api/admin/actualites/{$id}",
            ['titre' => 'Sujet à mettre à jour (màj)', 'contenu' => 'Version corrigée.', 'statut' => 'publie'],
            [],
            ['image_principale' => \Illuminate\Http\UploadedFile::fake()->image('couverture.png', 800, 400)],
            ['HTTP_ACCEPT' => 'application/json']
        )->assertOk()
            ->assertJsonPath('data.titre', 'Sujet à mettre à jour (màj)')
            ->assertJsonPath('data.statut', 'publie')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure(['data' => ['image_url']]);

        // L'actualité publiée remonte sur la page publique.
        $this->getJson('http://c1.localhost/api/public/actualites')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.titre', 'Sujet à mettre à jour (màj)');
    }

    public function test_faq_sections_et_questions_crud(): void
    {
        $this->prepareAdmin('c1');

        // Section.
        $this->postJson('http://c1.localhost/api/admin/faq/sections', [
            'title' => 'Inscription',
            'description' => 'Questions sur les inscriptions',
        ])->assertStatus(201)->assertJsonPath('data.slug', 'inscription');

        tenancy()->initialize('c1');
        $section = FaqSection::first();
        tenancy()->end();

        $this->putJson("http://c1.localhost/api/admin/faq/sections/{$section->id}", [
            'title' => 'Inscriptions',
        ])->assertOk()->assertJsonPath('data.title', 'Inscriptions');

        $this->getJson('http://c1.localhost/api/admin/faq/sections')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Inscriptions');

        // Question dans la section.
        $this->postJson('http://c1.localhost/api/admin/faq/questions', [
            'faq_section_id' => $section->id,
            'question' => 'Comment s\'inscrire ?',
            'answer' => 'Remplir le formulaire en ligne.',
        ])->assertStatus(201)->assertJsonPath('data.question', 'Comment s\'inscrire ?');

        // Liste des questions d'une section (endpoint backoffice).
        $this->getJson("http://c1.localhost/api/admin/faq/sections/{$section->id}/questions")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.question', 'Comment s\'inscrire ?');

        tenancy()->initialize('c1');
        $question = \App\Models\FaqQuestion::first();
        tenancy()->end();

        $this->putJson("http://c1.localhost/api/admin/faq/questions/{$question->id}", [
            'faq_section_id' => $section->id,
            'question' => 'Comment s\'inscrire ?',
            'answer' => 'Remplir le formulaire, puis envoyer les pièces.',
        ])->assertOk()->assertJsonPath('data.answer', 'Remplir le formulaire, puis envoyer les pièces.');

        $this->deleteJson("http://c1.localhost/api/admin/faq/questions/{$question->id}")->assertOk();

        // Supprimer une section avec une question résiduelle est interdit côté service.
        $this->deleteJson("http://c1.localhost/api/admin/faq/sections/{$section->id}")->assertOk();
    }

    public function test_utilisateurs_liste_creation_roles_suspension(): void
    {
        $this->prepareAdmin('c1');

        // Liste (l'admin du cabinet est créé par le pipeline).
        $this->getJson('http://c1.localhost/api/admin/utilisateurs')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        // Création avec rôles.
        $this->postJson('http://c1.localhost/api/admin/utilisateurs', [
            'nom' => 'Koffi',
            'prenom' => 'Aya',
            'email' => 'aya@c1.local',
            'roles' => ['parent'],
        ])->assertStatus(201)->assertJsonPath('data.roles.0', 'parent');

        tenancy()->initialize('c1');
        $utilisateur = User::where('email', 'aya@c1.local')->first();
        tenancy()->end();

        // Changement de rôles.
        $this->putJson("http://c1.localhost/api/admin/utilisateurs/{$utilisateur->id}", [
            'roles' => ['parent', 'gestionnaire_librairie'],
        ])->assertOk()->assertJsonCount(2, 'data.roles');

        // Suspension / activation.
        $this->patchJson("http://c1.localhost/api/admin/utilisateurs/{$utilisateur->id}/suspendre")
            ->assertOk()->assertJsonPath('data.statut', false);

        tenancy()->initialize('c1');
        $this->assertFalse((bool) User::where('email', 'aya@c1.local')->value('statut'));
        tenancy()->end();

        $this->patchJson("http://c1.localhost/api/admin/utilisateurs/{$utilisateur->id}/activer")
            ->assertOk()->assertJsonPath('data.statut', true);

        // Email dupliqué → 422.
        $this->postJson('http://c1.localhost/api/admin/utilisateurs', [
            'nom' => 'Asse',
            'email' => 'aya@c1.local',
        ])->assertStatus(422)->assertJson(['code' => 'VALIDATION']);
    }
}