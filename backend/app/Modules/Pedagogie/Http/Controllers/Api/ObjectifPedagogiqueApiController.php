<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Matiere;
use App\Models\ObjectifPedagogique;
use App\Models\PeriodeComptable;
use App\Modules\Pedagogie\Http\Requests\StoreObjectifPedagogiqueApiRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateObjectifPedagogiqueApiRequest;
use App\Modules\Pedagogie\Http\Resources\ObjectifPedagogiqueResource;
use App\Modules\Pedagogie\Services\ObjectifPedagogiqueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API objectifs pédagogiques (T7A.6, D-050).
 *
 * Deux usages sur le même contrôleur :
 *
 *  - l'**enseignant** consulte ses objectifs, saisit et corrige les siens ;
 *  - l'**admin** supervise tous les objectifs, corrige et supprime (la
 *    création reste réservée à l'enseignant, porteur de la relation).
 *
 * La propriété est tranchée par la policy (et non seulement par la liste) :
 * un identifiant de collègue, devinable, n'autorise aucune écriture.
 * Le gel des périodes (D-051) vit dans le service, quel que soit l'auteur.
 */
class ObjectifPedagogiqueApiController extends Controller
{
    public function __construct(
        private readonly ObjectifPedagogiqueService $service,
    ) {}

    /**
     * Objectifs visibles par le rôle connecté, taille de page réelle (D-058).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $objectifs = $this->service->paginateForUser(
            $request->user(),
            $request->only(['search', 'periode_id']),
            $request->integer('per_page', $request->integer('par_page', 15)),
        );

        return ObjectifPedagogiqueResource::collection($objectifs);
    }

    public function store(StoreObjectifPedagogiqueApiRequest $request): JsonResponse
    {
        $this->authorize('create', ObjectifPedagogique::class);

        $objectif = $this->service->create($request->validated());

        return ObjectifPedagogiqueResource::make($objectif)
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateObjectifPedagogiqueApiRequest $request,
        ObjectifPedagogique $objectif,
    ): ObjectifPedagogiqueResource {
        $this->authorize('update', $objectif);

        return ObjectifPedagogiqueResource::make(
            $this->service->update($objectif, $request->validated())
        );
    }

    public function destroy(ObjectifPedagogique $objectif): JsonResponse
    {
        $this->authorize('delete', $objectif);

        $this->service->delete($objectif);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Élèves du formulaire de création : uniquement ceux que l'enseignant
     * suit réellement — le formulaire ne propose jamais un élève étranger.
     */
    public function eleves(): JsonResponse
    {
        $profil = request()->user()?->enseignantProfil;

        abort_unless($profil, 403);

        return response()->json([
            'data' => $this->service->getElevesForTeacher(request()->user())
                ->map(fn ($eleve) => [
                    'id' => $eleve->id,
                    'nom' => $eleve->user?->nom,
                    'prenom' => $eleve->user?->prenom,
                    'classe' => $eleve->classe?->nom,
                ])
                ->values(),
        ]);
    }

    /**
     * Toutes les périodes, ouvertes comme clôturées : le filtre doit pouvoir
     * retrouver les objectifs d'une période close, la création n'accepte
     * que les ouvertes (contrôlé par le service, pas par cette liste).
     */
    public function periodes(): JsonResponse
    {
        return response()->json([
            'data' => PeriodeComptable::query()
                ->orderByDesc('date_debut')
                ->get()
                ->map(fn (PeriodeComptable $periode) => [
                    'id' => $periode->id,
                    'label' => $periode->label,
                    'date_debut' => $periode->date_debut?->format('Y-m-d'),
                    'date_fin' => $periode->date_fin?->format('Y-m-d'),
                    'statut' => $periode->statut,
                    'est_ouverte' => $periode->estOuverte(),
                ])
                ->values(),
        ]);
    }

    /**
     * Matières actives du formulaire de ventilation (enseignant et admin).
     */
    public function matieres(): JsonResponse
    {
        return response()->json([
            'data' => Matiere::query()
                ->where('actif', true)
                ->orderBy('nom')
                ->get(['id', 'nom', 'sigle']),
        ]);
    }
}
