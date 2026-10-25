<?php

namespace App\Modules\Users\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\User;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Users\Http\Requests\ActiverEleveApiRequest;
use App\Modules\Users\Http\Requests\StoreEleveApiRequest;
use App\Modules\Users\Http\Requests\UpdateEleveAdminApiRequest;
use App\Modules\Users\Http\Resources\EleveDetailResource;
use App\Modules\Users\Http\Resources\EleveResource;
use App\Modules\Users\Services\EleveService;
use App\Support\Recherche;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * API élèves du cabinet (création directe, sans demande de cours).
 *
 * `parent_id` est obligatoire dans la requête : un élève se rattache à un
 * parent du cabinet. Le contrôle du rôle « parent » du compte choisi ne peut
 * pas passer par un `exists:users,id` (la table pivot de Spatie y est
 * invisible) — il est fait ici, à l'identique de
 * `DemandeCoursAdminService::creerEleve`.
 */
class EleveApiController extends Controller
{
    public function __construct(
        private readonly EleveService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $eleves = Eleve::query()
            ->with(['user', 'classe', 'parent'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('user', function ($sous) use ($request) {
                    Recherche::likeInsensible(
                        $sous,
                        ['nom', 'prenom', 'email'],
                        $request->string('search')->toString()
                    );
                });
            })
            ->when($request->filled('parent_id'), function ($query) use ($request) {
                $query->where('parent_id', $request->integer('parent_id'));
            })
            ->latest()
            ->paginate((int) $request->integer('per_page', $request->integer('par_page', 15)))
            ->withQueryString();

        return EleveResource::collection($eleves);
    }

    public function store(StoreEleveApiRequest $request): JsonResponse
    {
        $data = $request->validated();

        $parent = User::role('parent')->find($data['parent_id']);

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => 'Le compte rattaché n\'a pas le rôle parent.',
            ]);
        }

        $user = $this->service->create([
            ...$data,
            // Repli sur le nom du parent, à l'égal du parcours demande : le
            // formulaire n'impose pas le nom de l'enfant, seulement son prénom.
            'nom' => $data['nom'] ?? $parent->nom,
            'parent_id' => $parent->id,
        ]);

        return EleveResource::make($user->load('eleve.classe', 'eleve.parent')->eleve)
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Fiche détaillée d'un élève (M3 scolarité) : fiche complète +
     * rattachements + état du compte. Ce que la liste n'expose pas, pour
     * que les écrans qui n'en ont pas ne paient pas le prix.
     */
    public function show(Eleve $eleve): JsonResponse
    {
        return EleveDetailResource::make(
            $eleve->load(['user', 'classe', 'parent'])
        )->toResponse(request());
    }

    /**
     * Modification complète par l'admin (M3 scolarité) : rattachements,
     * fiche scolaire et identité du compte.
     *
     * Le contrôle du rôle « parent » du compte ciblé ne peut pas passer par
     * un `exists:users,id` (la table pivot de Spatie y est invisible) — il
     * est fait ici, à l'identique du `store` : rattachement un élève à un
     * compte qui n'est pas un parent ouvrirait ses contrats à un tiers.
     */
    public function update(UpdateEleveAdminApiRequest $request, Eleve $eleve): JsonResponse
    {
        $data = $request->validated();

        if (! User::role('parent')->whereKey($data['parent_id'])->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Le compte rattaché n\'a pas le rôle parent.',
            ]);
        }

        $eleve = $this->service->update($eleve, $data);

        AuditService::enregistrer(
            'eleve.modifie',
            sprintf('Élève %s %s modifié', $eleve->user?->prenom, $eleve->user?->nom),
            $eleve,
            [
                'parent_id' => $data['parent_id'],
                'classe_id' => $data['classe_id'],
            ]
        );

        return EleveDetailResource::make($eleve)->toResponse(request());
    }

    /**
     * Activation du compte élève (M3 scolarité) : double flag
     * `eleves.statut` + `users.statut`, mot de passe optionnel.
     */
    public function activer(ActiverEleveApiRequest $request, Eleve $eleve): JsonResponse
    {
        $this->service->activateAccount($eleve, $request->validated());

        AuditService::enregistrer(
            'eleve.active',
            sprintf('Compte de l\'élève %s %s activé', $eleve->user?->prenom, $eleve->user?->nom),
            $eleve
        );

        return EleveDetailResource::make(
            $eleve->fresh(['user', 'classe', 'parent'])
        )->toResponse(request());
    }

    /**
     * Suspension du compte élève (M3 scolarité) : double flag également —
     * un seul des deux laisserait l'état du compte dépendre de qui l'a
     * touché en dernier. Rien n'est supprimé (contrats, cahiers, factures).
     */
    public function suspendre(Eleve $eleve): JsonResponse
    {
        $this->service->suspendreCompte($eleve);

        AuditService::enregistrer(
            'eleve.suspendu',
            sprintf('Compte de l\'élève %s %s suspendu', $eleve->user?->prenom, $eleve->user?->nom),
            $eleve,
            [],
            'warning'
        );

        return EleveDetailResource::make(
            $eleve->fresh(['user', 'classe', 'parent'])
        )->toResponse(request());
    }
}
