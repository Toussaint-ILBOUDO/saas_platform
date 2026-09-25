<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ReinitialisationMotDePasse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Authentification API par session (T3.2) : connexion, moi, déconnexion,
 * rôle actif, mot de passe oublié / réinitialisation / changement.
 */
class ApiAuthTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function prepareAdmin(string $slug, string $email = null): void
    {
        $this->makeCabinet($slug);
        tenancy()->initialize($slug);

        User::where('email', $email ?? "admin@{$slug}.local")->update([
            'password' => Hash::make('Secret1234'),
        ]);

        tenancy()->end();
    }

    public function test_connexion_moi_role_actif_deconnexion(): void
    {
        $this->prepareAdmin('c1');

        $this->postJson('http://c1.localhost/api/auth/connexion', [
            'email' => 'admin@c1.local',
            'password' => 'Secret1234',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@c1.local')
            ->assertJsonPath('user.roles.0', 'admin_cabinet')
            ->assertJsonPath('user.active_role', 'admin_cabinet');

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@c1.local');

        // Rôle actif : demande un rôle non possédé → refus ; puis le sien → OK.
        $this->postJson('http://c1.localhost/api/auth/role-actif', ['role' => 'enseignant'])
            ->assertStatus(403)
            ->assertJson(['code' => 'ROLE_INVALIDE']);

        $this->postJson('http://c1.localhost/api/auth/role-actif', ['role' => 'admin_cabinet'])
            ->assertOk()
            ->assertJsonPath('active_role', 'admin_cabinet');

        $this->postJson('http://c1.localhost/api/auth/deconnexion')->assertOk();

        $this->getJson('http://c1.localhost/api/auth/moi')->assertStatus(401);
    }

    public function test_connexion_identifiants_incorrects(): void
    {
        $this->prepareAdmin('c1');

        $this->postJson('http://c1.localhost/api/auth/connexion', [
            'email' => 'admin@c1.local',
            'password' => 'Mauvais',
        ])
            ->assertStatus(422)
            ->assertJson(['code' => 'IDENTIFIANTS_INCORRECTS']);
    }

    public function test_mot_de_passe_oublie_envoie_un_email(): void
    {
        $this->prepareAdmin('c1');
        Notification::fake();

        $this->postJson('http://c1.localhost/api/auth/mot-de-passe-oublie', [
            'email' => 'admin@c1.local',
        ])->assertOk();

        tenancy()->initialize('c1');

        Notification::assertSentTo(
            User::where('email', 'admin@c1.local')->first(),
            ReinitialisationMotDePasse::class
        );

        tenancy()->end();
    }

    public function test_reinitialiser_mot_de_passe(): void
    {
        $this->prepareAdmin('c1');

        tenancy()->initialize('c1');
        $user = User::where('email', 'admin@c1.local')->first();
        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
        tenancy()->end();

        // Jeton invalide → 422.
        $this->postJson('http://c1.localhost/api/auth/reinitialiser-mot-de-passe', [
            'email' => 'admin@c1.local',
            'token' => 'jeton-invalide',
            'password' => 'Nouveau1234',
            'password_confirmation' => 'Nouveau1234',
        ])
            ->assertStatus(422)
            ->assertJson(['code' => 'JETON_INVALIDE']);

        // Jeton valide → mot de passe remplacé.
        $this->postJson('http://c1.localhost/api/auth/reinitialiser-mot-de-passe', [
            'email' => 'admin@c1.local',
            'token' => $token,
            'password' => 'Nouveau1234',
            'password_confirmation' => 'Nouveau1234',
        ])->assertOk();

        tenancy()->initialize('c1');
        $this->assertTrue(Hash::check('Nouveau1234', User::where('email', 'admin@c1.local')->value('password')));
        tenancy()->end();
    }

    public function test_changer_mot_de_passe(): void
    {
        $this->prepareAdmin('c1');

        $this->postJson('http://c1.localhost/api/auth/connexion', [
            'email' => 'admin@c1.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->postJson('http://c1.localhost/api/auth/changer-mot-de-passe', [
            'mot_de_passe_actuel' => 'Mauvais',
            'nouveau_mot_de_passe' => 'Nouveau1234',
            'nouveau_mot_de_passe_confirmation' => 'Nouveau1234',
        ])
            ->assertStatus(422)
            ->assertJson(['code' => 'MOT_DE_PASSE_ACTUEL_INCORRECT']);

        $this->postJson('http://c1.localhost/api/auth/changer-mot-de-passe', [
            'mot_de_passe_actuel' => 'Secret1234',
            'nouveau_mot_de_passe' => 'Nouveau1234',
            'nouveau_mot_de_passe_confirmation' => 'Nouveau1234',
        ])->assertOk();

        tenancy()->initialize('c1');
        $this->assertTrue(Hash::check('Nouveau1234', User::where('email', 'admin@c1.local')->value('password')));
        tenancy()->end();
    }
}