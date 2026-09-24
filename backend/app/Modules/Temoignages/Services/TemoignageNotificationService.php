<?php

namespace App\Modules\Temoignages\Services;

use App\Models\Temoignage;
use App\Models\TemoignageCommentaire;
use App\Models\TemoignageSignalement;
use App\Models\User;
use App\Modules\Systeme\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TemoignageNotificationService
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    public function nouveau(Temoignage $temoignage): void
    {
        foreach ($this->admins() as $admin) {
            $this->notifications->create(
                $admin->id,
                'Nouveau témoignage publié',
                'Un témoignage de « ' . $temoignage->prenom . ' ' . $temoignage->nom . ' » vient d\'être publié.',
                'temoignage',
                ['temoignage_id' => $temoignage->id],
                'bi-chat-quote',
            );
        }
    }

    public function masque(Temoignage $temoignage): void
    {
        $this->notifications->create(
            $temoignage->user_id,
            'Votre témoignage a été masqué',
            'Votre témoignage a été masqué par la modération car il ne respecte pas les règles de publication.',
            'temoignage_moderation',
            ['temoignage_id' => $temoignage->id],
            'bi-shield-exclamation',
        );
    }

    public function supprime(Temoignage $temoignage): void
    {
        $this->notifications->create(
            $temoignage->user_id,
            'Votre témoignage a été supprimé',
            'Votre témoignage a été supprimé par la modération.',
            'temoignage_moderation',
            ['temoignage_id' => $temoignage->id],
            'bi-shield-exclamation',
        );
    }

    public function signale(TemoignageSignalement $signalement): void
    {
        foreach ($this->admins() as $admin) {
            $this->notifications->create(
                $admin->id,
                'Témoignage signalé',
                'Un témoignage a été signalé : « ' . Str::limit($signalement->motif, 60) . ' ».',
                'temoignage_signalement',
                ['signalement_id' => $signalement->id],
                'bi-flag',
            );
        }
    }

    public function commentaire(Temoignage $temoignage, TemoignageCommentaire $commentaire): void
    {
        if ($temoignage->user_id === $commentaire->user_id) {
            return;
        }

        $this->notifications->create(
            $temoignage->user_id,
            'Nouveau commentaire sur votre témoignage',
            Str::limit($commentaire->contenu, 90),
            'temoignage_commentaire',
            ['slug' => $temoignage->slug],
            'bi-chat-dots',
        );
    }

    protected function admins(): Collection
    {
        return User::role(['admin', 'super-admin'])->get();
    }
}
