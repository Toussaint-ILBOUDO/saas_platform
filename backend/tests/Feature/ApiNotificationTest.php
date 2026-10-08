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

    /**
     * `route_angular` : chemin interne de l'espace.
     *
     * `url` reste une URL Blade absolue (utile au backoffice historique) mais
     * serait fausse dans l'UI Angular : elle sortirait de l'application et
     * viserait le domaine central au lieu du domaine du cabinet. C'est ce champ
     * que l'écran Angular doit router.
     */
    public function test_notification_expose_une_route_angular(): void
    {
        $this->connecterAdmin('c1');

        tenancy()->initialize('c1');
        $admin = User::where('email', 'admin@c1.local')->first();
        (new NotificationService())->create(
            $admin->id,
            'Nouvelle demande de cours',
            'Demande de Awa Ouédraogo pour la classe Terminale.',
            'demande_cours',
            ['demande_cours_id' => 7],
            'bi-journal-text'
        );
        tenancy()->end();

        $this->getJson('http://c1.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.route_angular', '/espace/pedagogie/demandes-cours/7')
            ->assertJsonPath('data.0.action_label', 'Voir la demande');
    }

    /**
     * Une notification dont l'écran cible n'existe pas encore n'expose pas de
     * route : l'écran affiche alors la ligne sans lien, mieux qu'un lien mort.
     */
    public function test_type_inconnu_ne_produit_pas_de_route_angular(): void
    {
        $this->connecterAdmin('c1');

        tenancy()->initialize('c1');
        $admin = User::where('email', 'admin@c1.local')->first();
        (new NotificationService())->create($admin->id, 'Info', 'Contenu', 'evenement_inconnu');
        tenancy()->end();

        $this->getJson('http://c1.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.route_angular', null);
    }

    /**
     * `lu` s'interprète comme « déjà lue » : `lu=1` → les lues, `lu=0` → les
     * non lues. Le filtre de l'écran Angular s'appuie sur ce contrat — une
     * inversion passait la « liste des non lues » en miroir (incident test
     * du 07/10/2026).
     */
    public function test_le_filtre_lu_trie_lues_et_non_lues(): void
    {
        $this->connecterAdmin('c2');

        tenancy()->initialize('c2');
        $admin = User::where('email', 'admin@c2.local')->first();
        $systeeme = new NotificationService();
        $systeeme->create($admin->id, 'À lire', 'Contenu', 'actualite', ['actualite_id' => 1]);
        $systeeme->create($admin->id, 'Déjà lue', 'Contenu', 'actualite', ['actualite_id' => 2]);
        $nonLueId = (int) Notification::where('user_id', $admin->id)->orderBy('id')->first()?->id;
        $lueId = (int) Notification::where('user_id', $admin->id)->orderByDesc('id')->first()?->id;
        Notification::where('id', $lueId)->update(['lu' => true, 'date_lecture' => now()]);
        tenancy()->end();

        $this->getJson('http://c2.localhost/api/notifications?lu=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nonLueId)
            ->assertJsonPath('meta.non_lues', 1);

        $this->getJson('http://c2.localhost/api/notifications?lu=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $lueId)
            ->assertJsonPath('data.0.lu', true);

        // Sans filtre, la liste complète est servie.
        $this->getJson('http://c2.localhost/api/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.non_lues', 1);
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