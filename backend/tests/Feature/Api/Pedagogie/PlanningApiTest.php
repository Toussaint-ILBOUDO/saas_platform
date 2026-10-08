<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\PlanningCours;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API planning (T7A.4).
 *
 * Deux volets :
 *  - la détection de conflit horaire, qui avant T7A.4 ne comparait que les
 *    heures de début sur une même affectation et laissait donc passer
 *    l'enseignant « à deux endroits » comme l'élève « à deux cours » ;
 *  - les règles de visibilité : parent, élève dont le compte est activé, et
 *    les autres enseignants du même élève.
 */
class PlanningApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecte(string $slug, string $email, string $role, bool $active = true): void
    {
        // Le cabinet n'existe qu'une fois par test : `socle()` peut être appelé
        // après une reconnexion d'un autre compte.
        if (! $this->createdCabinets) {
            $this->makeCabinet($slug);
        }

        tenancy()->initialize($slug);
        $user = User::where('email', $email)->first();
        $user->update(['password' => Hash::make('Secret1234'), 'statut' => $active]);
        $user->assignRole($role);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function api(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/pedagogie/planning{$chemin}";
    }

    /**
     * Un enseignant (profil du compte admin) avec deux élèves et deux matières,
     * et un second enseignant sur le premier élève.
     *
     * @return array<string, mixed>
     */
    private function socle(string $slug): array
    {
        tenancy()->initialize($slug);

        $admin = User::where('email', "admin@{$slug}.local")->first();
        $prof = $admin->enseignantProfil()->firstOrCreate([], []);

        $matiere = \App\Models\Matiere::create(['nom' => 'Maths', 'sigle' => 'MATH']);
        $prof->matieres()->sync([$matiere->id]);

        $typeCours = TypeCours::create(['code' => 'DOM', 'libelle' => 'Domicile', 'actif' => true]);

        $creerEleve = function (string $nom) use ($slug, $typeCours, $prof, $matiere) {
            $u = User::create([
                'nom' => $nom,
                'prenom' => 'Enfant',
                'email' => strtolower($nom) . "@{$slug}.local",
                'password' => Hash::make('Secret1234'),
                'statut' => true,
            ]);
            $p = User::create([
                'nom' => $nom,
                'prenom' => 'Parent',
                'email' => 'parent-' . strtolower($nom) . "@{$slug}.local",
                'password' => Hash::make('Secret1234'),
                'statut' => true,
            ]);
            $parentProfil = $p->parentProfil()->firstOrCreate([], []);

            $classe = \App\Models\Classe::firstOrCreate(['sigle' => 'Tle'], ['nom' => 'Terminale']);

            $eleve = Eleve::create([
                'user_id' => $u->id,
                // `eleves.parent_id` référence `users.id` : le compte du parent,
                // pas son `parent_profils` (séquence indépendante). Utiliser le
                // profil ici rattachait l'enfant au compte d'un tiers et rendait
                // ce test incapable de détecter la fuite (cf. IdentiteParentTest).
                'parent_id' => $p->id,
                'classe_id' => $classe->id,
                'statut' => true,
            ]);

            $contrat = ContratCours::create([
                'eleve_id' => $eleve->id,
                'type_cours_id' => $typeCours->id,
                'date_debut' => '2026-09-01',
                'statut' => 'actif',
            ]);

            $affectation = AffectationEnseignant::create([
                'contrat_cours_id' => $contrat->id,
                'enseignant_id' => $prof->id,
                'matiere_id' => $matiere->id,
                'taux_horaire_enseignant' => 2500,
                'nombre_heures_prevues' => 4,
                'date_affectation' => now(),
                'statut' => 'actif',
            ]);

            return [
                'eleve' => $eleve,
                'contrat' => $contrat,
                'affectation' => $affectation,
                'matiere' => $matiere,
                'prof' => $prof,
            ];
        };

        $a = $creerEleve('Alpha');
        $b = $creerEleve('Beta');

        // Second enseignant, affecté sur le PREMIER élève seulement : il doit
        // voir les créneaux de Alpha (élève commun) mais pas ceux de Beta.
        $autreUser = User::create([
            'nom' => 'Colonel',
            'prenom' => 'Ibrahim',
            'email' => "colomb@{$slug}.local",
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $colomb = \App\Models\EnseignantProfil::create(['user_id' => $autreUser->id]);
        $colomb->matieres()->sync([$matiere->id]);
        $autreAffectation = AffectationEnseignant::create([
            'contrat_cours_id' => $a['contrat']->id,
            'enseignant_id' => $colomb->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => 3000,
            'nombre_heures_prevues' => 2,
            'date_affectation' => now(),
            'statut' => 'actif',
        ]);

        tenancy()->end();

        return [
            'alpha' => $a,
            'beta' => $b,
            'colomb' => $colomb,
            'autreAffectation' => $autreAffectation,
        ];
    }

    // ------------------------------------------------- création & conflits

    public function test_creation_creneau(): void
    {
        $this->connecte('p1', 'admin@p1.local', 'enseignant');
        $s = $this->socle('p1');

        $response = $this->postJson($this->api('p1'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.jour_label', 'Mercredi');
        $response->assertJsonPath('data.tranche_horaire', '18:00 - 19:00');
        $response->assertJsonPath('data.matiere.nom', 'Maths');
        $response->assertJsonPath('data.eleve.nom', 'Alpha');
    }

    public function test_enseignant_ne_peut_pas_etre_a_deux_endroits(): void
    {
        $this->connecte('p2', 'admin@p2.local', 'enseignant');
        $s = $this->socle('p2');

        // Mercredi 18h-19h chez Alpha.
        $this->postJson($this->api('p2'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        // Mercredi 18h30-19h30 chez Beta : l'enseignant ne peut pas y être aussi.
        // Avant T7A.4 ce créneau passait (seul l'heure de début exacte était
        // comparée, sur la même affectation).
        $conflit = $this->postJson($this->api('p2'), [
            'affectation_enseignant_id' => $s['beta']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:30',
            'heure_fin' => '19:30',
        ]);

        $conflit->assertStatus(422);
        $this->assertStringContainsString(
            'auprès d\'un autre élève',
            $conflit->json('erreurs.heure_debut.0')
        );
    }

    public function test_eleve_ne_peut_pas_etre_a_deux_cours_simultanement(): void
    {
        $this->connecte('p3', 'admin@p3.local', 'enseignant');
        $s = $this->socle('p3');

        $this->postJson($this->api('p3'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        // Autre enseignant, même élève, horaire chevauchant.
        $this->connecte('p3', 'colomb@p3.local', 'enseignant');

        $conflit = $this->postJson($this->api('p3'), [
            'affectation_enseignant_id' => $s['autreAffectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:45',
            'heure_fin' => '19:45',
        ]);

        $conflit->assertStatus(422);
        $this->assertStringContainsString(
            'déjà cours',
            $conflit->json('erreurs.heure_debut.0')
        );
    }

    public function test_creneaux_qui_se_touchent_sont_acceptes(): void
    {
        $this->connecte('p4', 'admin@p4.local', 'enseignant');
        $s = $this->socle('p4');

        $this->postJson($this->api('p4'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        // 19h-20h : la continuité d'une journée de cours est légitime.
        $this->postJson($this->api('p4'), [
            'affectation_enseignant_id' => $s['beta']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '19:00',
            'heure_fin' => '20:00',
        ])->assertCreated();
    }

    public function test_meme_eleve_meme_creneau_refuse(): void
    {
        $this->connecte('p5', 'admin@p5.local', 'enseignant');
        $s = $this->socle('p5');

        $this->postJson($this->api('p5'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        $this->postJson($this->api('p5'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertStatus(422);
    }

    public function test_modifier_un_creneau_ignore_lui_meme(): void
    {
        $this->connecte('p6', 'admin@p6.local', 'enseignant');
        $s = $this->socle('p6');

        $id = $this->postJson($this->api('p6'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->json('data.id');

        // Étendre le propre créneau jusqu'à 19h30 ne doit pas se heurter à
        // lui-même.
        $this->patchJson($this->api('p6', "/{$id}"), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:30',
        ])->assertOk()->assertJsonPath('data.heure_fin', '19:30');
    }

    public function test_un_enseignant_ne_peut_pas_toucher_le_creneau_dun_collegue(): void
    {
        $this->connecte('p7', 'admin@p7.local', 'enseignant');
        $s = $this->socle('p7');

        $id = $this->postJson($this->api('p7'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->json('data.id');

        $this->connecte('p7', 'colomb@p7.local', 'enseignant');

        // Un identifiant devinable ne donne aucun droit sur le planning d'autrui.
        $this->patchJson($this->api('p7', "/{$id}"), [
            'affectation_enseignant_id' => $s['autreAffectation']->id,
            'jour_semaine' => 4,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ])->assertStatus(403);

        $this->deleteJson($this->api('p7', "/{$id}"))->assertStatus(403);

        tenancy()->initialize('p7');
        $this->assertDatabaseHas('planning_cours', ['id' => $id, 'jour_semaine' => 3]);
        tenancy()->end();
    }

    public function test_index_expose_creneaux_partages_avec_un_autre_enseignant(): void
    {
        $this->connecte('p8', 'admin@p8.local', 'enseignant');
        $s = $this->socle('p8');

        $this->postJson($this->api('p8'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        // Le collègue est affecté au même élève Alpha : il doit voir ce créneau.
        tenancy()->initialize('p8');
        PlanningCours::create([
            'enseignant_id' => $s['colomb']->id,
            'affectation_enseignant_id' => $s['autreAffectation']->id,
            'jour_semaine' => 5,
            'heure_debut' => '16:00',
            'heure_fin' => '17:00',
        ]);
        tenancy()->end();

        $response = $this->getJson($this->api('p8'));

        $response->assertOk();
        $response->assertJsonCount(1, 'mes_creneaux');
        $response->assertJsonCount(1, 'creneaux_partages');
        $response->assertJsonPath('creneaux_partages.0.jour_label', 'Vendredi');
    }

    public function test_partage_limite_au_meme_contrat(): void
    {
        $this->connecte('p15', 'admin@p15.local', 'enseignant');
        $s = $this->socle('p15');

        $this->postJson($this->api('p15'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        // Même élève, mais un AUTRE contrat : le créneau ne doit pas être
        // partagé. Un enseignant qui intervient sur une autre offre de
        // l'enfant n'a pas à connaître le planning de celle-ci.
        tenancy()->initialize('p15');
        $autreContrat = ContratCours::create([
            'eleve_id' => $s['alpha']['eleve']->id,
            'type_cours_id' => $s['alpha']['contrat']->type_cours_id,
            'date_debut' => '2026-10-01',
            'statut' => 'actif',
        ]);
        $affectationAutreContrat = AffectationEnseignant::create([
            'contrat_cours_id' => $autreContrat->id,
            'enseignant_id' => $s['colomb']->id,
            'matiere_id' => $s['alpha']['matiere']->id,
            'taux_horaire_enseignant' => 3000,
            'nombre_heures_prevues' => 1,
            'date_affectation' => now(),
            'statut' => 'actif',
        ]);
        tenancy()->end();

        // Colomb intervient bien sur Alpha (contrat 1 et contrat 2) : le
        // créneau du contrat 1 reste donc visible.
        $this->connecte('p15', 'colomb@p15.local', 'enseignant');

        $this->getJson($this->api('p15'))
            ->assertOk()
            ->assertJsonCount(1, 'creneaux_partages');

        // En revanche, un créneau posé sur le contrat 2 ne doit jamais lui être
        // montré, et le périmètre ne déborde pas du contrat partagé.
        tenancy()->initialize('p15');
        PlanningCours::create([
            'enseignant_id' => $s['colomb']->id,
            'affectation_enseignant_id' => $affectationAutreContrat->id,
            'jour_semaine' => 2,
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
        ]);
        tenancy()->end();

        $this->app['auth']->forgetGuards();

        $this->getJson($this->api('p15'))
            ->assertOk()
            // Seul le créneau du contrat 1 est partagé ; celui du contrat 2
            // reste privé.
            ->assertJsonCount(1, 'creneaux_partages')
            ->assertJsonPath('creneaux_partages.0.jour_label', 'Mercredi');
    }

    public function test_suppression_de_creneau_autorisee(): void
    {
        $this->connecte('p9', 'admin@p9.local', 'enseignant');
        $s = $this->socle('p9');

        $id = $this->postJson($this->api('p9'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->json('data.id');

        // Contrairement aux contrats (D-054), planning_cours n'est référencé par
        // aucune table : un créneau est une intention, pas une écriture comptable.
        $this->deleteJson($this->api('p9', "/{$id}"))->assertNoContent();

        tenancy()->initialize('p9');
        $this->assertDatabaseMissing('planning_cours', ['id' => $id]);
        tenancy()->end();
    }

    // ------------------------------------------------------- visibilité

    public function test_parent_voit_le_planning_de_ses_enfants(): void
    {
        $this->connecte('p10', 'admin@p10.local', 'enseignant');
        $s = $this->socle('p10');

        $this->postJson($this->api('p10'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        $this->connecte('p10', 'parent-alpha@p10.local', 'parent');

        $response = $this->getJson('http://p10.localhost/api/mes-planning');

        $response->assertOk();
        $response->assertJsonCount(1, 'eleves');
        $response->assertJsonPath('eleves.0.eleve.nom', 'Alpha');
        $response->assertJsonPath('eleves.0.eleve.compte_actif', true);
        $response->assertJsonCount(1, 'eleves.0.creneaux');
    }

    public function test_eleve_voit_son_planning_si_son_compte_est_active(): void
    {
        $this->connecte('p11', 'admin@p11.local', 'enseignant');
        $s = $this->socle('p11');

        $this->postJson($this->api('p11'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        $this->connecte('p11', 'alpha@p11.local', 'eleve');

        $response = $this->getJson('http://p11.localhost/api/mes-planning');

        $response->assertOk();
        $response->assertJsonPath('compte_actif', true);
        $response->assertJsonCount(1, 'creneaux');
    }

    public function test_eleve_dont_le_compte_est_desactive_apres_connexion_ne_voit_plus_rien(): void
    {
        $this->connecte('p12', 'admin@p12.local', 'enseignant');
        $s = $this->socle('p12');

        $this->postJson($this->api('p12'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        // Tant que le compte est actif, l'élève voit son planning.
        $this->connecte('p12', 'alpha@p12.local', 'eleve');
        $this->getJson('http://p12.localhost/api/mes-planning')
            ->assertOk()
            ->assertJsonCount(1, 'creneaux');

        // Le compte est désactivé par le parent alors que la session est
        // ouverte : `AuthService` bloque la *connexion* suivante, mais pas la
        // session en cours. Le endpoint doit donc vérifier l'activation à
        // chaque appel, sinon la révocation ne prendrait effet qu'à la
        // reconnexion.
        tenancy()->initialize('p12');
        Eleve::where('id', $s['alpha']['eleve']->id)->update(['statut' => false]);
        tenancy()->end();

        // En test, le `SessionGuard` est réutilisé d'une requête à l'autre et
        // garde en mémoire l'utilisateur résolu : on le force à relire la base
        // pour reproduire ce que fait une vraie requête (nouveau processus).
        $this->app['auth']->forgetGuards();

        $this->getJson('http://p12.localhost/api/mes-planning')
            ->assertOk()
            ->assertJsonPath('compte_actif', false)
            ->assertJsonCount(0, 'creneaux');
    }

    public function test_eleve_inactif_ne_peut_meme_pas_se_connecter(): void
    {
        $this->connecte('p12b', 'admin@p12b.local', 'enseignant');
        $s = $this->socle('p12b');

        tenancy()->initialize('p12b');
        Eleve::where('id', $s['alpha']['eleve']->id)->update(['statut' => false]);
        tenancy()->end();

        // Garde-fou en amont : un compte élève non activé n'a pas d'espace, on
        // ne lui rend donc pas de jeton.
        $this->postJson('http://p12b.localhost/api/auth/connexion', [
            'email' => 'alpha@p12b.local',
            'password' => 'Secret1234',
        ])->assertForbidden();
    }

    public function test_parent_voit_le_planning_meme_si_le_compte_enfant_est_inactif(): void
    {
        $this->connecte('p13', 'admin@p13.local', 'enseignant');
        $s = $this->socle('p13');

        $this->postJson($this->api('p13'), [
            'affectation_enseignant_id' => $s['alpha']['affectation']->id,
            'jour_semaine' => 3,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
        ])->assertCreated();

        tenancy()->initialize('p13');
        Eleve::where('id', $s['alpha']['eleve']->id)->update(['statut' => false]);
        tenancy()->end();

        // C'est le parent qui active le compte de son enfant : il doit donc
        // conserver la visibilité sur l'organisation des cours.
        $this->connecte('p13', 'parent-alpha@p13.local', 'parent');

        $response = $this->getJson('http://p13.localhost/api/mes-planning');

        $response->assertOk();
        $response->assertJsonPath('eleves.0.eleve.compte_actif', false);
        $response->assertJsonCount(1, 'eleves.0.creneaux');
    }

    public function test_mes_planning_refuse_un_enseignant_sans_enfant(): void
    {
        $this->connecte('p14', 'admin@p14.local', 'enseignant');

        // L'espace élève/parent n'a pas de sens pour un enseignant sans élève.
        $this->getJson('http://p14.localhost/api/mes-planning')->assertStatus(403);
    }
}