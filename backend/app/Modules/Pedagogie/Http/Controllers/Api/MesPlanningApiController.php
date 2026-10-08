<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Pedagogie\Http\Resources\PlanningCoursResource;
use App\Modules\Pedagogie\Services\PlanningCoursService;
use App\Models\Eleve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API planning — consultation par l'élève et le parent (T7A.4).
 *
 * Règles de visibilité demandées :
 *  - le **parent** voit les créneaux de ses enfants ;
 *  - l'**élève** voit les siens, mais uniquement si son compte est activé
 *    (`eleves.statut` ET `users.statut`, cf. `EleveService::activateAccount`) ;
 *  - les autres enseignants qui interviennent sur le même contrat voient les
 *    créneaux du collègue — c'est handled côté enseignant
 *    (`PlanningApiController::index` → `creneaux_partages`).
 *
 * Le périmètre est déduit du profil du connecté, jamais d'un paramètre de
 * requête : un `?eleve_id=` devinable ne doit pas ouvrir le planning d'un autre
 * enfant.
 */
class MesPlanningApiController extends Controller
{
    public function __construct(
        private readonly PlanningCoursService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // `eleves.parent_id` référence `users.id` (cf. migration `eleves` et
        // `User::enfants()`), **pas** `parent_profils.id` qui est une séquence
        // indépendante. Comparer les deux ouvre le planning d'une autre famille
        // dès que les identifiants se recouvrent.
        if ($user->hasRole('parent') && $user->parentProfil) {
            return $this->pourParent($user->id);
        }

        if ($user->eleve) {
            return $this->pourEleve($user->eleve);
        }

        abort(403);
    }

    /**
     * Parent : planning de tous ses enfants, groupé par élève.
     */
    private function pourParent(int $parentUserId): JsonResponse
    {
        $eleves = Eleve::query()
            ->where('parent_id', $parentUserId)
            ->with('user')
            ->get();

        $parEleve = $eleves->map(function (Eleve $eleve) {
            return [
                'eleve' => [
                    'id' => $eleve->id,
                    'nom' => $eleve->user?->nom,
                    'prenom' => $eleve->user?->prenom,
                    // Le parent voit le planning de son enfant même si le compte
                    // de l'enfant n'est pas activé : c'est lui qui l'active.
                    'compte_actif' => (bool) $eleve->statut && (bool) $eleve->user?->statut,
                ],
                'creneaux' => PlanningCoursResource::collection(
                    $this->service->listForEleves([$eleve->id])
                ),
            ];
        });

        return response()->json([
            'eleves' => $parEleve->values(),
        ]);
    }

    /**
     * Élève : son propre planning, seulement si son compte est activé.
     */
    private function pourEleve(Eleve $eleve): JsonResponse
    {
        $actif = (bool) $eleve->statut && (bool) $eleve->user?->statut;

        return response()->json([
            'compte_actif' => $actif,
            'creneaux' => $actif
                ? PlanningCoursResource::collection($this->service->listForEleve($eleve))
                : PlanningCoursResource::collection(collect()),
        ]);
    }
}