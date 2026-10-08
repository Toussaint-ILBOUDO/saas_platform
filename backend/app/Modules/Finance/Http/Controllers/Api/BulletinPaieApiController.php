<?php

namespace App\Modules\Finance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulletinPaie;
use App\Models\BulletinPaieAjustement;
use App\Models\TypeAjustement;
use App\Modules\Finance\Http\Requests\CorrigerBulletinApiRequest;
use App\Modules\Finance\Http\Requests\FilterBulletinRequest;
use App\Modules\Finance\Http\Requests\GenererBulletinsApiRequest;
use App\Modules\Finance\Http\Requests\PayerBulletinApiRequest;
use App\Modules\Finance\Http\Requests\PreviewBulletinsApiRequest;
use App\Modules\Finance\Http\Requests\StoreAjustementApiRequest;
use App\Modules\Finance\Http\Resources\BulletinPaieAjustementResource;
use App\Modules\Finance\Http\Resources\BulletinPaieResource;
use App\Modules\Finance\Http\Resources\TypeAjustementResource;
use App\Modules\Finance\Services\BulletinPaieAdjustmentService;
use App\Modules\Finance\Services\BulletinPaieGenerationService;
use App\Modules\Finance\Services\BulletinPaiePdfService;
use App\Modules\Finance\Services\BulletinPaieQueryService;
use App\Modules\Finance\Services\BulletinPaieValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * T7A.9 — Bulletin de paie : administration.
 *
 * L'admin aperçoit les bulletins d'une période depuis les RAPPORTS VALIDÉS
 * (D-051), les génère, corrige un bulletin contesté, ajoute/retire un
 * ajustement et enregistre le versement. Le cycle enseignant (consulter,
 * valider, contester, confirmer la réception) vit dans le groupe
 * `mes-bulletins` — ici pas d'écriture pour l'enseignant.
 */
class BulletinPaieApiController extends Controller
{
    public function __construct(
        private readonly BulletinPaieGenerationService $generation,
        private readonly BulletinPaieAdjustmentService $adjustments,
        private readonly BulletinPaieValidationService $validation,
        private readonly BulletinPaieQueryService $queries,
        private readonly BulletinPaiePdfService $pdfs,
    ) {}

    /**
     * Index administration : tous les bulletins du cabinet.
     */
    public function index(FilterBulletinRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', BulletinPaie::class);

        $bulletins = $this->queries->paginerPourAdmin(
            $request->validated(),
            (int) $request->integer('per_page', $request->integer('par_page', 20)),
        );

        return BulletinPaieResource::collection($bulletins);
    }

    /**
     * Détail d'un bulletin.
     */
    public function show(BulletinPaie $bulletin): BulletinPaieResource
    {
        $this->authorize('view', $bulletin);

        return BulletinPaieResource::make($this->detail($bulletin));
    }

    /**
     * PDF d'un bulletin (stream).
     */
    public function pdf(BulletinPaie $bulletin): Response
    {
        $this->authorize('view', $bulletin);

        return $this->pdfs->stream($this->detail($bulletin));
    }

    /**
     * Aperçu des bulletins d'une période, sans rien écrire.
     *
     * Renvoie le détail par enseignant (heures/brut/frais/ajustements/net) et
     * les totaux de période, plus la liste des types d'ajustement actifs pour
     * construire la matrice de saisie.
     */
    public function preview(PreviewBulletinsApiRequest $request): JsonResponse
    {
        $apercu = $this->generation->preview(
            (int) $request->validated('periode_id'),
            $request->validated('frais_suivi', []),
            $request->validated('ajustements', []),
        );

        return response()->json([
            'data' => $this->normaliserApercu($apercu),
            'total_enseignants' => count($apercu),
            'total_heures' => round(
                array_sum(array_map(fn ($p) => $p['total_heures'], $apercu)),
                2
            ),
            'total_montant' => (int) array_sum(
                array_map(fn ($p) => $p['montant_net'], $apercu)
            ),
            'types_ajustement' => $this->listeTypesAjustement()
                ->map(fn (TypeAjustement $t) => [
                    'id' => (int) $t->id,
                    'libelle' => $t->libelle,
                    'direction' => $t->direction,
                    'is_active' => (bool) $t->is_active,
                ])
                ->values(),
        ]);
    }

    /**
     * Génération des bulletins de la période (D-051, garde période ouverte et
     * doublon dans le service).
     */
    public function generer(GenererBulletinsApiRequest $request): JsonResponse
    {
        $bulletins = $this->generation->generer(
            (int) $request->validated('periode_id'),
            $request->validated('frais_suivi', []),
            $request->validated('ajustements', []),
        );

        return BulletinPaieResource::collection($bulletins)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Correction d'un bulletin contesté (conteste → corrige).
     */
    public function corriger(
        CorrigerBulletinApiRequest $request,
        BulletinPaie $bulletin
    ): BulletinPaieResource {
        $bulletin = $this->validation->corriger(
            $bulletin,
            $request->validated('commentaire_admin'),
        );

        return BulletinPaieResource::make($this->detail($bulletin));
    }

    /**
     * Enregistrement du versement (valide → verse).
     */
    public function payer(
        PayerBulletinApiRequest $request,
        BulletinPaie $bulletin
    ): BulletinPaieResource {
        $bulletin = $this->validation->payer(
            $bulletin,
            $request->validated(),
        );

        return BulletinPaieResource::make($this->detail($bulletin));
    }

    /**
     * Ajout d'un ajustement (prime ou retenue).
     */
    public function storeAjustement(
        StoreAjustementApiRequest $request,
        BulletinPaie $bulletin
    ): JsonResponse {
        $ajustement = $this->adjustments->ajouter(
            $bulletin,
            $request->validated(),
        );

        return BulletinPaieAjustementResource::make($ajustement)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Suppression d'un ajustement.
     */
    public function destroyAjustement(
        Request $request,
        BulletinPaie $bulletin,
        BulletinPaieAjustement $ajustement
    ): JsonResponse {
        $this->authorize('update', $bulletin);

        $this->adjustments->supprimer($ajustement);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Types d'ajustement actifs (crédit/débit) pour la saisie.
     */
    public function typesAjustement(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', BulletinPaie::class);

        return TypeAjustementResource::collection($this->listeTypesAjustement());
    }

    /**
     * Charge la profondeur nécessaire au détail d'un bulletin.
     */
    private function detail(BulletinPaie $bulletin): BulletinPaie
    {
        return $bulletin->load([
            'enseignant.user',
            'periode',
            'lignes.eleve.user',
            'lignes.matiere',
            'ajustements',
            'recaperePar',
        ]);
    }

    /**
     * Liste des types d'ajustement actifs, ordonnés crédit puis débit.
     */
    private function listeTypesAjustement()
    {
        return TypeAjustement::where('is_active', true)
            ->orderBy('direction')
            ->orderBy('libelle')
            ->get();
    }

    /**
     * Sérialise l'aperçu du service en réponse JSON plate (l'enseignant y est
     * un objet simple, l'ajustement porte direction/montant pour la matrice).
     *
     * @return array<int, array<string, mixed>>
     */
    private function normaliserApercu(array $apercu): array
    {
        return array_map(function (array $p) {
            $enseignant = $p['enseignant'];

            return [
                'enseignant' => [
                    'id' => (int) $enseignant->id,
                    'nom' => $enseignant->user
                        ? trim(
                            ($enseignant->user->prenom ?? '')
                            .' '.($enseignant->user->nom ?? '')
                        )
                        : null,
                ],
                'lignes' => $p['lignes'],
                'total_heures' => (float) $p['total_heures'],
                'montant_brut' => (int) $p['montant_brut'],
                'frais_suivi' => (int) $p['frais_suivi'],
                'ajustements' => $p['ajustements']->map(fn ($aj) => [
                    'type_ajustement_id' => (int) $aj['type_ajustement_id'],
                    'libelle' => $aj['libelle'],
                    'direction' => $aj['direction'],
                    'montant' => (int) $aj['montant'],
                ])->values(),
                'total_credits' => (int) $p['total_credits'],
                'total_debits' => (int) $p['total_debits'],
                'montant_net' => (int) $p['montant_net'],
            ];
        }, $apercu);
    }
}