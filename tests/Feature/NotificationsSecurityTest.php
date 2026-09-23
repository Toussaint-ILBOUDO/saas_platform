<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de sécurité et fonctionnalité du système de notifications.
 */
class NotificationsSecurityTest extends TestCase
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

    private function createNotification(User $user, array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'titre' => 'Nouvelle notification',
            'contenu' => 'Contenu de la notification',
            'type' => 'info',
            'lu' => false,
        ], $overrides));
    }

    // =====================================================
    // SÉCURITÉ — accès
    // =====================================================

    public function test_visiteur_ne_peut_pas_voir_ses_notifications(): void
    {
        $this->get('/notifications')->assertRedirect(route('login'));
    }

    public function test_visiteur_ne_peut_pas_ouvrir_une_notification(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user);

        $this->get("/notifications/{$notification->id}")
            ->assertRedirect(route('login'));
    }

    public function test_utilisateur_voit_ses_propres_notifications(): void
    {
        $user = $this->createUserWithRole('parent');
        $this->createNotification($user);

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk();
    }

    public function test_utilisateur_ne_peut_pas_ouvrir_la_notification_d_autrui(): void
    {
        $userA = $this->createUserWithRole('parent');
        $userB = $this->createUserWithRole('parent');
        $notificationB = $this->createNotification($userB);

        $this->actingAs($userA)
            ->get("/notifications/{$notificationB->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationB->id,
            'lu' => false,
        ]);
    }

    public function test_utilisateur_peut_ouvrir_sa_propre_notification(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'lu' => true,
        ]);
    }

    // =====================================================
    // MARQUER COMME LU
    // =====================================================

    public function test_marquer_comme_lu_depuis_le_centre(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user);

        $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read")
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'lu' => true,
        ]);
    }

    public function test_marquer_comme_lu_autrui_interdit(): void
    {
        $userA = $this->createUserWithRole('parent');
        $userB = $this->createUserWithRole('parent');
        $notificationB = $this->createNotification($userB);

        $this->actingAs($userA)
            ->post("/notifications/{$notificationB->id}/read")
            ->assertForbidden();
    }

    public function test_tout_marquer_comme_lu(): void
    {
        $user = $this->createUserWithRole('parent');

        Notification::insert([
            [
                'user_id' => $user->id,
                'titre' => 'Notif 1',
                'contenu' => 'Contenu 1',
                'type' => 'info',
                'lu' => false,
            ],
            [
                'user_id' => $user->id,
                'titre' => 'Notif 2',
                'contenu' => 'Contenu 2',
                'type' => 'info',
                'lu' => false,
            ],
        ]);

        $this->actingAs($user)
            ->post('/notifications/read-all')
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'lu' => true,
        ]);

        $this->assertDatabaseCount('notifications', 2);
    }

    // =====================================================
    // SUPPRESSION
    // =====================================================

    public function test_supprimer_une_notification(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user);

        $this->actingAs($user)
            ->delete("/notifications/{$notification->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_supprimer_autrui_interdit(): void
    {
        $userA = $this->createUserWithRole('parent');
        $userB = $this->createUserWithRole('parent');
        $notificationB = $this->createNotification($userB);

        $this->actingAs($userA)
            ->delete("/notifications/{$notificationB->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationB->id,
        ]);
    }

    // =====================================================
    // COMPTEUR NON LUES
    // =====================================================

    public function test_compteur_non_lues(): void
    {
        $user = $this->createUserWithRole('parent');

        Notification::insert([
            [
                'user_id' => $user->id,
                'titre' => 'Lu',
                'contenu' => 'Lu',
                'type' => 'info',
                'lu' => true,
            ],
            [
                'user_id' => $user->id,
                'titre' => 'Non lu 1',
                'contenu' => 'Non lu 1',
                'type' => 'info',
                'lu' => false,
            ],
            [
                'user_id' => $user->id,
                'titre' => 'Non lu 2',
                'contenu' => 'Non lu 2',
                'type' => 'info',
                'lu' => false,
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/notifications/unread-count')
            ->assertOk()
            ->assertJson(['count' => 2]);
    }

    public function test_compteur_apres_marquer_comme_lu(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, ['lu' => false]);

        $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read");

        $this->actingAs($user)
            ->getJson('/notifications/unread-count')
            ->assertOk()
            ->assertJson(['count' => 0]);
    }

    public function test_compteur_apres_tout_marquer_comme_lu(): void
    {
        $user = $this->createUserWithRole('parent');

        Notification::insert([
            [
                'user_id' => $user->id,
                'titre' => 'Notif 1',
                'contenu' => 'Contenu 1',
                'type' => 'info',
                'lu' => false,
            ],
            [
                'user_id' => $user->id,
                'titre' => 'Notif 2',
                'contenu' => 'Contenu 2',
                'type' => 'info',
                'lu' => false,
            ],
        ]);

        $this->actingAs($user)
            ->post('/notifications/read-all');

        $this->actingAs($user)
            ->getJson('/notifications/unread-count')
            ->assertOk()
            ->assertJson(['count' => 0]);
    }

    // =====================================================
    // REDIRECTION PAR TYPE
    // =====================================================

    public function test_redirection_contrat(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, [
            'type' => 'contrat',
            'data' => ['contrat_id' => 1],
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect();
    }

    public function test_redirection_facture(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, [
            'type' => 'facture',
            'data' => ['facture_id' => 1],
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect();
    }

    public function test_redirection_actualite(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, [
            'type' => 'actualite',
            'data' => ['actualite_slug' => 'mon-article'],
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect(route('actualites.show', 'mon-article'));
    }

    public function test_redirection_rapport(): void
    {
        $user = $this->createUserWithRole('admin');
        $notification = $this->createNotification($user, [
            'type' => 'rapport',
            'data' => ['rapport_id' => 1],
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect(route('rapports-mensuels.show', 1));
    }

    public function test_redirection_demande_cours(): void
    {
        $user = $this->createUserWithRole('admin');
        $notification = $this->createNotification($user, [
            'type' => 'demande_cours',
            'data' => ['demande_cours_id' => 1],
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect(route('demande-cours.show', 1));
    }

    public function test_redirection_type_inconnu_retourne_index(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, [
            'type' => 'type_inexistant',
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect(route('notifications.index'));
    }

    // =====================================================
    // VUE INDEX
    // =====================================================

    public function test_centre_notifications_affiche_les_notifications(): void
    {
        $user = $this->createUserWithRole('parent');
        $this->createNotification($user, ['titre' => 'Test titre', 'contenu' => 'Test contenu']);

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Test titre')
            ->assertSee('Test contenu');
    }

    public function test_centre_notifications_etat_vide(): void
    {
        $user = $this->createUserWithRole('parent');

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Vous êtes à jour');
    }

    public function test_centre_notifications_montre_badge_non_lu(): void
    {
        $user = $this->createUserWithRole('parent');
        $this->createNotification($user, ['lu' => false, 'titre' => 'Non lue']);

        $this->actingAs($user)
            ->get('/notifications')
            ->assertSee('Nouveau')
            ->assertSee('Non lue');
    }

    // =====================================================
    // NOTIFICATION ICONE
    // =====================================================

    public function test_notification_a_une_icone_par_defaut(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, ['type' => 'facture']);

        $this->assertNotEmpty($notification->icone_html);
    }

    public function test_notification_type_facture_icone(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, ['type' => 'facture']);

        $this->assertStringContainsString('bi-receipt', $notification->icone_html);
    }

    public function test_notification_couleur_selon_type(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, ['type' => 'facture']);

        $this->assertEquals('text-warning', $notification->couleur);
    }

    public function test_notification_url_selon_type(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, [
            'type' => 'facture',
            'data' => ['facture_id' => 1],
        ]);

        $this->assertNotNull($notification->url);
    }

    public function test_notification_action_label_selon_type(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, ['type' => 'facture']);

        $this->assertEquals('Voir la facture', $notification->action_label);
    }

    // =====================================================
    // PAGINATION
    // =====================================================

    public function test_pagination_notifications(): void
    {
        $user = $this->createUserWithRole('parent');

        Notification::insert(
            collect(range(1, 25))->map(fn ($i) => [
                'user_id' => $user->id,
                'titre' => "Notif-" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'contenu' => "Contenu {$i}",
                'type' => 'info',
                'lu' => false,
                'created_at' => now()->subSeconds(26 - $i),
                'updated_at' => now()->subSeconds(26 - $i),
            ])->toArray()
        );

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Notif-25')
            ->assertDontSee('Notif-01');
    }

    // =====================================================
    // SÉCURITÉ — CSRF
    // =====================================================

    public function test_mark_as_read_requete_sans_csrf_rejetee(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user);

        $this->actingAs($user)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post("/notifications/{$notification->id}/read")
            ->assertRedirect();
    }

    // =====================================================
    // TYPE LIBRAIRIE COMMANDE — client vs admin
    // =====================================================

    public function test_commande_client_redirige_vers_mes_commandes(): void
    {
        $user = $this->createUserWithRole('parent');
        $notification = $this->createNotification($user, [
            'type' => 'librairie_commande',
            'data' => ['commande_id' => 1, 'route_key' => 'client'],
        ]);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect(route('librairie.mes-commandes.show', 1));
    }
}
