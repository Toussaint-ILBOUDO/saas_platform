<?php

namespace App\Modules\Systeme\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationResource;
use App\Models\Notification;
use App\Modules\Systeme\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notifications en base du cabinet (T3.6). Chaque utilisateur ne voit et ne
 * gère que les siennes (les autres se comportent en 404).
 */
class NotificationApiController extends Controller
{
    public function __construct(protected NotificationService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);
        $userId = $request->user()->id;

        // Les filtres portent sur les trois questions que se pose réellement
        // l'utilisateur devant une liste qui grossit : « qu'est-ce qui n'est
        // pas encore lu ? » et « qu'est-ce qui concerne les factures ? ».
        $notifications = Notification::where('user_id', $userId)
            ->when(
                $request->filled('type'),
                fn ($query, $type) => $query->where('type', (string) $type)
            )
            ->when(
                $request->has('lu') && $request->query('lu') !== '',
                fn ($query) => $query->where('lu', $request->boolean('lu'))
            )
            ->orderByDesc('id')
            ->paginate($perPage);

        // Volumétrie réelle du cabinet, pas de la page affichée : c'est ce
        // compteur qui motive à aller voir.
        $nonLues = Notification::where('user_id', $userId)
            ->where('lu', false)
            ->count();

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'non_lues' => $nonLues,
            ],
        ]);
    }

    public function marquerLue(Request $request, Notification $notification): JsonResponse
    {
        $this->verifierPropriete($request, $notification);

        $this->service->markAsRead($notification);

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    public function lireToutes(Request $request): JsonResponse
    {
        $this->service->markAllAsRead($request->user()->id);

        return response()->json(['message' => 'Toutes les notifications sont marquées comme lues.']);
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $this->verifierPropriete($request, $notification);

        $this->service->delete($notification);

        return response()->json(['message' => 'Notification supprimée.']);
    }

    private function verifierPropriete(Request $request, Notification $notification): void
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);
    }
}