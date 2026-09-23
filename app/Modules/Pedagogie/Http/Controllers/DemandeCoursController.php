<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Models\TypeCours;
use App\Models\Classe;
use App\Models\Matiere;
use App\Http\Controllers\Controller;
use App\Modules\Pedagogie\Http\Requests\StoreDemandeCoursRequest;
use App\Modules\Pedagogie\Services\DemandeCoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DemandeCoursController extends Controller
{
    public function __construct(
        private readonly DemandeCoursService $demandeCoursService
    ) {}

    /**
     * Enregistrement d'une nouvelle demande de cours
     */
    public function store(StoreDemandeCoursRequest $request): RedirectResponse
    {
        $this->demandeCoursService->create(
            $request->validated()
        );

        return back()->with('success', 'Demande envoyée avec succès.');
    }

    public function create(): View
    {
        return view('pedagogie.demande-cours.create', [
            'typesCours' => TypeCours::all(),
            'classes' => Classe::all(),
            'matieres' => Matiere::all(),
        ]);
    }
}