<?php

namespace Tests\Feature\Api\Temoignages;

use App\Models\Cabinet;
use App\Models\Temoignage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API témoignages (Angular) : liste publique paginée (le « Voir plus » de
 * la page dédiée en a besoin) et soumission d'un membre connecté, avec
 * anonymat possible.
 */
class TemoignageApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

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

    private function creerTemoignages(int $nombre): void
    {
        $this->preparerCabinet('c1');

        $parent = User::create([
            'nom' => 'Test',
            'prenom' => 'Parent',
            'email' => 'parent-temo@c1.local',
            'password' => Hash::make('Secret1234'),
        ]);
        $parent->assignRole('parent');
        $parent->parentProfil()->firstOrCreate([], []);

        for ($i = 1; $i <= $nombre; $i++) {
            Temoignage::create([
                'user_id' => $parent->id,
                'slug' => "temoignage-{$i}",
                'contenu' => "Témoignage numéro {$i} sur le cabinet, suffisamment long pour la validation.",
                'role' => 'parent',
                'anonyme' => false,
                'statut' => 'publie',
                'published_at' => now()->subMinutes($nombre - $i),
            ]);
        }

        tenancy()->end();
    }

    private function connecteParent(string $slug, string $email): void
    {
        $this->preparerCabinet($slug);

        $user = User::where('email', $email)->first() ?? User::create([
            'nom' => 'Diallo',
            'prenom' => 'Mariam',
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
        $user->assignRole('parent');
        $user->parentProfil()->firstOrCreate([], []);
        $user->update(['password' => Hash::make('Secret1234')]);

        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    public function test_liste_publique_est_paginee_avec_meta(): void
    {
        $this->creerTemoignages(8);

        $reponse = $this->getJson('http://c1.localhost/api/public/temoignages?per_page=6&page=1');
        $reponse->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonStructure(['data' => [['id', 'slug', 'contenu', 'auteur', 'role_label', 'anonyme', 'score']], 'meta' => ['current_page', 'last_page', 'total', 'per_page']])
            ->assertJsonPath('meta.per_page', 6)
            ->assertJsonPath('meta.total', 8)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('http://c1.localhost/api/public/temoignages?per_page=6&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_membre_connecte_peut_publier_son_temoignage(): void
    {
        $this->connecteParent('c1', 'auteur@c1.local');

        $reponse = $this->postJson('http://c1.localhost/api/auth/temoignages', [
            'contenu' => 'Le cabinet Magis Plus Center a transformé la scolarité de mon fils, merci pour tout.',
            'anonyme' => false,
        ]);

        $reponse->assertCreated()
            ->assertJsonPath('temoignage.auteur', 'Mariam D.')
            ->assertJsonPath('temoignage.role_label', 'Parent d\'élève')
            ->assertJsonPath('temoignage.anonyme', false);

        // Il apparaît immédiatement dans la liste publique (cache vidé).
        $this->getJson('http://c1.localhost/api/public/temoignages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.auteur', 'Mariam D.');

        $this->assertDatabaseHas('temoignages', [
            'statut' => 'publie',
            'anonyme' => false,
        ]);
    }

    public function test_temoignage_anonyme_masque_l_auteur(): void
    {
        $this->connecteParent('c1', 'prudent@c1.local');

        $this->postJson('http://c1.localhost/api/auth/temoignages', [
            'contenu' => 'Une expérience formidable avec les enseignants du cabinet, je recommande vivement.',
            'anonyme' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('temoignage.anonyme', true)
            ->assertJsonPath('temoignage.auteur', 'Témoignage anonyme');
    }

    public function test_visiteur_ne_peut_pas_temoigner(): void
    {
        $this->preparerCabinet('c1');

        $this->postJson('http://c1.localhost/api/auth/temoignages', [
            'contenu' => 'Un témoignage de visiteur qui ne devrait jamais passer la validation.',
        ])
            ->assertStatus(401);
    }

    public function test_contenu_trop_court_est_refuse(): void
    {
        $this->connecteParent('c1', 'bref@c1.local');

        $this->postJson('http://c1.localhost/api/auth/temoignages', [
            'contenu' => 'Trop court.',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['contenu']]);
    }

    public function test_soumission_est_throttlee(): void
    {
        $this->connecteParent('c1', 'spam@c1.local');

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('http://c1.localhost/api/auth/temoignages', [
                'contenu' => "Témoignage de test numéro {$i}, assez long pour passer la validation.",
            ])->assertCreated();
        }

        $this->postJson('http://c1.localhost/api/auth/temoignages', [
            'contenu' => 'Un onzième témoignage qui doit être refusé par le throttle.',
        ])->assertStatus(429);
    }
}
