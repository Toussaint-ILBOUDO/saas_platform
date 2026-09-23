<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ObjectifPedagogique;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Modules\Pedagogie\Services\ObjectifPedagogiqueService;
use App\Modules\Pedagogie\Http\Requests\StoreObjectifPedagogiqueRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateObjectifPedagogiqueRequest;

class ObjectifPedagogiqueController extends Controller
{
    public function __construct(
        protected ObjectifPedagogiqueService $service
    ) {}

    public function index()
    {
        $objectifs = $this->service->paginateForUser(
            auth()->user(),
            request()->only(['search', 'periode'])
        );

        return view(
            'pedagogie.objectifs-pedagogiques.index',
            compact('objectifs')
        );
    }

    public function create()
    {
        $this->authorize('create', ObjectifPedagogique::class);

        $eleves = $this->service->getElevesForTeacher(auth()->user());
        $matieres = Matiere::where('actif', true)->orderBy('nom')->get();

        return view(
            'pedagogie.objectifs-pedagogiques.create',
            compact('eleves', 'matieres')
        );
    }

    public function store(StoreObjectifPedagogiqueRequest $request)
    {
        $this->authorize('create', ObjectifPedagogique::class);

        $objectif = $this->service->create($request->validated());

        return redirect()
            ->route('objectifs-pedagogiques.show', $objectif)
            ->with('success', 'Objectif pédagogique créé avec succès');
    }

    public function show(ObjectifPedagogique $objectifPedagogique)
    {
        $this->authorize('view', $objectifPedagogique);

        $objectif = $objectifPedagogique->load([
            'eleve.user',
            'eleve.classe',
            'objectifsMatieres.matiere',
        ]);

        return view(
            'pedagogie.objectifs-pedagogiques.show',
            compact('objectif')
        );
    }

    public function edit(ObjectifPedagogique $objectifPedagogique)
    {
        $this->authorize('update', $objectifPedagogique);

        $objectif = $objectifPedagogique->load([
            'eleve.user',
            'eleve.classe',
            'objectifsMatieres.matiere',
        ]);

        $matieres = Matiere::where('actif', true)->orderBy('nom')->get();

        return view(
            'pedagogie.objectifs-pedagogiques.edit',
            compact('objectif', 'matieres')
        );
    }

    public function update(
        UpdateObjectifPedagogiqueRequest $request,
        ObjectifPedagogique $objectifPedagogique
    ) {
        $this->authorize('update', $objectifPedagogique);

        $this->service->update($objectifPedagogique, $request->validated());

        return redirect()
            ->route('objectifs-pedagogiques.show', $objectifPedagogique)
            ->with('success', 'Objectif pédagogique mis à jour avec succès');
    }

    public function destroy(ObjectifPedagogique $objectifPedagogique)
    {
        $this->authorize('delete', $objectifPedagogique);

        $this->service->delete($objectifPedagogique);

        return redirect()
            ->route('objectifs-pedagogiques.index')
            ->with('success', 'Objectif pédagogique supprimé avec succès');
    }
}
