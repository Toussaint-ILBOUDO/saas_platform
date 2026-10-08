<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandeCours;
use App\Modules\Pedagogie\Http\Requests\StoreDemandeCoursContratRequest;
use App\Modules\Pedagogie\Http\Requests\StoreDemandeCoursEleveRequest;
use App\Modules\Pedagogie\Http\Requests\StoreDemandeCoursParentRequest;
use App\Modules\Pedagogie\Http\Resources\DemandeCoursResource;
use App\Modules\Pedagogie\Services\DemandeCoursAdminService;
use App\Modules\Pedagogie\Services\DemandeCoursQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API demandes de cours — administration du cabinet.
 *
 * Alimente l'écran Angular « Demandes de cours » et remplace le backoffice
 * Blade (`DemandeCoursAdminController`) côté front.
 *
 * Les actions de constitution de dossier (parent, élève, contrat) renvoient la
 * **demande** mise à jour et non l'entité créée : l'écran a besoin de savoir
 * ce qui existe désormais sur la demande pour proposer l'étape suivante, et
 * l'objet créé est déjà inclus dans la ressource. `show` rejoue le chargement
 * complet pour que la réponse soit identique à celle d'un `GET` de détail.
 */
class DemandeCoursAdminApiController extends Controller
{
    public function __construct(
        private readonly DemandeCoursAdminService $service,
        private readonly DemandeCoursQueryService $queries
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [];

        if ($request->filled('search')) {
            $filters['search'] = $request->string('search')->toString();
        }

        if ($request->has('statut') && $request->query('statut') !== '') {
            $filters['statut'] = $request->string('statut')->toString();
        }

        if ($request->filled('classe_id')) {
            $filters['classe_id'] = $request->integer('classe_id');
        }

        $demandes = $this->queries->paginate(
            $filters,
            (int) $request->integer('per_page', $request->integer('par_page', 20))
        );

        // Les compteurs des cartes de tête sont ajoutés dans `meta` : ils sont
        // calculés sur l'ensemble du cabinet, pas sur la page affichée, sinon
        // le compteur « en attente » mentirait dès qu'un filtre est actif.
        return DemandeCoursResource::collection($demandes)->additional([
            'meta' => [
                'stats' => $this->queries->stats(),
            ],
        ]);
    }

    public function show(DemandeCours $demandeCours): JsonResponse
    {
        return $this->reponse($demandeCours);
    }

    /**
     * Marque la demande comme traitée.
     *
     * Idempotent : rejouer l'action sur une demande déjà traitée renvoie la
     * même réponse 200 au lieu d'échouer — l'admin peut double-cliquer.
     */
    public function valider(DemandeCours $demandeCours): JsonResponse
    {
        return $this->reponse($this->service->valider($demandeCours));
    }

    /**
     * Refuse la demande : le cabinet ne donne pas suite.
     */
    public function refuser(DemandeCours $demandeCours): JsonResponse
    {
        return $this->reponse($this->service->refuser($demandeCours));
    }

    /**
     * Crée le parent à partir des coordonnées de la demande.
     */
    public function creerParent(
        StoreDemandeCoursParentRequest $request,
        DemandeCours $demandeCours
    ): JsonResponse {
        $this->service->creerParent($demandeCours, $request->validated());

        return $this->reponse($demandeCours);
    }

    /**
     * Crée l'élève rattaché au parent de la demande.
     */
    public function creerEleve(
        StoreDemandeCoursEleveRequest $request,
        DemandeCours $demandeCours
    ): JsonResponse {
        $this->service->creerEleve($demandeCours, $request->validated());

        return $this->reponse($demandeCours);
    }

    /**
     * Crée le contrat de cours de l'élève créé précédemment.
     */
    public function creerContrat(
        StoreDemandeCoursContratRequest $request,
        DemandeCours $demandeCours
    ): JsonResponse {
        $this->service->creerContrat($demandeCours, $request->validated());

        return $this->reponse($demandeCours);
    }

    /**
     * Fiche complète après écriture : le rechargement évite de reconstruire à la
     * main les relations que les services viennent de renseigner.
     */
    private function reponse(DemandeCours $demandeCours): JsonResponse
    {
        return DemandeCoursResource::make(
            $this->queries->show($demandeCours->refresh())
        )->toResponse(request());
    }
}