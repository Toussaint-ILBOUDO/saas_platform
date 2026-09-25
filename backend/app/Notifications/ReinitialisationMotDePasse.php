<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email de réinitialisation de mot de passe (T3.2), lien en français.
 */
class ReinitialisationMotDePasse extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', rtrim(config('app.url'), '/').'/reinitialiser-mot-de-passe');

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour.')
            ->line('Vous recevez ce message car une réinitialisation du mot de passe a été demandée pour votre compte.')
            ->action(
                'Réinitialiser le mot de passe',
                $frontendUrl.'?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset())
            )
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message.')
            ->line('Ce lien expirera sous 60 minutes.');
    }
}