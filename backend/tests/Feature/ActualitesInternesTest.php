<?php

namespace Tests\Feature;

use App\Models\Actualite;
use App\Models\User;
use App\Modules\Communication\Services\ActualiteService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Actualités internes (panel) — fonctionnel & sécurité.
 *
 * La page « Actualités internes » (route actualites.internes) est destinée
 * aux trois rôles de la communauté scolaire : eleve, enseignant, parent.
 * Chaque utilisateur ne voit que les actualités publiées et actives qui le
 * ciblent (champ « destinataires ») ainsi que les actualités globales.
 */
class ActualitesInternesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function createUser(string $email, string $role): User
    {
        $user = User::create([
            'nom' => 'Nom',
            'prenom' => 'Prenom',
            'email' => $email,
            'password' => 'password',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createActualite(
        string $titre,
        array $destinataires = [],
        string $statut = 'publie',
        bool $isActive = true
    ): Actualite {
        $auteur = $this->createUser('auteur-' . uniqid() . '@example.com', 'admin');

        return Actualite::create([
            'user_id' => $auteur->id,
            'titre' => $titre,
            'slug' => str()->slug($titre) . '-' . uniqid(),
            'contenu' => 'Contenu de : ' . $titre,
            'statut' => $statut,
            'published_at' => now(),
            'is_active' => $isActive,
            'destinataires' => $destinataires ?: null,
        ]);
    }

    private function getInternesResponse(User $user)
    {
        return $this->actingAs($user)
            ->get(route('actualites.internes'))
            ->assertOk();
    }

    public function test_eleve_ne_voit_que_ses_actualites_et_les_globales(): void
    {
        $eleve = $this->createUser('eleve@example.com', 'eleve');

        $this->createActualite('Élèves : rentrée', ['eleves']);
        $this->createActualite('Enseignants : réunion', ['enseignants']);
        $this->createActualite('Parents : réunion', ['parents']);
        $this->createActualite('Général : fête');

        $this->getInternesResponse($eleve)
            ->assertSee('Actualités internes')
            ->assertSee('Élèves : rentrée')
            ->assertSee('Général : fête')
            ->assertDontSee('Enseignants : réunion')
            ->assertDontSee('Parents : réunion');
    }

    public function test_enseignant_ne_voit_que_ses_actualites_et_les_globales(): void
    {
        $enseignant = $this->createUser('enseignant@example.com', 'enseignant');

        $this->createActualite('Enseignants : réunion', ['enseignants']);
        $this->createActualite('Élèves : rentrée', ['eleves']);
        $this->createActualite('Parents : réunion', ['parents']);

        $this->actingAs($enseignant)
            ->get(route('actualites.internes'))
            ->assertOk()
            ->assertSee('Enseignants : réunion')
            ->assertDontSee('Élèves : rentrée')
            ->assertDontSee('Parents : réunion');
    }

    public function test_parent_ne_voit_que_ses_actualites_et_les_globales(): void
    {
        $parent = $this->createUser('parent@example.com', 'parent');

        $this->createActualite('Parents : réunion', ['parents']);
        $this->createActualite('Élèves : rentrée', ['eleves']);

        $this->actingAs($parent)
            ->get(route('actualites.internes'))
            ->assertOk()
            ->assertSee('Parents : réunion')
            ->assertDontSee('Élèves : rentrée');
    }

    public function test_une_actualite_publiee_multidestinataires_est_vue_par_les_roles_concernes(): void
    {
        $this->createActualite('Communiqué commun', ['eleves', 'parents']);

        $eleve = $this->createUser('eleve2@example.com', 'eleve');
        $parent = $this->createUser('parent2@example.com', 'parent');
        $enseignant = $this->createUser('enseignant2@example.com', 'enseignant');

        $this->actingAs($eleve)->get(route('actualites.internes'))->assertSee('Communiqué commun');
        $this->actingAs($parent)->get(route('actualites.internes'))->assertSee('Communiqué commun');
        $this->actingAs($enseignant)->get(route('actualites.internes'))
            ->assertOk()
            ->assertDontSee('Communiqué commun');
    }

    public function test_utilisateur_a_plusieurs_roles_voit_les_flux_combines(): void
    {
        $user = $this->createUser('multi@example.com', 'eleve');
        $user->assignRole('parent');

        $this->createActualite('Pour élèves seulement', ['eleves']);
        $this->createActualite('Pour parents seulement', ['parents']);
        $this->createActualite('Pour enseignants seulement', ['enseignants']);

        $this->actingAs($user)
            ->get(route('actualites.internes'))
            ->assertOk()
            ->assertSee('Pour élèves seulement')
            ->assertSee('Pour parents seulement')
            ->assertDontSee('Pour enseignants seulement');
    }

    public function test_les_actualites_non_publiees_ou_inactives_sont_cachees(): void
    {
        $eleve = $this->createUser('eleve3@example.com', 'eleve');

        $this->createActualite('Brouillon : caché', ['eleves'], 'brouillon');
        $this->createActualite('Inactive : cachée', ['eleves'], 'publie', false);

        $this->actingAs($eleve)
            ->get(route('actualites.internes'))
            ->assertOk()
            ->assertSee('Aucune actualité pour le moment')
            ->assertDontSee('Brouillon : caché')
            ->assertDontSee('Inactive : cachée');
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get(route('actualites.internes'))
            ->assertRedirect(route('login'));
    }

    public function test_un_admin_naccede_pas_a_lespace_communautaire(): void
    {
        $admin = $this->createUser('admin@example.com', 'admin');

        $this->actingAs($admin)
            ->get(route('actualites.internes'))
            ->assertForbidden();
    }

    public function test_lacces_ne_requiert_pas_de_permission_actualite_view(): void
    {
        $parent = $this->createUser('parent4@example.com', 'parent');

        $this->assertFalse($parent->can('actualite.view'));

        $this->createActualite('Parents : réunion', ['parents']);

        $this->actingAs($parent)
            ->get(route('actualites.internes'))
            ->assertOk()
            ->assertSee('Parents : réunion');
    }

    public function test_lurl_publique_dune_actualite_interne_redirige_la_communaute_vers_lespace_internes(): void
    {
        $eleve = $this->createUser('eleve-redirect@example.com', 'eleve');

        $actualite = $this->createActualite('Rappel : rapport mensuel', ['enseignants']);

        $this->actingAs($eleve)
            ->get(route('actualites.show', $actualite->slug))
            ->assertRedirect(route('actualites.internes'));
    }

    public function test_lurl_publique_dune_actualite_interne_est_lisible_par_un_visiteur(): void
    {
        $actualite = $this->createActualite('Rappel : rapport mensuel', ['enseignants']);

        $this->get(route('actualites.show', $actualite->slug))
            ->assertOk()
            ->assertSee($actualite->titre);
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion_sur_la_lecture_interne(): void
    {
        $actualite = $this->createActualite('Rappel : calendrier', ['eleves']);

        $this->get(route('actualites.internes.show', $actualite->slug))
            ->assertRedirect(route('login'));
    }

    public function test_un_admin_naccede_pas_a_la_lecture_interne(): void
    {
        $admin = $this->createUser('admin-show@example.com', 'admin');

        $actualite = $this->createActualite('Rappel : calendrier', ['eleves']);

        $this->actingAs($admin)
            ->get(route('actualites.internes.show', $actualite->slug))
            ->assertForbidden();
    }

    public function test_un_role_lit_une_actualite_qui_le_cible(): void
    {
        $enseignant = $this->createUser('enseignant-show@example.com', 'enseignant');

        $actualite = $this->createActualite('Rappel : rapport mensuel', ['enseignants']);

        $this->actingAs($enseignant)
            ->get(route('actualites.internes.show', $actualite->slug))
            ->assertOk()
            ->assertSee($actualite->titre)
            ->assertSee('Contenu de : ' . $actualite->titre)
            ->assertSee('Retour aux actualités internes');
    }

    public function test_un_role_ne_lit_pas_une_actualite_destinee_a_un_autre_public(): void
    {
        $parent = $this->createUser('parent-show@example.com', 'parent');

        $actualite = $this->createActualite('Rappel : réunion enseignants', ['enseignants']);

        $this->actingAs($parent)
            ->get(route('actualites.internes.show', $actualite->slug))
            ->assertNotFound();
    }

    public function test_la_publication_active_automatiquement_lactualite(): void
    {
        $actualite = $this->createActualite('À publier', [], 'brouillon', false);

        $publiee = app(ActualiteService::class)->publish($actualite, ['eleves'], 'interne');

        $this->assertSame('publie', $publiee->statut);
        $this->assertTrue($publiee->fresh()->is_active);
    }
}