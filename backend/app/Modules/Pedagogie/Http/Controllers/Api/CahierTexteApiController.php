<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CahierTexte;
use App\Models\Eleve;
use App\Modules\Pedagogie\Http\Requests\StoreCahierTexteApiRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateCahierTexteApiRequest;
use App\Modules\Pedagogie\Http\Resources\CahierTexteResource;
use App\Modules\Pedagogie\Services\CahierTextePdfService;
use App\Modules\Pedagogie\Services\CahierTexteService;
use App\Modules\Pedagogie\Services\PlanningCoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API cahier de texte (T7A.5).
 *
 * Deux usages distincts, une seule table :
 *
 *  - l'**enseignant** saisit ses séances (`index`, `store`, `update`,
 *    `destroy`) et exporte le PDF d'une séance ;
 *  - le **parent** et l'**élève** consultent l'historique en lecture, sans
 *    aucune écriture possible.
 *
 * La propriété d'une séance est vérifiée dans le service et non seulement dans
 * le formulaire : un `PUT` sur l'identifiant d'une séance de collègue ne peut
 * aboutir ni par l'API ni par un job, quel que soit le point d'entrée.
 *
 * Le périmètre de l'index est déduit du rôle connecté — jamais d'un
 * `?eleve_id=` devinable.
 */
class CahierTexteApiController extends Controller
{
    public function __construct(
        private readonly CahierTexteService $service,
        private readonly CahierTextePdfService $pdfs,
        private readonly PlanningCoursService $planning,
    ) {}

    /**
     * Séances visibles par l'utilisateur.
     *
     * Les trois rôles lisent la même ressource ; seul le périmètre change. La
     * taille de page est réellement appliquée (D-058).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $cahiers = $this->service->paginateForUser(
            $request->user(),
            $request->only(['search', 'date_debut', 'date_fin']),
            $request->integer('per_page', $request->integer('par_page', 20)),
        );

        return CahierTexteResource::collection($cahiers);
    }

    /**
     * Aperçu d'une séance. L'autorisation passe par la policy, qui connaît les
     * trois rôles (enseignant de la séance, parent ou élève concerné).
     */
    public function show(CahierTexte $cahier): CahierTexteResource
    {
        $this->authorize('view', $cahier);

        return CahierTexteResource::make($this->service->loadDetails($cahier));
    }

    /**
     * Affectations navigables pour la saisie : l'enseignant ne choisit que
     * parmi ses cours actifs.
     */
    public function affectations(): JsonResponse
    {
        return response()->json([
            'data' => $this->planning
                ->availableAffectations($this->profil())
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'matiere' => $a->matiere?->nom,
                    'eleve' => trim(
                        ($a->contrat?->eleve?->user?->prenom ?? '')
                        . ' ' . ($a->contrat?->eleve?->user?->nom ?? '')
                    ),
                ])->values(),
        ]);
    }

    /**
     * Saisie d'une séance.
     *
     * Une reprise idempotente (même `uuid_client`) renvoie **200** et la
     * séance existante ; une création renvoie **201**. La distinction est
     * nécessaire au client hors-ligne : il doit savoir s'il peut conserver
     * sa copie locale ou si le serveur a déjà enregistré la séance.
     */
    public function store(StoreCahierTexteApiRequest $request): JsonResponse
    {
        $this->authorize('create', CahierTexte::class);

        [$cahier, $creee] = $this->service->create($request->user(), $request->validated());

        return CahierTexteResource::make($cahier)
            ->toResponse(request())
            ->setStatusCode($creee ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function update(
        UpdateCahierTexteApiRequest $request,
        CahierTexte $cahier
    ): CahierTexteResource {
        $this->authorize('update', $cahier);

        return CahierTexteResource::make(
            $this->service->update($request->user(), $cahier, $request->validated())
        );
    }

    public function destroy(Request $request, CahierTexte $cahier): JsonResponse
    {
        $this->authorize('update', $cahier);

        $this->service->delete($request->user(), $cahier);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /** PDF d'une séance. Réservé à l'enseignant de la séance. */
    public function pdf(CahierTexte $cahier)
    {
        $cahier->loadMissing('affectation');

        abort_unless(
            (int) $cahier->affectation?->enseignant_id === (int) $this->profil()->id,
            403,
        );

        return $this->pdfs->downloadSingle($cahier);
    }

    /**
     * Historique d'un élève, en lecture, pour le parent et l'élève.
     *
     * Même endpoint que celui de l'enseignant, mais paramétré par l'élève : la
     * policy `viewHistory` tranche (le parent doit être le tuteur, l'élève ne
     * voit que lui-même). Sans elle, `?eleve_id` devinable ouvrirait le
     * cahier d'un autre enfant.
     */
    public function indexEleve(Request $request, Eleve $eleve): AnonymousResourceCollection
    {
        $this->authorize('viewHistory', [CahierTexte::class, $eleve->id]);

        $cahiers = $this->service->paginateForEleve(
            $eleve,
            $request->only(['search', 'date_debut', 'date_fin']),
            $request->integer('per_page', $request->integer('par_page', 20)),
        );

        return CahierTexteResource::collection($cahiers);
    }

    /**
     * Historique PDF d'un élève, pour le parent et l'élève lui-même.
     *
     * L'élève ne consulte que son propre historique : la policy refuse tout
     * `?eleve_id` qui n'est pas le sien, ce qui protège également le cas d'un
     * parent dont l'enfant aurait été réaffecté.
     */
    public function historiquePdf(Request $request, Eleve $eleve)
    {
        $this->authorize('viewHistory', [CahierTexte::class, $eleve->id]);

        $dates = $request->validate([
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        return $this->pdfs->downloadHistory($eleve, $dates['date_debut'] ?? null, $dates['date_fin'] ?? null);
    }

    private function profil(): \App\Models\EnseignantProfil
    {
        $profil = request()->user()?->enseignantProfil;

        abort_if(! $profil, 403);

        return $profil;
    }
}