<?php

namespace App\Modules\Users\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Users\Http\Requests\StoreParentApiRequest;
use App\Modules\Users\Http\Requests\UpdateParentApiRequest;
use App\Modules\Users\Http\Resources\ParentResource;
use App\Modules\Users\Services\ParentService;
use App\Support\Recherche;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API parents du cabinet (création directe, sans demande de cours).
 *
 * Le contrat direct existe déjà : `POST /pedagogie/contrats` porte `eleve_id`.
 * Ce qui manquait, c'était de fabriquer le parent et l'élève hors du parcours
 * « demande de cours » — d'où ces deux endpoints, adossés aux mêmes services
 * (`ParentService` / `EleveService`) que le parcours demande, pour que les
 * règles (rôle posé par le service, anti-doublon de numéro) n'existent qu'une
 * fois.
 */
class ParentApiController extends Controller
{
    public function __construct(
        private readonly ParentService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $parents = User::role('parent')
            // `enfants.user` : la Resource lit le nom/prénom de l'enfant —
            // sans lui, une page de 15 parents déclencherait 15 requêtes.
            ->with(['parentProfil', 'enfants.user', 'enfants.classe'])
            ->when($request->filled('search'), function ($query) use ($request) {
                Recherche::likeInsensible(
                    $query,
                    ['nom', 'prenom', 'email'],
                    $request->string('search')->toString()
                );
            })
            ->latest()
            ->paginate((int) $request->integer('per_page', $request->integer('par_page', 15)))
            ->withQueryString();

        return ParentResource::collection($parents);
    }

    public function store(StoreParentApiRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Anti-doublon sur les numéros, à égalité avec le parcours demande :
        // une famille qui revient ne doit pas se voir fabriquer un second parent.
        $this->service->refuserSiDejaConnu(
            $data['telephone_whatsapp'] ?? null,
            $data['telephone_appel'] ?? null
        );

        $parent = $this->service->create($data);

        return ParentResource::make($parent->load('parentProfil'))
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Fiche détaillée d'un parent : identité, profil et enfants rattachés
     * (M3 scolarité — l'écran détail/édition).
     */
    public function show(User $parent): JsonResponse
    {
        abort_unless($parent->hasRole('parent'), 404);

        return ParentResource::make(
            $parent->load(['parentProfil', 'enfants.user', 'enfants.classe'])
        )->toResponse(request());
    }

    /**
     * Modification du parent et de son profil (M3 scolarité).
     *
     * L'anti-doublon de numéro tourne avec `$sauf` = le parent lui-même :
     * garder son propre numéro n'est pas un doublon, en prendre un déjà
     * enregistré sur une autre famille si.
     */
    public function update(UpdateParentApiRequest $request, User $parent): JsonResponse
    {
        abort_unless($parent->hasRole('parent'), 404);

        $data = $request->validated();

        $this->service->refuserSiDejaConnu(
            $data['telephone_whatsapp'] ?? null,
            $data['telephone_appel'] ?? null,
            $parent->id
        );

        $parent = $this->service->update($parent, $data);

        AuditService::enregistrer(
            'parent.modifie',
            sprintf('Parent %s %s modifié', $parent->prenom, $parent->nom),
            $parent,
            ['champs' => array_keys($data)]
        );

        return ParentResource::make($parent)->toResponse(request());
    }
}
