<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\DocumentBibliotheque;
use App\Models\TypeDocument;
use App\Models\User;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Partie 02 — Sécurité de la Bibliothèque numérique :
 * accès aux documents, stockage privé, Policy.
 */
class BibliothequeSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::create([
            'nom' => 'Nom',
            'prenom' => 'Prenom',
            'email' => strtolower($role) . '_' . uniqid() . '@example.com',
            'password' => 'password',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createType(): TypeDocument
    {
        return TypeDocument::create(['nom' => 'Cours', 'sigle' => 'CRS']);
    }

    private function createDocument(array $overrides = []): DocumentBibliotheque
    {
        $auteur = $this->createUserWithRole('enseignant');

        return DocumentBibliotheque::create(array_merge([
            'user_id' => $auteur->id,
            'titre' => 'Document de test',
            'type_document_id' => $this->createType()->id,
            'is_public' => false,
            'statut' => 'publie',
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | INVITÉ
    |--------------------------------------------------------------------------
    */

    public function test_invite_peut_voir_un_document_public_publie(): void
    {
        $document = $this->createDocument(['is_public' => true, 'statut' => 'publie']);

        $this->get("/bibliotheque/{$document->slug}")
            ->assertOk();
    }

    public function test_invite_peut_voir_et_telecharger_un_document_public_publie(): void
    {
        Storage::fake('private_media');
        $file = UploadedFile::fake()->create('public.pdf', 20, 'application/pdf');

        $auteur = $this->createUserWithRole('enseignant');
        $this->actingAs($auteur);

        $document = app(DocumentBibliothequeService::class)->create([
            'titre' => 'Document public',
            'type_document_id' => $this->createType()->id,
            'is_public' => true,
            'statut' => 'publie',
        ], $file);

        auth()->logout();

        $this->get("/bibliotheque/voir/{$document->id}")->assertOk();
        $this->get("/bibliotheque/telecharger/{$document->id}")->assertOk();
    }

    public function test_invite_ne_peut_pas_voir_un_document_prive_publie(): void
    {
        $document = $this->createDocument(['is_public' => false, 'statut' => 'publie']);

        $this->get("/bibliotheque/{$document->slug}")->assertForbidden();
        $this->get("/bibliotheque/voir/{$document->id}")->assertForbidden();
        $this->get("/bibliotheque/telecharger/{$document->id}")->assertForbidden();
    }

    public function test_invite_ne_peut_pas_voir_un_document_brouillon(): void
    {
        $document = $this->createDocument(['is_public' => false, 'statut' => 'brouillon']);

        $this->get("/bibliotheque/{$document->slug}")->assertForbidden();
        $this->get("/bibliotheque/telecharger/{$document->id}")->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | UTILISATEUR CONNECTÉ
    |--------------------------------------------------------------------------
    */

    public function test_utilisateur_connecte_peut_voir_un_document_publie_prive(): void
    {
        $eleve = $this->createUserWithRole('eleve');
        $document = $this->createDocument(['is_public' => false, 'statut' => 'publie']);

        $this->actingAs($eleve)
            ->get("/bibliotheque/{$document->slug}")
            ->assertOk();
    }

    public function test_utilisateur_connecte_ne_peut_pas_voir_le_brouillon_d_autrui(): void
    {
        $eleve = $this->createUserWithRole('eleve');
        $document = $this->createDocument(['is_public' => false, 'statut' => 'brouillon']);

        $this->actingAs($eleve)
            ->get("/bibliotheque/{$document->slug}")
            ->assertForbidden();
        $this->actingAs($eleve)
            ->get("/bibliotheque/telecharger/{$document->id}")
            ->assertForbidden();
    }

    public function test_utilisateur_connecte_ne_peut_pas_commenter_un_brouillon_d_autrui(): void
    {
        $eleve = $this->createUserWithRole('eleve');
        $document = $this->createDocument(['is_public' => false, 'statut' => 'brouillon']);

        $this->actingAs($eleve)
            ->post("/bibliotheque/{$document->id}/commentaire", ['contenu' => 'Sans intérêt'])
            ->assertForbidden();
    }

    public function test_utilisateur_connecte_ne_peut_pas_mettre_en_favori_un_brouillon_d_autrui(): void
    {
        $eleve = $this->createUserWithRole('eleve');
        $document = $this->createDocument(['is_public' => false, 'statut' => 'brouillon']);

        $this->actingAs($eleve)
            ->post("/bibliotheque/{$document->id}/favori")
            ->assertForbidden();
    }

    public function test_auteur_peut_voir_son_propre_brouillon(): void
    {
        $auteur = $this->createUserWithRole('enseignant');
        $document = DocumentBibliotheque::create([
            'user_id' => $auteur->id,
            'titre' => 'Mon brouillon',
            'type_document_id' => $this->createType()->id,
            'is_public' => false,
            'statut' => 'brouillon',
        ]);

        $this->actingAs($auteur)
            ->get("/bibliotheque/{$document->slug}")
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRATION
    |--------------------------------------------------------------------------
    */

    public function test_admin_peut_voir_et_telecharger_n_importe_quel_document(): void
    {
        Storage::fake('private_media');

        $admin = $this->createUserWithRole('admin');
        $file = UploadedFile::fake()->create('prive.pdf', 20, 'application/pdf');

        $auteur = $this->createUserWithRole('enseignant');
        $this->actingAs($auteur);

        $document = app(DocumentBibliothequeService::class)->create([
            'titre' => 'Brouillon admin',
            'type_document_id' => $this->createType()->id,
            'is_public' => false,
            'statut' => 'brouillon',
        ], $file);

        $this->actingAs($admin)
            ->get("/bibliotheque/{$document->slug}")
            ->assertOk();
        $this->actingAs($admin)
            ->get("/bibliotheque/voir/{$document->id}")
            ->assertOk();
        $this->actingAs($admin)
            ->get("/bibliotheque/telecharger/{$document->id}")
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | STOCKAGE PRIVÉ
    |--------------------------------------------------------------------------
    */

    public function test_les_fichiers_documents_sont_stockes_sur_un_disque_prive(): void
    {
        Storage::fake('private_media');

        $auteur = $this->createUserWithRole('enseignant');
        $this->actingAs($auteur);

        $file = UploadedFile::fake()->create('document-test.pdf', 20, 'application/pdf');

        $document = app(DocumentBibliothequeService::class)->create([
            'titre' => 'Document stocké',
            'type_document_id' => $this->createType()->id,
            'is_public' => false,
            'statut' => 'brouillon',
        ], $file);

        $media = $document->getFirstMedia('document');

        $this->assertNotNull($media);
        $this->assertSame('private_media', $media->disk);
        $this->assertTrue(
            Storage::disk('private_media')->exists($media->getPathRelativeToRoot())
        );
        $this->assertFalse(
            Storage::disk('public')->exists($media->getPathRelativeToRoot())
        );
    }

    public function test_les_fichiers_prives_ne_sont_pas_servis_par_une_url_publique(): void
    {
        Storage::fake('private_media');

        $auteur = $this->createUserWithRole('enseignant');
        $this->actingAs($auteur);

        $file = UploadedFile::fake()->create('document-test.pdf', 20, 'application/pdf');

        $document = app(DocumentBibliothequeService::class)->create([
            'titre' => 'Document stocké',
            'type_document_id' => $this->createType()->id,
            'is_public' => false,
            'statut' => 'brouillon',
        ], $file);

        $media = $document->getFirstMedia('document');

        auth()->logout();

        $response = $this->get('/storage/' . $media->getPathRelativeToRoot());

        $this->assertNotSame(200, $response->getStatusCode());
    }
}
