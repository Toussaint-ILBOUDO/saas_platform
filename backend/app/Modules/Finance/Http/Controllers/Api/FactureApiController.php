<?php

namespace App\Modules\Finance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Modules\Finance\Http\Requests\FilterFactureRequest;
use App\Modules\Finance\Http\Requests\GenererFactureApiRequest;
use App\Modules\Finance\Http\Requests\PayerFactureApiRequest;
use App\Modules\Finance\Http\Requests\PreviewFactureApiRequest;
use App\Modules\Finance\Http\Resources\FactureResource;
use App\Modules\Finance\Services\FacturePdfService;
use App\Modules\Finance\Services\FacturationService;
use App\Modules\Finance\Services\FactureQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API facture parent (T7A.8).
 *
 * Deux périmètres, une seule ressource :
 *
 *  - le **parent** consulte SES factures et télécharge les PDF (groupe
 *    `role:parent`, prefix `mes-factures`) — rien à écrire ;
 *  - l'**administration** liste tout le cabinet, aperçoit la facture depuis
 *    les rapports validés, la génère et enregistre le règlement (groupe
 *    `role:admin_cabinet`, prefix `admin/factures`).
 *
 * La génération exige que TOUS les rapports du contrat soient validés
 * (D-051) : on aperçoit avant de générer, le preview disant précisément qui
 * manque. Le règlement est une route explicite (`paiement`), jamais un PATCH
 * générique sur `statut_paiement` : la machine à états reste dans le service.
 */
class FactureApiController extends Controller
{
    public function __construct(
        private readonly FacturationService $service,
        private readonly FactureQueryService $queries,
        private readonly FacturePdfService $pdfs,
    ) {}

    /**
     * Index parent : ses seules factures.
     *
     * Pas de `authorize('viewAny')` ici : la policy `viewAny` exige la
     * permission `facture.view`, que le parent n'a pas par construction. Le
     * périmètre est le scope même (cf. `MesEnfantsApiController`).
     */
    public function indexMesFactures(
        FilterFactureRequest $request
    ): AnonymousResourceCollection {
        $factures = $this->queries->paginerPourParent(
            (int) $request->user()->id,
            $request->validated(),
            (int) $request->integer('per_page', $request->integer('par_page', 20)),
        );

        return FactureResource::collection($factures);
    }

    /**
     * Détail parent : 404 si la facture n'est pas la sienne.
     */
    public function showMesFacture(
        Request $request
    ): FactureResource {
        $facture = $this->queries->trouverPourParent(
            (int) $request->user()->id,
            (int) $request->route('facture'),
        );

        return FactureResource::make($this->detail($facture));
    }

    /**
     * PDF parent : même périmètre strict que le détail.
     */
    public function pdfMesFacture(
        Request $request
    ): Response {
        $facture = $this->queries->trouverPourParent(
            (int) $request->user()->id,
            (int) $request->route('facture'),
        );

        return $this->pdfs->stream($this->detail($facture));
    }

    /**
     * Index administration : toutes les factures du cabinet.
     */
    public function indexAdmin(
        FilterFactureRequest $request
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Facture::class);

        $factures = $this->queries->paginerPourAdmin(
            $request->validated(),
            (int) $request->integer('per_page', $request->integer('par_page', 20)),
        );

        return FactureResource::collection($factures);
    }

    public function show(Facture $facture): FactureResource
    {
        $this->authorize('view', $facture);

        return FactureResource::make($this->detail($facture));
    }

    /**
     * Aperçu sans rien écrire : lignes issues des rapports VALIDÉS de la
     * période, totaux, frais saisis. Un contrat dont un enseignant n'a pas de
     * rapport validé se voit ici par des lignes vides (0 h) ; c'est la
     * GÉNÉRATION qui refuse explicitement avec la liste des manquants (clé
     * `rapports`) — l'aperçu reste un simple calcul, sans décision.
     */
    public function preview(
        PreviewFactureApiRequest $request
    ): JsonResponse {
        $this->authorize('create', Facture::class);

        $contrat = \App\Models\ContratCours::with('eleve')->findOrFail(
            (int) $request->validated('contrat_cours_id')
        );

        $apercu = $this->service->calculerPreview(
            $contrat,
            (int) $request->validated('periode_id'),
            $request->validated(),
        );

        return response()->json(['data' => $apercu]);
    }

    /**
     * Génération d'une facture. Le service porte les règles :
     * période ouverte (D-051), doublon contrat/période, prérequis de
     * validation de tous les rapports, lignes non vides.
     */
    public function store(GenererFactureApiRequest $request): JsonResponse
    {
        $this->authorize('create', Facture::class);

        $facture = $this->service->generer(
            $request->validated(),
            (int) $request->validated('periode_id'),
        );

        return FactureResource::make($this->detail($facture))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Règlement d'une facture. La route est explicite et bornée par la
     * ability `payer` ; le service refuse un second règlement (422).
     */
    public function payer(
        PayerFactureApiRequest $request,
        Facture $facture
    ): JsonResponse {
        $facture = $this->service->marquerPaye(
            $facture,
            $request->validated(),
        );

        return FactureResource::make($this->detail($facture))
            ->toResponse(request());
    }

    /**
     * PDF (stream) d'une facture — accessible à l'admin `facture.view`.
     */
    public function pdf(Facture $facture): Response
    {
        $this->authorize('view', $facture);

        return $this->pdfs->stream($this->detail($facture));
    }

    /**
     * Charge la profondeur nécessaire au détail d'une facture.
     */
    private function detail(Facture $facture): Facture
    {
        return $facture->load([
            'parent',
            'periode',
            'contrat.eleve.user',
            'contrat.eleve.classe',
            'contrat.typeCours',
            'lignes.affectation.enseignant.user',
            'lignes.affectation.matiere',
        ]);
    }
}