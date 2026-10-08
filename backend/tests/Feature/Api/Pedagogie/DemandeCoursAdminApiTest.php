<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\DemandeCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Matiere;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API demandes de cours — administration (écran Angular « Demandes de cours »).
 *
 * Verrouille trois choses :
 *  - la liste est filtrable (recherche, statut, classe) et renvoie les compteurs
 *    de tête, qui portent l'action plutôt que la pagination ;
 *  - « traiter » est idempotent (un double-clic ne doit pas échouer) ;
 *  - l'API est réservée à l'admin du cabinet (D-051) : un enseignant Connected
 *    reçoit 403, un visiteur 401.
 */
class DemandeCoursAdminApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    /**
     * Connecte un utilisateur du cabinet. Le cabinet est créé s'il n'existe
     * pas encore (les tests qui appellent d'abord `socle()` en profitent).
     */
    private function connecte(string $slug, string $email, string $role): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->makeCabinet($slug);

        tenancy()->initialize($slug);
        $user = User::where('email', $email)->first();
        $user?->assignRole($role);
        $user?->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function api(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/pedagogie{$chemin}";
    }

    /**
     * Erreur de validation selon la convention T3.1 du projet :
     * `{message, code, erreurs}` — et non l'enveloppe `errors` de Laravel que
     * `assertJsonValidationErrors()` cherche et ne trouve pas ici
     * (voir `bootstrap/app.php`).
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
     * Enseignant déclaré compétent sur une matière, et renvoie son
     * `enseignant_profils.id` (celui attendu par les affectations, pas
     * `users.id`).
     *
     * `AffectationService::exigerCompetence` lit `enseignant_matiere` : sans ce
     * lien, toute création de contrat depuis une demande serait rejetée.
     */
    private function enseignantCompetent(string $slug, int $matiereId): int
    {
        tenancy()->initialize($slug);

        $user = User::create([
            'nom' => 'Sawadogo',
            'prenom' => 'Abdoulaye',
            'email' => "enseignant-{$matiereId}@{$slug}.local",
            'password' => Hash::make('Enseignant1234'),
        ]);
        $user->assignRole('enseignant');

        $profil = EnseignantProfil::create(['user_id' => $user->id]);
        $profil->matieres()->sync([$matiereId]);

        tenancy()->end();

        return (int) $profil->id;
    }

    /**
     * Cabinet avec deux classes, un type de cours, une matière, et une
     * demande « en attente » + une demande « traitee ».
     *
     * @return array<string, mixed>
     */
    private function socle(string $slug): array
    {
        tenancy()->initialize($slug);

        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $sixieme = Classe::create(['nom' => 'Sixième', 'sigle' => '6e']);
        $typeCours = TypeCours::create(['code' => 'DOM', 'libelle' => 'A domicile', 'actif' => true]);
        $matiere = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);

        $enAttente = DemandeCours::create([
            'nom_parent' => 'Ouédraogo',
            'prenom_parent' => 'Awa',
            'telephone' => '0102030405',
            'telephone_whatsapp' => '+226 70 12 34 56',
            'type_cours_id' => $typeCours->id,
            'classe_id' => $classe->id,
            'volume_horaire_estime' => 4,
            'statut' => 'en_attente',
            'message' => 'Je souhaite renforcer le niveau de ma fille.',
        ]);
        $enAttente->matieres()->attach([$matiere->id]);

        $traitee = DemandeCours::create([
            'nom_parent' => 'Kaboré',
            'prenom_parent' => 'Moussa',
            'telephone' => '67080910',
            'type_cours_id' => $typeCours->id,
            'classe_id' => $sixieme->id,
            'volume_horaire_estime' => 2,
            'statut' => 'traitee',
        ]);

        tenancy()->end();

        return compact('classe', 'sixieme', 'typeCours', 'matiere', 'enAttente', 'traitee');
    }

    public function test_liste_renvoie_demandes_et_compteurs(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->getJson($this->api('c1', '/demandes-cours'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $socle['enAttente']->id)
            ->assertJsonPath('data.0.nom_parent', 'Ouédraogo')
            ->assertJsonPath('data.0.classe.nom', 'Terminale')
            ->assertJsonPath('data.0.type_cours.libelle', 'A domicile')
            ->assertJsonPath('meta.stats.total', 2)
            ->assertJsonPath('meta.stats.en_attente', 1)
            ->assertJsonPath('meta.stats.traitees', 1)
            ->assertJsonPath('meta.stats.annulees', 0);
    }

    /**
     * Le lien WhatsApp doit être exploitable tel quel : wa.me n'accepte que des
     * chiffres, préfixés du pays. Sans cette normalisation, le numéro local
     * burkinabè ouvre une conversation sur un numéro étranger.
     */
    public function test_lien_whatsapp_normalise_le_numero_local(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->getJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}"))
            ->assertOk()
            ->assertJsonPath('data.telephone_whatsapp', '+226 70 12 34 56')
            ->assertJsonPath('data.numero_whatsapp', '+226 70 12 34 56')
            ->assertJsonPath('data.lien_whatsapp', 'https://wa.me/22670123456');
    }

    /**
     * Une demande sans numéro WhatsApp (les anciennes) reste contactable : le
     * lien retombe sur le téléphone plutôt que de disparaître.
     */
    public function test_lien_whatsapp_replie_sur_le_telephone(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->getJson($this->api('c1', "/demandes-cours/{$socle['traitee']->id}"))
            ->assertOk()
            ->assertJsonPath('data.telephone_whatsapp', null)
            ->assertJsonPath('data.numero_whatsapp', '67080910')
            ->assertJsonPath('data.lien_whatsapp', 'https://wa.me/22667080910');
    }

    public function test_detail_expose_le_message_et_les_matieres(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->getJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}"))
            ->assertOk()
            ->assertJsonPath('data.message', 'Je souhaite renforcer le niveau de ma fille.')
            ->assertJsonCount(1, 'data.matieres')
            ->assertJsonPath('data.matieres.0.nom', 'Mathématiques');
    }

    /**
     * La recherche est insensible aux accents : « ouedraogo » doit trouver
     * « Ouédraogo », sinon la moitié des familles du pays ne trouve rien.
     */
    public function test_recherche_insensible_aux_accents_et_au_statut(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->getJson($this->api('c1', '/demandes-cours?search=ouedraogo'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $socle['enAttente']->id);

        $this->getJson($this->api('c1', '/demandes-cours?statut=traitee'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $socle['traitee']->id);
    }

    /**
     * Les compteurs de tête restent globaux même filtrés : sinon « en attente »
     * mentirait dès qu'un filtre est actif.
     */
    public function test_compteurs_reste_globaux_meme_filtre(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $this->socle('c1');

        $this->getJson($this->api('c1', '/demandes-cours?statut=traitee'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.stats.en_attente', 1)
            ->assertJsonPath('meta.stats.total', 2);
    }

    public function test_valider_passe_la_demande_a_traitee(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->patchJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/valider"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'traitee');

        $this->getJson($this->api('c1', '/demandes-cours'))
            ->assertOk()
            ->assertJsonPath('meta.stats.en_attente', 0)
            ->assertJsonPath('meta.stats.traitees', 2);
    }

    public function test_valider_est_idempotent(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $url = $this->api('c1', "/demandes-cours/{$socle['traitee']->id}/valider");

        $this->patchJson($url)->assertOk()->assertJsonPath('data.statut', 'traitee');
        $this->patchJson($url)->assertOk()->assertJsonPath('data.statut', 'traitee');
    }

    public function test_demande_inconnue_retourne_404(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $this->socle('c1');

        $this->getJson($this->api('c1', '/demandes-cours/999999'))
            ->assertStatus(404)
            ->assertJson(['code' => 'INTROUVABLE']);
    }

    public function test_enseignant_refuse_403(): void
    {
        $this->makeCabinet('c1');

        tenancy()->initialize('c1');
        User::create([
            'nom' => 'Zongo',
            'prenom' => 'Paul',
            'email' => 'paul@c1.local',
            'password' => Hash::make('Secret1234'),
        ])->assignRole('enseignant');
        tenancy()->end();

        $this->postJson('http://c1.localhost/api/auth/connexion', [
            'email' => 'paul@c1.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->getJson($this->api('c1', '/demandes-cours'))
            ->assertStatus(403)
            ->assertJson(['code' => 'ACCES_REFUSE']);
    }

    public function test_visiteur_non_connecte_retourne_401(): void
    {
        $this->makeCabinet('c1');

        $this->getJson($this->api('c1', '/demandes-cours'))
            ->assertStatus(401)
            ->assertJson(['code' => 'NON_CONNECTE']);
    }

    /* ============================================================
       Refus commercial
       ============================================================ */

    public function test_refuser_passe_la_demande_a_annulee(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->patchJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/refuser"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'annulee');

        $this->getJson($this->api('c1', '/demandes-cours'))
            ->assertOk()
            ->assertJsonPath('meta.stats.en_attente', 0)
            ->assertJsonPath('meta.stats.annulees', 1);
    }

    public function test_refuser_est_idempotent(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $url = $this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/refuser");

        $this->patchJson($url)->assertOk()->assertJsonPath('data.statut', 'annulee');
        $this->patchJson($url)->assertOk()->assertJsonPath('data.statut', 'annulee');
    }

    /**
     * Une demande sortie du pipeline ne peut pas être « traitée » : ce sont deux
     * intentions contradictoires, pas un doublon technique.
     */
    public function test_traiter_une_demande_refusee_renvoie_422(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->patchJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/refuser"))
            ->assertOk();

        $this->assertErreur(
            $this->patchJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/valider")),
            'statut'
        );
    }

    /* ============================================================
       Constitution du dossier : parent → élève → contrat
       ============================================================ */

    public function test_creer_parent_pre_remplit_les_coordonnees_de_la_demande(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->postJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/parent"), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'password' => 'MotDePasse1',
        ])
            ->assertOk()
            ->assertJsonPath('data.parent_cree.nom', 'Ouédraogo')
            ->assertJsonPath('data.parent_cree.prenom', 'Awa');

        tenancy()->initialize('c1');

        // Le numéro vient de la demande : l'admin n'a rien eu à ressaisir.
        $this->assertDatabaseHas('users', [
            'nom' => 'Ouédraogo',
            'telephone_whatsapp' => '+226 70 12 34 56',
            'telephone_appel' => '0102030405',
        ]);

        $this->assertDatabaseHas('demande_cours', [
            'id' => $socle['enAttente']->id,
            'parent_id' => User::role('parent')->where('nom', 'Ouédraogo')->value('id'),
        ]);

        tenancy()->end();
    }

    /**
     * Un parent déjà connu de ce numéro : le formerulat ne doit pas créer un
     * second compte pour la même famille.
     */
    public function test_creer_parent_refuse_un_numero_deja_connu(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        tenancy()->initialize('c1');
        User::create([
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'telephone_whatsapp' => '+226 70 12 34 56',
            'password' => Hash::make('Ancien1234'),
        ])->assignRole('parent');
        tenancy()->end();

        $this->assertErreur(
            $this->postJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/parent"), [
                'nom' => 'Ouédraogo',
                'prenom' => 'Awa',
                'password' => 'MotDePasse1',
            ]),
            'telephone_whatsapp'
        );
    }

    /**
     * Le garde-fou anti-doublon : rejouer l'action renvoie le parent déjà créé
     * au lieu d'en fabriquer un second.
     */
    public function test_creer_parent_est_idempotent(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $url = $this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/parent");
        $payload = ['nom' => 'Ouédraogo', 'prenom' => 'Awa', 'password' => 'MotDePasse1'];

        $premier = $this->postJson($url, $payload)->assertOk();
        $second = $this->postJson($url, $payload)->assertOk();

        $this->assertSame(
            $premier->json('data.parent_cree.id'),
            $second->json('data.parent_cree.id')
        );

        tenancy()->initialize('c1');
        $this->assertSame(1, User::role('parent')->count());
        tenancy()->end();
    }

    public function test_creer_eleve_avant_le_parent_renvoie_422(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->assertErreur(
            $this->postJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/eleve"), [
                'parent_id' => 1,
                'prenom' => 'Fatoumata',
            ]),
            'parent_id'
        );
    }

    public function test_creer_eleve_reprend_la_classe_demandee(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->postJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/parent"), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'password' => 'MotDePasse1',
        ])->assertOk();

        $this->postJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/eleve"), [
            'prenom' => 'Fatoumata',
        ])
            ->assertOk()
            ->assertJsonPath('data.eleve_cree.classe.nom', 'Terminale');

        tenancy()->initialize('c1');

        $eleve = Eleve::first();
        $this->assertSame('Fatoumata', $eleve->user->prenom);
        // La classe vient de la demande, pas d'une saisie de l'admin.
        $this->assertSame($socle['classe']->id, $eleve->classe_id);
        $this->assertSame(User::role('parent')->value('id'), $eleve->parent_id);

        tenancy()->end();
    }

    public function test_creer_eleve_est_idempotent(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        $this->postJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/parent"), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'password' => 'MotDePasse1',
        ])->assertOk();

        $url = $this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/eleve");

        $premier = $this->postJson($url, ['prenom' => 'Fatoumata'])->assertOk();
        $second = $this->postJson($url, ['prenom' => 'Fatoumata'])->assertOk();

        $this->assertSame(
            $premier->json('data.eleve_cree.id'),
            $second->json('data.eleve_cree.id')
        );

        tenancy()->initialize('c1');
        $this->assertSame(1, Eleve::count());
        tenancy()->end();
    }

    /**
     * Un parent existant mais sans le rôle `parent` ne peut pas porter d'élève :
     * `ContratCoursPolicy::view` lui ouvrirait les contrats d'une autre famille.
     */
    public function test_creer_eleve_refuse_un_compte_sans_role_parent(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        tenancy()->initialize('c1');
        $enseignant = User::create([
            'nom' => 'Zongo',
            'prenom' => 'Paul',
            'password' => Hash::make('Enseignant1234'),
        ])->assignRole('enseignant');
        $demande = DemandeCours::find($socle['enAttente']->id);
        $demande->update(['parent_id' => $enseignant->id]);
        tenancy()->end();

        $this->assertErreur(
            $this->postJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/eleve"), [
                'prenom' => 'Fatoumata',
            ]),
            'parent_id'
        );
    }

    public function test_creer_contrat_avant_leve_eleve_renvoie_422(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');

        // L'affectation doit être valide : sans quoi la 422 porterait sur
        // `enseignant_id` et le test passerait pour la mauvaise raison.
        $enseignant = $this->enseignantCompetent('c1', $socle['matiere']->id);

        $this->assertErreur(
            $this->postJson($this->api('c1', "/demandes-cours/{$socle['enAttente']->id}/contrat"), [
                'type_cours_id' => $socle['typeCours']->id,
                'date_debut' => '2026-10-05',
                'affectations' => [[
                    'enseignant_id' => $enseignant,
                    'matiere_id' => $socle['matiere']->id,
                    'taux_horaire_enseignant' => 2000,
                    'nombre_heures_prevues' => 4,
                ]],
            ]),
            'eleve_id'
        );
    }

    /**
     * Parcours complet : la demande devient un dossier et se clôture en « traitée ».
     *
     * Le contrat est marqué `traitee` automatiquement — le dossier constitué ne
     * demande plus de qualification.
     */
    public function test_parcours_complet_constitue_le_dossier(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');
        $id = $socle['enAttente']->id;

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/parent"), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'password' => 'MotDePasse1',
        ])->assertOk();

        $eleve = $this->postJson($this->api('c1', "/demandes-cours/{$id}/eleve"), [
            'prenom' => 'Fatoumata',
        ]);

        if ($eleve->status() !== 200) {
            $this->fail('Création élève refusée : '.$eleve->getContent());
        }

        $enseignant = $this->enseignantCompetent('c1', $socle['matiere']->id);

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/contrat"), [
            'type_cours_id' => $socle['typeCours']->id,
            'date_debut' => '2026-10-05',
            'notes_admin' => 'Dossier issu de la demande publique.',
            'affectations' => [[
                'enseignant_id' => $enseignant,
                'matiere_id' => $socle['matiere']->id,
                'taux_horaire_enseignant' => 2500,
                'nombre_heures_prevues' => 4,
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'traitee')
            ->assertJsonPath('data.contrat_cree.statut', 'actif');

        $this->getJson($this->api('c1', '/demandes-cours'))
            ->assertOk()
            ->assertJsonPath('meta.stats.avec_contrat', 1)
            ->assertJsonPath('meta.stats.en_attente', 0);
    }

    /**
     * Un contrat produit une facture : le refus ultérieur laisserait un
     * engagement de facturation orphelin.
     */
    public function test_refuser_une_demande_avec_contrat_renvoie_422(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');
        $id = $socle['enAttente']->id;

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/parent"), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'password' => 'MotDePasse1',
        ])->assertOk();

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/eleve"), [
            'prenom' => 'Fatoumata',
        ])->assertOk();

        $enseignant = $this->enseignantCompetent('c1', $socle['matiere']->id);

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/contrat"), [
            'type_cours_id' => $socle['typeCours']->id,
            'date_debut' => '2026-10-05',
            'affectations' => [[
                'enseignant_id' => $enseignant,
                'matiere_id' => $socle['matiere']->id,
                'taux_horaire_enseignant' => 2500,
                'nombre_heures_prevues' => 4,
            ]],
        ])->assertOk();

        $this->assertErreur(
            $this->patchJson($this->api('c1', "/demandes-cours/{$id}/refuser")),
            'statut'
        );
    }

    /**
     * Un enseignant non compétent sur la matière est rejeté par
     * `AffectationService` : la règle doit rester dans un seul endroit.
     */
    public function test_creer_contrat_refuse_un_enseignant_incompetent(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');
        $id = $socle['enAttente']->id;

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/parent"), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'password' => 'MotDePasse1',
        ])->assertOk();

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/eleve"), [
            'prenom' => 'Fatoumata',
        ])->assertOk();

        $enseignant = $this->enseignantCompetent('c1', $socle['matiere']->id);

        tenancy()->initialize('c1');
        $autreMatiere = Matiere::create(['nom' => 'Physique', 'sigle' => 'PHYS'])->id;
        tenancy()->end();

        $this->assertErreur(
            $this->postJson($this->api('c1', "/demandes-cours/{$id}/contrat"), [
                'type_cours_id' => $socle['typeCours']->id,
                'date_debut' => '2026-10-05',
                'affectations' => [[
                    'enseignant_id' => $enseignant,
                    'matiere_id' => $autreMatiere,
                    'taux_horaire_enseignant' => 2500,
                    'nombre_heures_prevues' => 4,
                ]],
            ]),
            'affectations'
        );
    }

    public function test_creer_contrat_est_idempotent(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');
        $id = $socle['enAttente']->id;

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/parent"), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'password' => 'MotDePasse1',
        ])->assertOk();

        $this->postJson($this->api('c1', "/demandes-cours/{$id}/eleve"), [
            'prenom' => 'Fatoumata',
        ])->assertOk();

        $enseignant = $this->enseignantCompetent('c1', $socle['matiere']->id);

        $url = $this->api('c1', "/demandes-cours/{$id}/contrat");
        $payload = [
            'type_cours_id' => $socle['typeCours']->id,
            'date_debut' => '2026-10-05',
            'affectations' => [[
                'enseignant_id' => $enseignant,
                'matiere_id' => $socle['matiere']->id,
                'taux_horaire_enseignant' => 2500,
                'nombre_heures_prevues' => 4,
            ]],
        ];

        $premier = $this->postJson($url, $payload)->assertOk();
        $second = $this->postJson($url, $payload)->assertOk();

        $this->assertSame(
            $premier->json('data.contrat_cree.id'),
            $second->json('data.contrat_cree.id')
        );

        tenancy()->initialize('c1');
        $this->assertSame(1, ContratCours::count());
        tenancy()->end();
    }

    /**
     * Une demande sortie du pipeline ne doit plus pouvoir devenir un dossier :
     * refuser porte sur la demande commerciale, pas sur un compte client.
     */
    public function test_actions_resistent_apres_refus(): void
    {
        $this->connecte('c1', 'admin@c1.local', 'admin_cabinet');
        $socle = $this->socle('c1');
        $id = $socle['enAttente']->id;

        $this->patchJson($this->api('c1', "/demandes-cours/{$id}/refuser"))->assertOk();

        $this->assertErreur(
            $this->postJson($this->api('c1', "/demandes-cours/{$id}/parent"), [
                'nom' => 'Ouédraogo',
                'prenom' => 'Awa',
                'password' => 'MotDePasse1',
            ]),
            'statut'
        );
    }
}