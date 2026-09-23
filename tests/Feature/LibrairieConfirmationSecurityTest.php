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
 * Étape 1 — C2 : confirmation et PDF de commande ne doivent plus être
 * accessibles par simple identifiant séquentiel (IDOR).
 */
class LibrairieConfirmationSecurityTest extends TestCase
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
            'email' => strtolower($role) . '_' . uniqid() . '@example.com',
            'password' => 'password',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createCommande(array $overrides = []): Commande
    {
        return Commande::create(array_merge([
            'nom_client' => 'Client Test',
            'telephone_client' => '00000000',
            'adresse_livraison' => 'Ouagadougou',
            'is_livraison' => false,
            'montant_total' => 5000,
            'frais_livraison' => 0,
            'token' => Str::random(64),
            'statut' => Commande::STATUT_EN_ATTENTE,
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | VISITEUR — accès par jeton uniquement
    |--------------------------------------------------------------------------
    */

    public function test_visiteur_sans_jeton_ne_peut_pas_voir_la_confirmation(): void
    {
        $commande = $this->createCommande();

        $this->get("/librairie/commandes/{$commande->id}/confirmation")
            ->assertNotFound();
    }

    public function test_visiteur_avec_mauvais_jeton_ne_peut_pas_voir_la_confirmation(): void
    {
        $commande = $this->createCommande();

        $this->get("/librairie/commandes/{$commande->id}/mauvais-jeton/confirmation")
            ->assertNotFound();
    }

    public function test_visiteur_avec_bon_jeton_voit_la_confirmation(): void
    {
        $commande = $this->createCommande();

        $this->get("/librairie/commandes/{$commande->id}/{$commande->token}/confirmation")
            ->assertOk();
    }

    public function test_visiteur_sans_jeton_ne_peut_pas_telecharger_le_pdf(): void
    {
        $commande = $this->createCommande();

        $this->get("/librairie/commandes/{$commande->id}/pdf")
            ->assertNotFound();
    }

    public function test_visiteur_avec_bon_jeton_telecharge_le_pdf(): void
    {
        $commande = $this->createCommande();

        $this->get("/librairie/commandes/{$commande->id}/{$commande->token}/pdf")
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | UTILISATEUR CONNECTÉ — la CommandePolicy s'applique
    |--------------------------------------------------------------------------
    */

    public function test_utilisateur_connecte_ne_peut_pas_voir_la_confirmation_d_une_commande_d_autrui(): void
    {
        $userA = $this->createUserWithRole('parent');
        $autre = $this->createUserWithRole('parent');
        $commandeAutre = $this->createCommande(['user_id' => $autre->id]);

        $this->actingAs($userA)
            ->get("/librairie/commandes/{$commandeAutre->id}/{$commandeAutre->token}/confirmation")
            ->assertForbidden();
    }

    public function test_utilisateur_connecte_ne_peut_pas_telecharger_le_pdf_d_une_commande_d_autrui(): void
    {
        $userA = $this->createUserWithRole('parent');
        $autre = $this->createUserWithRole('parent');
        $commandeAutre = $this->createCommande(['user_id' => $autre->id]);

        $this->actingAs($userA)
            ->get("/librairie/commandes/{$commandeAutre->id}/{$commandeAutre->token}/pdf")
            ->assertForbidden();
    }

    public function test_utilisateur_connecte_voit_la_confirmation_de_sa_propre_commande(): void
    {
        $userA = $this->createUserWithRole('parent');
        $commandeA = $this->createCommande(['user_id' => $userA->id]);

        $this->actingAs($userA)
            ->get("/librairie/commandes/{$commandeA->id}/{$commandeA->token}/confirmation")
            ->assertOk();
    }

    public function test_utilisateur_connecte_telecharge_le_pdf_de_sa_propre_commande(): void
    {
        $userA = $this->createUserWithRole('parent');
        $commandeA = $this->createCommande(['user_id' => $userA->id]);

        $this->actingAs($userA)
            ->get("/librairie/commandes/{$commandeA->id}/{$commandeA->token}/pdf")
            ->assertOk();
    }

    public function test_admin_voit_la_confirmation_de_n_importe_quelle_commande(): void
    {
        $admin = $this->createUserWithRole('admin');
        $commande = $this->createCommande();

        $this->actingAs($admin)
            ->get("/librairie/commandes/{$commande->id}/{$commande->token}/confirmation")
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | INTÉGRATION — parcours de commande invité
    |--------------------------------------------------------------------------
    */

    public function test_parcours_commande_invite_redirige_avec_jeton(): void
    {
        $categorie = CategorieProduit::create([
            'nom' => 'Fournitures',
            'slug' => 'fournitures',
        ]);

        $produit = Produit::create([
            'nom' => 'Cahier Test',
            'slug' => 'cahier-test',
            'prix' => 1000,
            'stock' => 10,
            'statut' => 'actif',
            'categorie_id' => $categorie->id,
        ]);

        $this->post('/librairie/commander', [
            'panier' => [
                ['produit_id' => $produit->id, 'quantite' => 2],
            ],
            'nom_client' => 'Visiteur',
            'telephone_client' => '00000000',
            'whatsapp' => '00000000',
            'adresse_livraison' => 'Ouagadougou',
            'is_livraison' => false,
        ])->assertRedirect();

        $commande = Commande::first();

        $this->assertNotNull($commande);
        $this->assertNotNull($commande->token);

        $this->get("/librairie/commandes/{$commande->id}/{$commande->token}/confirmation")
            ->assertOk();
    }
}
