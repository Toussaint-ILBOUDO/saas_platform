<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CahierTexte;
use App\Models\Eleve;
use App\Modules\Pedagogie\Services\CahierTexteService;
use App\Modules\Pedagogie\Http\Requests\StoreCahierTexteRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateCahierTexteRequest;

class CahierTexteWebController extends Controller
{
    public function __construct(
        protected CahierTexteService $service
    ) {}

    /**
     * LISTE
     */
    public function index(Request $request)
    {
        $cahiers = $this->service->paginateForUser(
            auth()->user(),
            [
                'search' => $request->input('search')
            ]
        );

        return view(
            'pedagogie.cahiers-textes.index',
            compact('cahiers')
        );
    }

    /**
     * STEP 1 : choix élève (IMPORTANT UX)
     */
    public function createSelectEleve()
    {
        
        $eleves = $this->service->getElevesForTeacher(auth()->user());

        return view('pedagogie.cahiers-textes.select-eleve', compact('eleves'));
    }

    public function create(Eleve $eleve)
    {
        $this->authorize('create', CahierTexte::class);

        $affectations = $this->service->getAffectationsForEleve(
            auth()->user(),
            $eleve
        );

        return view('pedagogie.cahiers-textes.create', compact('eleve', 'affectations'));
    }

    public function createByEleve(Eleve $eleve)
    {
        $this->authorize('create', CahierTexte::class);

        $affectations = $this->service->getAffectationsForEleve(
            auth()->user(),
            $eleve
        );

        return view('pedagogie.cahiers-textes.create', compact('eleve', 'affectations'));
    }

    /**
     * STORE
     */
    public function store(StoreCahierTexteRequest $request)
    {
        $this->authorize('create', CahierTexte::class);

        $this->service->create($request->validated());

        return redirect()
            ->route('cahiers-textes.index')
            ->with('success', 'Séance enregistrée avec succès.');
    }

    /**
     * SHOW
     */
    public function show(CahierTexte $cahier)
    {
        $this->authorize('view', $cahier);

        return view(
            'pedagogie.cahiers-textes.show',
            [
                'cahier' => $this->service->loadDetails($cahier)
            ]
        );
    }

    /**
     * EDIT
     */
    public function edit(CahierTexte $cahier)
    {
        $this->authorize('update', $cahier);

        return view(
            'pedagogie.cahiers-textes.edit',
            [
                'cahier' => $this->service->loadDetails($cahier)
            ]
        );
    }

    /**
     * UPDATE
     */
    public function update(
        UpdateCahierTexteRequest $request,
        CahierTexte $cahier
    ) {
        $this->authorize('update', $cahier);

        $this->service->update(
            $cahier,
            $request->validated()
        );

        return redirect()
            ->route('cahiers-textes.show', $cahier)
            ->with('success', 'Séance modifiée avec succès.');
    }

    /**
     * PDF
     */
    public function pdf(CahierTexte $cahier)
    {
        $this->authorize('view', $cahier);

        return $this->service->downloadPdf($cahier);
    }
}