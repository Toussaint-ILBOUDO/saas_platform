<?php

namespace App\Modules\Systeme\Services;

use App\Mail\ActualitePublishedMail;
use App\Models\Actualite;
use App\Models\BulletinPaie;
use App\Models\ContratCours;
use App\Models\DemandeCours;
use App\Models\Facture;
use App\Models\PaiementEnseignant;
use App\Models\RapportMensuelEnseignant;
use App\Models\User;
use App\Modules\Communication\Enums\ActualiteDestinataire;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class NotificationDispatcher
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    // =========================
    // ACTUALITÉS
    // =========================

    public function actualitePublished(
        Actualite $actualite,
        array $destinataires,
        string $canal
    ): void {
        $roleNames = $this->resolveRoleNames($actualite, $destinataires);

        if (empty($roleNames)) {
            return;
        }

        $users = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roleNames))
            ->get();

        foreach ($users as $user) {
            if (in_array($canal, ['interne', 'interne_email'], true)) {
                $this->notificationService->create(
                    $user->id,
                    'Nouvelle actualité',
                    '« ' . Str::limit($actualite->titre, 60) . ' » vient d\'être publiée.',
                    'actualite',
                    [
                        'actualite_id' => $actualite->id,
                        'actualite_slug' => $actualite->slug,
                    ],
                    'bi-megaphone',
                );
            }

            if (in_array($canal, ['email', 'interne_email'], true) && $user->email) {
                Mail::to($user->email)
                    ->queue(new ActualitePublishedMail($actualite));
            }
        }
    }

    /**
     * Convertit les valeurs métier (formulaire) en rôles Spatie existants.
     *
     * - valeur métier inconnue   -> journalisée puis ignorée (jamais d'exception)
     * - rôle Spatie absent en DB -> journalisé puis ignoré (évite RoleDoesNotExist)
     */
    protected function resolveRoleNames(Actualite $actualite, array $destinataires): array
    {
        $mapped = [];

        foreach ($destinataires as $value) {
            $roleName = ActualiteDestinataire::toSpatieRole((string) $value);

            if ($roleName === null) {
                Log::warning('Actualité publiée : destinataire métier inconnu ignoré.', [
                    'actualite_id' => $actualite->id,
                    'valeur' => $value,
                ]);
                continue;
            }

            $mapped[] = $roleName;
        }

        $mapped = array_values(array_unique($mapped));

        if (empty($mapped)) {
            return [];
        }

        $existing = $this->existingRoleNames($mapped);

        $missing = array_diff($mapped, $existing);

        if (! empty($missing)) {
            Log::warning('Actualité publiée : rôle(s) Spatie introuvable(s) en base, ignoré(s).', [
                'actualite_id' => $actualite->id,
                'roles_manquants' => array_values($missing),
            ]);
        }

        return $existing;
    }

    /**
     * Rôles Spatie (guard web) réellement enregistrés en base.
     * Une seule requête, mise en cache 1 heure.
     */
    protected function existingRoleNames(array $roleNames): array
    {
        sort($roleNames);

        $cacheKey = 'actualites.roles.'.md5(implode(',', $roleNames));

        return Cache::remember($cacheKey, 3600, function () use ($roleNames) {
            return Role::query()
                ->whereIn('name', $roleNames)
                ->where('guard_name', 'web')
                ->pluck('name')
                ->all();
        });
    }

    /**
     * Rôles « staff » du cabinet (D-007) : ceux que l'on notifie pour les
     * demandes de cours, commandes, rapports et bulletins.
     */
    protected function staffRoleNames(): array
    {
        return ['admin_cabinet', 'gestionnaire_librairie'];
    }

    // =========================
    // LIBRAIRIE
    // =========================

    public function newOrder(\App\Models\Commande $commande): void
    {
        $roles = Role::whereIn('name', $this->staffRoleNames())->pluck('name');

        if ($roles->isEmpty()) return;

        $admins = User::role($roles->all())->get();

        foreach ($admins as $admin) {
            $this->notificationService->create(
                $admin->id,
                'Nouvelle commande',
                'Commande ' . $commande->reference . ' de ' . $commande->nom_client . ' enregistrée.',
                'librairie_commande',
                ['commande_id' => $commande->id],
                'bi-cart-check',
            );
        }
    }

    public function commandeStatutChange(\App\Models\Commande $commande, string $nouveauStatut): void
    {
        if ($commande->user_id) {
            $estAdmin = $commande->user->hasAnyRole($this->staffRoleNames());

            $this->notificationService->create(
                $commande->user_id,
                'Commande ' . $commande->reference,
                'Votre commande ' . $commande->reference . ' est maintenant : ' . \App\Models\Commande::statutLabel($nouveauStatut) . '.',
                'librairie_commande',
                [
                    'commande_id' => $commande->id,
                    'statut' => $nouveauStatut,
                    'route_key' => $estAdmin ? 'admin' : 'client',
                ],
                'bi-cart-check',
            );
        }
    }

    // =========================
    // CONTRAT
    // =========================

    public function contractCreated(ContratCours $contrat): void
    {
        $parentId = $contrat->eleve?->parent_id;

        if (!$parentId) return;

        $this->notificationService->create(
            $parentId,
            'Nouveau contrat de cours',
            'Un contrat a été créé pour ' . $contrat->eleve->prenom . ' ' . $contrat->eleve->nom . '.',
            'contrat',
            ['contrat_id' => $contrat->id],
            'bi-file-earmark-text',
        );
    }

    public function teacherAssigned(int $enseignantUserId, ContratCours $contrat): void
    {
        $this->notificationService->create(
            $enseignantUserId,
            'Nouvelle affectation',
            'Vous avez été affecté au contrat de ' . $contrat->eleve->prenom . ' ' . $contrat->eleve->nom . '.',
            'affectation',
            ['contrat_id' => $contrat->id],
            'bi-person-check',
        );
    }

    public function parentTeacherAssigned(ContratCours $contrat): void
    {
        $parentId = $contrat->eleve?->parent_id;

        if (!$parentId) return;

        $this->notificationService->create(
            $parentId,
            'Affectation enseignant',
            'Un enseignant a été assigné au contrat de ' . $contrat->eleve->prenom . ' ' . $contrat->eleve->nom . '.',
            'affectation',
            ['contrat_id' => $contrat->id],
            'bi-person-check',
        );
    }

    // =========================
    // FACTURE
    // =========================

    public function invoiceCreated(Facture $facture): void
    {
        $this->notificationService->create(
            $facture->parent_id,
            'Nouvelle facture',
            'Une facture de ' . number_format($facture->montant_total, 0, ',', ' ') . ' FCFA est disponible.',
            'facture',
            ['facture_id' => $facture->id],
            'bi-receipt',
        );
    }

    public function invoicePaid(Facture $facture): void
    {
        $this->notificationService->create(
            $facture->parent_id,
            'Facture payée',
            'Votre facture de ' . number_format($facture->montant_total, 0, ',', ' ') . ' FCFA a été enregistrée comme payée.',
            'facture',
            ['facture_id' => $facture->id],
            'bi-receipt',
        );
    }

    // =========================
    // PAIEMENT ENSEIGNANT
    // =========================

    public function teacherPaid(PaiementEnseignant $paiement): void
    {
        $enseignantUserId = $paiement->enseignant?->user_id;

        if (!$enseignantUserId) return;

        $this->notificationService->create(
            $enseignantUserId,
            'Paiement reçu',
            'Un paiement de ' . number_format($paiement->montant_total, 0, ',', ' ') . ' FCFA a été effectué sur votre compte.',
            'paiement_enseignant',
            ['paiement_id' => $paiement->id],
            'bi-wallet2',
        );
    }

    // =========================
    // RAPPORT MENSUEL
    // =========================

    public function reportSubmitted(RapportMensuelEnseignant $rapport): void
    {
       $admins = User::role('admin_cabinet')->get();

        foreach ($admins as $admin) {
            $this->notificationService->create(
                $admin->id,
                'Nouveau rapport mensuel',
                'Un rapport mensuel a été soumis par un enseignant.',
                'rapport',
                ['rapport_id' => $rapport->id],
                'bi-clipboard-data',
            );
        }
    }

    // =========================
    // DEMANDE COURS
    // =========================

    public function courseRequestCreated(DemandeCours $demande): void
    {
       $admins = User::role('admin_cabinet')->get();

        foreach ($admins as $admin) {
            $this->notificationService->create(
                $admin->id,
                'Nouvelle demande de cours',
                'Demande de ' . $demande->prenom_parent . ' ' . $demande->nom_parent . ' pour la classe ' . $demande->classe->nom . '.',
                'demande_cours',
                ['demande_cours_id' => $demande->id],
                'bi-journal-text',
            );
        }
    }

    // =========================
    // BULLETIN DE PAIE
    // =========================

    public function bulletinGenere(BulletinPaie $bulletin): void
    {
        $enseignantUserId = $bulletin->enseignant?->user_id;

        if (!$enseignantUserId) return;

        $this->notificationService->create(
            $enseignantUserId,
            'Bulletin de paie disponible',
            'Votre bulletin pour la période « ' . $bulletin->periode->label . ' » est prêt à consulter.',
            'bulletin_paie',
            ['bulletin_paie_id' => $bulletin->id],
            'bi-cash-stack',
        );
    }

    public function bulletinConsulte(BulletinPaie $bulletin): void
    {
        $admins = User::role('admin_cabinet')->get();

        foreach ($admins as $admin) {
            $this->notificationService->create(
                $admin->id,
                'Bulletin consulté',
                'Le bulletin ' . $bulletin->numero . ' a été consulté par l\'enseignant.',
                'bulletin_paie',
                ['bulletin_paie_id' => $bulletin->id],
                'bi-cash-stack',
            );
        }
    }

    public function bulletinValide(BulletinPaie $bulletin): void
    {
        $admins = User::role('admin_cabinet')->get();

        foreach ($admins as $admin) {
            $this->notificationService->create(
                $admin->id,
                'Bulletin validé',
                'Le bulletin ' . $bulletin->numero . ' a été validé par l\'enseignant.',
                'bulletin_paie',
                ['bulletin_paie_id' => $bulletin->id],
                'bi-cash-stack',
            );
        }
    }

    public function bulletinConteste(BulletinPaie $bulletin): void
    {
        $admins = User::role('admin_cabinet')->get();

        foreach ($admins as $admin) {
            $this->notificationService->create(
                $admin->id,
                'Bulletin contesté',
                'Le bulletin ' . $bulletin->numero . ' a été contesté par l\'enseignant.',
                'bulletin_paie',
                ['bulletin_paie_id' => $bulletin->id],
                'bi-cash-stack',
            );
        }
    }

    public function bulletinCorrige(BulletinPaie $bulletin): void
    {
        $enseignantUserId = $bulletin->enseignant?->user_id;

        if (!$enseignantUserId) return;

        $this->notificationService->create(
            $enseignantUserId,
            'Bulletin corrigé',
            'Votre bulletin ' . $bulletin->numero . ' a été corrigé par l\'administration.',
            'bulletin_paie',
            ['bulletin_paie_id' => $bulletin->id],
            'bi-cash-stack',
        );
    }

    public function bulletinVerse(BulletinPaie $bulletin): void
    {
        $enseignantUserId = $bulletin->enseignant?->user_id;

        if (!$enseignantUserId) return;

        $this->notificationService->create(
            $enseignantUserId,
            'Paiement effectué',
            'Le paiement de votre bulletin ' . $bulletin->numero . ' a été effectué.',
            'bulletin_paie',
            ['bulletin_paie_id' => $bulletin->id],
            'bi-cash-stack',
        );
    }
}
