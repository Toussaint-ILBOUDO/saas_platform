<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeCours;
use App\Modules\Pedagogie\Http\Requests\StoreTypeCoursRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateTypeCoursRequest;
use App\Modules\Pedagogie\Http\Resources\TypeCoursResource;
use App\Modules\Pedagogie\Services\TypeCoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API référentiel — types de cours (T7A.2).
 *
 * Pas de `destroy` : comme sur le web, un type de cours ne se supprime pas
 * (les contrats de cours le référencent), il s'active / se désactive.
 */
class TypeCoursApiController extends Controller
{
    public function __construct(
        private readonly TypeCoursService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [];

        if ($request->filled('search')) {
            $filters['search'] = $request->string('search')->toString();
        }

        if ($request->has('actif') && $request->query('actif') !== '') {
            $filters['actif'] = $request->boolean('actif');
        }

        $typeCours = $this->service->paginate($filters, (int) $request->integer('per_page', $request->integer('par_page', 15)));

        return TypeCoursResource::collection($typeCours);
    }

    public function store(StoreTypeCoursRequest $request): JsonResponse
    {
        $typeCours = $this->service->create($request->validated());

        return TypeCoursResource::make($typeCours)
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(TypeCours $typeCours): JsonResponse
    {
        return TypeCoursResource::make($typeCours)->toResponse(request());
    }

    public function update(UpdateTypeCoursRequest $request, TypeCours $typeCours): JsonResponse
    {
        $typeCours = $this->service->update($typeCours, $request->validated());

        return TypeCoursResource::make($typeCours)->toResponse(request());
    }

    public function activer(TypeCours $typeCours): JsonResponse
    {
        $this->service->activate($typeCours);

        return TypeCoursResource::make($typeCours->fresh())->toResponse(request());
    }

    public function desactiver(TypeCours $typeCours): JsonResponse
    {
        $this->service->deactivate($typeCours);

        return TypeCoursResource::make($typeCours->fresh())->toResponse(request());
    }
}