<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\Classe;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Création directe de parent et d'élève (sans demande de cours).
 *
 * Verrouille cinq choses :
 *  - un admin crée un parent : rôle « parent » posé + profil créé ;
 *  - un numéro déjà connu est refusé (anti-doublon partagé avec le parcours
 *    demande) ;
 *  - un admin crée un élève avec `parent_id` obligatoire : rôle « eleve »,
 *    rattachement au parent, compte inactif ;
 *  - un parent sans rôle ou un parent_id absent est rejeté en 422 ;
 *  - l'API est réservée à l'admin du cabinet : enseignant 403, visiteur 401.
 */
class CreationDirecteApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

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

    public function test_admin_cree_un_parent_directement(): void
    {
        $slug = 'cabinet-parent';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $reponse = $this->postJson($this->api($slug, '/parents'), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'telephone_whatsapp' => '+226 70 11 22 33',
            'telephone_appel' => '70 11 22 33',
            'email' => 'awa@famille.local',
            'password' => 'MotDePasse1',
            'profession' => 'Enseignante',
            'adresse_domicile' => 'Ouagadougou',
        ]);

        $reponse->assertStatus(201);
        $this->assertSame('Ouédraogo', $reponse->json('data.nom'));
        $this->assertSame('Awa', $reponse->json('data.prenom'));
        $this->assertSame('Enseignante', $reponse->json('data.profil.profession'));

        tenancy()->initialize($slug);
        $user = User::where('email', 'awa@famille.local')->firstOrFail();
        $this->assertTrue($user->hasRole('parent'));
        $this->assertNotNull($user->parentProfil);
        tenancy()->end();
    }

    public function test_creation_parent_refuse_un_numero_deja_conn(): void
    {
        $slug = 'cabinet-doublon';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        $this->postJson($this->api($slug, '/parents'), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'telephone_whatsapp' => '+226 70 11 22 33',
            'password' => 'MotDePasse1',
        ])->assertStatus(201);

        $second = $this->postJson($this->api($slug, '/parents'), [
            'nom' => 'Ouédraogo',
            'prenom' => 'Salif',
            'telephone_whatsapp' => '+226 70 11 22 33',
            'password' => 'MotDePasse1',
        ]);

        $this->assertErreur($second, 'telephone_whatsapp');
    }

    public function test_admin_cree_un_eleve_directement_avec_parent(): void
    {
        $slug = 'cabinet-eleve';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        tenancy()->initialize($slug);
        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        tenancy()->end();

        $parent = $this->postJson($this->api($slug, '/parents'), [
            'nom' => 'Kaboré',
            'prenom' => 'Moussa',
            'telephone_whatsapp' => '67080910',
            'password' => 'MotDePasse1',
        ])->assertStatus(201);

        $reponse = $this->postJson($this->api($slug, '/eleves'), [
            'parent_id' => $parent->json('data.id'),
            'prenom' => 'Fatoumata',
            'classe_id' => $classe->id,
            'date_naissance' => '2012-03-14',
            'ecole' => 'Lycée Municipal',
        ]);

        $reponse->assertStatus(201);
        $this->assertSame('Fatoumata', $reponse->json('data.prenom'));
        // Repli sur le nom du parent quand le formulaire n'impose pas le nom.
        $this->assertSame('Kaboré', $reponse->json('data.nom'));
        $this->assertSame('Tle', $reponse->json('data.classe.sigle'));
        $this->assertSame($parent->json('data.id'), $reponse->json('data.parent.id'));
        $this->assertFalse($reponse->json('data.statut_compte'));

        tenancy()->initialize($slug);
        $eleveUser = User::where('prenom', 'Fatoumata')->firstOrFail();
        $this->assertTrue($eleveUser->hasRole('eleve'));
        $this->assertSame($parent->json('data.id'), (int) $eleveUser->eleve->parent_id);
        tenancy()->end();
    }

    public function test_creation_eleve_exige_parent_id(): void
    {
        $slug = 'cabinet-sans-parent';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        tenancy()->initialize($slug);
        $classe = Classe::create(['nom' => 'Sixième', 'sigle' => '6e']);
        tenancy()->end();

        $reponse = $this->postJson($this->api($slug, '/eleves'), [
            'prenom' => 'Awa',
            'classe_id' => $classe->id,
        ]);

        $this->assertErreur($reponse, 'parent_id');
    }

    public function test_creation_eleve_exige_un_parent_au_role(): void
    {
        $slug = 'cabinet-parent-faux';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        tenancy()->initialize($slug);
        $classe = Classe::create(['nom' => 'Cinquième', 'sigle' => '5e']);
        // Un compte sans rôle « parent » ne peut pas porter un élève : sinon
        // ce serait ouvrir à un tiers les contrats d'une famille.
        $autre = User::create([
            'nom' => 'Compte',
            'prenom' => 'Sans-role',
            'email' => 'sans-role@local.test',
            'password' => Hash::make('Secret1234'),
        ]);
        tenancy()->end();

        $reponse = $this->postJson($this->api($slug, '/eleves'), [
            'parent_id' => $autre->id,
            'prenom' => 'Awa',
            'classe_id' => $classe->id,
        ]);

        $this->assertErreur($reponse, 'parent_id');
    }

    public function test_listes_parents_et_eleves_renvoient_les_profils(): void
    {
        $slug = 'cabinet-listes';
        $this->connecte($slug, "admin@{$slug}.local", 'admin_cabinet');

        tenancy()->initialize($slug);
        $classe = Classe::create(['nom' => 'Quatrième', 'sigle' => '4e']);
        tenancy()->end();

        $parent = $this->postJson($this->api($slug, '/parents'), [
            'nom' => 'Zongo',
            'prenom' => 'Issouf',
            'telephone_whatsapp' => '76543210',
            'password' => 'MotDePasse1',
        ])->assertStatus(201);

        $this->postJson($this->api($slug, '/eleves'), [
            'parent_id' => $parent->json('data.id'),
            'prenom' => 'Adama',
            'classe_id' => $classe->id,
        ])->assertStatus(201);

        $this->getJson($this->api($slug, '/parents'))
            ->assertOk()
            ->assertJsonPath('data.0.nom', 'Zongo');

        $this->getJson($this->api($slug, '/eleves'))
            ->assertOk()
            ->assertJsonPath('data.0.prenom', 'Adama')
            ->assertJsonPath('data.0.classe.sigle', '4e');
    }

    public function test_enseignant_connecte_recu_403(): void
    {
        $slug = 'cabinet-enseignant';

        if (tenancy()->initialized) {
            tenancy()->end();
        }
        $this->makeCabinet($slug);
        tenancy()->initialize($slug);
        $enseignant = User::create([
            'nom' => 'Sawadogo',
            'prenom' => 'Abdoulaye',
            'email' => 'enseignant@test.local',
            'password' => Hash::make('Secret1234'),
        ]);
        $enseignant->assignRole('enseignant');
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => 'enseignant@test.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->postJson($this->api($slug, '/parents'), [
            'nom' => 'X',
            'prenom' => 'Y',
            'password' => 'MotDePasse1',
        ])->assertStatus(403);
    }

    public function test_visiteur_recu_401(): void
    {
        $slug = 'cabinet-visiteur';
        $this->makeCabinet($slug);

        $this->postJson($this->api($slug, '/parents'), [
            'nom' => 'X',
            'prenom' => 'Y',
            'password' => 'MotDePasse1',
        ])->assertStatus(401);

        $this->postJson($this->api($slug, '/eleves'), [
            'parent_id' => 1,
            'prenom' => 'Y',
            'classe_id' => 1,
        ])->assertStatus(401);
    }
}
