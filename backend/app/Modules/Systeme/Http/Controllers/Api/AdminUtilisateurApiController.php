<?php

namespace App\Modules\Systeme\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UtilisateurAdminResource;
use App\Support\Recherche;
use App\Models\EvenementFacturable;
use App\Models\User;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Plateforme\Services\JournalFacturable;
use App\Modules\Systeme\Http\Requests\Api\StoreUtilisateurApiRequest;
use App\Modules\Systeme\Http\Requests\Api\UpdateUtilisateurApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gestion des utilisateurs du cabinet (backoffice MVP T3.5) : liste, création,
 * modification, rôles, activation/suspension.
 */
class AdminUtilisateurApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $users = User::query()
            ->with(['roles', 'eleve.classe'])
            ->when((string) $request->input('search', '') !== '', function ($query) use ($request) {
                Recherche::likeInsensible(
                    $query,
                    ['nom', 'prenom', 'email'],
                    (string) $request->input('search')
                );
            })
            ->when((string) $request->input('role', '') !== '', function ($query) use ($request) {
                $query->role((string) $request->input('role'));
            })
            ->when($request->has('statut'), function ($query) use ($request) {
                $query->where('statut', $request->boolean('statut'));
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => UtilisateurAdminResource::collection($users),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function store(StoreUtilisateurApiRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'] ?? '',
            'email' => $validated['email'],
            'telephone_whatsapp' => $validated['telephone_whatsapp'] ?? null,
            'telephone_appel' => $validated['telephone_appel'] ?? null,
            'password' => $validated['password'] ?? Str::random(16),
            'statut' => $validated['statut'] ?? true,
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json([
            'message' => 'Utilisateur créé.',
            'data' => new UtilisateurAdminResource($user->load('roles')),
        ], 201);
    }

    public function show(User $utilisateur): JsonResponse
    {
        return response()->json(['data' => new UtilisateurAdminResource($utilisateur->load('roles'))]);
    }

    public function update(UpdateUtilisateurApiRequest $request, User $utilisateur): JsonResponse
    {
        $validated = $request->validated();

        $utilisateur->update([
            'nom' => $validated['nom'] ?? $utilisateur->nom,
            'prenom' => $validated['prenom'] ?? $utilisateur->prenom,
            'email' => $validated['email'] ?? $utilisateur->email,
            'telephone_whatsapp' => $validated['telephone_whatsapp'] ?? $utilisateur->telephone_whatsapp,
            'telephone_appel' => $validated['telephone_appel'] ?? $utilisateur->telephone_appel,
            'statut' => array_key_exists('statut', $validated) ? $validated['statut'] : $utilisateur->statut,
        ]);

        if (isset($validated['roles'])) {
            $utilisateur->syncRoles($validated['roles']);
        }

        if (! empty($validated['password'])) {
            $utilisateur->update(['password' => $validated['password']]);
        }

        return response()->json([
            'message' => 'Utilisateur mis à jour.',
            'data' => new UtilisateurAdminResource($utilisateur->fresh('roles')),
        ]);
    }

    public function suspendre(User $utilisateur): JsonResponse
    {
        $utilisateur->update(['statut' => false]);

        AuditService::enregistrer(
            'utilisateur.suspendu',
            'Utilisateur ' . $utilisateur->email . ' suspendu',
            $utilisateur,
            [],
            'warning'
        );

        $this->capturerEvenement($utilisateur, true);

        return response()->json([
            'message' => 'Utilisateur suspendu.',
            'data' => new UtilisateurAdminResource($utilisateur->fresh('roles')),
        ]);
    }

    public function activer(User $utilisateur): JsonResponse
    {
        $utilisateur->update(['statut' => true]);

        AuditService::enregistrer(
            'utilisateur.active',
            'Utilisateur ' . $utilisateur->email . ' réactivé',
            $utilisateur
        );

        $this->capturerEvenement($utilisateur, false);

        return response()->json([
            'message' => 'Utilisateur activé.',
            'data' => new UtilisateurAdminResource($utilisateur->fresh('roles')),
        ]);
    }

    /**
     * P8 : journalisation facturable — activation/suspension d'un compte
     * parent ou enseignant (les élèves passent par EleveService).
     */
    private function capturerEvenement(User $utilisateur, bool $suspension): void
    {
        $role = $utilisateur->roles->pluck('name')->first(
            fn ($nom) => in_array($nom, ['parent', 'enseignant'], true)
        );

        if ($role === null) {
            return;
        }

        $type = match ([$role, $suspension]) {
            ['parent', false] => EvenementFacturable::TYPE_COMPTE_PARENT_ACTIVE,
            ['parent', true] => EvenementFacturable::TYPE_COMPTE_PARENT_SUSPENDU,
            ['enseignant', false] => EvenementFacturable::TYPE_COMPTE_ENSEIGNANT_ACTIVE,
            ['enseignant', true] => EvenementFacturable::TYPE_COMPTE_ENSEIGNANT_SUSPENDU,
            default => null,
        };

        if ($type === null) {
            return;
        }

        JournalFacturable::capturer(
            $type,
            'user',
            $utilisateur->id,
            'Compte '.$role.($suspension ? ' suspendu' : ' activé'),
        );
    }
}