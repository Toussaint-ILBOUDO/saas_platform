<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\StorePeriodeComptableRequest;
use App\Modules\Finance\Http\Requests\UpdatePeriodeComptableRequest;
use App\Models\PeriodeComptable;
use App\Modules\Finance\Services\PeriodeComptableService;

class PeriodeComptableController extends Controller
{
    public function __construct(
        private PeriodeComptableService $service
    ) {}

    public function index()
    {
        return $this->service->list();
    }

    public function store(StorePeriodeComptableRequest $request)
    {
        return $this->service->create($request->validated());
    }

    public function show(PeriodeComptable $periode)
    {
        return $periode;
    }

    public function update(UpdatePeriodeComptableRequest $request, PeriodeComptable $periode)
    {
        return $this->service->update($periode, $request->validated());
    }

    public function destroy(PeriodeComptable $periode)
    {
        return $this->service->delete($periode);
    }

    public function close(PeriodeComptable $periode)
    {
        return $this->service->close($periode);
    }
}