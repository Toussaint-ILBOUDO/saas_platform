<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Systeme\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * `Notification::route_angular` — le chemin que l'UI Angular doit router.
 *
 * Un lien de notification n'a de valeur que s'il pointe sur un écran qui
 * existe **et** que le destinataire peut voir. Deux écueils sont verrouillés
 * ici :
 *
 *  - le même objet vit dans deux espaces (la facture : portail famille ou
 *    gestion financière ; le rapport : écran enseignant ou administration) ;
 *    la route est donc résolue à la lecture, selon `Auth::user()`,
 *  - un écran absent de l'espace Angular (`boutique/commandes`, témoignage)
 *    ne produit AUCUN lien : le routeur `**` racine renverrait autrement sur
 *    le site public.
 */
class NotificationRouteAngularTest extends TenantTestCase
{
    use InteractsWithCabinets;

    /**
     * La facture est émise au parent débiteur (`invoiceCreated` /
     * `invoicePaid` passent par `facture->parent_id`) : c'est le portail
     * famille qui doit s'ouvrir. L'administration, elle, gère depuis
     * `finance/factures`.
     */
    public function test_la_facture_vise_le_portail_famille_puis_la_gestion(): void
    {
        $s = $this->socle('nr1');

        $this->notifier('nr1', $s['parent_email'], 'facture', ['facture_id' => 12]);
        $this->notifier('nr1', $s['admin_email'], 'facture', ['facture_id' => 12]);

        $this->connecte('nr1', $s['parent_email']);
        $this->getJson('http://nr1.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.route_angular', '/espace/modules/mes-factures/12');

        $this->connecte('nr1', $s['admin_email']);
        $this->getJson('http://nr1.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.route_angular', '/espace/finance/factures/12');
    }

    /**
     * `reportSubmitted` prévient l'administration, `reportValidated` /
     * `reportRejected` l'enseignant ; le bulletin a la même dualité
     * (`bulletinGenere` côté enseignant, `bulletinConsulté` côté admin).
     * Chacun ouvre la fiche dans SON écran, jamais dans celui de l'autre.
     */
    public function test_rapport_et_bulletin_vont_chacun_vers_leur_destinataire(): void
    {
        $s = $this->socle('nr2');

        $this->notifier('nr2', $s['parent_email'], 'rapport', ['rapport_id' => 5]);
        $this->notifier('nr2', $s['enseignant_email'], 'rapport', ['rapport_id' => 5]);
        $this->notifier('nr2', $s['enseignant_email'], 'bulletin_paie', ['bulletin_paie_id' => 9]);
        $this->notifier('nr2', $s['admin_email'], 'bulletin_paie', ['bulletin_paie_id' => 9]);

        // Le parent n'a aucun écran de rapport ni de bulletin : aucun lien.
        $this->connecte('nr2', $s['parent_email']);
        $this->getJson('http://nr2.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.route_angular', null);

        $this->connecte('nr2', $s['enseignant_email']);
        $this->getJson('http://nr2.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.route_angular', '/espace/modules/mes-bulletins/9');

        // L'enseignant a deux notifications : le rapport d'abord, le bulletin ensuite.
        $this->getJson('http://nr2.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.1.route_angular', '/espace/modules/rapports-mensuels/5');

        $this->connecte('nr2', $s['admin_email']);
        $this->getJson('http://nr2.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.route_angular', '/espace/finance/bulletins-paie/9');
    }

    /**
     * La boutique Angular et l'écran témoignage n'existent pas (placeholders).
     * Le routeur `**` racine renverrait tout chemin inconnu sur le site
     * public : mieux vaut une notification sans lien.
     */
    public function test_les_ecrans_absents_ne_produisent_aucun_lien(): void
    {
        $s = $this->socle('nr3');

        $this->notifier('nr3', $s['admin_email'], 'librairie_commande', ['commande_id' => 3]);
        $this->notifier('nr3', $s['admin_email'], 'temoignage', ['temoignage_id' => 4]);
        $this->notifier('nr3', $s['admin_email'], 'temoignage_moderation', ['temoignage_id' => 4]);

        $this->connecte('nr3', $s['admin_email']);
        $this->getJson('http://nr3.localhost/api/notifications')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.route_angular', null)
            ->assertJsonPath('data.1.route_angular', null)
            ->assertJsonPath('data.2.route_angular', null);
    }

    /* ------------------------------------------------------------------ */

    private function connecte(string $slug, string $email): void
    {
        tenancy()->initialize($slug);
        User::where('email', $email)->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    /**
     * Crée une notification pour le compte indiqué. `route_angular` est un
     * accesseur résolu à la lecture : on notifie en tenant, puis on se
     * reconnecte dans le destinataire pour observer la route servie.
     */
    private function notifier(
        string $slug,
        string $email,
        string $type,
        array $data
    ): void {
        tenancy()->initialize($slug);
        $user = User::where('email', $email)->firstOrFail();

        (new NotificationService())->create(
            $user->id,
            'Notification ' . $type,
            'Contenu de la notification.',
            $type,
            $data,
            'bi-bell'
        );

        tenancy()->end();
    }

    /**
     * Un cabinet, un admin (créé par la migration du tenant), un enseignant
     * et un parent. Un seul socle couvre les destinations : c'est la même
     * notification qui doit se déplacer selon qui la lit.
     *
     * @return array<string, string>
     */
    private function socle(string $slug): array
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);

        $enseignant = User::create([
            'nom' => 'Kaboré',
            'prenom' => 'Awa',
            'email' => "awa@{$slug}.local",
            'password' => Hash::make('Secret1234'),
        ]);
        $enseignant->assignRole('enseignant');

        $parent = User::create([
            'nom' => 'Ouédraogo',
            'prenom' => 'Parent',
            'email' => "parent@{$slug}.local",
            'password' => Hash::make('Secret1234'),
        ]);
        $parent->assignRole('parent');
        $parent->parentProfil()->firstOrCreate([], []);

        $adminEmail = "admin@{$slug}.local";

        tenancy()->end();

        return [
            'admin_email' => $adminEmail,
            'enseignant_email' => $enseignant->email,
            'parent_email' => $parent->email,
        ];
    }
}
