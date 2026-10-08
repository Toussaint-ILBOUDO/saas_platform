<?php

namespace App\Modules\Users\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Recherche;
use App\Modules\Users\Http\Requests\StoreEnseignantRequest;
use App\Modules\Users\Http\Requests\UpdateEnseignantRequest;
use App\Modules\Users\Http\Resources\EnseignantResource;
use App\Modules\Users\Services\EnseignantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API référentiel — enseignants (T7A.2).
 *
 * L'enseignant est un `User` + `EnseignantProfil` : on ne supprime jamais un
 * compte (il est porté par des rapports, bulletins et contrats), donc pas de
 * `destroy`. Le rôle « enseignant » est posé par le service, jamais par la
 * requête (sinon un admin pourrait promouvoir un parent en enseignant).
 */
class EnseignantApiController extends Controller
{
    public function __construct(
        private readonly EnseignantService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $enseignants = User::role('enseignant')
            ->with('enseignantProfil.matieres')
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

        return EnseignantResource::collection($enseignants);
    }

    public function store(StoreEnseignantRequest $request): JsonResponse
    {
        $enseignant = $this->service->create($request->validated());

        return EnseignantResource::make($enseignant)
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $enseignant): JsonResponse
    {
        $user = User::role('enseignant')
            ->with('enseignantProfil.matieres')
            ->findOrFail($enseignant);

        return EnseignantResource::make($user)->toResponse(request());
    }

    public function update(UpdateEnseignantRequest $request, int $enseignant): JsonResponse
    {
        $user = $this->service->update($enseignant, $request->validated());

        return EnseignantResource::make($user)->toResponse(request());
    }
}