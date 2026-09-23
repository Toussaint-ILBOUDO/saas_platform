<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Models\Matiere;

use App\Modules\Pedagogie\Services\MatiereService;

use App\Modules\Pedagogie\Http\Requests\StoreMatiereRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateMatiereRequest;

class MatiereController
{
    public function __construct(
        protected MatiereService $service
    ) {
    }

    public function index()
    {
        $matieres = $this->service->paginate();

        return view(
            'pedagogie.matieres.index',
            compact('matieres')
        );
    }

    public function create()
    {
        return view(
            'pedagogie.matieres.create'
        );
    }

    public function store(
        StoreMatiereRequest $request
    ) {
        $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('matieres.index')
            ->with(
                'success',
                'Matière créée avec succès.'
            );
    }

    public function show(
        Matiere $matiere
    ) {
        return view(
            'pedagogie.matieres.show',
            compact('matiere')
        );
    }

    public function edit(
        Matiere $matiere
    ) {
        return view(
            'pedagogie.matieres.edit',
            compact('matiere')
        );
    }

    public function update(
        UpdateMatiereRequest $request,
        Matiere $matiere
    ) {

        $this->service->update(
            $matiere,
            $request->validated()
        );

        return redirect()
            ->route('matieres.index')
            ->with(
                'success',
                'Matière modifiée avec succès.'
            );
    }

    public function destroy(
        Matiere $matiere
    ) {

        $this->service->delete(
            $matiere
        );

        return redirect()
            ->route('matieres.index')
            ->with(
                'success',
                'Matière supprimée avec succès.'
            );
    }
}