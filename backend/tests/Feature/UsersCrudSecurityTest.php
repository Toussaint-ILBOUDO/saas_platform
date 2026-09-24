<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Étape 1 — C1 : les resources Parents / Élèves / Enseignants (et
 * l'activation de compte élève) doivent être réservées à l'administration.
 */
class UsersCrudSecurityTest extends TestCase
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

    private function createEleveWithUser(): Eleve
    {
        $parent = $this->createUserWithRole('parent');
        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = $this->createUserWithRole('eleve');

        return Eleve::create([
            'user_id' => $eleve->id,
            'parent_id' => $parent->id,
            'classe_id' => $classe->id,
            'statut' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VISITEUR — accès refusé (redirection vers la connexion)
    |--------------------------------------------------------------------------
    */

    public function test_visiteur_ne_peut_pas_lister_les_parents(): void
    {
        $this->get('/parents')->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_lister_les_eleves(): void
    {
        $this->get('/eleves')->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_lister_les_enseignants(): void
    {
        $this->get('/enseignants')->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_creer_de_parent(): void
    {
        $this->post('/parents', [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'password' => 'secret123',
        ])->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_creer_d_eleve(): void
    {
        $this->post('/eleves', [
            'nom' => 'Doe',
            'prenom' => 'Jean',
            'parent_id' => 1,
            'classe_id' => 1,
        ])->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_creer_d_enseignant(): void
    {
        $this->post('/enseignants', [
            'nom' => 'Doe',
            'prenom' => 'Marc',
            'password' => 'secret123',
        ])->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_modifier_un_parent(): void
    {
        $parent = $this->createUserWithRole('parent');

        $this->put("/parents/{$parent->id}", [
            'nom' => 'Nouveau',
            'prenom' => 'Nom',
        ])->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_modifier_un_enseignant(): void
    {
        $enseignant = $this->createUserWithRole('enseignant');

        $this->put("/enseignants/{$enseignant->id}", [
            'nom' => 'Nouveau',
            'prenom' => 'Nom',
        ])->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_activer_un_compte_eleve(): void
    {
        $eleve = $this->createEleveWithUser();

        $this->post("/eleves/{$eleve->id}/account", [
            'email' => 'nouveau@example.com',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
        ])->assertRedirect(route('login'));
    }

    /*
    |--------------------------------------------------------------------------
    | UTILISATEUR AUTHENTIFIÉ SANS RÔLE ADMIN — accès refusé (403)
    |--------------------------------------------------------------------------
    */

    public function test_parent_ne_peut_pas_lister_les_parents(): void
    {
        $user = $this->createUserWithRole('parent');

        $this->actingAs($user)->get('/parents')->assertForbidden();
    }

    public function test_parent_ne_peut_pas_lister_les_eleves(): void
    {
        $user = $this->createUserWithRole('parent');

        $this->actingAs($user)->get('/eleves')->assertForbidden();
    }

    public function test_parent_ne_peut_pas_lister_les_enseignants(): void
    {
        $user = $this->createUserWithRole('parent');

        $this->actingAs($user)->get('/enseignants')->assertForbidden();
    }

    public function test_parent_ne_peut_pas_creer_de_parent(): void
    {
        $user = $this->createUserWithRole('parent');

        $this->actingAs($user)->post('/parents', [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'password' => 'secret123',
        ])->assertForbidden();
    }

    public function test_parent_ne_peut_pas_modifier_un_parent(): void
    {
        $user = $this->createUserWithRole('parent');
        $cible = $this->createUserWithRole('parent');

        $this->actingAs($user)->put("/parents/{$cible->id}", [
            'nom' => 'Nouveau',
            'prenom' => 'Nom',
        ])->assertForbidden();
    }

    public function test_parent_ne_peut_pas_modifier_un_enseignant(): void
    {
        $user = $this->createUserWithRole('parent');
        $enseignant = $this->createUserWithRole('enseignant');

        $this->actingAs($user)->put("/enseignants/{$enseignant->id}", [
            'nom' => 'Nouveau',
            'prenom' => 'Nom',
        ])->assertForbidden();
    }

    public function test_parent_ne_peut_pas_activer_un_compte_eleve(): void
    {
        $user = $this->createUserWithRole('parent');
        $eleve = $this->createEleveWithUser();

        $this->actingAs($user)->post("/eleves/{$eleve->id}/account", [
            'email' => 'nouveau@example.com',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
        ])->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN — l'administration conserve ses accès légitimes
    |--------------------------------------------------------------------------
    */

    public function test_admin_peut_lister_les_parents(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)->get('/parents')->assertOk();
    }

    public function test_admin_peut_lister_les_eleves(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)->get('/eleves')->assertOk();
    }

    public function test_admin_peut_lister_les_enseignants(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)->get('/enseignants')->assertOk();
    }

    public function test_admin_peut_creer_un_parent(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)
            ->post('/parents', [
                'nom' => 'Doe',
                'prenom' => 'Jane',
                'email' => 'parent_creer@example.com',
                'password' => 'secret123',
            ])
            ->assertRedirect(route('parents.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'parent_creer@example.com',
        ]);
    }

    public function test_admin_peut_activer_un_compte_eleve(): void
    {
        $admin = $this->createUserWithRole('admin');
        $eleve = $this->createEleveWithUser();

        $this->actingAs($admin)
            ->post("/eleves/{$eleve->id}/account", [
                'email' => 'eleve_actif@example.com',
                'password' => 'motdepasse',
                'password_confirmation' => 'motdepasse',
            ])
            ->assertRedirect(route('eleves.edit', $eleve));

        $this->assertDatabaseHas('users', [
            'id' => $eleve->user_id,
            'email' => 'eleve_actif@example.com',
            'statut' => true,
        ]);
    }

    public function test_admin_peut_modifier_un_enseignant(): void
    {
        $admin = $this->createUserWithRole('admin');
        $enseignant = $this->createUserWithRole('enseignant');

        $this->actingAs($admin)
            ->put("/enseignants/{$enseignant->id}", [
                'nom' => 'NouveauNom',
                'prenom' => 'NouveauPrenom',
            ])
            ->assertRedirect(route('enseignants.index'));

        $this->assertDatabaseHas('users', [
            'id' => $enseignant->id,
            'nom' => 'NouveauNom',
        ]);
    }
}
