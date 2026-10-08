<?php

namespace App\Modules\Finance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeriodeComptable;
use App\Modules\Finance\Http\Requests\StorePeriodeComptableRequest;
use App\Modules\Finance\Http\Requests\UpdatePeriodeComptableRequest;
use App\Modules\Finance\Http\Resources\PeriodeComptableResource;
use App\Modules\Finance\Services\PeriodeComptableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

class PeriodeComptableApiController extends Controller
{
    public function __construct(
        private readonly PeriodeComptableService $service
    ) {}

    public function index(): ResourceCollection
    {
        $periodes = $this->service->list();

        return PeriodeComptableResource::collection($periodes);
    }

    public function store(StorePeriodeComptableRequest $request): JsonResponse
    {
        $periode = $this->service->create($request->validated());

        return PeriodeComptableResource::make($periode)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(PeriodeComptable $periode): JsonResponse
    {
        return PeriodeComptableResource::make($periode)->toResponse(request());
    }

    public function update(
        UpdatePeriodeComptableRequest $request,
        PeriodeComptable $periode
    ): JsonResponse {
        $periode = $this->service->update($periode, $request->validated());

        return PeriodeComptableResource::make($periode)->toResponse(request());
    }

    public function close(PeriodeComptable $periode): JsonResponse
    {
        $periode = $this->service->close($periode);

        return PeriodeComptableResource::make($periode->fresh(['clotureur']))->toResponse(request());
    }

    /**
     * Réouverture après une clôture erronée (D-051).
     *
     * Le service refuse la réouverture si des écritures financières existent
     * (factures ou bulletins) — c'est la règle d'invariance la plus importante.
     */
    public function reopen(PeriodeComptable $periode): JsonResponse
    {
        $periode = $this->service->reopen($periode);

        return PeriodeComptableResource::make($periode)->toResponse(request());
    }
}