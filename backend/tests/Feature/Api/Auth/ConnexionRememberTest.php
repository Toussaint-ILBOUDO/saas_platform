<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Cookie « rester connecté » (remember-me, décision PWA) : la connexion à
 * l'API Angular pose un cookie recaller de 400 jours, re-poussé à chaque
 * visite /auth/moi — le téléphone reste connecté « à vie » tant que l'app
 * est ouverte au moins une fois par période. La déconnexion l'efface et
 * invalide le jeton côté serveur.
 */
class ConnexionRememberTest extends TenantTestCase
{
    use InteractsWithCabinets;

    protected function setUp(): void
    {
        parent::setUp();

        // Comme un navigateur de même origine : les requêtes JSON portent les
        // cookies. Sans cela, `getJson`/`postJson` n'en envoient aucun et le
        // replay du recaller ne serait jamais exercé.
        $this->withCredentials();
    }

    private function preparerCabinet(string $slug): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        if (! Cabinet::find($slug)) {
            $this->makeCabinet($slug);
        }
        tenancy()->initialize($slug);
    }

    private function creerCompte(string $slug, string $email): User
    {
        $this->preparerCabinet($slug);

        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'Souvenir',
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
        $user->assignRole('enseignant');

        tenancy()->end();

        return $user;
    }

    private function connecter(string $slug, string $email)
    {
        return $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    /** Simule une prochaine visite du navigateur : session purgée, guard oublié. */
    private function nouvelleVisite(): void
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    public function test_connexion_pose_cookie_recaller_400_jours(): void
    {
        $this->creerCompte('c1', 'reca@c1.local');

        $reponse = $this->connecter('c1', 'reca@c1.local');
        $nom = Auth::guard('web')->getRecallerName();

        $reponse->assertCookie($nom);

        // Échéance ≈ 400 jours (maximum navigateur, « à vie » au quotidien).
        $cookie = $reponse->getCookie($nom, false);
        $jours = ($cookie->getExpiresTime() - time()) / 86400;
        $this->assertGreaterThan(390, $jours);
        $this->assertLessThan(410, $jours);

        // Valeur recaller attendue : id|jeton|empreinte(mot de passe).
        $this->assertCount(3, explode('|', $reponse->getCookie($nom)->getValue()));
    }

    public function test_reconnexion_automatique_sans_session_via_cookie_recaller(): void
    {
        $this->creerCompte('c1', 'reca2@c1.local');

        $connexion = $this->connecter('c1', 'reca2@c1.local');
        $nom = Auth::guard('web')->getRecallerName();
        $valeur = $connexion->getCookie($nom)->getValue();

        // Phone redémarré : plus de session, seuls les cookies persistent.
        $this->nouvelleVisite();
        $this->withCookie($nom, $valeur);

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertOk()
            ->assertJsonPath('user.email', 'reca2@c1.local');
    }

    public function test_visite_recurrente_repousse_le_cookie_recaller(): void
    {
        $this->creerCompte('c1', 'reca3@c1.local');

        $this->connecter('c1', 'reca3@c1.local');
        $nom = Auth::guard('web')->getRecallerName();

        // Ouvrir l'app (session encore valide) re-pousse l'échéance.
        $this->app['auth']->forgetGuards();

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertOk()
            ->assertCookie($nom);
    }

    public function test_deconnexion_efface_cookie_recaller(): void
    {
        $this->creerCompte('c1', 'reca4@c1.local');

        $connexion = $this->connecter('c1', 'reca4@c1.local');
        $nom = Auth::guard('web')->getRecallerName();
        $valeur = $connexion->getCookie($nom)->getValue();

        $this->nouvelleVisite();
        $this->withCookie($nom, $valeur);
        $this->getJson('http://c1.localhost/api/auth/moi')->assertOk();

        $this->postJson('http://c1.localhost/api/auth/deconnexion')
            ->assertOk()
            ->assertCookieExpired($nom);
    }

    public function test_ancien_cookie_recaller_renvoie_401_apres_deconnexion(): void
    {
        $this->creerCompte('c1', 'reca5@c1.local');

        $connexion = $this->connecter('c1', 'reca5@c1.local');
        $nom = Auth::guard('web')->getRecallerName();
        $valeur = $connexion->getCookie($nom)->getValue();

        $this->postJson('http://c1.localhost/api/auth/deconnexion')->assertOk();

        // Jeton cyclé côté serveur : même si un cookie est rejoué, 401.
        $this->nouvelleVisite();
        $this->withCookie($nom, $valeur);

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertStatus(401)
            ->assertJsonPath('code', 'NON_CONNECTE');
    }
}
