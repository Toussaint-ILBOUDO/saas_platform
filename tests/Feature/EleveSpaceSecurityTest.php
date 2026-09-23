<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\CahierTexte;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\EvaluationCours;
use App\Models\Matiere;
use App\Models\TypeCours;
use App\Models\User;
use App\Policies\CahierTextePolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Espace élève — fonctionnel & sécurité :
 * - A. activation du compte (statut eleve) qui débloque réellement la connexion ;
 * - B1. « Mes cours » filtrés à ses propres contrats ;
 * - B2. planning de ses séances (semaine) ;
 * - C3. cahiers de textes sécurisés pour l'élève (index + show) ;
 * - C4. évaluations limitées à ses propres enregistrements ;
 * - D. fiche élève (eleves.fiche) + dashboard.
 * - Règression affectations admin (admin double-role enseignant voit tout).
 */
class EleveSpaceSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function createUser(string $email, string $role, array $extra = []): User
    {
        $user = User::create(array_merge([
            'nom' => 'Nom',
            'prenom' => 'Prenom',
            'email' => $email,
            'password' => 'password',
        ], $extra));

        $user->assignRole($role);

        return $user;
    }

    private function createEnseignant(string $email): User
    {
        $user = $this->createUser($email, 'enseignant');
        EnseignantProfil::create(['user_id' => $user->id]);

        return $user;
    }

    private function createEleve(string $email, bool $statut = true, ?User $parent = null): array
    {
        $eleveUser = $this->createUser($email, 'eleve', ['statut' => $statut]);
        $parent = $parent ?? $this->createUser('parent_' . uniqid() . '@example.com', 'parent');
        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);

        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $parent->id,
            'classe_id' => $classe->id,
            'statut' => $statut,
        ]);

        return [$eleveUser, $eleve, $parent];
    }

    private function createContrat(Eleve $eleve, string $matiereNom = 'Mathématiques'): array
    {
        $typeCours = TypeCours::create(['libelle' => 'A domicile']);

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'statut' => 'actif',
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
        ]);

        $matiere = Matiere::create([
            'nom' => $matiereNom,
            'sigle' => strtoupper(substr($matiereNom, 0, 4)),
            'actif' => true,
        ]);

        return [$contrat, $matiere, $typeCours];
    }

    private function affecter(User $enseignant, ContratCours $contrat, Matiere $matiere): AffectationEnseignant
    {
        return AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $enseignant->enseignantProfil->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => 5000,
            'nombre_heures_prevues' => 4,
            'date_affectation' => now()->toDateString(),
            'statut' => 'actif',
        ]);
    }

    private function createCahier(AffectationEnseignant $affectation, string $contenu): CahierTexte
    {
        return CahierTexte::create([
            'affectation_enseignant_id' => $affectation->id,
            'date_seance' => now()->toDateString(),
            'heure_debut' => '09:00:00',
            'heure_fin' => '10:00:00',
            'duree_heures' => 1,
            'contenu_cours' => $contenu,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | A. ACTIVATION DU COMPTE — le statut eleve débloque réellement le login
    |--------------------------------------------------------------------------
    */

    public function test_eleve_inactif_ne_peut_pas_se_connecter(): void
    {
        [$eleveUser, ,] = $this->createEleve('inactif_es@example.com', false);

        $this->post('/login', [
            'email' => $eleveUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_activation_par_admin_met_le_statut_a_true_et_debloque_la_connexion(): void
    {
        $admin = $this->createUser('admin_act_es@example.com', 'admin');
        [$eleveUser, $eleve,] = $this->createEleve('eleve_act_es@example.com', false);

        $this->actingAs($admin)
            ->post('/eleves/' . $eleve->id . '/account', [
                'email' => 'eleve.actif@example.com',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ])
            ->assertRedirect(route('eleves.edit', $eleve->id));

        $this->assertTrue((bool) $eleve->refresh()->statut);
        $this->assertTrue((bool) $eleveUser->refresh()->statut);

        $this->post('/logout');

        $this->assertGuest();

        $this->post('/login', [
            'email' => 'eleve.actif@example.com',
            'password' => 'secret1234',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Élève');
    }

    /*
    |--------------------------------------------------------------------------
    | RÉGRESSION Tâche 0 — admin qui porte AUSSI un profil enseignant :
    | il doit voir TOUTES les affectations (pas seulement « ses » affectations).
    |--------------------------------------------------------------------------
    */

    public function test_admin_avec_profil_enseignant_voit_toutes_les_affectations(): void
    {
        $admin = $this->createUser('admin_dual_es@example.com', 'admin');
        EnseignantProfil::create(['user_id' => $admin->id]);

        $t1 = $this->createEnseignant('t1_es@example.com');
        $t2 = $this->createEnseignant('t2_es@example.com');

        [$eleveUser1, $eleve1,] = $this->createEleve('elv1_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('elv2_es@example.com');

        [$contrat1, $matiere1,] = $this->createContrat($eleve1, 'Physique');
        [$contrat2, $matiere2,] = $this->createContrat($eleve2, 'Anglais');

        $this->affecter($t1, $contrat1, $matiere1);
        $this->affecter($t2, $contrat2, $matiere2);

        $this->actingAs($admin);

        $this->get('/contrats/' . $contrat1->id)
            ->assertOk()
            ->assertSee($matiere1->nom)
            ->assertDontSee('Aucune affectation enregistrée');

        $this->get('/contrats/' . $contrat2->id)
            ->assertOk()
            ->assertSee($matiere2->nom)
            ->assertDontSee('Aucune affectation enregistrée');
    }

    /*
    |--------------------------------------------------------------------------
    | B1. MES COURS (élève) — uniquement ses propres contrats
    |--------------------------------------------------------------------------
    */

    public function test_eleve_mes_cours_filtre_ses_contrats(): void
    {
        [$eleveUser1, $eleve1,] = $this->createEleve('elv_mc1_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('elv_mc2_es@example.com');

        [$contrat1, ,] = $this->createContrat($eleve1, 'Français');
        [$contrat2, ,] = $this->createContrat($eleve2, 'Histoire');

        $this->actingAs($eleveUser1)
            ->get('/mes-cours')
            ->assertOk()
            ->assertSee('#' . $contrat1->id)
            ->assertDontSee('#' . $contrat2->id);
    }

    public function test_eleve_peut_consulter_son_contrat(): void
    {
        $t = $this->createEnseignant('ct_t_show_es@example.com');

        [$eleveUser, $eleve,] = $this->createEleve('elv_show_es@example.com');
        [$contrat, $matiere,] = $this->createContrat($eleve, 'SVT');

        $this->affecter($t, $contrat, $matiere);

        $this->actingAs($eleveUser)
            ->get('/contrats/' . $contrat->id)
            ->assertOk()
            ->assertSee('Contrat #' . $contrat->id)
            ->assertSee('SVT');
    }

    public function test_eleve_ne_peut_pas_consulter_le_contrat_d_un_autre_eleve(): void
    {
        [$eleveUser1, $eleve1,] = $this->createEleve('elv_a_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('elv_b_es@example.com');

        [$contrat2, ,] = $this->createContrat($eleve2, 'Physique');

        $this->actingAs($eleveUser1)
            ->get('/contrats/' . $contrat2->id)
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | B2. PLANNING — ses séances de la semaine, réservé élève
    |--------------------------------------------------------------------------
    */

    public function test_eleve_planning_affiche_seulement_ses_seances(): void
    {
        $t = $this->createEnseignant('plan_t_es@example.com');

        [$eleveUser1, $eleve1,] = $this->createEleve('elv_pl1_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('elv_pl2_es@example.com');

        [$contrat1, $matiere1,] = $this->createContrat($eleve1, 'Danses');
        [$contrat2, $matiere2,] = $this->createContrat($eleve2, 'Chant');

        $aff1 = $this->affecter($t, $contrat1, $matiere1);
        $aff2 = $this->affecter($t, $contrat2, $matiere2);

        $this->createCahier($aff1, 'Cours de danses du mercredi');
        $this->createCahier($aff2, 'Cours de chant du jeudi');

        $this->actingAs($eleveUser1)
            ->get('/planning')
            ->assertOk()
            ->assertSee('Danses')
            ->assertDontSee('Chant');
    }

    public function test_planning_reserve_au_role_eleve(): void
    {
        $enseignant = $this->createEnseignant('plan_forbidden@example.com');

        $this->actingAs($enseignant)
            ->get('/planning')
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | C3. CAHIERS DE TEXTES sécurisés pour l'élève (index + show + historique)
    |--------------------------------------------------------------------------
    */

    public function test_eleve_voit_seulement_ses_cahiers_de_textes(): void
    {
        $t = $this->createEnseignant('ct_t_es@example.com');

        [$eleveUser1, $eleve1,] = $this->createEleve('elv_ct1_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('elv_ct2_es@example.com');

        [$contrat1, $matiere1,] = $this->createContrat($eleve1, 'Guitare');
        [$contrat2, $matiere2,] = $this->createContrat($eleve2, 'Piano');

        $aff1 = $this->affecter($t, $contrat1, $matiere1);
        $aff2 = $this->affecter($t, $contrat2, $matiere2);

        $this->createCahier($aff1, 'Accords de base');
        $this->createCahier($aff2, 'Gammes de piano');

        $this->actingAs($eleveUser1)
            ->get('/cahiers-textes')
            ->assertOk()
            ->assertSee('Accords de base')
            ->assertDontSee('Gammes de piano');
    }

    public function test_eleve_peut_ouvrir_son_cahier_de_texte(): void
    {
        $t = $this->createEnseignant('ct_t2_es@example.com');

        [$eleveUser, $eleve,] = $this->createEleve('elv_ct_open_es@example.com');

        [$contrat, $matiere,] = $this->createContrat($eleve, 'Maths');
        $aff = $this->affecter($t, $contrat, $matiere);
        $cahier = $this->createCahier($aff, 'Dérivées');

        $this->actingAs($eleveUser)
            ->get('/cahiers-textes/' . $cahier->id)
            ->assertOk();
    }

    public function test_eleve_ne_peut_pas_ouvrir_le_cahier_d_un_autre(): void
    {
        $t = $this->createEnseignant('ct_t3_es@example.com');

        [$eleveUser1, $eleve1,] = $this->createEleve('elv_ct_other1_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('elv_ct_other2_es@example.com');

        [$contrat2, $matiere2,] = $this->createContrat($eleve2, 'Géographie');
        $aff2 = $this->affecter($t, $contrat2, $matiere2);
        $cahier2 = $this->createCahier($aff2, 'Reliefs');

        $this->actingAs($eleveUser1)
            ->get('/cahiers-textes/' . $cahier2->id)
            ->assertForbidden();
    }

    public function test_policy_historique_eleve_limitee_a_sa_propre_fiche(): void
    {
        $t = $this->createEnseignant('ct_h_es@example.com');

        [$eleveUser1, $eleve1,] = $this->createEleve('elv_hist1_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('elv_hist2_es@example.com');

        $policy = new CahierTextePolicy();

        $this->assertTrue($policy->viewHistory($eleveUser1, $eleve1->id));
        $this->assertFalse($policy->viewHistory($eleveUser1, $eleve2->id));
    }

    /*
    |--------------------------------------------------------------------------
    | C4. ÉVALUATIONS — l'élève ne voit que les siennes, réservé rôle eleve
    |--------------------------------------------------------------------------
    */

    public function test_eleve_evaluations_ne_voit_que_les_siennes(): void
    {
        $t = $this->createEnseignant('eval_t_es@example.com');
        $auteur = $this->createUser('auteur_es@example.com', 'admin');

        [$eleveUser1, $eleve1,] = $this->createEleve('elv_ev1_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('elv_ev2_es@example.com');

        EvaluationCours::create([
            'eleve_id' => $eleve1->id,
            'enseignant_id' => $t->enseignantProfil->id,
            'auteur_id' => $auteur->id,
            'note' => 4.5,
            'commentaire' => 'Très bon suivi',
        ]);

        EvaluationCours::create([
            'eleve_id' => $eleve2->id,
            'enseignant_id' => $t->enseignantProfil->id,
            'auteur_id' => $auteur->id,
            'note' => 3.0,
            'commentaire' => 'À améliorer',
        ]);

        $this->actingAs($eleveUser1)
            ->get('/evaluations')
            ->assertOk()
            ->assertSee('Très bon suivi')
            ->assertDontSee('À améliorer');
    }

    public function test_evaluations_reserve_au_role_eleve(): void
    {
        $enseignant = $this->createEnseignant('eval_forbidden@example.com');

        $this->actingAs($enseignant)
            ->get('/evaluations')
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | D. FICHE ÉLÈVE + DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function test_eleve_consulte_sa_propre_fiche(): void
    {
        [$eleveUser, $eleve,] = $this->createEleve('fiche_own_es@example.com');

        $this->actingAs($eleveUser)
            ->get('/eleves/' . $eleve->id . '/fiche')
            ->assertOk();
    }

    public function test_eleve_ne_peut_pas_consulter_la_fiche_d_un_autre(): void
    {
        [$eleveUser1, $eleve1,] = $this->createEleve('fiche_a_es@example.com');
        [$eleveUser2, $eleve2,] = $this->createEleve('fiche_b_es@example.com');

        $this->actingAs($eleveUser1)
            ->get('/eleves/' . $eleve2->id . '/fiche')
            ->assertForbidden();
    }

    public function test_dashboard_eleve_propose_les_acces_de_son_espace(): void
    {
        [$eleveUser, $eleve,] = $this->createEleve('dash_es@example.com');

        $this->withSession(['active_role' => 'eleve'])
            ->actingAs($eleveUser)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('mes-cours.index'), false)
            ->assertSee(route('planning.index'), false)
            ->assertSee(route('evaluations.index'), false)
            ->assertSee(route('cahiers-textes.index'), false)
            ->assertSee(route('eleves.fiche', $eleve), false);
    }
}