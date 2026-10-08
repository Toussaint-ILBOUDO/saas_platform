<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API référentiels pédagogiques (T7A.2) : classes, matières, types de cours,
 * enseignants. Toutes les routes sont murées par « auth:web » +
 * « role:admin_cabinet » et servies par domaine cabinet.
 *
 * Ces référentiels conditionnent tout le reste du module (affectations,
 * plannings, facturation par ligne) : les tests verrouillent donc l'existence,
 * l'unicité, l'unicité du sigle et la protection contre la suppression d'un
 * élément utilisé.
 */
class ReferentielApiTest extends TenantTestCase
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

    // ---------------------------------------------------------------- classes

    public function test_classes_crud_complet(): void
    {
        $this->connecteAdmin('r1');

        $create = $this->postJson($this->api('r1', '/classes'), [
            'nom' => '6ème A',
            'sigle' => '6A',
        ]);

        $create->assertCreated();
        $create->assertJsonPath('data.nom', '6ème A');
        $create->assertJsonPath('data.sigle', '6A');

        $id = $create->json('data.id');

        $this->getJson($this->api('r1', "/classes/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.sigle', '6A');

        $this->putJson($this->api('r1', "/classes/{$id}"), [
            'nom' => '6ème B',
            'sigle' => '6B',
        ])->assertOk()->assertJsonPath('data.nom', '6ème B');

        $this->getJson($this->api('r1', '/classes'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->deleteJson($this->api('r1', "/classes/{$id}"))->assertNoContent();

        tenancy()->initialize('r1');
        $this->assertDatabaseMissing('classes', ['id' => $id]);
        tenancy()->end();
    }

    public function test_classe_ne_peut_pas_avoir_un_sigle_duplique(): void
    {
        $this->connecteAdmin('r2');

        $this->postJson($this->api('r2', '/classes'), [
            'nom' => '5ème A',
            'sigle' => '5A',
        ])->assertCreated();

        // Le sigle est la clé de lecture dans les listes : il doit être unique.
        $this->postJson($this->api('r2', '/classes'), [
            'nom' => '5ème B',
            'sigle' => '5A',
        ])->assertStatus(422);
    }

    public function test_classe_utilisee_par_un_eleve_nest_pas_supprimable(): void
    {
        $this->connecteAdmin('r3');

        $id = $this->postJson($this->api('r3', '/classes'), [
            'nom' => '4ème A',
            'sigle' => '4A',
        ])->json('data.id');

        tenancy()->initialize('r3');
        // Un élève rattaché à la classe suffit à la rendre non supprimable.
        $eleveUser = User::create([
            'nom' => 'Kouassi',
            'prenom' => 'Aya',
            'email' => 'aya@r3.local',
            'password' => Hash::make('Secret1234'),
        ]);
        $parentUser = User::create([
            'nom' => 'Kouassi',
            'prenom' => 'Père',
            'email' => 'pere@r3.local',
            'password' => Hash::make('Secret1234'),
        ]);
        Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $parentUser->id,
            'classe_id' => $id,
            'statut' => true,
        ]);
        tenancy()->end();

        // 409 : on ne casse pas l'historique. Le message ne doit pas promettre
        // une « désactivation » de classe — la table `classes` n'a pas de
        // colonne `actif`, cette action n'existe pas côté API.
        $refus = $this->deleteJson($this->api('r3', "/classes/{$id}"))
            ->assertStatus(409)
            ->assertJsonPath('code', 'CLASSE_UTILISEE')
            ->assertJsonMissingPath('data');

        $this->assertStringNotContainsStringIgnoringCase(
            'désactivez',
            $refus->json('message')
        );
    }

    // --------------------------------------------------------------- matières

    public function test_matieres_crud_et_desactivation(): void
    {
        $this->connecteAdmin('r4');

        $create = $this->postJson($this->api('r4', '/matieres'), [
            'nom' => 'Mathématiques',
            'sigle' => 'MATH',
            'description' => 'Calcul et géométrie',
        ]);

        $create->assertCreated();
        // L'API ne prend pas le statut en saisie : une matière naît active.
        $create->assertJsonPath('data.actif', true);

        $id = $create->json('data.id');

        $this->putJson($this->api('r4', "/matieres/{$id}"), [
            'nom' => 'Mathématiques',
            'sigle' => 'MATH',
            'description' => 'Calcul, géométrie et statistiques',
        ])->assertOk();

        // La désactivation est une action dédiée (D-053), pas une propriété
        // d'édition : l'update ignore `actif`, sinon n'importe quelle
        // renommage de matière pourrait la désactiver par mégarde.
        $this->patchJson($this->api('r4', "/matieres/{$id}/desactiver"))
            ->assertOk()
            ->assertJsonPath('data.actif', false);

        $this->patchJson($this->api('r4', "/matieres/{$id}/activer"))
            ->assertOk()
            ->assertJsonPath('data.actif', true);

        $this->deleteJson($this->api('r4', "/matieres/{$id}"))->assertNoContent();
    }

    public function test_matiere_utilisee_est_desactivee_et_non_supprimee(): void
    {
        $this->connecteAdmin('r4b');

        $matiere = $this->postJson($this->api('r4b', '/matieres'), [
            'nom' => 'Physique',
            'sigle' => 'PHYS',
        ])->json('data.id');

        // Une affectation la référence : la supprimer effacerait l'histoire de
        // facturation qui en dépend.
        tenancy()->initialize('r4b');
        // L'admin n'a pas encore de profil enseignant : on le crée, comme le
        // fait l'écran « référentiels » quand il affecte un prof à un contrat.
        $enseignant = User::where('email', 'admin@r4b.local')->first()->enseignantProfil()->firstOrCreate([], []);
        $parent = User::create([
            'nom' => 'Traoré',
            'prenom' => 'Père',
            'email' => 'pere@r4b.local',
            'password' => Hash::make('Secret1234'),
        ]);
        $eleve = Eleve::create([
            'user_id' => User::create([
                'nom' => 'Traoré',
                'prenom' => 'Awa',
                'email' => 'awa@r4b.local',
                'password' => Hash::make('Secret1234'),
            ])->id,
            'parent_id' => $parent->id,
            'classe_id' => Classe::create(['sigle' => '3E', 'nom' => 'Troisième'])->id,
            'statut' => true,
        ]);
        $typeCours = TypeCours::create(['code' => 'DOM', 'libelle' => 'Domicile', 'actif' => true]);
        $contrat = \App\Models\ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'date_debut' => '2026-09-01',
            'statut' => 'actif',
        ]);
        AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $enseignant->id,
            'matiere_id' => $matiere,
            'taux_horaire_enseignant' => 2000,
            'nombre_heures_prevues' => 2,
            'date_affectation' => now(),
            'statut' => 'actif',
        ]);
        tenancy()->end();

        $this->deleteJson($this->api('r4b', "/matieres/{$matiere}"))
            ->assertStatus(409)
            ->assertJsonPath('code', 'MATIERE_UTILISEE');

        // Le chemin prévu reste la désactivation : elle la retire des listes
        // de saisie sans casser l'affectation ni son historique.
        $this->patchJson($this->api('r4b', "/matieres/{$matiere}/desactiver"))
            ->assertOk()
            ->assertJsonPath('data.actif', false);

        tenancy()->initialize('r4b');
        $this->assertDatabaseHas('matieres', ['id' => $matiere, 'actif' => false]);
        $this->assertDatabaseHas('affectation_enseignants', ['matiere_id' => $matiere]);
        tenancy()->end();
    }

    public function test_recherche_et_filtre_actif_en_base(): void
    {
        $this->connecteAdmin('r6');

        foreach ([['Français', 'FR'], ['Mathématiques', 'MATH'], ['Physique', 'PHYS']] as [$nom, $sigle]) {
            $this->postJson($this->api('r6', '/matieres'), ['nom' => $nom, 'sigle' => $sigle])->assertCreated();
        }
        $this->postJson($this->api('r6', '/classes'), ['nom' => 'Terminale', 'sigle' => 'Tle'])->assertCreated();
        $this->postJson($this->api('r6', '/classes'), ['nom' => 'Sixième', 'sigle' => '6e'])->assertCreated();

        // La recherche filtre en base, pas sur la page déjà paginée : un
        // filtre appliqué côté client donnerait un résultat faux.
        $this->getJson($this->api('r6', '/matieres?search=math'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nom', 'Mathématiques');

        $this->getJson($this->api('r6', '/classes?search=6e'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sigle', '6e');

        // Insensible à la casse : PostgreSQL compare `LIKE` de façon
        // sensible, « math » ne trouvait pas « Mathématiques ».
        $this->getJson($this->api('r6', '/matieres?search=MATH'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sigle', 'MATH');

        // Un joker tapé par l'utilisateur est échappé, pas interprété.
        $this->getJson($this->api('r6', '/matieres?search=%25'))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Filtre « inactives » : une matière désactivée disparaît des listes
        // de saisie sans disparaître de l'historique.
        $desactivee = $this->getJson($this->api('r6', '/matieres?search=phys'))
            ->assertOk()
            ->json('data.0.id');
        $this->patchJson($this->api('r6', "/matieres/{$desactivee}/desactiver"))->assertOk();

        $this->getJson($this->api('r6', '/matieres?actif=1'))->assertOk()->assertJsonCount(2, 'data');
        $this->getJson($this->api('r6', '/matieres?actif=0'))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson($this->api('r6', '/matieres'))->assertOk()->assertJsonCount(3, 'data');
    }

    /**
     * Le nom du paramètre de taille de page a divergé entre les modules
     * (`per_page` dans Communication/Systeme, `par_page` dans Pédagogie) alors
     * que le frontend envoie `per_page` partout : la taille demandée était
     * silencieusement ignorée sur cinq endpoints, sans le moindre message
     * d'erreur — le simple fait d'afficher plus d'éléments par page ne
     * fonctionnait pas.
     *
     * Les deux noms sont acceptés, `per_page` étant la convention du projet ;
     * ce test verrouille l'alias pour qu'un contrôleur futur ne puisse pas
     * réintroduire la divergence.
     */
    public function test_taille_de_page_accepte_per_page_et_par_page(): void
    {
        $this->connecteAdmin('rp');

        foreach (['6e', '5e', '4e', '3e', '2e', '1e'] as $sigle) {
            $this->postJson($this->api('rp', '/classes'), ['nom' => 'Niveau ' . $sigle, 'sigle' => $sigle])
                ->assertCreated();
        }

        $this->getJson($this->api('rp', '/classes?per_page=2'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 6);

        // L'alias historique reste accepté.
        $this->getJson($this->api('rp', '/classes?par_page=3'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 3);
    }

    public function test_matiere_ne_peut_pas_etre_dupliquee(): void
    {
        $this->connecteAdmin('r5');

        $this->postJson($this->api('r5', '/matieres'), [
            'nom' => 'Physique',
            'sigle' => 'PHY',
        ])->assertCreated();

        $this->postJson($this->api('r5', '/matieres'), [
            'nom' => 'Physique',
            'sigle' => 'PHY',
        ])->assertStatus(422);
    }

    // ---------------------------------------------------------- types de cours

    public function test_types_de_cours_activation_par_actions_dediees(): void
    {
        $this->connecteAdmin('r6');

        $create = $this->postJson($this->api('r6', '/type-cours'), [
            'code' => 'CM',
            'libelle' => 'Cours magistral',
            'description' => 'Cours en groupe',
        ]);

        $create->assertCreated();
        $create->assertJsonPath('data.actif', true);

        $id = $create->json('data.id');

        $this->patchJson($this->api('r6', "/type-cours/{$id}/desactiver"))
            ->assertOk()
            ->assertJsonPath('data.actif', false);

        $this->patchJson($this->api('r6', "/type-cours/{$id}/activer"))
            ->assertOk()
            ->assertJsonPath('data.actif', true);

        // Pas de suppression : les contrats de cours référencent le type.
        // La route DELETE n'est pas enregistrée → 405 Method Not Allowed.
        $this->deleteJson($this->api('r6', "/type-cours/{$id}"))->assertStatus(405);
    }

    public function test_types_de_cours_code_unique_et_filtrage_actif(): void
    {
        $this->connecteAdmin('r7');

        $this->postJson($this->api('r7', '/type-cours'), [
            'code' => 'TD',
            'libelle' => 'Travaux dirigés',
        ])->assertCreated();

        $this->postJson($this->api('r7', '/type-cours'), [
            'code' => 'TD',
            'libelle' => 'Autre TD',
        ])->assertStatus(422);

        $this->getJson($this->api('r7', '/type-cours?actif=0'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ------------------------------------------------------------- enseignants

    public function test_enseignant_crud_avec_profil_et_matieres(): void
    {
        $this->connecteAdmin('r8');

        $matiereId = $this->postJson($this->api('r8', '/matieres'), [
            'nom' => 'Anglais',
            'sigle' => 'ANG',
        ])->json('data.id');

        $create = $this->postJson($this->api('r8', '/enseignants'), [
            'nom' => 'Traoré',
            'prenom' => 'Fatoumata',
            'email' => 'fatoumata@r8.local',
            'password' => 'Secret1234',
            'diplome_max' => 'Licence',
            'lieu_de_service' => 'Koudougou',
            'matieres' => [$matiereId],
        ]);

        $create->assertCreated();
        $create->assertJsonPath('data.nom', 'Traoré');
        $create->assertJsonPath('data.profil.diplome_max', 'Licence');

        // Le rôle « enseignant » est posé par le service, jamais par la requête.
        $id = $create->json('data.id');

        tenancy()->initialize('r8');
        $this->assertTrue(User::find($id)->hasRole('enseignant'));
        tenancy()->end();

        $this->getJson($this->api('r8', "/enseignants/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.email', 'fatoumata@r8.local');

        $this->putJson($this->api('r8', "/enseignants/{$id}"), [
            'nom' => 'Traoré',
            'prenom' => 'Fatoumata B.',
            'diplome_max' => 'Master',
        ])->assertOk()->assertJsonPath('data.profil.diplome_max', 'Master');

        $this->getJson($this->api('r8', '/enseignants'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_enseignant_email_doit_etre_unique(): void
    {
        $this->connecteAdmin('r9');

        $this->postJson($this->api('r9', '/enseignants'), [
            'nom' => 'Diallo',
            'prenom' => 'Salif',
            'email' => 'salif@r9.local',
            'password' => 'Secret1234',
        ])->assertCreated();

        $this->postJson($this->api('r9', '/enseignants'), [
            'nom' => 'Autre',
            'prenom' => 'Teacher',
            'email' => 'salif@r9.local',
            'password' => 'Secret1234',
        ])->assertStatus(422);
    }

    public function test_referentiels_reserves_a_admin_cabinet(): void
    {
        $this->makeCabinet('r10');

        tenancy()->initialize('r10');
        $parent = User::create([
            'nom' => 'Parent',
            'prenom' => 'Paul',
            'email' => 'parent@r10.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $parent->assignRole('parent');
        tenancy()->end();

        $this->postJson('http://r10.localhost/api/auth/connexion', [
            'email' => 'parent@r10.local',
            'password' => 'Secret1234',
        ])->assertOk();

        // Le rôle middleware suffit à tout bloquer, mais on vérifie aussi que la
        // policy de FormRequest est bien alignée sur admin_cabinet.
        $this->getJson($this->api('r10', '/classes'))->assertStatus(403);
        $this->getJson($this->api('r10', '/matieres'))->assertStatus(403);
        $this->postJson($this->api('r10', '/enseignants'), [
            'nom' => 'X',
            'prenom' => 'Y',
            'password' => 'Secret1234',
        ])->assertStatus(403);
    }
}