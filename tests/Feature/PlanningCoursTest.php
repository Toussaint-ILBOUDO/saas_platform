<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Matiere;
use App\Models\PlanningCours;
use App\Models\TypeCours;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Planning des cours (enseignant) — créneaux réguliers.
 *
 * L'enseignant planifie des créneaux récurrents (ex. tous les mercredis
 * 18h-19h) rattachés à ses affectations. Ils sont visibles par l'élève,
 * ses parents et les autres enseignants intervenant auprès du même élève.
 */
class PlanningCoursTest extends TestCase
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

    private function createContexte(string $matiereNom = 'Mathématiques'): array
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

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'statut' => 'actif',
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
        ]);

        $matiere = Matiere::create([
            'nom' => $matiereNom,
            'sigle' => str()->upper(substr($matiereNom, 0, 4)),
            'actif' => true,
        ]);

        return [$eleve, $eleveUser, $parent, $contrat, $matiere];
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

    private function donneesCreneau(AffectationEnseignant $affectation, array $overrides = []): array
    {
        return array_merge([
            'affectation_enseignant_id' => $affectation->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Enseignant : gestion du planning
    |--------------------------------------------------------------------------
    */

    public function test_enseignant_peut_planifier_un_creneau_recurrent(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte();
        $enseignant = $this->createEnseignant('ens_plan@example.com');
        $affectation = $this->affecter($enseignant, $contrat, $matiere);

        $this->actingAs($enseignant)
            ->post(route('planning-enseignant.store'), $this->donneesCreneau($affectation))
            ->assertRedirect(route('planning-enseignant.index'));

        $this->assertDatabaseHas('planning_cours', [
            'enseignant_id' => $enseignant->enseignantProfil->id,
            'affectation_enseignant_id' => $affectation->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ]);
    }

    public function test_enseignant_ne_peut_pas_utiliser_laffectation_dun_autre(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte();
        $enseignantA = $this->createEnseignant('ens_a@example.com');
        $affectation = $this->affecter($enseignantA, $contrat, $matiere);

        $enseignantB = $this->createEnseignant('ens_b@example.com');

        $this->actingAs($enseignantB)
            ->post(route('planning-enseignant.store'), $this->donneesCreneau($affectation))
            ->assertSessionHasErrors(['affectation_enseignant_id']);

        $this->assertDatabaseMissing('planning_cours', [
            'affectation_enseignant_id' => $affectation->id,
        ]);
    }

    public function test_un_creneau_duplique_est_refuse(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte();
        $enseignant = $this->createEnseignant('ens_dup@example.com');
        $affectation = $this->affecter($enseignant, $contrat, $matiere);

        $this->actingAs($enseignant)
            ->post(route('planning-enseignant.store'), $this->donneesCreneau($affectation))
            ->assertRedirect(route('planning-enseignant.index'));

        $this->actingAs($enseignant)
            ->post(route('planning-enseignant.store'), $this->donneesCreneau($affectation))
            ->assertSessionHasErrors(['jour_semaine']);
    }

    public function test_enseignant_voit_ses_creneaux_sur_son_planning(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte('Mathématiques');
        $enseignant = $this->createEnseignant('ens_voir@example.com');
        $affectation = $this->affecter($enseignant, $contrat, $matiere);

        PlanningCours::create([
            'enseignant_id' => $enseignant->enseignantProfil->id,
            'affectation_enseignant_id' => $affectation->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ]);

        $this->actingAs($enseignant)
            ->get(route('planning-enseignant.index'))
            ->assertOk()
            ->assertSee('Mon planning')
            ->assertSee('Mercredi')
            ->assertSee('Mathématiques')
            ->assertSee('18:00')
            ->assertSee('19:00');
    }

    public function test_enseignant_modifie_son_creneau(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte();
        $enseignant = $this->createEnseignant('ens_upd@example.com');
        $affectation = $this->affecter($enseignant, $contrat, $matiere);

        $this->actingAs($enseignant)
            ->post(route('planning-enseignant.store'), $this->donneesCreneau($affectation));

        $creneau = PlanningCours::where('affectation_enseignant_id', $affectation->id)->firstOrFail();

        $this->actingAs($enseignant)
            ->put(
                route('planning-enseignant.update', $creneau),
                $this->donneesCreneau($affectation, ['jour_semaine' => 4, 'heure_fin' => '20:00'])
            )
            ->assertRedirect(route('planning-enseignant.index'));

        $this->assertDatabaseHas('planning_cours', [
            'id' => $creneau->id,
            'jour_semaine' => 4,
            'heure_debut' => '18:00',
            'heure_fin' => '20:00',
        ]);
    }

    public function test_enseignant_supprime_son_creneau(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte();
        $enseignant = $this->createEnseignant('ens_del@example.com');
        $affectation = $this->affecter($enseignant, $contrat, $matiere);

        $this->actingAs($enseignant)
            ->post(route('planning-enseignant.store'), $this->donneesCreneau($affectation));

        $creneau = PlanningCours::where('affectation_enseignant_id', $affectation->id)->firstOrFail();

        $this->actingAs($enseignant)
            ->delete(route('planning-enseignant.destroy', $creneau))
            ->assertRedirect(route('planning-enseignant.index'));

        $this->assertDatabaseMissing('planning_cours', ['id' => $creneau->id]);
    }

    public function test_un_enseignant_ne_peut_pas_modifier_ni_supprimer_le_creneau_dun_autre(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte();
        $enseignantA = $this->createEnseignant('ens_owner@example.com');
        $affectationA = $this->affecter($enseignantA, $contrat, $matiere);

        $this->actingAs($enseignantA)
            ->post(route('planning-enseignant.store'), $this->donneesCreneau($affectationA));

        $creneau = PlanningCours::where('affectation_enseignant_id', $affectationA->id)->firstOrFail();

        $enseignantB = $this->createEnseignant('ens_intrus@example.com');

        $this->actingAs($enseignantB)
            ->put(
                route('planning-enseignant.update', $creneau),
                $this->donneesCreneau($affectationA, ['heure_fin' => '21:00'])
            )
            ->assertForbidden();

        $this->actingAs($enseignantB)
            ->delete(route('planning-enseignant.destroy', $creneau))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Visibilité entre enseignants du même élève
    |--------------------------------------------------------------------------
    */

    public function test_un_enseignant_voit_les_creneaux_des_autres_enseignants_du_meme_eleve(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiereMaths] = $this->createContexte('Mathématiques');
        $matierePhysique = Matiere::create(['nom' => 'Physique', 'sigle' => 'Phys', 'actif' => true]);

        $enseignantA = $this->createEnseignant('ens_partage_a@example.com');
        $this->affecter($enseignantA, $contrat, $matiereMaths);

        $enseignantB = $this->createEnseignant('ens_partage_b@example.com');
        $affectationPhysique = $this->affecter($enseignantB, $contrat, $matierePhysique);

        PlanningCours::create([
            'enseignant_id' => $enseignantA->enseignantProfil->id,
            'affectation_enseignant_id' => $affectationPhysique->id,
            'jour_semaine' => 5,
            'heure_debut' => '17:00',
            'heure_fin' => '18:00',
        ]);

        $this->actingAs($enseignantB)
            ->get(route('planning-enseignant.index'))
            ->assertOk()
            ->assertSee('Créneaux des autres enseignants de mes élèves')
            ->assertSee('Physique')
            ->assertSee('17:00');
    }

    public function test_un_enseignant_sans_eleve_commun_ne_voit_pas_les_creneaux_des_autres(): void
    {
        [$eleveA, $eleveUserA, $parentA, $contratA, $matiereMaths] = $this->createContexte('Mathématiques');
        $enseignantA = $this->createEnseignant('ens_iso_a@example.com');
        $this->affecter($enseignantA, $contratA, $matiereMaths);

        [$eleveB, $eleveUserB, $parentB, $contratB, $matierePhysique] = $this->createContexte('Physique');
        $enseignantC = $this->createEnseignant('ens_iso_c@example.com');
        $this->affecter($enseignantC, $contratB, $matierePhysique);

        PlanningCours::create([
            'enseignant_id' => $enseignantA->enseignantProfil->id,
            'affectation_enseignant_id' => AffectationEnseignant::where('enseignant_id', $enseignantA->enseignantProfil->id)->firstOrFail()->id,
            'jour_semaine' => 2,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ]);

        $this->actingAs($enseignantC)
            ->get(route('planning-enseignant.index'))
            ->assertOk()
            ->assertDontSee('Mathématiques');
    }

    /*
    |--------------------------------------------------------------------------
    | Visibilité élève / parent
    |--------------------------------------------------------------------------
    */

    public function test_eleve_et_parent_voient_les_creneaux_des_enseignants_de_leur_eleve(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte('Mathématiques');
        $enseignant = $this->createEnseignant('ens_visibilite@example.com');
        $affectation = $this->affecter($enseignant, $contrat, $matiere);

        PlanningCours::create([
            'enseignant_id' => $enseignant->enseignantProfil->id,
            'affectation_enseignant_id' => $affectation->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ]);

        $this->actingAs($eleveUser)
            ->get(route('planning.index'))
            ->assertOk()
            ->assertSee('Créneaux réguliers des enseignants')
            ->assertSee('Mercredi')
            ->assertSee('Mathématiques')
            ->assertSee('18:00');

        $this->actingAs($parent)
            ->get(route('planning.index'))
            ->assertOk()
            ->assertSee('Mathématiques')
            ->assertSee('18:00');
    }

    public function test_un_eleve_ne_voit_pas_les_creneaux_des_autres_eleves(): void
    {
        [$eleve1, $eleveUser1, $parent1, $contrat1, $matiere1] = $this->createContexte('Mathématiques');
        [$eleve2, $eleveUser2, $parent2, $contrat2, $matiere2] = $this->createContexte('Physique');

        $enseignant1 = $this->createEnseignant('ens_iso_eleve1@example.com');
        $this->affecter($enseignant1, $contrat1, $matiere1);

        $enseignant2 = $this->createEnseignant('ens_iso_eleve2@example.com');
        $affectation2 = $this->affecter($enseignant2, $contrat2, $matiere2);

        PlanningCours::create([
            'enseignant_id' => $enseignant2->enseignantProfil->id,
            'affectation_enseignant_id' => $affectation2->id,
            'jour_semaine' => 4,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ]);

        $this->actingAs($eleveUser1)
            ->get(route('planning.index'))
            ->assertOk()
            ->assertDontSee('Physique');
    }

    /*
    |--------------------------------------------------------------------------
    | Sécurité des routes
    |--------------------------------------------------------------------------
    */

    public function test_un_visiteur_est_redirige_vers_la_connexion_pour_le_planning_enseignant(): void
    {
        $this->get(route('planning-enseignant.index'))
            ->assertRedirect(route('login'));
    }

    public function test_un_admin_naccede_pas_au_planning_enseignant(): void
    {
        $admin = $this->createUser('admin_planning@example.com', 'admin');

        $this->actingAs($admin)
            ->get(route('planning-enseignant.index'))
            ->assertForbidden();
    }

    public function test_un_eleve_ne_peut_pas_planifier_de_creneau(): void
    {
        [$eleve, $eleveUser, $parent, $contrat, $matiere] = $this->createContexte();
        $enseignant = $this->createEnseignant('ens_nequant@example.com');
        $affectation = $this->affecter($enseignant, $contrat, $matiere);

        $this->actingAs($eleveUser)
            ->post(route('planning-enseignant.store'), $this->donneesCreneau($affectation))
            ->assertForbidden();

        $this->assertDatabaseMissing('planning_cours', [
            'affectation_enseignant_id' => $affectation->id,
        ]);
    }
}