<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\ObjectifPedagogique;
use App\Models\PeriodeComptable;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API objectifs pédagogiques (T7A.6, D-050).
 *
 * L'objectif relie un élève à une PÉRIODE COMPTABLE et à UN enseignant.
 * Les tests verrouillent en priorité :
 *
 *  - le triplet d'identité (unicité refusée en 422, périmètre enseignant) ;
 *  - la propriété : un collègue, même affecté au même élève, ne touche à rien ;
 *  - le gel D-051 : ni création, ni correction, ni suppression sur close ;
 *  - la préservation de la ventilation (D-050) : une correction partielle ne
 *    recrée pas les lignes de matière et ne perd pas les moyennes.
 */
class ObjectifPedagogiqueApiTest extends TenantTestCase
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
        return "http://{$slug}.localhost/api/enseignant/objectifs{$chemin}";
    }

    private function adminApi(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/admin/pedagogie/objectifs{$chemin}";
    }

    /**
     * Cabinet : période ouverte, deux élèves (l'un avec cours, l'autre sans),
     * deux matières, un enseignant affecté et un collègue affecté au même
     * élève — le cas qui interdit tout écrasement par identifiant devinable.
     *
     * @return array<string, mixed>
     */
    private function socle(string $slug): array
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);

        PeriodeComptable::create([
            'label' => 'Année 2026',
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
            'type' => 'annuel',
        ]);

        $admin = User::where('email', "admin@{$slug}.local")->first();

        $eleveUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Ibrahim', "ibrahim@{$slug}.local");
        $eleveUser->assignRole('eleve');
        $mereUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Mère', "mere@{$slug}.local");
        $mereUser->assignRole('parent');
        $mereUser->parentProfil()->firstOrCreate([], []);

        $autreEleveUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Awa', "awa@{$slug}.local");
        $autreEleveUser->assignRole('eleve');

        $classe = \App\Models\Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $mereUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);
        $eleveSansCours = Eleve::create([
            'user_id' => $autreEleveUser->id,
            'parent_id' => $mereUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $maths = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);
        $francais = Matiere::create(['nom' => 'Français', 'sigle' => 'FR']);

        $admin->assignRole('enseignant');
        $prof = $admin->enseignantProfil()->firstOrCreate([], []);
        $prof->matieres()->sync([$maths->id]);

        $autreUser = $this->creeUtilisateur($slug, 'Sawadogo', 'Abdoulaye', "abdoulaye@{$slug}.local");
        $autreUser->assignRole('enseignant');
        $autre = \App\Models\EnseignantProfil::create(['user_id' => $autreUser->id]);
        $autre->matieres()->sync([$francais->id]);

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

        AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $prof->id,
            'matiere_id' => $maths->id,
            'taux_horaire_enseignant' => 2500,
            'nombre_heures_prevues' => 4,
            'date_affectation' => '2026-01-01',
            'statut' => 'actif',
        ]);

        AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $autre->id,
            'matiere_id' => $francais->id,
            'taux_horaire_enseignant' => 2000,
            'nombre_heures_prevues' => 2,
            'date_affectation' => '2026-01-01',
            'statut' => 'actif',
        ]);

        $periodeId = (int) PeriodeComptable::query()->value('id');

        $resultat = [
            'periode_id' => $periodeId,
            'eleve_id' => (int) $eleve->id,
            'eleve_sans_cours_id' => (int) $eleveSansCours->id,
            'maths_id' => (int) $maths->id,
            'francais_id' => (int) $francais->id,
            'prof_email' => "admin@{$slug}.local",
            'collegue_email' => "abdoulaye@{$slug}.local",
            'eleve_email' => "ibrahim@{$slug}.local",
            'parent_email' => "mere@{$slug}.local",
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
    private function payload(array $socle, array $surcharge = []): array
    {
        return array_merge([
            'eleve_id' => $socle['eleve_id'],
            'periode_id' => $socle['periode_id'],
            'moyenne_visee' => 14,
            'matieres' => [
                ['matiere_id' => $socle['maths_id'], 'moyenne_visee' => 15],
            ],
        ], $surcharge);
    }

    private function assertErreur(TestResponse $reponse, string $champ): void
    {
        $reponse->assertStatus(422);
        $this->assertSame('VALIDATION', $reponse->json('code'));
        $this->assertNotEmpty(
            $reponse->json("erreurs.{$champ}"),
            "Attendu une erreur de validation sur « {$champ} », reçu : "
                . json_encode($reponse->json(), JSON_UNESCAPED_UNICODE)
        );
    }

    /** Deuxième période comptable, ouverte puis éventuellement close. */
    private function deuxiemePeriode(string $slug, bool $close = false): int
    {
        tenancy()->initialize($slug);

        $periode = PeriodeComptable::create([
            'label' => 'Avril 2026',
            'date_debut' => '2026-04-01',
            'date_fin' => '2026-04-30',
            'type' => 'mensuel',
        ]);

        if ($close) {
            PeriodeComptable::whereKey($periode->id)
                ->update(['statut' => PeriodeComptable::CLOTUREE]);
        }

        $id = (int) $periode->id;
        tenancy()->end();

        return $id;
    }

    private function cloturer(string $slug, int $periodeId): void
    {
        tenancy()->initialize($slug);
        PeriodeComptable::whereKey($periodeId)
            ->update(['statut' => PeriodeComptable::CLOTUREE]);
        tenancy()->end();
    }

    public function test_la_liste_requiert_une_connexion(): void
    {
        $this->socle('objcab1');

        $this->getJson($this->api('objcab1'))->assertUnauthorized();
    }

    public function test_un_parent_est_refuse_sur_les_deux_prefixes(): void
    {
        $socle = $this->socle('objcab2');
        $this->connecte('objcab2', $socle['parent_email']);

        $this->getJson($this->api('objcab2'))->assertForbidden();
        $this->getJson($this->adminApi('objcab2'))->assertForbidden();
    }

    public function test_l_enseignant_ne_liste_que_ses_objectifs(): void
    {
        $socle = $this->socle('objcab3');
        $this->connecte('objcab3', $socle['prof_email']);

        $this->postJson($this->api('objcab3'), $this->payload($socle))
            ->assertStatus(201);

        $this->connecte('objcab3', $socle['collegue_email']);
        $this->postJson($this->api('objcab3'), $this->payload($socle, [
            'matieres' => [
                ['matiere_id' => $socle['francais_id'], 'moyenne_visee' => 12],
            ],
        ]))->assertStatus(201);

        $reponse = $this->getJson($this->api('objcab3'));
        $reponse->assertOk();
        $this->assertCount(1, $reponse->json('data'));
        $reponse->assertJsonPath('data.0.eleve.id', $socle['eleve_id']);
        $reponse->assertJsonPath('data.0.periode.id', $socle['periode_id']);
        $reponse->assertJsonPath('data.0.matieres.0.matiere.id', $socle['francais_id']);
        $this->assertNotEmpty($reponse->json('data.0.enseignant.id'));
        $this->assertNotEmpty($reponse->json('meta.per_page'));
    }

    public function test_la_taille_de_page_demandee_est_appliquee(): void
    {
        $socle = $this->socle('objcab4');
        $this->connecte('objcab4', $socle['prof_email']);

        $this->postJson($this->api('objcab4'), $this->payload($socle))
            ->assertStatus(201);

        $this->getJson($this->api('objcab4') . '?per_page=5')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_le_filtre_par_periode_est_applique(): void
    {
        $socle = $this->socle('objcab5');
        $avril = $this->deuxiemePeriode('objcab5');
        $this->connecte('objcab5', $socle['prof_email']);

        $this->postJson($this->api('objcab5'), $this->payload($socle))
            ->assertStatus(201);
        $this->postJson($this->api('objcab5'), $this->payload($socle, [
            'periode_id' => $avril,
        ]))->assertStatus(201);

        $this->cloturer('objcab5', $avril);

        $reponse = $this->getJson($this->api('objcab5') . "?periode_id={$avril}");
        $reponse->assertOk();
        $this->assertCount(1, $reponse->json('data'));
        $reponse->assertJsonPath('data.0.periode.id', $avril);
        $reponse->assertJsonPath('data.0.periode.est_ouverte', false);
    }

    public function test_la_creation_renvoie_le_triplet_complet(): void
    {
        $socle = $this->socle('objcab6');
        $this->connecte('objcab6', $socle['prof_email']);

        $reponse = $this->postJson($this->api('objcab6'), $this->payload($socle));

        $reponse->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'eleve' => ['id', 'nom', 'prenom', 'classe'],
                    'periode' => ['id', 'label', 'date_debut', 'date_fin', 'statut', 'est_ouverte'],
                    'enseignant' => ['id', 'nom', 'prenom'],
                    'moyenne_visee',
                    'matieres' => [['id', 'matiere', 'moyenne_visee']],
                ],
            ])
            ->assertJsonPath('data.eleve.id', $socle['eleve_id'])
            ->assertJsonPath('data.periode.id', $socle['periode_id'])
            ->assertJsonPath('data.moyenne_visee', 14)
            ->assertJsonPath('data.matieres.0.moyenne_visee', 15);

        tenancy()->initialize('objcab6');
        $this->assertSame(1, ObjectifPedagogique::count());
        tenancy()->end();
    }

    public function test_la_creation_refuse_un_doublon_du_meme_triplet(): void
    {
        $socle = $this->socle('objcab7');
        $this->connecte('objcab7', $socle['prof_email']);

        $this->postJson($this->api('objcab7'), $this->payload($socle))
            ->assertStatus(201);

        $this->assertErreur(
            $this->postJson($this->api('objcab7'), $this->payload($socle)),
            'periode_id'
        );

        tenancy()->initialize('objcab7');
        $this->assertSame(1, ObjectifPedagogique::count());
        tenancy()->end();
    }

    public function test_la_creation_refuse_un_eleve_sans_cours_actif(): void
    {
        $socle = $this->socle('objcab8');
        $this->connecte('objcab8', $socle['prof_email']);

        $this->assertErreur(
            $this->postJson($this->api('objcab8'), $this->payload($socle, [
                'eleve_id' => $socle['eleve_sans_cours_id'],
            ])),
            'eleve_id'
        );
    }

    public function test_la_creation_refuse_une_periode_close(): void
    {
        $socle = $this->socle('objcab9');
        $cloturee = $this->deuxiemePeriode('objcab9', close: true);
        $this->connecte('objcab9', $socle['prof_email']);

        $this->assertErreur(
            $this->postJson($this->api('objcab9'), $this->payload($socle, [
                'periode_id' => $cloturee,
            ])),
            'periode'
        );
    }

    public function test_la_creation_exige_une_ventilation_de_matieres(): void
    {
        $socle = $this->socle('objcab10');
        $this->connecte('objcab10', $socle['prof_email']);

        $this->assertErreur(
            $this->postJson($this->api('objcab10'), $this->payload($socle, [
                'matieres' => [],
            ])),
            'matieres'
        );
    }

    public function test_les_eleves_du_formulaire_ne_sont_que_ses_cours(): void
    {
        $socle = $this->socle('objcab11');
        $this->connecte('objcab11', $socle['prof_email']);

        $reponse = $this->getJson($this->api('objcab11') . '/eleves');
        $reponse->assertOk();
        $ids = collect($reponse->json('data'))->pluck('id')->all();
        $this->assertContains($socle['eleve_id'], $ids);
        $this->assertNotContains($socle['eleve_sans_cours_id'], $ids);
    }

    public function test_les_periodes_et_matieres_du_formulaire_sont_exposes(): void
    {
        $socle = $this->socle('objcab12');
        $this->connecte('objcab12', $socle['prof_email']);

        $this->getJson($this->api('objcab12') . '/periodes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $socle['periode_id'])
            ->assertJsonPath('data.0.est_ouverte', true);

        $matieres = $this->getJson($this->api('objcab12') . '/matieres');
        $matieres->assertOk();
        $ids = collect($matieres->json('data'))->pluck('id')->all();
        $this->assertContains($socle['maths_id'], $ids);
        $this->assertContains($socle['francais_id'], $ids);
    }

    public function test_la_correction_preserve_la_ventilation(): void
    {
        $socle = $this->socle('objcab13');
        $this->connecte('objcab13', $socle['prof_email']);

        $id = $this->postJson($this->api('objcab13'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data.id');

        $reponse = $this->putJson($this->api('objcab13', "/{$id}"), [
            'moyenne_visee' => 16,
        ]);

        $reponse->assertOk()
            ->assertJsonPath('data.moyenne_visee', 16)
            ->assertJsonPath('data.matieres.0.moyenne_visee', 15);

        tenancy()->initialize('objcab13');
        $this->assertSame(1, ObjectifPedagogique::find($id)->objectifsMatieres()->count());
        tenancy()->end();
    }

    public function test_la_ventilation_peut_etre_remplacee(): void
    {
        $socle = $this->socle('objcab14');
        $this->connecte('objcab14', $socle['prof_email']);

        $id = $this->postJson($this->api('objcab14'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data.id');

        $this->putJson($this->api('objcab14', "/{$id}"), [
            'matieres' => [
                ['matiere_id' => $socle['maths_id'], 'moyenne_visee' => 13, 'moyenne_obtenue' => 15, 'commentaire' => 'Série en cours.'],
                ['matiere_id' => $socle['francais_id'], 'moyenne_visee' => 11],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.matieres.0.moyenne_obtenue', 15)
            ->assertJsonPath('data.matieres.0.commentaire', 'Série en cours.')
            ->assertJsonPath('data.matieres.1.moyenne_visee', 11);

        tenancy()->initialize('objcab14');
        $this->assertSame(2, ObjectifPedagogique::find($id)->objectifsMatieres()->count());
        tenancy()->end();
    }

    public function test_un_collegue_ne_peut_ni_modifier_ni_supprimer(): void
    {
        $socle = $this->socle('objcab15');
        $this->connecte('objcab15', $socle['prof_email']);
        $id = $this->postJson($this->api('objcab15'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data.id');

        $this->connecte('objcab15', $socle['collegue_email']);

        $this->putJson($this->api('objcab15', "/{$id}"), ['moyenne_visee' => 10])
            ->assertForbidden();
        $this->deleteJson($this->api('objcab15', "/{$id}"))->assertForbidden();
    }

    public function test_l_enseignant_supprime_son_objectif(): void
    {
        $socle = $this->socle('objcab16');
        $this->connecte('objcab16', $socle['prof_email']);
        $id = $this->postJson($this->api('objcab16'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data.id');

        $this->deleteJson($this->api('objcab16', "/{$id}"))->assertNoContent();

        tenancy()->initialize('objcab16');
        $this->assertSame(0, ObjectifPedagogique::count());
        tenancy()->end();
    }

    public function test_une_periode_close_gele_la_modification_et_la_suppression(): void
    {
        $socle = $this->socle('objcab17');
        $this->connecte('objcab17', $socle['prof_email']);
        $id = $this->postJson($this->api('objcab17'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data.id');

        $this->cloturer('objcab17', $socle['periode_id']);

        $this->assertErreur(
            $this->putJson($this->api('objcab17', "/{$id}"), ['moyenne_visee' => 10]),
            'periode'
        );
        $this->assertErreur(
            $this->deleteJson($this->api('objcab17', "/{$id}")),
            'periode'
        );

        tenancy()->initialize('objcab17');
        $this->assertSame(1, ObjectifPedagogique::count());
        tenancy()->end();
    }

    public function test_l_admin_liste_corrige_et_supprime(): void
    {
        $socle = $this->socle('objcab18');
        $this->connecte('objcab18', $socle['prof_email']);
        $this->postJson($this->api('objcab18'), $this->payload($socle))
            ->assertStatus(201);

        $this->connecte('objcab18', $socle['collegue_email']);
        $id = $this->postJson($this->api('objcab18'), $this->payload($socle, [
            'matieres' => [
                ['matiere_id' => $socle['francais_id'], 'moyenne_visee' => 12],
            ],
        ]))->assertStatus(201)->json('data.id');

        $this->connecte('objcab18', $socle['prof_email']);

        $liste = $this->getJson($this->adminApi('objcab18'));
        $liste->assertOk();
        $this->assertCount(2, $liste->json('data'));

        $this->putJson($this->adminApi('objcab18', "/{$id}"), [
            'commentaire_admin' => 'Objectif validé en réunion.',
        ])
            ->assertOk()
            ->assertJsonPath('data.commentaire_admin', 'Objectif validé en réunion.');

        $this->deleteJson($this->adminApi('objcab18', "/{$id}"))->assertNoContent();

        tenancy()->initialize('objcab18');
        $this->assertSame(1, ObjectifPedagogique::count());
        tenancy()->end();
    }

    public function test_l_admin_ne_peut_pas_creer_un_objectif(): void
    {
        $socle = $this->socle('objcab19');
        $this->connecte('objcab19', $socle['prof_email']);

        $this->postJson($this->adminApi('objcab19'), $this->payload($socle))
            ->assertStatus(405);
    }

    public function test_un_identifiant_inconnu_renvoie_404(): void
    {
        $socle = $this->socle('objcab20');
        $this->connecte('objcab20', $socle['prof_email']);

        $this->putJson($this->api('objcab20', '/999999'), ['moyenne_visee' => 10])
            ->assertNotFound();
        $this->deleteJson($this->api('objcab20', '/999999'))->assertNotFound();
    }
}
