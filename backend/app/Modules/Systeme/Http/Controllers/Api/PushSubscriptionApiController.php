<?php

namespace App\Modules\Systeme\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Systeme\Http\Requests\Api\StorePushSubscriptionApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NotificationChannels\WebPush\PushSubscription;

/**
 * Abonnement push Web (T3.6) : le navigateur enregistre sa souscription PWA
 * (service worker) via l'API ; l'envoi réel utilise les clés VAPID en P4/P5.
 */
class PushSubscriptionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subscriptions = $request->user()->pushSubscriptions()
            ->get(['endpoint', 'public_key', 'auth_token', 'content_encoding', 'created_at'])
            ->map(fn (PushSubscription $sub) => [
                'endpoint' => $sub->endpoint,
                'public_key' => $sub->public_key,
                'auth_token' => $sub->auth_token,
                'content_encoding' => $sub->content_encoding,
                'created_at' => $sub->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $subscriptions]);
    }

    public function store(StorePushSubscriptionApiRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $keys = $validated['keys'] ?? [];

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $keys['public_key'] ?? null,
            $keys['auth_token'] ?? null,
            $validated['content_encoding'] ?? null,
        );

        return response()->json(['message' => 'Abonnement push enregistré.'], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:1024'],
        ]);

        $request->user()->deletePushSubscription($validated['endpoint']);

        return response()->json(['message' => 'Abonnement push retiré.']);
    }
}