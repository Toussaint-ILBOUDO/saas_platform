<?php

namespace App\Modules\Systeme\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Modules\Systeme\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $service
    ) {}

    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view(
            'panel.notifications.index',
            compact('notifications')
        );
    }

    public function show(
        Request $request,
        Notification $notification
    ) {

        abort_if(
            $notification->user_id !== $request->user()->id,
            403
        );

        $this->service->markAsRead($notification);

        $data = $notification->data ?? [];

        return match ($notification->type) {

            'contrat',
            'affectation' => redirect()->route(
                'contrats.show',
                $data['contrat_id'] ?? 0
            ),

            'facture' => redirect()->route(
                'finance.factures.show',
                $data['facture_id'] ?? 0
            ),

            'librairie_commande' => redirect()->route(
                ($data['route_key'] ?? 'admin') === 'client'
                    ? 'librairie.mes-commandes.show'
                    : 'admin.librairie.commandes.show',
                $data['commande_id'] ?? 0
            ),

            'actualite' => redirect()->route(
                'actualites.show',
                $data['actualite_slug'] ?? $data['actualite_id'] ?? ''
            ),

            'rapport' => redirect()->route(
                'rapports-mensuels.show',
                $data['rapport_id'] ?? 0
            ),

            'demande_cours' => redirect()->route(
                'demande-cours.show',
                $data['demande_cours_id'] ?? 0
            ),

            'temoignage' => redirect()->route(
                'admin.temoignages.show',
                $data['temoignage_id'] ?? 0
            ),

            'temoignage_moderation' => redirect()->route(
                'temoignages.mes.index'
            ),

            'temoignage_signalement' => redirect()->route(
                'admin.temoignages.signalements'
            ),

            'temoignage_commentaire' => redirect()->route(
                'temoignages.show',
                $data['slug'] ?? ''
            ),

            default => redirect()->route(
                'notifications.index'
            )
        };
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => Notification::query()
                ->where('user_id', $request->user()->id)
                ->where('lu', false)
                ->count()
        ]);
    }

    public function markAsRead(
        Request $request,
        Notification $notification
    ) {

        abort_if(
            $notification->user_id !== $request->user()->id,
            403
        );

        $this->service->markAsRead($notification);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Notification marquée comme lue.']);
        }

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function markAllAsRead(
        Request $request
    ) {

        $this->service->markAllAsRead(
            $request->user()->id
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Toutes les notifications ont été lues.']);
        }

        return back()->with('success', 'Toutes les notifications ont été lues.');
    }

    public function destroy(
        Request $request,
        Notification $notification
    ) {

        abort_if(
            $notification->user_id !== $request->user()->id,
            403
        );

        $this->service->delete(
            $notification
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Notification supprimée.']);
        }

        return back()->with('success', 'Notification supprimée.');
    }
}
