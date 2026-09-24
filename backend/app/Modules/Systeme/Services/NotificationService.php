<?php

namespace App\Modules\Systeme\Services;

use App\Models\Notification;

class NotificationService
{
    public function create(
        int $userId,
        string $titre,
        string $contenu,
        string $type,
        array $data = [],
        ?string $icone = null,
    ): Notification {

        return Notification::create([
            'user_id' => $userId,
            'titre' => $titre,
            'contenu' => $contenu,
            'type' => $type,
            'icone' => $icone,
            'data' => $data,
        ]);
    }

    public function markAsRead(
        Notification $notification
    ): Notification {

        if (!$notification->lu) {

            $notification->update([
                'lu' => true,
                'date_lecture' => now(),
            ]);
        }

        return $notification;
    }

    public function markAllAsRead(
        int $userId
    ): void {

        Notification::where('user_id', $userId)
            ->where('lu', false)
            ->update([
                'lu' => true,
                'date_lecture' => now(),
            ]);
    }

    public function delete(
        Notification $notification
    ): void {

        $notification->delete();
    }
}
