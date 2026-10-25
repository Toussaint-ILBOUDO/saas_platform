<?php

namespace Tests\Feature\Api;

use App\Mail\NotificationFinanciereMail;
use App\Models\BulletinPaie;
use App\Models\EnseignantProfil;
use App\Models\Facture;
use App\Models\Notification;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Models\User;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Canal mail des événements financiers (T7A.10, §8 CONCEPTION_FINANCE).
 *
 * Les cinq déclencheurs du cycle — facture créée, règlement, rapport
 * validé, rapport rejeté, bulletin versé — notifient **deux fois** le même
 * destinataire avec le même message : la notification d'espace, et le mail
 * en file. Les invariants verrouillés ici :
 *
 *  - le lien du mail vise l'écran du **destinataire** (portail famille pour
 *    la facture du parent, écran enseignant pour le rapport/le bulletin),
 *    sur le **domaine du cabinet** — la request en cours porte ce domaine
 *    au moment du dispatch ;
 *  - la notification d'espace n'est jamais détournée : le mail est un
 *    canal de plus, pas un remplaçant ;
 *  - un destinataire sans adresse électronique reste muet côté mail sans
 *    priver personne de sa notification d'espace.
 */
class NotificationMailFinancierTest extends TenantTestCase
{
    use InteractsWithCabinets;

    /**
     * Facture créée puis réglée : le parent reçoit deux mails sur le portail
     * famille, et garde ses deux notifications d'espace.
     */
    public function test_facture_creee_et_payee_notifient_le_parent_par_mail_et_en_base(): void
    {
        Mail::fake();
        $s = $this->socle('mf1');

        // Pose la request du cabinet : son domaine préfixe les liens du mail,
        // comme en production.
        $this->getJson("http://mf1.localhost/api/notifications")->assertUnauthorized();

        $facture = (new Facture())->forceFill([
            'id' => 7,
            'parent_id' => $s['parent_id'],
            'montant_total' => 25000,
        ]);

        tenancy()->initialize('mf1');
        $dispatcher = app(NotificationDispatcher::class);
        $dispatcher->invoiceCreated($facture);
        $dispatcher->invoicePaid($facture);

        Mail::assertQueuedCount(2);
        Mail::assertQueued(
            NotificationFinanciereMail::class,
            fn (NotificationFinanciereMail $mail) => $mail->hasTo($s['parent_email'])
                && $mail->sujet === 'Nouvelle facture'
                && str_contains((string) $mail->url, 'http://mf1.localhost/espace/modules/mes-factures/7')
        );
        Mail::assertQueued(
            NotificationFinanciereMail::class,
            fn (NotificationFinanciereMail $mail) => $mail->hasTo($s['parent_email'])
                && $mail->sujet === 'Facture payée'
                && str_contains((string) $mail->url, 'http://mf1.localhost/espace/modules/mes-factures/7')
        );

        $this->assertSame(2, Notification::where('user_id', $s['parent_id'])->count());
    }

    /**
     * Rapport validé / rejeté : l'enseignant reçoit les deux mails sur SON
     * écran — jamais sur celui de l'administration.
     */
    public function test_rapport_valide_et_rejete_notifient_l_enseignant_par_mail(): void
    {
        Mail::fake();
        $s = $this->socle('mf2');

        $this->getJson("http://mf2.localhost/api/notifications")->assertUnauthorized();

        $rapport = (new RapportMensuelEnseignant())->forceFill(['id' => 11]);
        $rapport->setRelation(
            'enseignant',
            (new EnseignantProfil())->forceFill(['user_id' => $s['enseignant_id']])
        );
        $rapport->setRelation(
            'periode',
            (new PeriodeComptable())->forceFill(['label' => 'Octobre 2026'])
        );

        tenancy()->initialize('mf2');
        $dispatcher = app(NotificationDispatcher::class);
        $dispatcher->reportValidated($rapport);
        $dispatcher->reportRejected($rapport, 'Volume horaire incomplet.');

        Mail::assertQueuedCount(2);
        Mail::assertQueued(
            NotificationFinanciereMail::class,
            fn (NotificationFinanciereMail $mail) => $mail->hasTo($s['enseignant_email'])
                && $mail->sujet === 'Rapport mensuel validé'
                && str_contains((string) $mail->url, 'http://mf2.localhost/espace/modules/rapports-mensuels/11')
        );
        Mail::assertQueued(
            NotificationFinanciereMail::class,
            fn (NotificationFinanciereMail $mail) => $mail->hasTo($s['enseignant_email'])
                && $mail->sujet === 'Rapport mensuel rejeté'
                && str_contains($mail->contenu, 'Volume horaire incomplet.')
        );

        $this->assertSame(2, Notification::where('user_id', $s['enseignant_id'])->count());
    }

    /**
     * Bulletin versé (D-052) : le mail suit l'enseignant sur « mes
     * bulletins », comme la notification d'espace.
     */
    public function test_bulletin_verse_notifie_l_enseignant_par_mail(): void
    {
        Mail::fake();
        $s = $this->socle('mf3');

        $this->getJson("http://mf3.localhost/api/notifications")->assertUnauthorized();

        $bulletin = (new BulletinPaie())->forceFill(['id' => 9, 'numero' => 'BP-0001']);
        $bulletin->setRelation(
            'enseignant',
            (new EnseignantProfil())->forceFill(['user_id' => $s['enseignant_id']])
        );
        $bulletin->setRelation(
            'periode',
            (new PeriodeComptable())->forceFill(['label' => 'Octobre 2026'])
        );

        tenancy()->initialize('mf3');
        app(NotificationDispatcher::class)->bulletinVerse($bulletin);

        Mail::assertQueued(
            NotificationFinanciereMail::class,
            fn (NotificationFinanciereMail $mail) => $mail->hasTo($s['enseignant_email'])
                && $mail->sujet === 'Paiement effectué'
                && str_contains((string) $mail->url, 'http://mf3.localhost/espace/modules/mes-bulletins/9')
        );
        $this->assertSame(1, Notification::where('user_id', $s['enseignant_id'])->count());
    }

    /**
     * `users.email` est nullable : un destinataire sans adresse reçoit sa
     * notification d'espace, et le mail est simplement tu — jamais l'inverse.
     */
    public function test_un_destinataire_sans_email_garde_sa_notification_d_espace(): void
    {
        Mail::fake();
        $s = $this->socle('mf4');

        tenancy()->initialize('mf4');
        User::where('id', $s['parent_id'])->update(['email' => null]);

        $facture = (new Facture())->forceFill([
            'id' => 12,
            'parent_id' => $s['parent_id'],
            'montant_total' => 18000,
        ]);
        app(NotificationDispatcher::class)->invoiceCreated($facture);

        Mail::assertNothingQueued();
        $this->assertSame(1, Notification::where('user_id', $s['parent_id'])->count());
    }

    /**
     * Un cabinet, un enseignant et un parent — les deux destinataires du
     * cycle financier. L'admin est créé par la migration du tenant.
     *
     * @return array<string, int|string>
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

        $resultat = [
            'enseignant_id' => $enseignant->id,
            'enseignant_email' => $enseignant->email,
            'parent_id' => $parent->id,
            'parent_email' => $parent->email,
        ];

        tenancy()->end();

        return $resultat;
    }
}
