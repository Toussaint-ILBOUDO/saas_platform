<?php

namespace Tests\Feature;

use App\Models\CategorieProduit;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Commandes librairie — référence publique aléatoire.
 *
 * Le « n° de commande » séquentiel (id) est remplacé partout par une
 * référence aléatoire « CMD-XXXXXXXX », générée à la création et unique.
 */
class CommandeReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::create([
            'nom' => 'Nom',
            'prenom' => 'Prenom',
            'email' => $role . '_' . uniqid() . '@example.com',
            'password' => 'password',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createCommande(array $overrides = []): Commande
    {
        return Commande::create(array_merge([
            'user_id' => $this->createUserWithRole('parent')->id,
            'nom_client' => 'Client Test',
            'telephone_client' => '00000000',
            'adresse_livraison' => 'Ouagadougou',
            'montant_total' => 5000,
            'token' => Str::random(64),
            'statut' => Commande::STATUT_CONFIRMEE,
        ], $overrides));
    }

    public function test_la_reference_est_generee_aleatoirement_et_unique(): void
    {
        $commandeA = $this->createCommande();
        $commandeB = $this->createCommande();
        $commandeC = $this->createCommande();

        foreach ([$commandeA, $commandeB, $commandeC] as $commande) {
            $this->assertNotNull($commande->reference);
            $this->assertMatchesRegularExpression('/^CMD-[A-Z0-9]{8}$/', $commande->reference);
            $this->assertNotSame('#' . $commande->id, $commande->reference);
        }

        $this->assertCount(3, collect([$commandeA, $commandeB, $commandeC])->pluck('reference')->unique());
    }

    public function test_les_vues_du_client_affichent_la_reference_et_non_l_identifiant(): void
    {
        $client = $this->createUserWithRole('parent');
        $commande = $this->createCommande(['user_id' => $client->id]);

        $this->actingAs($client)
            ->get(route('librairie.mes-commandes.index'))
            ->assertOk()
            ->assertSee($commande->reference)
            ->assertDontSee('#' . $commande->id);

        $this->actingAs($client)
            ->get(route('librairie.mes-commandes.show', $commande))
            ->assertOk()
            ->assertSee($commande->reference)
            ->assertDontSee('#' . $commande->id);
    }

    public function test_la_confirmation_publique_affiche_la_reference(): void
    {
        $commande = $this->createCommande();

        $this->get("/librairie/commandes/{$commande->id}/{$commande->token}/confirmation")
            ->assertOk()
            ->assertSee('référence de commande')
            ->assertSee($commande->reference)
            ->assertDontSee('n°')
            ->assertDontSee('#' . $commande->id);
    }

    public function test_l_index_admin_affiche_et_recherche_par_reference(): void
    {
        $admin = $this->createUserWithRole('admin');
        $commandeA = $this->createCommande();
        $commandeB = $this->createCommande();

        $this->actingAs($admin)
            ->get(route('admin.librairie.commandes.index'))
            ->assertOk()
            ->assertSee($commandeA->reference)
            ->assertSee($commandeB->reference);

        $this->actingAs($admin)
            ->get(route('admin.librairie.commandes.index', ['search' => $commandeA->reference]))
            ->assertOk()
            ->assertSee($commandeA->reference)
            ->assertDontSee($commandeB->reference);
    }
}