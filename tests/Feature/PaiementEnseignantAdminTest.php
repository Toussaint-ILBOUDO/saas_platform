<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\CahierTexte;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Matiere;
use App\Models\Notification;
use App\Models\PaiementEnseignant;
use App\Models\PeriodeComptable;
use App\Models\TypeCours;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paiements enseignants — admin (gestion finance) :
 * - accès index / create / show, génération via le service, notification ;
 * - blocs métier & sécurité (doublon, absence d'heures, rôles interdits).
 */
class PaiementEnseignantAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function createUser(string $email, string $role = 'enseignant'): User
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

    private function createEnseignant(string $email): array
    {
        $user = $this->createUser($email, 'enseignant');
        $profil = EnseignantProfil::create(['user_id' => $user->id]);

        return [$user, $profil];
    }

    private function createPeriode(
        string $label = 'Janvier 2026',
        string $debut = '2026-01-01',
        string $fin = '2026-01-31'
    ): PeriodeComptable {
        return PeriodeComptable::create([
            'label' => $label,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'type' => 'mensuel',
            'statut' => 'ouverte',
        ]);
    }

    private function seedScenario(): array
    {
        $admin = $this->createUser('admin@example.com', 'admin');
        [$enseignant, $profil] = $this->createEnseignant('ens@example.com');
        $eleveUser = $this->createUser('eleve@example.com', 'eleve');
        $parentUser = $this->createUser('parent@example.com', 'parent');

        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $parentUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => TypeCours::create(['libelle' => 'A domicile'])->id,
            'statut' => 'actif',
            'date_debut' => '2025-12-01',
            'date_fin' => '2026-06-30',
        ]);

        $matiere = Matiere::create([
            'nom' => 'Mathématiques',
            'sigle' => 'MATH',
            'actif' => true,
        ]);

        $affectation = AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $profil->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => 5000,
            'nombre_heures_prevues' => 4,
            'date_affectation' => '2025-12-15',
            'statut' => 'actif',
        ]);

        $periode = $this->createPeriode();

        // 2 séances DANS la période (janvier) => 2h × 5000 = 10 000 F
        CahierTexte::create([
            'affectation_enseignant_id' => $affectation->id,
            'date_seance' => '2026-01-10',
            'heure_debut' => '09:00:00',
            'heure_fin' => '10:00:00',
            'duree_heures' => 1,
            'contenu_cours' => 'Séance 1',
        ]);
        CahierTexte::create([
            'affectation_enseignant_id' => $affectation->id,
            'date_seance' => '2026-01-15',
            'heure_debut' => '14:00:00',
            'heure_fin' => '15:00:00',
            'duree_heures' => 1,
            'contenu_cours' => 'Séance 2',
        ]);

        // 1 séance HORS période (février) => ne doit pas être comptée
        CahierTexte::create([
            'affectation_enseignant_id' => $affectation->id,
            'date_seance' => '2026-02-05',
            'heure_debut' => '09:00:00',
            'heure_fin' => '10:00:00',
            'duree_heures' => 1,
            'contenu_cours' => 'Séance hors période',
        ]);

        return [
            'admin' => $admin,
            'enseignant' => $enseignant,
            'profil' => $profil,
            'eleveUser' => $eleveUser,
            'contrat' => $contrat,
            'affectation' => $affectation,
            'periode' => $periode,
        ];
    }

    private function validPayload(array $space): array
    {
        return [
            'enseignant_id' => $space['profil']->id,
            'contrat_cours_id' => $space['contrat']->id,
            'periode_id' => $space['periode']->id,
            'transaction_reference' => 'OM-123456',
        ];
    }

    public function test_admin_accede_a_l_index_des_paiements_enseignants(): void
    {
        $space = $this->seedScenario();

        $this->actingAs($space['admin'])
            ->get(route('finance.paiements-enseignants.index'))
            ->assertOk();

        $this->actingAs($this->createUser('super@example.com', 'super-admin'))
            ->get(route('finance.paiements-enseignants.index'))
            ->assertOk();
    }

    public function test_admin_genere_un_paiement_et_le_detail_est_correct(): void
    {
        $space = $this->seedScenario();

        $this->actingAs($space['admin'])
            ->post(
                route('finance.paiements-enseignants.store'),
                $this->validPayload($space)
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $paiement = PaiementEnseignant::first();

        $this->assertNotNull($paiement);
        $this->assertSame($space['profil']->id, $paiement->enseignant_id);
        $this->assertSame(2.0, (float) $paiement->total_heures_effectuees);
        $this->assertSame(10000, (int) $paiement->montant_total);
        $this->assertSame('paye', $paiement->statut);
        $this->assertSame('OM-123456', $paiement->transaction_reference);

        $this->assertSame(1, $paiement->lignes()->count());
        $this->assertSame(2.0, (float) $paiement->lignes()->first()->nombre_heures);
        $this->assertSame(10000, (int) $paiement->lignes()->first()->montant);
    }

    public function test_admin_consulte_le_formulaire_et_le_show(): void
    {
        $space = $this->seedScenario();

        $this->actingAs($space['admin'])
            ->post(
                route('finance.paiements-enseignants.store'),
                $this->validPayload($space)
            );

        $paiement = PaiementEnseignant::firstOrFail();
        $this->assertSame(1, $paiement->lignes()->count());

        $this->actingAs($space['admin'])
            ->get(route('finance.paiements-enseignants.create'))
            ->assertOk()
            ->assertSee('ens@example.com');

        $this->actingAs($space['admin'])
            ->get(route('finance.paiements-enseignants.show', $paiement))
            ->assertOk()
            ->assertSee('Mathématiques')
            ->assertSee('10 000 F');
    }

    public function test_lenseignant_est_notifie_du_paiement(): void
    {
        $space = $this->seedScenario();

        $this->actingAs($space['admin'])
            ->post(
                route('finance.paiements-enseignants.store'),
                $this->validPayload($space)
            );

        $paiement = PaiementEnseignant::firstOrFail();

        $notification = Notification::query()
            ->where('user_id', $space['enseignant']->id)
            ->where('type', 'paiement_enseignant')
            ->first();

        $this->assertNotNull(
            $notification,
            'La notification de paiement doit être créée pour l\'enseignant.'
        );
        $this->assertSame('Paiement reçu', $notification->titre);
        $this->assertSame(
            $paiement->id,
            $notification->data['paiement_id'] ?? null
        );
    }

    public function test_un_doublon_sur_la_meme_periode_est_bloque(): void
    {
        $space = $this->seedScenario();

        $this->actingAs($space['admin'])
            ->post(
                route('finance.paiements-enseignants.store'),
                $this->validPayload($space)
            );

        $this->actingAs($space['admin'])
            ->post(
                route('finance.paiements-enseignants.store'),
                $this->validPayload($space)
            )
            ->assertSessionHasErrors('paiement');

        $this->assertSame(
            1,
            PaiementEnseignant::query()->count()
        );
    }

    public function test_aucune_heure_sur_la_periode_est_bloque(): void
    {
        $space = $this->seedScenario();

        $payload = $this->validPayload($space);
        $payload['periode_id'] = $this->createPeriode(
            'Mars 2026',
            '2026-03-01',
            '2026-03-31'
        )->id;

        $this->actingAs($space['admin'])
            ->post(route('finance.paiements-enseignants.store'), $payload)
            ->assertSessionHasErrors('periode');

        $this->assertSame(
            0,
            PaiementEnseignant::query()->count()
        );
    }

    public function test_les_autres_roles_ne_peuvent_pas_gerer_les_paiements(): void
    {
        $space = $this->seedScenario();

        foreach ([
            $space['enseignant'],
            $space['eleveUser'],
            $this->createUser('parent-interdit@example.com', 'parent'),
        ] as $user) {
            $this->actingAs($user)
                ->get(route('finance.paiements-enseignants.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(
                    route('finance.paiements-enseignants.store'),
                    $this->validPayload($space)
                )
                ->assertForbidden();
        }
    }

    public function test_un_invite_est_redirige_vers_le_login(): void
    {
        $space = $this->seedScenario();

        $this->get(route('finance.paiements-enseignants.index'))
            ->assertRedirect(route('login'));
    }
}