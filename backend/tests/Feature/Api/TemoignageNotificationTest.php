<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\Temoignage;
use App\Models\TemoignageSignalement;
use App\Models\User;
use App\Modules\Temoignages\Services\TemoignageNotificationService;
use Illuminate\Support\Collection;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Destinataires des notifications de modération des témoignages (D-007/D-051).
 *
 * Régression : `TemoignageNotificationService::admins()` interrogeait
 * `['admin', 'super-admin']`, deux rôles qui n'existent dans aucune base tenant.
 * La requête renvoyait zéro destinataire, donc **aucune** notification de
 * modération n'était créée — publication et signalement inclus — sans qu'aucune
 * erreur ne remonte. Ces tests vérifient qu'un staff du cabinet est bien notifié.
 */
class TemoignageNotificationTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function service(): TemoignageNotificationService
    {
        return app(TemoignageNotificationService::class);
    }

    /**
     * Exécute un callback dans la base du cabinet.
     *
     * Indispensable : `User::role(...)` résout les rôles Spatie sur la
     * connexion par défaut, qui n'est la base du cabinet que si la tenancy est
     * initialisée. C'est d'ailleurs la cause historique du bug — sans ce
     * contexte, la requête part sur la base centrale.
     */
    private function dansCabinet(callable $action): mixed
    {
        if (tenancy()->initialized) {
            return $action();
        }

        tenancy()->initialize('c1');

        try {
            return $action();
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Un parent prêt à publier un témoignage. La clé étrangère
     * `temoignages_user_id` est stricte : il faut un vrai utilisateur.
     */
    private function auteur(): User
    {
        return $this->dansCabinet(function () {
            $user = User::firstOrCreate(
                ['email' => 'nadie@c1.local'],
                [
                    'nom' => 'Sawadogo',
                    'prenom' => 'Nadié',
                    'password' => bcrypt('Secret1234'),
                    'statut' => true,
                ]
            );

            return $user->hasRole('parent') ? $user : $user->assignRole('parent');
        });
    }

    private function temoignage(): Temoignage
    {
        return $this->dansCabinet(fn () => Temoignage::create([
            'user_id' => $this->auteur()->id,
            'nom' => 'Sawadogo',
            'prenom' => 'Nadié',
            'contenu' => 'Un cabinet très sérieux.',
            'statut' => 'publie',
            'slug' => 'un-cabinet-tres-serieux',
        ]));
    }

    private function notificationsDe(string $email): Collection
    {
        return $this->dansCabinet(fn () => Notification::where(
            'user_id',
            User::where('email', $email)->value('id')
        )->get());
    }

    public function test_publication_notifie_le_staff_du_cabinet(): void
    {
        $this->makeCabinet('c1');

        $temoignage = $this->dansCabinet(function () {
            $temoignage = $this->temoignage();

            $this->service()->nouveau($temoignage);

            return $temoignage;
        });

        $notifications = $this->notificationsDe('admin@c1.local');

        $this->assertCount(1, $notifications, 'Le staff doit être notifié à la publication.');
        $this->assertSame('temoignage', $notifications->first()->type);
        $this->assertSame($temoignage->id, $notifications->first()->data['temoignage_id']);
    }

    public function test_signalement_notifie_le_staff_du_cabinet(): void
    {
        $this->makeCabinet('c1');

        $this->dansCabinet(function () {
            $signalement = TemoignageSignalement::create([
                'temoignage_id' => $this->temoignage()->id,
                'user_id' => $this->auteur()->id,
                'motif' => 'Contenu injurieux',
            ]);

            $this->service()->signale($signalement);
        });

        $notifications = $this->notificationsDe('admin@c1.local');

        $this->assertCount(1, $notifications, 'Un signalement doit notifier le staff.');
        $this->assertSame('temoignage_signalement', $notifications->first()->type);
    }

    /**
     * Le gestionnaire de librairie est aussi du staff (D-007) : il modère les
     * avis produits, il doit donc être notifié comme l'admin.
     */
    public function test_gestionnaire_librairie_est_egalement_notifie(): void
    {
        $this->makeCabinet('c1');

        $this->dansCabinet(function () {
            User::create([
                'nom' => 'Compaoré',
                'prenom' => 'Alizeta',
                'email' => 'librairie@c1.local',
                'password' => bcrypt('Secret1234'),
                'statut' => true,
            ])->assignRole('gestionnaire_librairie');

            $this->service()->nouveau($this->temoignage());
        });

        $this->assertCount(1, $this->notificationsDe('librairie@c1.local'));
    }

    /**
     * Un parent n'est pas du staff : il ne doit rien recevoir de la modération.
     */
    public function test_parent_non_staff_non_notifie(): void
    {
        $this->makeCabinet('c1');

        $this->dansCabinet(function () {
            User::create([
                'nom' => 'Kaboré',
                'prenom' => 'Salif',
                'email' => 'parent@c1.local',
                'password' => bcrypt('Secret1234'),
                'statut' => true,
            ])->assignRole('parent');

            $this->service()->nouveau($this->temoignage());
        });

        $this->assertCount(0, $this->notificationsDe('parent@c1.local'));
    }
}
