<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TypeCours;
use App\Modules\Pedagogie\Http\Requests\FilterTypeCoursRequest;
use App\Modules\Pedagogie\Http\Requests\StoreTypeCoursRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateTypeCoursRequest;
use App\Modules\Pedagogie\Services\TypeCoursService;

class TypeCoursController extends Controller
{
    public function __construct(
        private readonly TypeCoursService $service
    ) {
    }

    public function index(FilterTypeCoursRequest $request)
    {
        $typeCours = $this->service->paginate(
            $request->validated()
        );

        return view(
            'pedagogie.type-cours.index',
            compact('typeCours')
        );
    }

    public function create()
    {
        return view('pedagogie.type-cours.create');
    }

    public function store(StoreTypeCoursRequest $request)
    {
        $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('type-cours.index')
            ->with(
                'success',
                'Type de cours créé avec succès.'
            );
    }

    public function edit(TypeCours $typeCour)
    {
        return view(
            'pedagogie.type-cours.edit',
            compact('typeCour')
        );
    }

    public function update(
        UpdateTypeCoursRequest $request,
        TypeCours $typeCour
    ) {
        $this->service->update(
            $typeCour,
            $request->validated()
        );

        return redirect()
            ->route('type-cours.index')
            ->with(
                'success',
                'Type de cours modifié avec succès.'
            );
    }

    public function activate(TypeCours $typeCour)
    {
        $this->service->activate($typeCour);

        return back()->with(
            'success',
            'Type de cours activé.'
        );
    }

    public function deactivate(TypeCours $typeCour)
    {
        $this->service->deactivate($typeCour);

        return back()->with(
            'success',
            'Type de cours désactivé.'
        );
    }
}