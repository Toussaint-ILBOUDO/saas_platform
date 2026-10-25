<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\Classe;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Scolarité admin (M3) : détail/édition des fiches parent & élève,
 * rattachements, activation/suspension du compte élève.
 *
 * Verrouille :
 *  - le détail expose le profil parent (avec ses enfants) et la fiche
 *    élève complète, inatteignables depuis les seules listes ;
 *  - l'admin édite identité + profil + rattachements (`parent_id`,
 *    `classe_id`) — la fiche partagée les interdit explicitement ;
 *  - rattachement à un compte sans rôle « parent » refusé (422) ;
 *  - anti-doublon de numéro à l'édition : le propre numéro du parent passe,
 *    celui d'une autre famille non ;
 *  - activation/suspension en DOUBLE FLAG (`eleves.statut` + `users.statut`)
 *    : connexion bloquée 403 tant que le compte n'est pas activé, rétablie
 *    après activation, coupée après suspension ;
 *  - le mot de passe posé à l'activation est le seul accepté, et la route
 *    d'un compte sans le rôle parent répond 404 ;
 *  - l'API reste réservée à l'admin : 403 enseignant, 401 visiteur.
 */
class ScolariteApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecte(string $slug, string $email, string $role): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        // Certains tests créent le cabinet avant (scénario visiteur 401) :
        // `cabinets.id` est unique, un second `makeCabinet` casserait tout.
        if (! \App\Models\Cabinet::whereKey($slug)->exists()) {
            $this->makeCabinet($slug);
        }

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

    /** Crée une classe dans le tenant et renvoie son id. */
    private function creeClasse(string $slug, string $nom, string $sigle): int
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($slug);
        $classe = Classe::create(['nom' => $nom, 'sigle' => $sigle]);
        tenancy()->end();

        return $classe->id;
    }

    /** Crée un parent via l'API admin et renvoie son id (users.id). */
    private function creeParent(string $slug, string $prenom, string $whatsapp): int
    {
        return $this->postJson($this->api($slug, '/parents'), [
            'nom' => 'Kaboré',
            'prenom' => $prenom,
            'telephone_whatsapp' => $whatsapp,
            'email' => strtolower($prenom) . '@famille.test',
            'password' => 'MotDePasse1',
            'profession' => 'Commerçante',
        ])->assertStatus(201)->json('data.id');
    }

    /** Crée un élève via l'API admin et renvoie son id (eleves.id). */
    private function creeEleve(string $slug, int $parentId, int $classeId, string $prenom): int
    {
        return $this->postJson($this->api($slug, '/eleves'), [
            'parent_id' => $parentId,
            'prenom' => $prenom,
            'classe_id' => $classeId,
            'email' => strtolower($prenom) . '@eleve.test',
        ])->assertStatus(201)->json('data.id');
    }

    public function test_routes_scolarite_exigent_admin_et_connexion(): void
    {
        $slug = 'sco-droits';

        // Visiteur : 401 sur le détail comme sur l'édition.
        $this->makeCabinet($slug);
        $this->getJson($this->api($slug, '/parents/1'))->assertStatus(401);
        $this->putJson($this->api($slug, '/parents/1'), [])->assertStatus(401);

        // Setup avec l'admin : la cible du 403 doit exister — `SubstituteBindings`
        // tourne avant le middleware `role:` (non prioritaire) : sans ressource
        // réelle, on recevrait un 404 d'« introuvable » avant le 403 de rôle.
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');
        $classeId = $this->creeClasse($slug, 'Terminale', 'Tle');
        $parentId = $this->creeParent($slug, 'Awa', '70112233');
        $eleveId = $this->creeEleve($slug, $parentId, $classeId, 'Fatoumata');

        // Enseignant : connecté mais pas admin du cabinet.
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($slug);
        $enseignant = User::create([
            'nom' => 'Sawadogo',
            'prenom' => 'Abdoulaye',
            'email' => 'enseignant@sco.test',
            'password' => Hash::make('Secret1234'),
        ]);
        $enseignant->assignRole('enseignant');
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => 'enseignant@sco.test',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->getJson($this->api($slug, "/parents/{$parentId}"))->assertStatus(403);
        $this->putJson($this->api($slug, "/parents/{$parentId}"), [
            'nom' => 'X',
            'prenom' => 'Y',
        ])->assertStatus(403);
        $this->getJson($this->api($slug, "/eleves/{$eleveId}"))->assertStatus(403);
        $this->putJson($this->api($slug, "/eleves/{$eleveId}"), [
            'parent_id' => $parentId,
            'classe_id' => $classeId,
            'prenom' => 'Fatoumata',
        ])->assertStatus(403);
        $this->patchJson($this->api($slug, "/eleves/{$eleveId}/activer"), [])->assertStatus(403);
        $this->patchJson($this->api($slug, "/eleves/{$eleveId}/suspendre"))->assertStatus(403);
    }

    public function test_detail_parent_expose_profil_et_enfants(): void
    {
        $slug = 'sco-parent-detail';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $classeId = $this->creeClasse($slug, 'Terminale', 'Tle');
        $parentId = $this->creeParent($slug, 'Awa', '70112233');
        $this->creeEleve($slug, $parentId, $classeId, 'Fatoumata');

        $reponse = $this->getJson($this->api($slug, "/parents/{$parentId}"));

        $reponse->assertOk()
            ->assertJsonPath('data.profil.profession', 'Commerçante')
            ->assertJsonPath('data.statut', true)
            ->assertJsonPath('data.enfants.0.prenom', 'Fatoumata')
            ->assertJsonPath('data.enfants.0.classe.sigle', 'Tle')
            ->assertJsonPath('data.enfants.0.statut_compte', false);
    }

    public function test_editing_parent_maj_identite_et_profil(): void
    {
        $slug = 'sco-parent-edit';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $parentId = $this->creeParent($slug, 'Awa', '70112233');

        // Le numéro reste celui du parent (anti-doublon avec `$sauf`) : c'est
        // le cas d'usage réel, changer la profession sans redemander le tel.
        $reponse = $this->putJson($this->api($slug, "/parents/{$parentId}"), [
            'nom' => 'Kaboré',
            'prenom' => 'Awa',
            'telephone_whatsapp' => '70112233',
            'profession' => 'Docteur',
            'adresse_domicile' => 'Ouagadougou, secteur 30',
            'nombre_enfants' => 3,
        ]);

        $reponse->assertOk()
            ->assertJsonPath('data.profil.profession', 'Docteur')
            ->assertJsonPath('data.profil.adresse_domicile', 'Ouagadougou, secteur 30')
            ->assertJsonPath('data.profil.nombre_enfants', 3);

        tenancy()->initialize($slug);
        $parent = User::with('parentProfil')->findOrFail($parentId);
        $this->assertSame('Docteur', $parent->parentProfil->profession);
        $this->assertSame(3, (int) $parent->parentProfil->nombre_enfants);
        tenancy()->end();
    }

    public function test_editing_parent_refuse_le_numero_d_une_autre_famille(): void
    {
        $slug = 'sco-parent-doublon';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $this->creeParent($slug, 'Awa', '70112233');
        $second = $this->creeParent($slug, 'Salif', '76543210');

        $reponse = $this->putJson($this->api($slug, "/parents/{$second}"), [
            'nom' => 'Kaboré',
            'prenom' => 'Salif',
            'telephone_whatsapp' => '70112233',
        ]);

        $this->assertErreur($reponse, 'telephone_whatsapp');
    }

    public function test_detail_eleve_expose_fiche_et_rattachements(): void
    {
        $slug = 'sco-eleve-detail';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $classeId = $this->creeClasse($slug, 'Sixième', '6e');
        $parentId = $this->creeParent($slug, 'Moussa', '67080910');
        $eleveId = $this->creeEleve($slug, $parentId, $classeId, 'Adama');

        $reponse = $this->getJson($this->api($slug, "/eleves/{$eleveId}"));

        $reponse->assertOk()
            ->assertJsonPath('data.prenom', 'Adama')
            ->assertJsonPath('data.parent_id', $parentId)
            ->assertJsonPath('data.classe_id', $classeId)
            ->assertJsonPath('data.classe.sigle', '6e')
            ->assertJsonPath('data.statut_compte', false)
            ->assertJsonPath('data.fiche.ecole', null);

        $this->assertArrayHasKey('fiche', $reponse->json('data'));
    }

    public function test_editing_eleve_change_rattachements_et_fiche(): void
    {
        $slug = 'sco-eleve-edit';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $classeId = $this->creeClasse($slug, 'Sixième', '6e');
        $autreClasseId = $this->creeClasse($slug, 'Cinquième', '5e');
        $parentId = $this->creeParent($slug, 'Moussa', '67080910');
        $autreParentId = $this->creeParent($slug, 'Issouf', '76543210');
        $eleveId = $this->creeEleve($slug, $parentId, $classeId, 'Adama');

        $reponse = $this->putJson($this->api($slug, "/eleves/{$eleveId}"), [
            'parent_id' => $autreParentId,
            'classe_id' => $autreClasseId,
            'prenom' => 'Adama',
            'nom' => 'Kaboré',
            'ecole' => 'Lycée Municipal',
            'regime_etude' => 'externe',
            'maladies_allergies' => 'Arachides',
        ]);

        $reponse->assertOk()
            ->assertJsonPath('data.parent_id', $autreParentId)
            ->assertJsonPath('data.classe_id', $autreClasseId)
            ->assertJsonPath('data.ecole', 'Lycée Municipal')
            ->assertJsonPath('data.fiche.regime_etude', 'externe')
            ->assertJsonPath('data.fiche.maladies_allergies', 'Arachides');

        tenancy()->initialize($slug);
        $eleve = \App\Models\Eleve::findOrFail($eleveId);
        $this->assertSame($autreParentId, (int) $eleve->parent_id);
        $this->assertSame($autreClasseId, (int) $eleve->classe_id);
        $this->assertSame('Lycée Municipal', $eleve->ecole);
        tenancy()->end();
    }

    public function test_rattachement_exige_un_compte_au_role_parent(): void
    {
        $slug = 'sco-parent-faux';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $classeId = $this->creeClasse($slug, 'Quatrième', '4e');
        $parentId = $this->creeParent($slug, 'Awa', '70112233');
        $eleveId = $this->creeEleve($slug, $parentId, $classeId, 'Adama');

        // Un compte sans rôle parent ne peut pas porter un élève : ce serait
        // ouvrir les contrats de la famille à un tiers.
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($slug);
        $tiers = User::create([
            'nom' => 'Compte',
            'prenom' => 'Sans-role',
            'email' => 'tiers@sco.test',
            'password' => Hash::make('Secret1234'),
        ]);
        tenancy()->end();

        $reponse = $this->putJson($this->api($slug, "/eleves/{$eleveId}"), [
            'parent_id' => $tiers->id,
            'classe_id' => $classeId,
            'prenom' => 'Adama',
        ]);

        $this->assertErreur($reponse, 'parent_id');
    }

    public function test_email_d_un_autre_compte_est_refuse_a_ledition_eleve(): void
    {
        $slug = 'sco-email-pris';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $classeId = $this->creeClasse($slug, 'Troisième', '3e');
        $parentId = $this->creeParent($slug, 'Awa', '70112233');
        $premier = $this->creeEleve($slug, $parentId, $classeId, 'Adama');
        $second = $this->creeEleve($slug, $parentId, $classeId, 'Boubacar');

        $reponse = $this->putJson($this->api($slug, "/eleves/{$second}"), [
            'parent_id' => $parentId,
            'classe_id' => $classeId,
            'prenom' => 'Boubacar',
            'email' => 'adama@eleve.test', // celui du premier
        ]);

        $this->assertErreur($reponse, 'email');

        // ... mais garder le sien reste accepté.
        $this->putJson($this->api($slug, "/eleves/{$premier}"), [
            'parent_id' => $parentId,
            'classe_id' => $classeId,
            'prenom' => 'Adama',
            'email' => 'adama@eleve.test',
        ])->assertOk();
    }

    public function test_activation_eleve_pose_double_flag_et_mot_de_passe(): void
    {
        $slug = 'sco-activation';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $classeId = $this->creeClasse($slug, 'Seconde', '2nde');
        $parentId = $this->creeParent($slug, 'Awa', '70112233');
        $eleveId = $this->creeEleve($slug, $parentId, $classeId, 'Adama');

        // Compte créé inactif : connexion refusée.
        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => 'adama@eleve.test',
            'password' => 'password123',
        ])->assertStatus(403)->assertJsonPath('code', 'COMPTE_ELEVE_INACTIF');

        // Activation avec un mot de passe choisi par l'admin.
        $reponse = $this->patchJson(
            $this->api($slug, "/eleves/{$eleveId}/activer"),
            ['password' => 'NouveauMot123']
        );

        $reponse->assertOk()->assertJsonPath('data.statut_compte', true);

        tenancy()->initialize($slug);
        $eleve = \App\Models\Eleve::with('user')->findOrFail($eleveId);
        $this->assertTrue((bool) $eleve->statut, 'eleves.statut doit passer à true');
        $this->assertTrue((bool) $eleve->user->statut, 'users.statut doit passer à true');
        $this->assertFalse(
            Hash::check('password123', $eleve->user->password),
            'L\'ancien mot de passe doit être remplacé'
        );
        $this->assertTrue(Hash::check('NouveauMot123', $eleve->user->password));
        tenancy()->end();

        // Le nouveau mot de passe connecte, l'ancien non.
        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => 'adama@eleve.test',
            'password' => 'NouveauMot123',
        ])->assertOk();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => 'adama@eleve.test',
            'password' => 'password123',
        ])->assertStatus(422);
    }

    public function test_activation_sans_mot_de_passe_conserve_les_identifiants(): void
    {
        $slug = 'sco-reactivation';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $classeId = $this->creeClasse($slug, 'Seconde', '2nde');
        $parentId = $this->creeParent($slug, 'Awa', '70112233');
        $eleveId = $this->creeEleve($slug, $parentId, $classeId, 'Fatou');

        // Réactivation d'un compte : aucun champ obligatoire, identifiants conservés.
        $this->patchJson($this->api($slug, "/eleves/{$eleveId}/activer"), [])
            ->assertOk()
            ->assertJsonPath('data.statut_compte', true);

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => 'fatou@eleve.test',
            'password' => 'password123',
        ])->assertOk();
    }

    public function test_suspension_eleve_bloque_la_connexion_sans_rien_supprimer(): void
    {
        $slug = 'sco-suspension';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $classeId = $this->creeClasse($slug, 'Seconde', '2nde');
        $parentId = $this->creeParent($slug, 'Awa', '70112233');
        $eleveId = $this->creeEleve($slug, $parentId, $classeId, 'Adama');

        // Activation d'abord (le compte doit être joignable pour prouver la coupe).
        $this->patchJson($this->api($slug, "/eleves/{$eleveId}/activer"), [])->assertOk();
        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => 'adama@eleve.test',
            'password' => 'password123',
        ])->assertOk();

        // La connexion élève ci-dessus a pris la session : on repasse admin
        // avant d'appeler une route réservée à l'admin.
        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => "admin@{$slug}.local",
            'password' => 'Secret1234',
        ])->assertOk();

        $reponse = $this->patchJson($this->api($slug, "/eleves/{$eleveId}/suspendre"));
        $reponse->assertOk()->assertJsonPath('data.statut_compte', false);

        // Double flag coupé : l'élève ne peut plus entrer.
        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => 'adama@eleve.test',
            'password' => 'password123',
        ])->assertStatus(403)->assertJsonPath('code', 'COMPTE_ELEVE_INACTIF');

        tenancy()->initialize($slug);
        $eleve = \App\Models\Eleve::with('user')->findOrFail($eleveId);
        $this->assertFalse((bool) $eleve->statut);
        $this->assertFalse((bool) $eleve->user->statut);
        // Rien n'est supprimé : la suspension coupe la connexion, pas la fiche.
        $this->assertNotNull(\App\Models\Eleve::find($eleveId));
        $this->assertNotNull(User::find($eleve->user_id));
        tenancy()->end();
    }

    public function test_une_route_parent_repond_404_sans_role_parent(): void
    {
        $slug = 'sco-parent-404';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        // L'admin lui-même n'a pas le rôle parent : la route ne doit pas
        // livrer n'importe quel compte `users` trouvé au passage.
        $this->getJson($this->api($slug, '/parents/1'))->assertStatus(404);
        $this->putJson($this->api($slug, '/parents/1'), [
            'nom' => 'X',
            'prenom' => 'Y',
        ])->assertStatus(404);
    }
}
