<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\CahierTexte;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Matiere;
use App\Models\TypeCours;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Partie 03 — Cohérence des routes et des vues :
 * route `login` unique, vues référencées par les controllers,
 * routes critiques protégées par le middleware `auth`.
 */
class RoutesSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Vues référencées par des controllers mais volontairement non créées
     * (routes non liées dans l'interface) — documentées dans AUDIT_PROJET.md.
     */
    private const DOCUMENTED_MISSING_VIEWS = [
        'pedagogie.classes.show',
        'pedagogie.matieres.show',
        'pdf.cahiers-textes.form',
    ];

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

    /*
    |--------------------------------------------------------------------------
    | ROUTE `login` UNIQUE
    |--------------------------------------------------------------------------
    */

    public function test_la_route_nommee_login_est_unique_et_pointe_sur_login(): void
    {
        $routes = app('router')->getRoutes();

        $loginRoutes = collect($routes->getRoutes())
            ->filter(fn ($route) => $route->getName() === 'login');

        $this->assertCount(1, $loginRoutes);

        $this->assertEquals(
            'login',
            $routes->getByName('login')->uri()
        );
    }

    public function test_route_legacy_connexion_supprimee_renvoie_404(): void
    {
        $this->get('/connexion')->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | VUES RÉFÉRENCÉES PAR LES CONTROLLERS
    |--------------------------------------------------------------------------
    */

    public function test_toutes_les_vues_referencees_par_les_controllers_existent(): void
    {
        $missing = [];

        foreach (glob(base_path('app/Modules') . '/**/*Controller.php') as $file) {
            $content = file_get_contents($file);

            preg_match_all("/view\(\s*['\"]([a-zA-Z0-9_.\-]+)['\"]/", $content, $matches);

            foreach ($matches[1] as $view) {
                if (in_array($view, self::DOCUMENTED_MISSING_VIEWS, true)) {
                    continue;
                }

                $path = resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');

                if (!file_exists($path)) {
                    $missing[] = "view('{$view}') <- " . basename($file);
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            'Vues référencées par les controllers mais absentes du disque :'
        );
    }

    public function test_la_vue_d_edition_d_un_cahier_texte_se_affiche(): void
    {
        $cahier = $this->createCahierTexte();

        ['enseignantUser' => $enseignant] = $cahier['users'];

        $this->actingAs($enseignant)
            ->get("/cahiers-textes/{$cahier['cahier']->id}/edit")
            ->assertOk();
    }

    public function test_la_vue_des_rapports_mensuels_se_affiche(): void
    {
        $user = $this->createUserWithRole('enseignant');

        $this->actingAs($user)
            ->get('/documents-administratifs/rapports')
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | PROTECTION DES ROUTES CRITIQUES
    |--------------------------------------------------------------------------
    */

    public function test_un_invite_est_redirige_vers_login_sur_les_routes_protegees(): void
    {
        $this->get('/documents-administratifs/rapports')
            ->assertRedirect(route('login'));

        $this->get('/documents-administratifs/cahiers-textes')
            ->assertRedirect(route('login'));

        $this->get('/cahiers-textes/1/edit')
            ->assertRedirect(route('login'));
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function createCahierTexte(): array
    {
        $enseignantUser = $this->createUserWithRole('enseignant');
        $profil = EnseignantProfil::create(['user_id' => $enseignantUser->id]);

        $parentUser = $this->createUserWithRole('parent');
        $eleveUser = $this->createUserWithRole('eleve');
        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $parentUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $typeCours = TypeCours::create(['libelle' => 'A domicile']);
        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
        ]);

        $matiere = Matiere::create([
            'nom' => 'Mathématiques',
            'sigle' => 'Maths',
            'actif' => true,
        ]);

        $affectation = AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $profil->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => 5000,
            'nombre_heures_prevues' => 4,
            'date_affectation' => now()->toDateString(),
            'statut' => 'actif',
        ]);

        $cahier = CahierTexte::create([
            'affectation_enseignant_id' => $affectation->id,
            'date_seance' => now()->toDateString(),
            'heure_debut' => '14:00',
            'heure_fin' => '16:00',
            'duree_heures' => 2,
            'contenu_cours' => 'Contenu de la séance de test.',
            'objectifs_atteints' => 'Objectifs atteints.',
            'observations' => null,
        ]);

        return [
            'cahier' => $cahier,
            'users' => [
                'enseignantUser' => $enseignantUser,
            ],
        ];
    }
}
