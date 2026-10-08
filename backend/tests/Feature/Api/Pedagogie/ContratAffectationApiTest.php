<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\LigneFacture;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API contrats de cours + affectations (T7A.3).
 *
 * L'affectation est la pièce dont dépend toute la facturation (D-049) et toute
 * la rémunération (D-048). Les tests verrouillent donc trois choses :
 *  - le refus d'affecter un enseignant non compétent sur la matière ;
 *  - le gel du taux horaire dès que des heures sont facturées ;
 *  - l'absence de toute route DELETE, à cause de la cascade
 *    contrat → affectations → lignes de facture / de bulletin.
 */
class ContratAffectationApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecteAdmin(string $slug): void
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);
        User::where('email', "admin@{$slug}.local")
            ->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => "admin@{$slug}.local",
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function api(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/pedagogie{$chemin}";
    }

    /**
     * Prépare un cabinet avec : un élève, un enseignant compétent en maths,
     * un second enseignant non compétent, un type de cours, et renvoie les ids.
     *
     * @return array<string, int>
     */
    private function socle(string $slug): array
    {
        tenancy()->initialize($slug);

        $admin = User::where('email', "admin@{$slug}.local")->first();

        $eleveUser = User::create([
            'nom' => 'Ouédraogo',
            'prenom' => 'Ibrahim',
            'email' => "ibrahim@{$slug}.local",
            'password' => Hash::make('Secret1234'),
        ]);
        $parentUser = User::create([
            'nom' => 'Ouédraogo',
            'prenom' => 'Mère',
            'email' => "mere@{$slug}.local",
            'password' => Hash::make('Secret1234'),
        ]);
        $classe = \App\Models\Classe::create([
            'nom' => 'Terminale',
            'sigle' => 'Tle',
        ]);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $parentUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $matiere = \App\Models\Matiere::create([
            'nom' => 'Mathématiques',
            'sigle' => 'MATH',
        ]);

        // Enseignant compétent en maths.
        $prof = $admin->enseignantProfil()->firstOrCreate([], []);
        $prof2 = \App\Models\EnseignantProfil::where('user_id', $admin->id)->first();

        // Un second enseignant, sans la matière.
        $autreUser = User::create([
            'nom' => 'Sawadogo',
            'prenom' => 'Abdoulaye',
            'email' => "abdoulaye@{$slug}.local",
            'password' => Hash::make('Secret1234'),
        ]);
        $autre = \App\Models\EnseignantProfil::create(['user_id' => $autreUser->id]);

        $prof2->matieres()->sync([$matiere->id]);

        $typeCours = TypeCours::create([
            'code' => 'DOM',
            'libelle' => 'À domicile',
            'actif' => true,
        ]);

        return [
            'eleve_id' => (int) $eleve->id,
            'matiere_id' => (int) $matiere->id,
            'enseignant_id' => (int) $prof2->id,
            'autre_enseignant_id' => (int) $autre->id,
            'type_cours_id' => (int) $typeCours->id,
        ];
    }

    private function contratValide(array $socle, array $surcharge = []): array
    {
        return array_merge([
            'eleve_id' => $socle['eleve_id'],
            'type_cours_id' => $socle['type_cours_id'],
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-09-30',
            'autres_frais_suivi' => 0,
            'affectations' => [[
                'enseignant_id' => $socle['enseignant_id'],
                'matiere_id' => $socle['matiere_id'],
                'taux_horaire_enseignant' => 2500,
                'nombre_heures_prevues' => 4,
            ]],
        ], $surcharge);
    }

    /**
     * Facture d'appoint pour un contrat : c'est elle qui rend le taux horaire
     * de l'affectation economicement figé (D-049).
     *
     * Appelé tenancy déjà initialisé sur la bonne base.
     */
    private function facture(array $socle, int $contratId): \App\Models\Facture
    {
        return \App\Models\Facture::create([
            'contrat_cours_id' => $contratId,
            'parent_id' => \App\Models\Eleve::find($socle['eleve_id'])->parent_id,
            'eleve_id' => $socle['eleve_id'],
            'periode_id' => \App\Models\PeriodeComptable::create([
                'label' => 'Sept 2026',
                'date_debut' => '2026-09-01',
                'date_fin' => '2026-09-30',
                'type' => 'mensuel',
            ])->id,
            'numero_facture' => 'FAC-' . uniqid(),
            'montant_total' => 10000,
            'statut_paiement' => 'en_attente',
        ]);
    }

    // ------------------------------------------------------------- contrats

    public function test_creation_contrat_avec_affectation(): void
    {
        $this->connecteAdmin('c1');
        $socle = $this->socle('c1');

        $response = $this->postJson($this->api('c1', '/contrats'), $this->contratValide($socle));

        $response->assertCreated();
        $response->assertJsonPath('data.statut', 'actif');
        $response->assertJsonCount(1, 'data.affectations');

        // Le taux et les heures prevues sont exposés : ce sont les paramètres
        // financiers qui alimentent facture et bulletin.
        $response->assertJsonPath('data.affectations.0.taux_horaire_enseignant', 2500);
        $response->assertJsonPath('data.affectations.0.nombre_heures_prevues', 4);
        $response->assertJsonPath('data.affectations.0.montant_prevu', 10000);
        $response->assertJsonPath('data.affectations.0.statut', 'actif');

        tenancy()->initialize('c1');
        $this->assertDatabaseHas('contrat_cours', ['id' => $response->json('data.id'), 'statut' => 'actif']);
        tenancy()->end();
    }

    public function test_enseignant_non_competent_refuse(): void
    {
        $this->connecteAdmin('c2');
        $socle = $this->socle('c2');

        $payload = $this->contratValide($socle, [
            'affectations' => [[
                'enseignant_id' => $socle['autre_enseignant_id'],
                'matiere_id' => $socle['matiere_id'],
                'taux_horaire_enseignant' => 2500,
                'nombre_heures_prevues' => 4,
            ]],
        ]);

        $response = $this->postJson($this->api('c2', '/contrats'), $payload);

        // 422 propre, pas un 500 : la règle est une ValidationException.
        $response->assertStatus(422);
        $response->assertJsonPath('code', 'VALIDATION');
        $this->assertStringContainsString(
            "pas déclaré compétent",
            $response->json('erreurs.affectations.0')
        );
    }

    public function test_heures_prevues_fractionnaires_acceptees(): void
    {
        $this->connecteAdmin('c3');
        $socle = $this->socle('c3');

        // La colonne est decimal(5,2) : 1,5 h doit passer.
        $this->postJson($this->api('c3', '/contrats'), $this->contratValide($socle, [
            'affectations' => [[
                'enseignant_id' => $socle['enseignant_id'],
                'matiere_id' => $socle['matiere_id'],
                'taux_horaire_enseignant' => 2000,
                'nombre_heures_prevues' => 1.5,
            ]],
        ]))->assertCreated();
    }

    public function test_aucune_route_delete_sur_contrat_ou_affectation(): void
    {
        $this->connecteAdmin('c4');
        $socle = $this->socle('c4');

        $contratId = $this->postJson($this->api('c4', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        tenancy()->initialize('c4');
        $affectationId = AffectationEnseignant::where('contrat_cours_id', $contratId)->value('id');
        tenancy()->end();

        // La cascade contrat -> affectations -> lignes de facture / bulletin
        // effacerait des heures déjà payées : aucun DELETE n'est routé.
        $this->deleteJson($this->api('c4', "/contrats/{$contratId}"))->assertStatus(405);
        $this->deleteJson($this->api('c4', "/contrats/{$contratId}/affectations/{$affectationId}"))
            ->assertStatus(405);
    }

    public function test_suspension_contrat_suspend_les_affectations(): void
    {
        $this->connecteAdmin('c5');
        $socle = $this->socle('c5');

        $contratId = $this->postJson($this->api('c5', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        $suspendu = $this->patchJson($this->api('c5', "/contrats/{$contratId}/statut"), [
            'statut' => 'suspendu',
        ]);

        $suspendu->assertOk();
        $suspendu->assertJsonPath('data.statut', 'suspendu');

        // Les affectations actives suivent le contrat : laisser « actif » ferait
        // croire que des heures peuvent encore être facturées.
        $suspendu->assertJsonPath('data.affectations.0.statut', 'suspendu');
    }

    public function test_contrat_suspendu_nest_plus_modifiable(): void
    {
        $this->connecteAdmin('c6');
        $socle = $this->socle('c6');

        $contratId = $this->postJson($this->api('c6', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        $this->patchJson($this->api('c6', "/contrats/{$contratId}/statut"), [
            'statut' => 'suspendu',
        ])->assertOk();

        $this->putJson($this->api('c6', "/contrats/{$contratId}"), [
            'date_debut' => '2026-09-15',
            'date_fin' => '2026-09-30',
        ])->assertStatus(422);
    }

    // ---------------------------------------------------------- affectations

    public function test_ajout_affectation_supplementaire(): void
    {
        $this->connecteAdmin('c7');
        $socle = $this->socle('c7');

        $contratId = $this->postJson($this->api('c7', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        // Deuxième matière pour le même enseignant.
        $matiere2 = null;
        tenancy()->initialize('c7');
        $matiere2 = \App\Models\Matiere::create(['nom' => 'Physique', 'sigle' => 'PHY']);
        $prof = \App\Models\EnseignantProfil::find($socle['enseignant_id']);
        $prof->matieres()->syncWithoutDetaching([$matiere2->id]);
        tenancy()->end();

        $response = $this->postJson($this->api('c7', "/contrats/{$contratId}/affectations"), [
            'enseignant_id' => $socle['enseignant_id'],
            'matiere_id' => (int) $matiere2->id,
            'taux_horaire_enseignant' => 3000,
            'nombre_heures_prevues' => 2,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.statut', 'actif');

        $this->getJson($this->api('c7', "/contrats/{$contratId}"))
            ->assertJsonCount(2, 'data.affectations');
    }

    public function test_affectation_dupliquee_refusee(): void
    {
        $this->connecteAdmin('c8');
        $socle = $this->socle('c8');

        $contratId = $this->postJson($this->api('c8', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        // Même enseignant, même matière : sinon la facturation compterait deux
        // fois les heures de la même ligne de rapport.
        $this->postJson($this->api('c8', "/contrats/{$contratId}/affectations"), [
            'enseignant_id' => $socle['enseignant_id'],
            'matiere_id' => $socle['matiere_id'],
            'taux_horaire_enseignant' => 2500,
            'nombre_heures_prevues' => 4,
        ])->assertStatus(422);
    }

    public function test_matiere_dune_affectation_est_immuable(): void
    {
        $this->connecteAdmin('c9');
        $socle = $this->socle('c9');

        $contratId = $this->postJson($this->api('c9', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        tenancy()->initialize('c9');
        $affectationId = AffectationEnseignant::where('contrat_cours_id', $contratId)->value('id');
        $autreMatiere = \App\Models\Matiere::create(['nom' => 'Chimie', 'sigle' => 'CHI']);
        tenancy()->end();

        // Changer la matière déplacerait les heures déjà rattachées vers une
        // autre ligne de rapport : c'est un report comptable, pas une correction.
        $this->patchJson($this->api('c9', "/contrats/{$contratId}/affectations/{$affectationId}"), [
            'matiere_id' => (int) $autreMatiere->id,
        ])->assertStatus(422);

        // Renvoyer la même matière est un simple aller-retour : accepté.
        $this->patchJson($this->api('c9', "/contrats/{$contratId}/affectations/{$affectationId}"), [
            'matiere_id' => $socle['matiere_id'],
            'nombre_heures_prevues' => 6,
        ])->assertOk();
    }

    public function test_taux_horaire_gele_des_quil_y_a_des_heures_facturees(): void
    {
        $this->connecteAdmin('c10');
        $socle = $this->socle('c10');

        $contratId = $this->postJson($this->api('c10', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        tenancy()->initialize('c10');
        $affectationId = AffectationEnseignant::where('contrat_cours_id', $contratId)->value('id');

        // Une ligne de facture existe : le taux est la source du bulletin (D-048)
        // et du montant facturé (D-049). Le changer après coup ferait diverger
        // la facture déjà émise de la rémunération due.
        LigneFacture::create([
            'facture_id' => $this->facture($socle, $contratId)->id,
            'affectation_enseignant_id' => $affectationId,
            'nombre_heures' => 4,
            'taux_horaire' => 2500,
            'montant' => 10000,
        ]);
        tenancy()->end();

        $this->patchJson($this->api('c10', "/contrats/{$contratId}/affectations/{$affectationId}"), [
            'taux_horaire_enseignant' => 5000,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION');

        // En revanche le volume d'heures prévues reste modifiable : c'est une
        // prévision, pas une donnée d'écriture comptable.
        $this->patchJson($this->api('c10', "/contrats/{$contratId}/affectations/{$affectationId}"), [
            'nombre_heures_prevues' => 8,
        ])->assertOk();
    }

    public function test_terminer_une_affectation_facturee_est_refuse(): void
    {
        $this->connecteAdmin('c11');
        $socle = $this->socle('c11');

        $contratId = $this->postJson($this->api('c11', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        tenancy()->initialize('c11');
        $affectationId = AffectationEnseignant::where('contrat_cours_id', $contratId)->value('id');
        $ligne = AffectationEnseignant::find($affectationId);
        $ligne->lignesFacture()->create([
            'facture_id' => $this->facture($socle, $contratId)->id,
            'nombre_heures' => 4,
            'taux_horaire' => 2500,
            'montant' => 10000,
        ]);
        tenancy()->end();

        // « Terminer » une affectation qui a des heures facturées laisserait
        // des heures payées rattachées à un contrat clos.
        $this->patchJson($this->api('c11', "/contrats/{$contratId}/affectations/{$affectationId}/statut"), [
            'statut' => 'termine',
        ])->assertStatus(422);

        // Suspendre reste possible : cela empêche juste de nouvelles heures.
        $this->patchJson($this->api('c11', "/contrats/{$contratId}/affectations/{$affectationId}/statut"), [
            'statut' => 'suspendu',
        ])->assertOk()->assertJsonPath('data.statut', 'suspendu');
    }

    // ------------------------------------------------------------ mes cours

    public function test_mes_cours_ne_montre_que_les_contrats_de_l_enseignant(): void
    {
        $this->connecteAdmin('c12');
        $socle = $this->socle('c12');

        // Contrat du seul enseignant affecté.
        $contratA = $this->postJson($this->api('c12', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        // Contrat d'un autre enseignant, sans rapport avec le précédent.
        tenancy()->initialize('c12');
        $autreProfId = (int) $socle['autre_enseignant_id'];
        $eleve2User = User::create([
            'nom' => 'Zongo',
            'prenom' => 'Fatima',
            'email' => 'fatima@c12.local',
            'password' => Hash::make('Secret1234'),
        ]);
        $parent2 = User::create([
            'nom' => 'Zongo',
            'prenom' => 'Père',
            'email' => 'pere2@c12.local',
            'password' => Hash::make('Secret1234'),
        ]);
        $eleve2 = Eleve::create([
            'user_id' => $eleve2User->id,
            'parent_id' => $parent2->id,
            'classe_id' => \App\Models\Classe::first()->id,
            'statut' => true,
        ]);
        $contratB = ContratCours::create([
            'eleve_id' => $eleve2->id,
            'type_cours_id' => $socle['type_cours_id'],
            'date_debut' => '2026-09-01',
            'statut' => 'actif',
        ]);
        AffectationEnseignant::create([
            'contrat_cours_id' => $contratB->id,
            'enseignant_id' => $autreProfId,
            'matiere_id' => $socle['matiere_id'],
            'taux_horaire_enseignant' => 2500,
            'nombre_heures_prevues' => 4,
            'date_affectation' => now(),
            'statut' => 'actif',
        ]);
        tenancy()->end();

        // Le compte doit porter le rôle `enseignant` AVANT la connexion : le
        // middleware `role:` lit les rôles à l'ouverture de session.
        tenancy()->initialize('c12');
        $admin = User::where('email', 'admin@c12.local')->first();
        $admin->assignRole('enseignant');
        tenancy()->end();

        $this->postJson('http://c12.localhost/api/auth/connexion', [
            'email' => 'admin@c12.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $response = $this->getJson('http://c12.localhost/api/mes-cours');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $contratA);
        $this->assertNotEquals($contratB, $response->json('data.0.id'));
    }

    public function test_mes_cours_refuse_un_parent(): void
    {
        $this->makeCabinet('c13');

        tenancy()->initialize('c13');
        $parent = User::create([
            'nom' => 'Bamba',
            'prenom' => 'Ali',
            'email' => 'ali@c13.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $parent->assignRole('parent');
        tenancy()->end();

        $this->postJson('http://c13.localhost/api/auth/connexion', [
            'email' => 'ali@c13.local',
            'password' => 'Secret1234',
        ])->assertOk();

        // Le rôle parent n'a pas d'espace « mes cours » enseignant/élève.
        $this->getJson('http://c13.localhost/api/mes-cours')->assertStatus(403);
    }

    public function test_liste_contrats_filtree_et_recherche_eleve(): void
    {
        $this->connecteAdmin('c20');
        $socle = $this->socle('c20');

        $actif = $this->postJson($this->api('c20', '/contrats'), $this->contratValide($socle))
            ->json('data.id');

        // Un second élève, pour que la recherche soit discriminante.
        tenancy()->initialize('c20');
        $parent2 = User::create([
            'nom' => 'Nadié',
            'prenom' => 'Père',
            'email' => 'pere2@c20.local',
            'password' => Hash::make('Secret1234'),
        ]);
        $autreEleve = Eleve::create([
            'user_id' => User::create([
                'nom' => 'Nadié',
                'prenom' => 'Ibrahim',
                'email' => 'ibrahim2@c20.local',
                'password' => Hash::make('Secret1234'),
            ])->id,
            'parent_id' => $parent2->id,
            'classe_id' => \App\Models\Classe::first()->id,
            'statut' => true,
        ]);
        tenancy()->end();

        $suspendu = $this->postJson($this->api('c20', '/contrats'), $this->contratValide($socle, [
            'eleve_id' => $autreEleve->id,
        ]))->json('data.id');
        $this->patchJson($this->api('c20', "/contrats/{$suspendu}/statut"), ['statut' => 'suspendu'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'suspendu');

        $this->getJson($this->api('c20', '/contrats'))->assertOk()->assertJsonCount(2, 'data');
        $this->getJson($this->api('c20', '/contrats?statut=actif'))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson($this->api('c20', '/contrats?statut=suspendu'))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson($this->api('c20', '/contrats?statut='))->assertOk()->assertJsonCount(2, 'data');

        // La recherche porte sur le nom de l'élève, tapé **sans accents** :
        // « Ouédraogo » se saisit « ouedraogo » au clavier. C'est le cas réel
        // d'usage, et il impose le repli des accents côté colonne.
        $this->getJson($this->api('c20', '/contrats?search=ouedraogo'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $actif);

        // Insensible à la casse également.
        $this->getJson($this->api('c20', '/contrats?search=OUEDRAOGO'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $actif);

        $this->getJson($this->api('c20', '/contrats?search=nadie'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $suspendu);

        $this->getJson($this->api('c20', '/contrats?search=INEXISTANT'))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Filtre par type de cours + compteur d'affectations.
        $this->getJson($this->api('c20', "/contrats?type_cours_id={$socle['type_cours_id']}"))
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->getJson($this->api('c20', '/contrats?type_cours_id=99999'))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Le détail renvoie le contrat enveloppé dans `data`.
        $this->getJson($this->api('c20', "/contrats/{$actif}"))
            ->assertOk()
            ->assertJsonPath('data.nb_affectations', 1);
    }

    public function test_liste_eleves_expose_eleve_id_pour_les_contrats(): void
    {
        $this->connecteAdmin('c21');
        $socle = $this->socle('c21');

        // Le filtre `role=eleve` sert d'abord au sélecteur de l'écran des
        // contrats : le rôle doit être posé, sinon la liste est vide.
        tenancy()->initialize('c21');
        User::where('email', 'ibrahim@c21.local')->first()->assignRole('eleve');
        tenancy()->end();

        // `users.id` et `eleves.id` sont deux clés distinctes : sans `eleve.id`
        // dans la réponse, l'écran des contrats ne peut pas créer de contrat.
        $liste = $this->getJson('http://c21.localhost/api/admin/utilisateurs?role=eleve')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $eleveId = $liste->json('data.0.eleve.id');

        $this->assertIsInt($eleveId);
        $this->assertSame($socle['eleve_id'], $eleveId);

        // Le contrat créé avec cet `eleve_id` pointe bien la bonne fiche élève.
        $contrat = $this->postJson($this->api('c21', '/contrats'), $this->contratValide($socle, [
            'eleve_id' => $eleveId,
        ]))->assertCreated();

        $contrat->assertJsonPath('data.eleve.id', $eleveId);
    }

    public function test_contrats_reserves_a_admin_cabinet(): void
    {
        $this->makeCabinet('c14');

        tenancy()->initialize('c14');
        $enseignant = User::create([
            'nom' => 'Nikiema',
            'prenom' => 'Serge',
            'email' => 'serge@c14.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $enseignant->assignRole('enseignant');
        tenancy()->end();

        $this->postJson('http://c14.localhost/api/auth/connexion', [
            'email' => 'serge@c14.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->getJson($this->api('c14', '/contrats'))->assertStatus(403);
        $this->postJson($this->api('c14', '/contrats'), [])->assertStatus(403);
    }
}