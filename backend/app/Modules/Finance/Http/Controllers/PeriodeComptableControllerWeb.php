<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PeriodeComptable;
use App\Modules\Finance\Http\Requests\StorePeriodeComptableRequest;
use App\Modules\Finance\Http\Requests\UpdatePeriodeComptableRequest;
use App\Modules\Finance\Services\PeriodeComptableService;

class PeriodeComptableControllerWeb extends Controller
{
    public function __construct(
        private readonly PeriodeComptableService $service
    ) {}

    public function index()
    {
        $periodes = $this->service->list();

        return view('pedagogie.finance.periodes.index', compact('periodes'));
    }

    public function create()
    {
        return view('pedagogie.finance.periodes.create');
    }

    public function store(StorePeriodeComptableRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('finance.periodes.index')
            ->with('success', 'La période comptable a été créée avec succès.');
    }

    public function show(PeriodeComptable $periode)
    {
        return view('pedagogie.finance.periodes.show', compact('periode'));
    }

    public function edit(PeriodeComptable $periode)
    {
        return view('pedagogie.finance.periodes.edit', compact('periode'));
    }

    public function update(UpdatePeriodeComptableRequest $request, PeriodeComptable $periode)
    {
        $this->service->update($periode, $request->validated());

        return redirect()
            ->route('finance.periodes.index')
            ->with('success', 'La période comptable a été mise à jour.');
    }

    public function close(PeriodeComptable $periode)
    {
        $this->service->close($periode);

        return redirect()
            ->route('finance.periodes.index')
            ->with('success', 'La période comptable a été clôturée.');
    }

    /**
     * Réouverture après une clôture erronée (D-051).
     */
    public function reopen(PeriodeComptable $periode)
    {
        $this->service->reopen($periode);

        return redirect()
            ->route('finance.periodes.index')
            ->with('success', 'La période comptable a été rouverte.');
    }
}