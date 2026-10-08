<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanningCours;
use App\Modules\Pedagogie\Http\Requests\StorePlanningCoursRequest;
use App\Modules\Pedagogie\Http\Resources\PlanningCoursResource;
use App\Modules\Pedagogie\Services\PlanningCoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API planning enseignant (T7A.4).
 *
 * L'enseignant ne gère que ses propres créneaux : l'appartenance est vérifiée
 * dans le contrôleur (403), pas seulement dans le formulaire, sinon un
 * identifiant devinable suffirait à réécrire le planning d'un collègue.
 *
 * Le `DELETE` est en revanche autorisé ici : `planning_cours` n'est référencée
 * par aucune autre table (contrairement aux contrats et affectations, D-054).
 * Un créneau est une intention de cours, pas une écriture comptable.
 */
class PlanningApiController extends Controller
{
    public function __construct(
        private readonly PlanningCoursService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $profil = $this->profil();

        return response()->json([
            'mes_creneaux' => PlanningCoursResource::collection(
                $this->service->listForEnseignant($profil)
            ),
            // Les créneaux des autres enseignants qui interviennent auprès des
            // mêmes élèves : sans cela l'enseignant ne voit pas quand l'élève
            // est déjà occupé avec quelqu'un d'autre.
            'creneaux_partages' => PlanningCoursResource::collection(
                $this->service->listSharedForEnseignant($profil)
            ),
            'affectations' => $this->service->availableAffectations($profil)
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'matiere' => $a->matiere?->nom,
                    'eleve' => trim(($a->contrat?->eleve?->user?->prenom ?? '') . ' '
                        . ($a->contrat?->eleve?->user?->nom ?? '')),
                ])->values(),
        ]);
    }

    public function store(StorePlanningCoursRequest $request): JsonResponse
    {
        $creneau = $this->service->createForEnseignant($this->profil(), $request->validated());

        return $this->reponse($creneau, Response::HTTP_CREATED);
    }

    public function update(StorePlanningCoursRequest $request, PlanningCours $planningCours): JsonResponse
    {
        $this->verifiePropriete($planningCours);

        $creneau = $this->service->updateForEnseignant(
            $this->profil(),
            $planningCours,
            $request->validated(),
        );

        return $this->reponse($creneau);
    }

    public function destroy(PlanningCours $planningCours): JsonResponse
    {
        $this->verifiePropriete($planningCours);

        $planningCours->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function reponse(PlanningCours $creneau, int $code = Response::HTTP_OK): JsonResponse
    {
        $creneau->load(['enseignant.user', 'affectation.matiere', 'affectation.contrat.eleve.user']);

        return PlanningCoursResource::make($creneau)
            ->toResponse(request())
            ->setStatusCode($code);
    }

    private function profil(): \App\Models\EnseignantProfil
    {
        $profil = request()->user()?->enseignantProfil;

        abort_if(! $profil, 403);

        return $profil;
    }

    private function verifiePropriete(PlanningCours $creneau): void
    {
        abort_unless((int) $creneau->enseignant_id === (int) $this->profil()->id, 403);
    }
}