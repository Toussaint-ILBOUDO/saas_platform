<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Models\Classe;

use App\Modules\Pedagogie\Services\ClasseService;

use App\Modules\Pedagogie\Http\Requests\StoreClasseRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateClasseRequest;

class ClasseController
{
    public function __construct(
        protected ClasseService $service
    ) {
    }

    public function index()
    {
        return view(
            'pedagogie.classes.index',
            [
                'classes' => $this->service->paginate(),
                'stats'   => $this->service->getStats(),
            ]
        );
    }

    public function create()
    {
        return view(
            'pedagogie.classes.create'
        );
    }

    public function store(
        StoreClasseRequest $request
    ) {
        $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('classes.index')
            ->with(
                'success',
                'Classe créée avec succès.'
            );
    }

    public function show(
        Classe $classe
    ) {
        return view(
            'pedagogie.classes.show',
            compact('classe')
        );
    }

    public function edit(
        Classe $classe
    ) {
        return view(
            'pedagogie.classes.edit',
            compact('classe')
        );
    }

    public function update(
        UpdateClasseRequest $request,
        Classe $classe
    ) {

        $this->service->update(
            $classe,
            $request->validated()
        );

        return redirect()
            ->route('classes.index')
            ->with(
                'success',
                'Classe modifiée avec succès.'
            );
    }

    public function destroy(
        Classe $classe
    ) {

        $this->service->delete($classe);

        return redirect()
            ->route('classes.index')
            ->with(
                'success',
                'Classe supprimée avec succès.'
            );
    }
}