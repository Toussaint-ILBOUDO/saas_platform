<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Models\DemandeCours;
use App\Http\Controllers\Controller;
use App\Modules\Pedagogie\Services\DemandeCoursAdminService;

class DemandeCoursAdminController extends Controller
{
    public function __construct(
        protected DemandeCoursAdminService $service
    ) {
    }

    public function index()
    {
        return view(
            'pedagogie.demande-cours.index',
            [
                'demandes' => $this->service->paginate(),
                'stats' => $this->service->getStats(),
            ]
        );
    }

    public function show(
        DemandeCours $demandeCours
    ) {
        $demandeCours->load([
            'classe',
            'typeCours',
            'matieres',
        ]);

        return view(
            'pedagogie.demande-cours.show',
            compact('demandeCours')
        );
    }

    public function valider(
        DemandeCours $demandeCours
    ) {
        $this->service->valider($demandeCours);

        return redirect()
            ->route('demande-cours.show', $demandeCours)
            ->with('success', 'Demande marquée comme traitée.');
    }
}