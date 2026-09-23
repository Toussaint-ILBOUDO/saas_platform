<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TypeAjustement;
use App\Modules\Finance\Http\Requests\StoreTypeAjustementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TypeAjustementController extends Controller
{
    public function index(): View
    {
        $types = TypeAjustement::query()
            ->orderBy('direction')
            ->orderBy('libelle')
            ->get();

        return view('finances.type-ajustements.index', compact('types'));
    }

    public function create(): View
    {
        return view('finances.type-ajustements.create');
    }

    public function store(StoreTypeAjustementRequest $request): RedirectResponse
    {
        TypeAjustement::create($request->validated());

        return redirect()
            ->route('finance.type-ajustements.index')
            ->with('success', 'Type d\'ajustement créé avec succès.');
    }

    public function edit(TypeAjustement $typeAjustement): View
    {
        return view('finances.type-ajustements.edit', [
            'type' => $typeAjustement,
        ]);
    }

    public function update(
        StoreTypeAjustementRequest $request,
        TypeAjustement $typeAjustement
    ): RedirectResponse {
        $typeAjustement->update($request->validated());

        return redirect()
            ->route('finance.type-ajustements.index')
            ->with('success', 'Type d\'ajustement mis à jour.');
    }

    public function destroy(TypeAjustement $typeAjustement): RedirectResponse
    {
        $typeAjustement->delete();

        return redirect()
            ->route('finance.type-ajustements.index')
            ->with('success', 'Type d\'ajustement supprimé.');
    }
}
