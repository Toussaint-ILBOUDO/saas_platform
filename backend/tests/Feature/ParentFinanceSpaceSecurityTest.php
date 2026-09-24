<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\CahierTexte;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Facture;
use App\Models\LigneFacture;
use App\Models\Matiere;
use App\Models\Notification;
use App\Models\PeriodeComptable;
use App\Models\TypeCours;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Espace parent — fonctionnel & sécurité :
 * - sidebar d'accès : factures, paiements, mes enfants, planning ;
 * - factures : liste propre (parent_id), show + PDF via la policy FacturePolicy ;
 * - notification 'facture' → finance.factures.show accessible au parent ;
 * - planning : séances des enfants (semaine) ;
 * - non-régression admin.
 */
class ParentFinanceSpaceSecurityTest extends TestCase
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

    private function createClasse(): Classe
    {
        return Classe::create([
            'nom' => 'Terminale',
            'sigle' => 'Tle' . uniqid(),
        ]);
    }

    private function createEleve(User $user, User $parent): Eleve
    {
        return Eleve::create([
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'classe_id' => $this->createClasse()->id,
            'statut' => true,
        ]);
    }

    private function createContrat(Eleve $eleve): ContratCours
    {
        return ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => TypeCours::create(['libelle' => 'A domicile'])->id,
            'statut' => 'actif',
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
        ]);
    }

    private function affecter(User $enseignant, ContratCours $contrat, string $matiereNom): AffectationEnseignant
    {
        $matiere = Matiere::create([
            'nom' => $matiereNom,
            'sigle' => strtoupper(substr(str_replace(' ', '', $matiereNom), 0, 4)),
            'actif' => true,
        ]);

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

    private function createCahier(AffectationEnseignant $affectation): CahierTexte
    {
        return CahierTexte::create([
            'affectation_enseignant_id' => $affectation->id,
            'date_seance' => now()->toDateString(),
            'heure_debut' => '09:00:00',
            'heure_fin' => '10:00:00',
            'duree_heures' => 1,
            'contenu_cours' => 'Cours parent',
        ]);
    }

    private function createPeriode(): PeriodeComptable
    {
        return PeriodeComptable::create([
            'label' => 'Janvier 2026',
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-01-31',
            'type' => 'mensuel',
            'statut' => 'ouverte',
        ]);
    }

    private function createFacture(
        ContratCours $contrat,
        User $parent,
        Eleve $eleve,
        PeriodeComptable $periode,
        string $numero,
        string $statut = 'en_attente',
        ?AffectationEnseignant $affectation = null
    ): Facture {
        $facture = Facture::create([
            'contrat_cours_id' => $contrat->id,
            'parent_id' => $parent->id,
            'eleve_id' => $eleve->id,
            'periode_id' => $periode->id,
            'numero_facture' => $numero,
            'montant_total' => 50000,
            'frais_suivi' => 0,
            'autres_frais' => 0,
            'remise' => 0,
            'statut_paiement' => $statut,
            'date_paiement' => $statut === 'payee' ? now()->toDateString() : null,
            'mode_paiement' => $statut === 'payee' ? 'especes' : null,
        ]);

        if ($affectation) {
            LigneFacture::create([
                'facture_id' => $facture->id,
                'affectation_enseignant_id' => $affectation->id,
                'nombre_heures' => 4,
                'taux_horaire' => 12000,
                'montant' => 48000,
            ]);
        }

        return $facture;
    }

    /**
     * Jeu de données complet : parent + enfant1 + enfant2, autre parent, factures.
     */
    private function seedSpace(): array
    {
        $parent = $this->createUser('parent@example.com', 'parent');
        $otherParent = $this->createUser('autre-parent@example.com', 'parent');
        $enseignant = $this->createEnseignant('enseignant@example.com');

        $eleveUser1 = $this->createUser('enfant1@example.com', 'eleve');
        $eleveUser2 = $this->createUser('enfant2@example.com', 'eleve');
        $eleveUser3 = $this->createUser('enfant3@example.com', 'eleve');

        $eleve1 = $this->createEleve($eleveUser1, $parent);
        $eleve2 = $this->createEleve($eleveUser2, $parent);
        $eleve3 = $this->createEleve($eleveUser3, $otherParent);

        $contrat1 = $this->createContrat($eleve1);
        $contrat2 = $this->createContrat($eleve2);
        $contrat3 = $this->createContrat($eleve3);

        $aff1 = $this->affecter($enseignant, $contrat1, 'Mathématiques');
        $aff2 = $this->affecter($enseignant, $contrat2, 'Physique');
        $this->affecter($enseignant, $contrat3, 'Français');

        $this->createCahier($aff1);
        $this->createCahier($aff2);

        $periode = $this->createPeriode();

        $facture1 = $this->createFacture($contrat1, $parent, $eleve1, $periode, 'FACT-P1', 'payee', $aff1);
        $facture2 = $this->createFacture($contrat2, $parent, $eleve2, $periode, 'FACT-P2', 'en_attente', $aff2);
        $facture3 = $this->createFacture($contrat3, $otherParent, $eleve3, $periode, 'FACT-OP', 'en_attente');

        return compact(
            'parent',
            'otherParent',
            'eleveUser1',
            'eleveUser2',
            'eleveUser3',
            'facture1',
            'facture2',
            'facture3',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MES FACTURES — liste propre au parent
    |--------------------------------------------------------------------------
    */

    public function test_parent_ne_voit_que_ses_factures(): void
    {
        $space = $this->seedSpace();

        $response = $this
            ->actingAs($space['parent'])
            ->get(route('mes-factures.index'));

        $response->assertOk();
        $response->assertSee('FACT-P1');
        $response->assertSee('FACT-P2');
        $response->assertDontSee('FACT-OP');
    }

    public function test_parent_peut_filtrer_ses_paiements_deja_regles(): void
    {
        $space = $this->seedSpace();

        $response = $this
            ->actingAs($space['parent'])
            ->get(route('mes-factures.index', ['statut' => 'payee']));

        $response->assertOk();
        $response->assertSee('FACT-P1');
        $response->assertDontSee('FACT-P2');
    }

    public function test_mes_factures_peut_etre_filtre_par_enfant(): void
    {
        $space = $this->seedSpace();

        $response = $this
            ->actingAs($space['parent'])
            ->get(route('mes-factures.index', ['eleve' => $space['facture1']->eleve_id]));

        $response->assertOk();
        $response->assertSee('FACT-P1');
        $response->assertDontSee('FACT-P2');
    }

    public function test_mes_factures_est_interdit_aux_autres_roles(): void
    {
        $space = $this->seedSpace();

        $this->actingAs($space['eleveUser1'])
            ->get(route('mes-factures.index'))
            ->assertForbidden();

        $this->actingAs($this->createUser('enseignant-bis@example.com', 'enseignant'))
            ->get(route('mes-factures.index'))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW + PDF — via FacturePolicy (parent propriétaire)
    |--------------------------------------------------------------------------
    */

    public function test_parent_ouvre_sa_facture_depuis_finance_factures_show(): void
    {
        $space = $this->seedSpace();

        $this->actingAs($space['parent'])
            ->get(route('finance.factures.show', $space['facture1']))
            ->assertOk()
            ->assertSee('FACT-P1');
    }

    public function test_parent_ne_peut_pas_ouvrir_une_facture_dautre_parent(): void
    {
        $space = $this->seedSpace();

        $this->actingAs($space['parent'])
            ->get(route('finance.factures.show', $space['facture3']))
            ->assertForbidden();

        $this->actingAs($space['otherParent'])
            ->get(route('finance.factures.show', $space['facture1']))
            ->assertForbidden();
    }

    public function test_parent_telecharge_et_affiche_le_pdf_de_sa_facture(): void
    {
        $space = $this->seedSpace();

        $this->actingAs($space['parent'])
            ->get(route('finance.factures.pdf', $space['facture1']))
            ->assertOk();

        $this->actingAs($space['parent'])
            ->get(route('finance.factures.pdf.download', $space['facture1']))
            ->assertOk();
    }

    public function test_parent_ne_peut_pas_acceder_a_la_gestion_finance_admin(): void
    {
        $space = $this->seedSpace();

        $this->actingAs($space['parent'])
            ->get(route('finance.factures.index'))
            ->assertForbidden();
    }

    public function test_notification_facture_pointe_vers_une_page_lisible_par_le_parent(): void
    {
        $space = $this->seedSpace();

        $notification = Notification::create([
            'user_id' => $space['parent']->id,
            'titre' => 'Nouvelle facture',
            'contenu' => 'Une facture est disponible.',
            'type' => 'facture',
            'data' => ['facture_id' => $space['facture2']->id],
        ]);

        $this->assertSame(
            route('finance.factures.show', $space['facture2']->id),
            $notification->url
        );

        $this->actingAs($space['parent'])
            ->get($notification->url)
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | MES ENFANTS
    |--------------------------------------------------------------------------
    */

    public function test_parent_liste_ses_enfants(): void
    {
        $space = $this->seedSpace();

        $response = $this
            ->actingAs($space['parent'])
            ->get(route('mes-enfants'));

        $response->assertOk();
        $response->assertSee('enfant1@example.com');
        $response->assertSee('enfant2@example.com');
        $response->assertDontSee('enfant3@example.com');
    }

    /*
    |--------------------------------------------------------------------------
    | PLANNING — séances des enfants
    |--------------------------------------------------------------------------
    */

    public function test_parent_voit_le_planning_des_seances_de_ses_enfants(): void
    {
        $space = $this->seedSpace();

        $response = $this
            ->actingAs($space['parent'])
            ->get(route('planning.index'));

        $response->assertOk();
        $response->assertSee('Mathématiques');
        $response->assertSee('Physique');
    }

    public function test_parent_ne_voit_pas_les_seances_des_enfants_des_autres(): void
    {
        $space = $this->seedSpace();

        // Frans' contrat3 has no cahier ; if we add one it must not appear for parent.
        $this->actingAs($space['parent'])
            ->get(route('planning.index'))
            ->assertOk()
            ->assertDontSee('Français');
    }

    public function test_enfant_accede_toujours_a_son_planning(): void
    {
        $space = $this->seedSpace();

        $this->actingAs($space['eleveUser1'])
            ->get(route('planning.index'))
            ->assertOk()
            ->assertSee('Mathématiques');
    }

    /*
    |--------------------------------------------------------------------------
    | NON-RÉGRESSION ADMIN
    |--------------------------------------------------------------------------
    */

    public function test_admin_conserve_lacces_a_la_gestion_factures(): void
    {
        $space = $this->seedSpace();

        $admin = $this->createUser('admin@example.com', 'admin');

        $this->actingAs($admin)
            ->get(route('finance.factures.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('finance.factures.show', $space['facture1']))
            ->assertOk();
    }
}