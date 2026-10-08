<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\CahierTexte;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\PeriodeComptable;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PDO;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API cahier de texte (T7A.5).
 *
 * Les heures du cahier de texte sont les heures **facturées au parent** et
 * **rémunérées à l'enseignant**. Un doublon n'y est donc pas anodin : il
 * gonflerait une facture comme un bulletin. Les tests verrouillent donc en
 * priorité trois choses :
 *  - l'**idempotence** (`uuid_client`) : une reprise hors-ligne ne doit pas
 *    créer une seconde ligne ;
 *  - le **périmètre** : une séance de collègue reste inaccessible en
 *    modification comme en suppression ;
 *  - le **gel des périodes** (D-051) : ni saisie, ni correction, ni
 *    suppression après clôture.
 */
class CahierTexteApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecte(string $slug, string $email): void
    {
        tenancy()->initialize($slug);
        User::where('email', $email)->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function api(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/enseignant/cahiers-textes{$chemin}";
    }

    private function historique(string $slug, int $eleveId): string
    {
        return "http://{$slug}.localhost/api/mes-enfants/{$eleveId}/cahiers-textes";
    }

    /**
     * Cabinet minimal : une période ouverte couvrant l'année, un élève, sa mère,
     * un enseignant compétent en mathématiques et un collègue non compétent.
     *
     * @return array<string, mixed>
     */
    private function socle(string $slug): array
    {
        // Le cabinet (et sa base tenant) doit exister avant toute initialisation :
        // `tenancy()->initialize()` échoue si le tenant est inconnu.
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);

        PeriodeComptable::create([
            'label' => 'Année 2026',
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
            'type' => 'annuel',
        ]);

        $admin = User::where('email', "admin@{$slug}.local")->first();

        // Sans rôle, `AuthService` refuse la connexion (`AUCUN_ROLE`) : poser
        // le rôle fait partie de la préparation, pas d'un détail.
        $eleveUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Ibrahim', "ibrahim@{$slug}.local");
        $eleveUser->assignRole('eleve');
        $mereUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Mère', "mere@{$slug}.local");
        $mereUser->assignRole('parent');
        // La policy exige un `parent_profils` : sans lui, la famille ne peut rien
        // lire du tout.
        $mereUser->parentProfil()->firstOrCreate([], []);

        $classe = \App\Models\Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $mereUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $matiere = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);

        $admin->assignRole('enseignant');
        $prof = $admin->enseignantProfil()->firstOrCreate([], []);
        $prof->matieres()->sync([$matiere->id]);

        $autreUser = $this->creeUtilisateur($slug, 'Sawadogo', 'Abdoulaye', "abdoulaye@{$slug}.local");
        $autreUser->assignRole('enseignant');
        $autre = \App\Models\EnseignantProfil::create(['user_id' => $autreUser->id]);

        $typeCours = TypeCours::create([
            'code' => 'DOM',
            'libelle' => 'À domicile',
            'actif' => true,
        ]);

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
            'autres_frais_suivi' => 0,
            'statut' => 'actif',
        ]);

        $affectation = AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $prof->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => 2500,
            'nombre_heures_prevues' => 4,
            'date_affectation' => '2026-01-01',
            'statut' => 'actif',
        ]);

        $resultat = [
            'eleve_id' => (int) $eleve->id,
            'matiere_id' => (int) $matiere->id,
            'classe_id' => (int) $classe->id,
            'mere_id' => (int) $mereUser->id,
            'affectation_id' => (int) $affectation->id,
            'enseignant_email' => "admin@{$slug}.local",
            'eleve_email' => "ibrahim@{$slug}.local",
            'parent_email' => "mere@{$slug}.local",
            'autre_email' => "abdoulaye@{$slug}.local",
            'autre_id' => (int) $autre->id,
        ];

        tenancy()->end();

        return $resultat;
    }

    /** Tenancy déjà initialisé : à n'appeler que depuis un helper de test. */
    private function creeUtilisateur(string $slug, string $nom, string $prenom, string $email): User
    {
        return User::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
    }

    /** @param array<string, mixed> $socle */
    private function seance(array $socle, array $surcharge = []): array
    {
        return array_merge([
            'affectation_enseignant_id' => $socle['affectation_id'],
            'date_seance' => now()->format('Y-m-d'),
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
            'contenu_cours' => 'Dérivation et optimisation linéaire.',
        ], $surcharge);
    }

    private function cloturePeriode(string $slug): void
    {
        tenancy()->initialize($slug);
        // Les statuts sont les constantes du modèle : `cloturee`, pas `close`.
        PeriodeComptable::query()->update(['statut' => PeriodeComptable::CLOTUREE]);
        tenancy()->end();
    }

    private function compteCahiers(string $slug): int
    {
        tenancy()->initialize($slug);
        $n = CahierTexte::count();
        tenancy()->end();

        return $n;
    }

    /**
     * Erreur de validation selon la convention T3.1 du projet :
     * `{message, code, erreurs}` — et non l'enveloppe `errors` de Laravel,
     * que `assertJsonValidationErrors()` cherche et ne trouve pas ici.
     */
    private function assertErreur(TestResponse $reponse, string $champ): void
    {
        $reponse->assertStatus(422);
        $this->assertSame('VALIDATION', $reponse->json('code'));
        $this->assertNotEmpty(
            $reponse->json("erreurs.{$champ}"),
            "Attendu une erreur de validation sur « {$champ} », reçu : "
                .json_encode($reponse->json(), JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Erreur de validation portant sur plusieurs champs à la fois.
     *
     * @param  list<string>  $champs
     */
    private function assertErreurs(TestResponse $reponse, array $champs): void
    {
        $reponse->assertStatus(422);
        $this->assertSame('VALIDATION', $reponse->json('code'));

        foreach ($champs as $champ) {
            $this->assertNotEmpty(
                $reponse->json("erreurs.{$champ}"),
                "Attendu une erreur de validation sur « {$champ} », reçu : "
                    .json_encode($reponse->json(), JSON_UNESCAPED_UNICODE)
            );
        }
    }

    // ------------------------------------------------------- saisie (POST)

    public function test_enseignant_saisit_une_seance(): void
    {
        $s = $this->socle('ct1');
        $this->connecte('ct1', $s['enseignant_email']);

        $this->postJson($this->api('ct1'), $this->seance($s))
            ->assertCreated()
            ->assertJsonPath('data.duree_heures', 1)
            ->assertJsonPath('data.matiere.nom', 'Mathématiques')
            ->assertJsonPath('data.eleve.prenom', 'Ibrahim');

        $this->assertSame(1, $this->compteCahiers('ct1'));
    }

    /**
     * Le cœur de T7A.5 : un client hors ligne qui n'a pas reçu l'accusé de
     * réception réémet le même POST. Le serveur doit répondre 200 avec la
     * séance **déjà enregistrée**, jamais 201 avec une seconde ligne — sinon ces
     * heures seraient comptées deux fois en facture comme en paie.
     */
    public function test_reprise_idempotente_meme_uuid_client(): void
    {
        $s = $this->socle('ct2');
        $this->connecte('ct2', $s['enseignant_email']);

        $donnees = $this->seance($s, ['uuid_client' => '11111111-2222-4333-8444-555555555555']);

        $premiere = $this->postJson($this->api('ct2'), $donnees)
            ->assertCreated()
            ->json('data.id');

        $seconde = $this->postJson($this->api('ct2'), $donnees)
            ->assertOk()
            ->json('data.id');

        $this->assertSame($premiere, $seconde);
        $this->assertSame(1, $this->compteCahiers('ct2'));
    }

    /**
     * La course : deux réémissions **simultanées** franchissent toutes deux le
     * contrôle « ai-je déjà cette séance ? », et la seconde meurt sur l'index
     * unique. Une réponse 500 serait le pire sort pour un client hors-ligne :
     * il réessaiera, et un 500 se distingue à peine d'une panne réseau, donc
     * il réessaiera en boucle en réémettant la même création.
     *
     * Le point subtil de la reproduction : le concurrent doit **valider son
     * propre commit**. Insérer la ligne concurrente sur la connexion du service
     * la ferait disparaître dans le rollback de la transaction du service — on
     * ne testerait alors qu'un cas où la ligne n'existe pas du tout, et le
     * garde-fou concurrentiel resterait non prouve. D'où une seconde connexion
     * PDO sur la même base tenant, en autocommit : c'est le seul moyen, sans
     * threading, de fabriquer une vraie transaction indépendante.
     *
     * Le contrôle `assertOk` et l'identité du contenu rendu importaient : un
     * simple « deux POST ⇒ une ligne » passerait aussi avec un contrôleur sans
     * aucun garde-fou.
     */
    public function test_course_deux_reemissions_simultanees(): void
    {
        $s = $this->socle('ct9');
        $this->connecte('ct9', $s['enseignant_email']);

        $uuid = '77777777-6666-4555-8444-333333333333';
        $concurrent = false;

        $pdoConcurrent = $this->connexionAutocommitTenant();

        // Une seule fois : l'insertion concurrente ne doit pas boucler avec elle.
        CahierTexte::creating(function (CahierTexte $seance) use (&$concurrent, $pdoConcurrent, $s, $uuid) {
            if ($concurrent || $seance->uuid_client !== $uuid) {
                return;
            }

            $concurrent = true;

            $pdoConcurrent->prepare(
                'insert into cahier_textes
                 (affectation_enseignant_id, date_seance, heure_debut, heure_fin,
                  duree_heures, contenu_cours, uuid_client, created_at, updated_at)
                 values (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $s['affectation_id'],
                now()->format('Y-m-d'),
                '18:00',
                '19:00',
                1,
                'Séance postée par le client concurrent.',
                $uuid,
                now(),
                now(),
            ]);
        });

        $reponse = $this->postJson($this->api('ct9'), $this->seance($s, ['uuid_client' => $uuid]));

        $this->assertTrue($concurrent, 'Le concurrent n\'a pas ete insere : le test ne prouve plus rien.');

        // 200 et non 201 : la ligne existe déjà, la reprise est idempotente.
        $reponse->assertOk();

        // C'est bien la ligne **du concurrent** qui est rendue : après la
        // violation de contrainte le service relit, il n'invente pas de doublon.
        $this->assertSame(
            'Séance postée par le client concurrent.',
            $reponse->json('data.contenu_cours')
        );

        $this->assertSame(1, $this->compteCahiers('ct9'));
    }

    /**
     * Connexion PDO supplémentaire sur la base tenant courante, en autocommit :
     * une transaction réellement indépendante de celle du service.
     */
    private function connexionAutocommitTenant(): PDO
    {
        $config = config('database.connections.tenant');

        return new PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'],
                $config['port'] ?? 5432,
                $config['database'],
            ),
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_AUTOCOMMIT => true]
        );
    }

    /**
     * Le même `uuid_client` sous un autre enseignant est une collision, pas une
     * reprise. Répondre « ok » en renvoyant la séance du collègue serait une
     * fuite de données déguisée en succès.
     */
    public function test_uuid_client_dun_collegue_est_refuse(): void
    {
        $s = $this->socle('ct3');
        $this->connecte('ct3', $s['enseignant_email']);

        $uuid = '99999999-8888-4777-8666-555555555555';

        $this->postJson($this->api('ct3'), $this->seance($s, ['uuid_client' => $uuid]))
            ->assertCreated();

        tenancy()->initialize('ct3');
        $affectationCollegue = AffectationEnseignant::create([
            'contrat_cours_id' => ContratCours::value('id'),
            'enseignant_id' => $s['autre_id'],
            'matiere_id' => $s['matiere_id'],
            'taux_horaire_enseignant' => 2000,
            'nombre_heures_prevues' => 2,
            'date_affectation' => '2026-01-01',
            'statut' => 'actif',
        ])->id;
        tenancy()->end();

        $this->connecte('ct3', $s['autre_email']);

        $collision = $this->postJson($this->api('ct3'), $this->seance($s, [
            'affectation_enseignant_id' => (int) $affectationCollegue,
            'uuid_client' => $uuid,
        ]));
        $this->assertErreur($collision, 'uuid_client');

        $this->assertSame(1, $this->compteCahiers('ct3'));
    }

    public function test_saisie_valide_le_cours_le_contenu_et_la_date(): void
    {
        $s = $this->socle('ct4');
        $this->connecte('ct4', $s['enseignant_email']);

        $this->assertErreurs(
            $this->postJson($this->api('ct4'), []),
            ['affectation_enseignant_id', 'date_seance', 'contenu_cours']
        );

        $this->assertErreur(
            $this->postJson($this->api('ct4'), $this->seance($s, ['contenu_cours' => 'court'])),
            'contenu_cours'
        );

        // Une séance ne se saisit pas à une date future : ces heures n'ont pas
        // eu lieu, elles gonfleraient une facture.
        $this->assertErreur(
            $this->postJson($this->api('ct4'), $this->seance($s, [
                'date_seance' => now()->addWeek()->format('Y-m-d'),
            ])),
            'date_seance'
        );
    }

    /**
     * Une affectation qui ne lui appartient pas est refusée : c'est ce qui
     * empêche de porter des heures sur le cours d'un collègue.
     */
    public function test_saisie_refusee_sur_le_cours_d_un_collegue(): void
    {
        $s = $this->socle('ct5');
        $this->connecte('ct5', $s['autre_email']);

        $this->assertErreur(
            $this->postJson($this->api('ct5'), $this->seance($s)),
            'affectation_enseignant_id'
        );

        $this->assertSame(0, $this->compteCahiers('ct5'));
    }

    /**
     * La ratification du lendemain doit rester possible : un enseignant qui
     * oublie une séance ne doit pas la perdre définitivement. La borne basse
     * n'est pas « aujourd'hui » mais la période comptable ouverte.
     */
    public function test_ratification_de_la_veille_autorisée(): void
    {
        $s = $this->socle('ct6');
        $this->connecte('ct6', $s['enseignant_email']);

        $this->postJson($this->api('ct6'), $this->seance($s, [
            'date_seance' => now()->subDay()->format('Y-m-d'),
            'contenu_cours' => 'Séance ratifiée le lendemain de son tenue.',
        ]))->assertCreated();
    }

    // ---------------------------------------------------- D-051 : gel

    public function test_saisie_refusee_hors_periode_ouverte(): void
    {
        $s = $this->socle('ct7');
        $this->connecte('ct7', $s['enseignant_email']);
        $this->cloturePeriode('ct7');

        // `GardePeriodeOuverte` (Finance) signale sous la clé `date` : c'est
        // un garde partagé à tous les modules, on ne renomme pas.
        // Période close : `GardePeriodeOuverte` signale sous `periode`.
        $this->assertErreur(
            $this->postJson($this->api('ct7'), $this->seance($s)),
            'periode'
        );
    }

    public function test_saisie_refusee_hors_de_toute_periode(): void
    {
        $s = $this->socle('ct8');
        $this->connecte('ct8', $s['enseignant_email']);

        tenancy()->initialize('ct8');
        PeriodeComptable::query()->delete();
        tenancy()->end();

        $this->assertErreur(
            $this->postJson($this->api('ct8'), $this->seance($s)),
            'date'
        );
    }

    // --------------------------------------------- périmètre & lecture

    public function test_index_ne_liste_que_les_seances_de_l_enseignant(): void
    {
        $s = $this->socle('ct9');
        $this->connecte('ct9', $s['enseignant_email']);
        $this->postJson($this->api('ct9'), $this->seance($s))->assertCreated();

        $this->getJson($this->api('ct9'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.matiere.nom', 'Mathématiques');
    }

    /**
     * D-058 : la taille de page était figée en dur dans le service. Elle est
     * désormais réellement appliquée, vérifiée avec une valeur **non nulle** —
     * seul le défaut avait été testé jusque-là, ce qui avait laissé passer
     * l'écart.
     */
    public function test_index_honore_la_taille_de_page(): void
    {
        $s = $this->socle('ct10');
        $this->connecte('ct10', $s['enseignant_email']);

        foreach (['Lundi', 'Mardi', 'Mercredi'] as $jour) {
            $this->postJson($this->api('ct10'), $this->seance($s, [
                'contenu_cours' => "Cours du {$jour} de la semaine.",
            ]))->assertCreated();
        }

        $this->getJson($this->api('ct10', '?per_page=2'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    /**
     * D-057 : la recherche doit être insensible aux accents. « Ouedraogo »
     * trouve « Ouédraogo » — sinon la moitié des familles du projet est
     * introuvable, les noms étant massivement accentués.
     */
    public function test_recherche_insensible_aux_accents(): void
    {
        $s = $this->socle('ct11');
        $this->connecte('ct11', $s['enseignant_email']);

        $this->postJson($this->api('ct11'), $this->seance($s, [
            'contenu_cours' => 'Optimisation et dérivées partielles.',
        ]))->assertCreated();

        $this->getJson($this->api('ct11', '?search=derivees'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson($this->api('ct11', '?search=Ouedraogo'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Un joker tapé est échappé, pas interprété.
        $this->getJson($this->api('ct11', '?search=%25'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_affectations_liste_seulement_les_cours_actifs(): void
    {
        $s = $this->socle('ct12');
        $this->connecte('ct12', $s['enseignant_email']);

        $this->getJson($this->api('ct12', '/affectations'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.matiere', 'Mathématiques');

        tenancy()->initialize('ct12');
        AffectationEnseignant::where('id', $s['affectation_id'])->update(['statut' => 'suspendu']);
        tenancy()->end();

        $this->getJson($this->api('ct12', '/affectations'))->assertOk()->assertJsonCount(0, 'data');
    }

    // ------------------------------------------- correction & suppression

    public function test_correction_et_immuabilite_de_la_date(): void
    {
        $s = $this->socle('ct13');
        $this->connecte('ct13', $s['enseignant_email']);
        $id = $this->postJson($this->api('ct13'), $this->seance($s))->assertCreated()->json('data.id');

        $this->putJson($this->api('ct13', "/{$id}"), [
            'heure_debut' => '17:00',
            'heure_fin' => '19:00',
            'contenu_cours' => 'Dérivation et optimisation linéaire, corrigée.',
        ])->assertOk()->assertJsonPath('data.duree_heures', 2);

        // La date est immuable : la corriger se fait en supprimant puis
        // ressaisissant, ce que la période ouverte autorise.
        $this->assertErreur(
            $this->putJson($this->api('ct13', "/{$id}"), [
                'date_seance' => now()->subDay()->format('Y-m-d'),
                'heure_debut' => '17:00',
                'heure_fin' => '19:00',
                'contenu_cours' => 'Tentative de déplacement de la séance.',
            ]),
            'date_seance'
        );
    }

    public function test_correction_et_suppression_interdites_sur_la_seance_d_un_collegue(): void
    {
        $s = $this->socle('ct14');
        $this->connecte('ct14', $s['enseignant_email']);
        $id = $this->postJson($this->api('ct14'), $this->seance($s))->assertCreated()->json('data.id');

        $this->connecte('ct14', $s['autre_email']);

        $this->putJson($this->api('ct14', "/{$id}"), [
            'heure_debut' => '17:00',
            'heure_fin' => '19:00',
            'contenu_cours' => 'Modification indue de la séance du collègue.',
        ])->assertForbidden();

        $this->deleteJson($this->api('ct14', "/{$id}"))->assertForbidden();

        $this->assertSame(1, $this->compteCahiers('ct14'));
    }

    public function test_suppression_autorisee_tant_que_la_periode_est_ouverte(): void
    {
        $s = $this->socle('ct15');
        $this->connecte('ct15', $s['enseignant_email']);
        $id = $this->postJson($this->api('ct15'), $this->seance($s))->assertCreated()->json('data.id');

        $this->deleteJson($this->api('ct15', "/{$id}"))->assertNoContent();
        $this->assertSame(0, $this->compteCahiers('ct15'));
    }

    /**
     * D-051 a supprimé `cahier_textes.valide_admin` : la validation d'une
     * séance appartient au rapport mensuel. Ce test verrouille ce retrait.
     *
     * Tant que la colonne était laissée dans `$fillable`, une écriture de masse
     * la mentionnant partait en `INSERT` etlevait une erreur SQL — un défaut
     * invisible tant qu'aucun appelant ne la fournissait, donc imbattable par
     * la seule recette des endpoints.
     */
    public function test_aucune_colonne_valide_admin_fantome(): void
    {
        $s = $this->socle('ct16');
        $this->connecte('ct16', $s['enseignant_email']);

        $this->assertNotContains(
            'valide_admin',
            (new CahierTexte())->getFillable(),
            'valide_admin a été supprimée par D-051 : ne pas la remettre en fillable.'
        );

        tenancy()->initialize('ct16');

        // La colonne a bien disparu du schéma…
        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::connection('tenant')
                ->hasColumn('cahier_textes', 'valide_admin')
        );

        // …et le modèle ne l'expose pas non plus.
        $this->assertArrayNotHasKey('valide_admin', (new CahierTexte())->getAttributes());

        tenancy()->end();

        // La saisie, elle, fonctionne toujours.
        $this->postJson($this->api('ct16'), $this->seance($s))->assertCreated();
    }

    public function test_suppression_refusee_apres_cloture_de_la_periode(): void
    {
        $s = $this->socle('ct17');
        $this->connecte('ct17', $s['enseignant_email']);
        $id = $this->postJson($this->api('ct17'), $this->seance($s))->assertCreated()->json('data.id');

        $this->cloturePeriode('ct17');

        $this->assertErreur(
            $this->deleteJson($this->api('ct17', "/{$id}")),
            'periode'
        );

        $this->assertSame(1, $this->compteCahiers('ct17'));
    }

    // ------------------------------------------------------------ rôles

    public function test_ecriture_reservee_a_l_enseignant(): void
    {
        $s = $this->socle('ct18');

        // Ni la mère ni l'élève n'écrivent : la saisie est l'acte professionnel
        // de l'enseignant, la famille ne fait que lire.
        foreach ([$s['parent_email'], $s['eleve_email']] as $email) {
            $this->connecte('ct18', $email);

            $this->postJson($this->api('ct18'), $this->seance($s))
                ->assertForbidden();
        }

        $this->assertSame(0, $this->compteCahiers('ct18'));
    }

    /**
     * L'élève doit connaître son `eleves.id` pour construire l'URL de son
     * historique : `users.id` et `eleves.id` sont deux clés distinctes.
     *
     * Sans ce contrat, l'écran de consultation se routerait sur un identifiant
     * faux et répondrait 404 — un défaut invisible côté serveur, puisque
     * toutes les routes continueraient de fonctionner pour l'API.
     */
    public function test_session_eleve_expose_son_identifiant_eleve(): void
    {
        $s = $this->socle('ct26');
        $this->connecte('ct26', $s['eleve_email']);

        $moi = $this->getJson('http://ct26.localhost/api/auth/moi')
            ->assertOk()
            ->json('user');

        $this->assertSame($s['eleve_id'], $moi['eleve']['id']);

        // Et l'URL construite avec cet identifiant ouvre bien l'historique.
        $this->getJson($this->historique('ct26', $moi['eleve']['id']))->assertOk();
    }

    public function test_anonyme_refuse(): void
    {
        $this->socle('ct19');

        $this->getJson($this->api('ct19'))->assertUnauthorized();
        $this->postJson($this->api('ct19'), [])->assertUnauthorized();
    }

    public function test_parent_et_eleve_lisent_leur_historique(): void
    {
        $s = $this->socle('ct20');
        $this->connecte('ct20', $s['enseignant_email']);
        $this->postJson($this->api('ct20'), $this->seance($s))->assertCreated();

        foreach ([$s['parent_email'], $s['eleve_email']] as $email) {
            $this->connecte('ct20', $email);

            $this->getJson($this->historique('ct20', $s['eleve_id']))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.matiere.nom', 'Mathématiques');
        }
    }

    /**
     * Le paramètre `?eleve_id` de l'URL ne doit rien ouvrir : un parent ne voit
     * que ses enfants, l'élève que lui-même.
     */
    public function test_historique_refuse_pour_un_enfant_etranger(): void
    {
        $s = $this->socle('ct21');
        $etranger = $this->creeEleveLieA($s['mere_id'], $s['classe_id'], 'ct21', 'awa');

        $this->connecte('ct21', $s['eleve_email']);
        $this->getJson($this->historique('ct21', $etranger))->assertForbidden();

        // Un élève qui n'est pas celui du compte ne passe pas non plus.
        $this->connecte('ct21', $s['parent_email']);
        $this->getJson($this->historique('ct21', $etranger))->assertOk();
    }

    public function test_parent_ne_voit_pas_le_cahier_d_un_enfant_d_un_autre_parent(): void
    {
        $s = $this->socle('ct22');

        tenancy()->initialize('ct22');
        $autreMere = $this->creeUtilisateur('ct22', 'Kaboré', 'Fatoumata', 'fatoumata@ct22.local');
        $classe = \App\Models\Classe::first();
        tenancy()->end();

        $enfantAutreParent = $this->creeEleveLieA((int) $autreMere->id, (int) $classe->id, 'ct22', 'awa');

        $this->connecte('ct22', $s['parent_email']);

        $this->getJson($this->historique('ct22', $enfantAutreParent))->assertForbidden();
    }

    // ------------------------------------------------------------- PDF

    public function test_enseignant_telecharge_le_pdf_de_sa_seance(): void
    {
        $s = $this->socle('ct23');
        $this->connecte('ct23', $s['enseignant_email']);
        $id = $this->postJson($this->api('ct23'), $this->seance($s))->assertCreated()->json('data.id');

        $this->get($this->api('ct23', "/{$id}/pdf"))->assertOk();
    }

    public function test_pdf_interdit_a_un_collegue(): void
    {
        $s = $this->socle('ct24');
        $this->connecte('ct24', $s['enseignant_email']);
        $id = $this->postJson($this->api('ct24'), $this->seance($s))->assertCreated()->json('data.id');

        $this->connecte('ct24', $s['autre_email']);

        $this->get($this->api('ct24', "/{$id}/pdf"))->assertForbidden();
    }

    public function test_historique_pdf_refuse_a_un_eleve_tiers(): void
    {
        $s = $this->socle('ct25');
        $etranger = $this->creeEleveLieA($s['mere_id'], $s['classe_id'], 'ct25', 'awa');

        $this->connecte('ct25', $s['eleve_email']);

        $this->getJson($this->historique('ct25', $etranger) . '/historique-pdf')
            ->assertForbidden();
    }

    /**
     * Un élève de seconde, sans parent inscrit : `eleves.parent_id` est
     * obligatoire en base, on rattache donc l'élève à un tuteur et on vérifie
     * malgré tout qu'un **autre** compte n'ouvre pas son historique.
     */
    private function creeEleveLieA(int $parentId, int $classeId, string $slug, string $prefixe): int
    {
        tenancy()->initialize($slug);

        $user = $this->creeUtilisateur($slug, 'Kaboré', 'Awa', "{$prefixe}-{$slug}@local.test");
        $eleve = Eleve::create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'classe_id' => $classeId,
            'statut' => true,
        ]);

        $id = (int) $eleve->id;
        tenancy()->end();

        return $id;
    }
}