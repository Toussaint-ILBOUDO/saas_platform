<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
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
 * « Mes cours » — Espace enseignant :
 * - accès aux contrats affectés uniquement ;
 * - security : contrats d'autres enseignants invisibles ;
 * - création de contrat réservée aux admins ;
 * - comportement admin/super-admin inchangé.
 */
class MesCoursSecurityTest extends TestCase
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

    private function createEnseignant(string $email): User
    {
        $user = $this->createUser($email, 'enseignant');
        EnseignantProfil::create(['user_id' => $user->id]);

        return $user;
    }

    private function createContrat(array $overrides = []): array
    {
        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);

        $eleveUser = $this->createUser('eleve_' . uniqid() . '@example.com', 'eleve');
        $parent = $this->createUser('parent_' . uniqid() . '@example.com', 'parent');

        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $parent->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $typeCours = TypeCours::create(['libelle' => 'A domicile']);

        $contrat = ContratCours::create(array_merge([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'statut' => 'actif',
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
        ], $overrides));

        $matiere = Matiere::create([
            'nom' => 'Mathématiques',
            'sigle' => 'Maths',
            'actif' => true,
        ]);

        return [$contrat, $matiere, $eleve, $typeCours];
    }

    private function affecter(User $enseignant, ContratCours $contrat, Matiere $matiere): void
    {
        AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $enseignant->enseignantProfil->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => 5000,
            'nombre_heures_prevues' => 4,
            'date_affectation' => now()->toDateString(),
            'statut' => 'actif',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SCÉNARIO 1 & 2 — Admin / Super-admin : comportement inchangé
    |--------------------------------------------------------------------------
    */

    public function test_admin_voit_tous_les_contrats_et_peut_creer(): void
    {
        $admin = $this->createUser('admin_mc@example.com', 'admin');
        $this->actingAs($admin);

        [$contratA, , ,] = $this->createContrat();
        [$contratB, , ,] = $this->createContrat();

        $this->get('/contrats')
            ->assertOk()
            ->assertSee('#' . $contratA->id)
            ->assertSee('#' . $contratB->id);

        $this->get('/contrats/create')->assertOk();
    }

    public function test_super_admin_voit_tous_les_contrats(): void
    {
        $superAdmin = $this->createUser('superadmin_mc@example.com', 'super-admin');
        $this->actingAs($superAdmin);

        [$contratA, , ,] = $this->createContrat();

        $this->get('/contrats')
            ->assertOk()
            ->assertSee('#' . $contratA->id);
    }

    /*
    |--------------------------------------------------------------------------
    | SCÉNARIO 3 — Enseignant avec affectations : uniquement SES contrats
    |--------------------------------------------------------------------------
    */

    public function test_enseignant_mes_cours_filtre_par_affectation(): void
    {
        $jean = $this->createEnseignant('jean_mc@example.com');
        $paul = $this->createEnseignant('paul_mc@example.com');

        [$contratJean1, $matiere1, ,] = $this->createContrat();
        [$contratJean2, $matiere2, ,] = $this->createContrat();
        [$contratPaul, $matiere3, ,] = $this->createContrat();

        $this->affecter($jean, $contratJean1, $matiere1);
        $this->affecter($jean, $contratJean2, $matiere2);
        $this->affecter($paul, $contratPaul, $matiere3);

        $this->actingAs($jean)
            ->get('/mes-cours')
            ->assertOk()
            ->assertSee('#' . $contratJean1->id)
            ->assertSee('#' . $contratJean2->id)
            ->assertDontSee('#' . $contratPaul->id);
    }

    /*
    |--------------------------------------------------------------------------
    | SCÉNARIO 4 — Enseignant sans affectation : liste vide, pas de 403
    |--------------------------------------------------------------------------
    */

    public function test_enseignant_sans_affectation_voit_message_vide(): void
    {
        $enseignant = $this->createEnseignant('sans_mc@example.com');

        [$contratAutre, , ,] = $this->createContrat();

        $this->actingAs($enseignant)
            ->get('/mes-cours')
            ->assertOk()
            ->assertSee('Aucun cours ne vous est actuellement affecté')
            ->assertDontSee('#' . $contratAutre->id);
    }

    /*
    |--------------------------------------------------------------------------
    | SCÉNARIO 5 — Accès direct à un contrat d'un autre enseignant → 403
    |--------------------------------------------------------------------------
    */

    public function test_enseignant_ne_peut_pas_consulter_contrat_d_un_autre(): void
    {
        $jean = $this->createEnseignant('jean2_mc@example.com');
        $paul = $this->createEnseignant('paul2_mc@example.com');

        [$contratPaul, $matiere, ,] = $this->createContrat();
        $this->affecter($paul, $contratPaul, $matiere);

        $this->actingAs($jean)
            ->get('/contrats/' . $contratPaul->id)
            ->assertForbidden();
    }

    public function test_enseignant_peut_consulter_son_contrat(): void
    {
        $jean = $this->createEnseignant('jean3_mc@example.com');

        [$contratJean, $matiere, ,] = $this->createContrat();
        $this->affecter($jean, $contratJean, $matiere);

        $this->actingAs($jean)
            ->get('/contrats/' . $contratJean->id)
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | SCÉNARIO 6 — Enseignant ne peut pas créer un contrat (GET + POST)
    |--------------------------------------------------------------------------
    */

    public function test_enseignant_ne_peut_pas_acceder_a_la_creation(): void
    {
        $enseignant = $this->createEnseignant('create1_mc@example.com');

        $this->actingAs($enseignant)
            ->get('/contrats/create')
            ->assertForbidden();
    }

    public function test_enseignant_ne_peut_pas_creer_contrat_par_post_direct(): void
    {
        $enseignant = $this->createEnseignant('create2_mc@example.com');

        $this->actingAs($enseignant)
            ->post('/contrats', [])
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLÉMENTS — Sidebar et rôles non autorisés
    |--------------------------------------------------------------------------
    */

    public function test_route_mes_cours_reservee_au_role_enseignant(): void
    {
        $parent = $this->createUser('parent_mc@example.com', 'parent');

        $this->actingAs($parent)
            ->get('/mes-cours')
            ->assertForbidden();
    }

    public function test_retour_du_show_pointe_vers_mes_cours_pour_enseignant(): void
    {
        $jean = $this->createEnseignant('retour_mc@example.com');

        [$contratJean, $matiere, ,] = $this->createContrat();
        $this->affecter($jean, $contratJean, $matiere);

        $this->actingAs($jean)
            ->get('/contrats/' . $contratJean->id)
            ->assertOk()
            ->assertSee(route('mes-cours.index'));
    }
}