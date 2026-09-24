<?php

namespace App\Modules\Finance\Services;

use App\Models\BulletinPaie;
use App\Models\User;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Validation\ValidationException;

class BulletinPaieValidationService
{
    public function __construct(
        private NotificationDispatcher $notifier
    ) {}

    /**
     * L'enseignant marque le bulletin comme consulté.
     */
    public function consulter(BulletinPaie $bulletin): BulletinPaie
    {
        $this->verifierStatut($bulletin, ['genere', 'corrige']);

        $bulletin->update([
            'statut'            => 'consulte',
            'date_consultation' => now(),
        ]);

        $this->notifier->bulletinConsulte($bulletin);

        return $bulletin->fresh();
    }

    /**
     * L'enseignant valide son bulletin.
     */
    public function valider(BulletinPaie $bulletin): BulletinPaie
    {
        $this->verifierStatut($bulletin, ['consulte']);

        $bulletin->update([
            'statut'          => 'valide',
            'date_validation' => now(),
        ]);

        $this->notifier->bulletinValide($bulletin);

        return $bulletin->fresh();
    }

    /**
     * L'enseignant conteste son bulletin.
     */
    public function contester(
        BulletinPaie $bulletin,
        string $commentaire
    ): BulletinPaie {
        $this->verifierStatut($bulletin, ['consulte']);

        $bulletin->update([
            'statut'                 => 'conteste',
            'commentaire_enseignant' => $commentaire,
        ]);

        $this->notifier->bulletinConteste($bulletin);

        return $bulletin->fresh();
    }

    /**
     * L'admin corrige un bulletin contesté.
     */
    public function corriger(
        BulletinPaie $bulletin,
        ?string $commentaireAdmin = null
    ): BulletinPaie {
        $this->verifierStatut($bulletin, ['conteste']);

        $bulletin->update([
            'statut'    => 'corrige',
            'commentaire_enseignant' => $commentaireAdmin
                ?? $bulletin->commentaire_enseignant,
        ]);

        $this->notifier->bulletinCorrige($bulletin);

        return $bulletin->fresh();
    }

    /**
     * L'admin paie le bulletin (après validation enseignant).
     */
    public function payer(
        BulletinPaie $bulletin,
        array $data
    ): BulletinPaie {
        $this->verifierStatut($bulletin, ['valide']);

        $bulletin->update([
            'statut'             => 'verse',
            'date_paiement'      => $data['date_paiement'],
            'mode_paiement'      => $data['mode_paiement'],
            'reference_paiement' => $data['reference_paiement'] ?? null,
        ]);

        $this->notifier->bulletinVerse($bulletin);

        return $bulletin->fresh();
    }

    /**
     * Vérifie que le statut autorise l'action.
     */
    private function verifierStatut(
        BulletinPaie $bulletin,
        array $statutsAutorises
    ): void {
        if (!in_array($bulletin->statut, $statutsAutorises)) {
            throw ValidationException::withMessages([
                'statut' => "Statut actuel « {$bulletin->statut} » "
                    . "n'autorise pas cette action. "
                    . "Statuts autorisés : "
                    . implode(', ', $statutsAutorises) . ".",
            ]);
        }
    }
}
