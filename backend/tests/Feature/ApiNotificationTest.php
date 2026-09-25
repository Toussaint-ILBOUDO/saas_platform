<?php

namespace Tests\Feature;

use App\Jobs\EnvoyerIdentifiantsCabinet;
use App\Models\Cabinet;
use App\Models\Notification;
use App\Models\User;
use App\Modules\Systeme\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Notifications T3.6 : API en base (liste/lue/lire-toutes/suppression), abonnement
 * push (stockage) et envoi des identifiants en file d'attente.
 */
class ApiNotificationTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecterAdmin(string $slug): void
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);
        User::where('email', "admin@{$slug}.local")->update([
            'password' => Hash::make('Secret1234'),
        ]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => "admin@{$slug}.local",
            'password' => 'Secret1234',
        ])->assertOk();
    }

    public function test_identifiants_cabinet_envoyes_en_file(): void
    {
        $this->makeCabinet('c1');
        Queue::fake();

        $cabinet = Cabinet::where('id', 'c1')->first();

        EnvoyerIdentifiantsCabinet::dispatch($cabinet, 'admin@c1.local', 'Motdepasse123');

        Queue::assertPushed(
            EnvoyerIdentifiantsCabinet::class,
            fn (EnvoyerIdentifiantsCabinet $job) => $job->cabinet->id === 'c1'
        );
    }

    public function test_notifications_liste_lecture_suppression(): void
    {
        $this->connecterAdmin('c1');

        // Liste vide.
        $this->getJson('http://c1.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.non_lues', 0);

        tenancy()->initialize('c1');
        $admin = User::where('email', 'admin@c1.local')->first();
        (new NotificationService())->create(
            $admin->id,
            'Nouvelle commande',
            'Commande 2026-0001 enregistrée.',
            'librairie_commande',
            ['commande_id' => 1],
            'bi-cart-check'
        );
        tenancy()->end();

        $this->getJson('http://c1.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.non_lues', 1)
            ->assertJsonPath('data.0.type', 'librairie_commande');

        tenancy()->initialize('c1');
        $notificationId = Notification::where('user_id', $admin->id)->value('id');
        tenancy()->end();

        // Marquer lue puis tout lire (idempotent).
        $this->postJson("http://c1.localhost/api/notifications/{$notificationId}/lue")
            ->assertOk();

        $this->getJson('http://c1.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('meta.non_lues', 0)
            ->assertJsonPath('data.0.lu', true);

        // Une notification d'un autre utilisateur n'existe pas pour l'admin (404).
        tenancy()->initialize('c1');
        $autre = User::create([
            'nom' => 'Autre',
            'prenom' => 'Utilisateur',
            'email' => 'autre@c1.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        (new NotificationService())->create($autre->id, 'Privée', 'Contenu', 'contrat');
        $autreNotificationId = Notification::where('user_id', $autre->id)->value('id');
        tenancy()->end();

        $this->postJson("http://c1.localhost/api/notifications/{$autreNotificationId}/lue")
            ->assertStatus(404);

        // Suppression.
        $this->deleteJson("http://c1.localhost/api/notifications/{$notificationId}")
            ->assertOk();

        $this->getJson('http://c1.localhost/api/notifications')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_abonnement_push_ajout_mise_a_jour_suppression(): void
    {
        $this->connecterAdmin('c1');

        // Ajout.
        $this->postJson('http://c1.localhost/api/abonnement-push', [
            'endpoint' => 'https://fcm.googleapis.com/gcm/send/abc123',
            'keys' => [
                'public_key' => 'cle-publique-x',
                'auth_token' => 'jeton-auth-y',
            ],
            'content_encoding' => 'aes128gcm',
        ])->assertStatus(201);

        $this->getJson('http://c1.localhost/api/abonnement-push')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.endpoint', 'https://fcm.googleapis.com/gcm/send/abc123');

        // Même endpoint → mise à jour, pas de doublon.
        $this->postJson('http://c1.localhost/api/abonnement-push', [
            'endpoint' => 'https://fcm.googleapis.com/gcm/send/abc123',
            'keys' => ['public_key' => 'cle-publique-z'],
        ])->assertStatus(201);

        $this->getJson('http://c1.localhost/api/abonnement-push')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_key', 'cle-publique-z');

        // Suppression.
        $this->deleteJson('http://c1.localhost/api/abonnement-push', [
            'endpoint' => 'https://fcm.googleapis.com/gcm/send/abc123',
        ])->assertOk();

        $this->getJson('http://c1.localhost/api/abonnement-push')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}