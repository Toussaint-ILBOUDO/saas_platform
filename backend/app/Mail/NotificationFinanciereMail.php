<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/**
 * Canal mail des notifications financières (T7A.10, §8 CONCEPTION_FINANCE).
 *
 * Un seul mailable pour tout le cycle financier — facture créée, règlement,
 * rapport validé/rejeté, bulletin versé — car le message sert la
 * notification déjà connue du destinataire : le mail reprend **le même
 * sujet et le même texte** que la notification d'espace, plus un lien
 * direct vers l'écran concerné.
 *
 * Aucun modèle Eloquent n'est porté par le mailable (des scalaires
 * uniquement) : le job de file est traité hors request, et un modèle
 * sérialisé rechargerait malgré le `QueueTenancyBootstrapper`. Le lien est
 * absolu, construit pendant la request sur le domaine du cabinet.
 */
class NotificationFinanciereMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $sujet,
        public string $contenu,
        public ?string $url = null,
        public string $cabinet = 'K\'Educ',
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->sujet)
            ->view('emails.notification-financiere');
    }
}
