<?php

namespace App\Modules\Finance\Services;

use App\Models\BulletinPaie;
use App\Models\User;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Support\Facades\Auth;
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
     * L'enseignant conteste son bulletin (D-052).
     *
     * Le motif est structuré : une catégorie tirée d'une liste fermée, plus un
     * détail libre. La catégorie permet à l'administration de voir d'où
     * viennent les contestations récurrentes (taux erroné, séance non
     * enregistrée, retenue contestée…) au lieu de n'avoir que des textes
     * libres à qualifier à la main.
     */
    public function contester(
        BulletinPaie $bulletin,
        string $motif,
        string $commentaire
    ): BulletinPaie {

        $this->verifierStatut($bulletin, ['consulte']);

        $this->validerMotifContestation($motif, $commentaire);

        $bulletin->update([
            'statut'                 => 'conteste',
            'motif_contestation'     => $motif,
            'commentaire_enseignant' => $commentaire,
        ]);

        $this->notifier->bulletinConteste($bulletin);

        return $bulletin->fresh();
    }

    /**
     * D-052 — L'enseignant confirme avoir reçu son paiement.
     *
     * Le versement se fait hors plateforme : sans cette confirmation, rien
     * n'atteste que l'argent a effectivement atteint l'enseignant. Elle clôt
     * le cycle et prévient l'administration, qui peut ainsi relancer les
     * versements restés « payés mais non réceptionnés ».
     */
    public function confirmerReception(BulletinPaie $bulletin): BulletinPaie
    {
        $this->verifierStatut($bulletin, ['verse']);

        if ($bulletin->date_reception !== null) {
            throw ValidationException::withMessages([
                'bulletin' => 'La réception de ce paiement est déjà confirmée.',
            ]);
        }

        $bulletin->update([
            'date_reception' => now(),
            'recu_par'       => Auth::id(),
        ]);

        $this->notifier->bulletinRecu($bulletin);

        return $bulletin->fresh();
    }

    /**
     * Contrôle du couple catégorie / détail d'une contestation.
     */
    private function validerMotifContestation(
        string $motif,
        string $commentaire
    ): void {
        $erreurs = [];

        if (! array_key_exists($motif, BulletinPaie::MOTIFS_CONTESTATION)) {
            $erreurs['motif_contestation'] = 'Motif de contestation inconnu.';
        }

        if (mb_strlen(trim($commentaire)) < 20) {
            $erreurs['commentaire_enseignant'] =
                'Décrivez la contestation en 20 caractères au minimum : '
                . 'l\'administration doit comprendre ce qui est contesté.';
        }

        if ($erreurs !== []) {
            throw ValidationException::withMessages($erreurs);
        }
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

            // Un nouveau versement repart d'une réception à zéro : les colonnes
            // sont vidées pour ne jamais dater la confirmation d'un ancien
            // paiement.
            'date_reception'     => null,
            'recu_par'           => null,
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
