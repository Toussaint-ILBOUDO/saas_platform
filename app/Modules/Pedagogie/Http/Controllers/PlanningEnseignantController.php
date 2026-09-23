<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PlanningCours;
use App\Modules\Pedagogie\Http\Requests\StorePlanningCoursRequest;
use App\Modules\Pedagogie\Services\PlanningCoursService;
use Illuminate\Support\Collection;

class PlanningEnseignantController extends Controller
{
    public function __construct(
        private readonly PlanningCoursService $service
    ) {}

    public function index()
    {
        $profil = auth()->user()->enseignantProfil;

        abort_unless($profil, 403);

        return view('pedagogie.planning-enseignant.index', [
            'mesCreneaux' => $this->service->listForEnseignant($profil),
            'creneauxPartages' => $this->service->listSharedForEnseignant($profil),
            'affectations' => $this->service->availableAffectations($profil),
        ]);
    }

    public function store(StorePlanningCoursRequest $request)
    {
        $profil = auth()->user()->enseignantProfil;

        abort_unless($profil, 403);

        $this->service->createForEnseignant($profil, $request->validated());

        return redirect()->route('planning-enseignant.index')
            ->with('success', 'Créneau régulier ajouté à votre planning.');
    }

    public function edit(PlanningCours $planningCours)
    {
        $profil = auth()->user()->enseignantProfil;

        abort_unless($profil, 403);

        $this->authorizeOwnership($profil->id, $planningCours);

        return view('pedagogie.planning-enseignant.edit', [
            'creneau' => $planningCours->load(['affectation.matiere', 'affectation.contrat.eleve.user']),
            'affectations' => $this->mergeCurrentAffectation(
                $this->service->availableAffectations($profil),
                $planningCours
            ),
        ]);
    }

    public function update(StorePlanningCoursRequest $request, PlanningCours $planningCours)
    {
        $profil = auth()->user()->enseignantProfil;

        abort_unless($profil, 403);

        $this->authorizeOwnership($profil->id, $planningCours);

        $this->service->updateForEnseignant($profil, $planningCours, $request->validated());

        return redirect()->route('planning-enseignant.index')
            ->with('success', 'Créneau mis à jour.');
    }

    public function destroy(PlanningCours $planningCours)
    {
        $profil = auth()->user()->enseignantProfil;

        abort_unless($profil, 403);

        $this->authorizeOwnership($profil->id, $planningCours);

        $planningCours->delete();

        return redirect()->route('planning-enseignant.index')
            ->with('success', 'Créneau supprimé.');
    }

    /**
     * Garantit que l'enseignant n'agit que sur ses propres créneaux.
     */
    protected function authorizeOwnership(int $enseignantId, PlanningCours $planningCours): void
    {
        abort_unless($planningCours->enseignant_id === $enseignantId, 403);
    }

    /**
     * Conserve l'affectation d'un créneau même si elle est devenue inactive.
     */
    protected function mergeCurrentAffectation(Collection $affectations, PlanningCours $planningCours): Collection
    {
        $have = $affectations->contains('id', $planningCours->affectation_enseignant_id);

        if ($have) {
            return $affectations;
        }

        return $affectations->prepend($planningCours->affectation);
    }
}