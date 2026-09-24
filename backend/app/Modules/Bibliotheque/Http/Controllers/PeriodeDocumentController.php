<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PeriodeDocument;
use App\Modules\Bibliotheque\Http\Requests\StorePeriodeDocumentRequest;
use App\Modules\Bibliotheque\Http\Requests\UpdatePeriodeDocumentRequest;

class PeriodeDocumentController extends Controller
{
    public function index()
    {
        $periodes = PeriodeDocument::orderBy('nom')->paginate(15);

        return view('bibliotheque.periodes.index', compact('periodes'));
    }

    public function create()
    {
        return view('bibliotheque.periodes.create');
    }

    public function store(StorePeriodeDocumentRequest $request)
    {
        PeriodeDocument::create($request->validated());

        return redirect()
            ->route('admin.bibliotheque.periodes.index')
            ->with('success', 'Période créée avec succès.');
    }

    public function edit(PeriodeDocument $periode)
    {
        return view('bibliotheque.periodes.edit', compact('periode'));
    }

    public function update(UpdatePeriodeDocumentRequest $request, PeriodeDocument $periode)
    {
        $periode->update($request->validated());

        return redirect()
            ->route('admin.bibliotheque.periodes.index')
            ->with('success', 'Période modifiée avec succès.');
    }

    public function destroy(PeriodeDocument $periode)
    {
        $periode->delete();

        return redirect()
            ->route('admin.bibliotheque.periodes.index')
            ->with('success', 'Période supprimée.');
    }
}
