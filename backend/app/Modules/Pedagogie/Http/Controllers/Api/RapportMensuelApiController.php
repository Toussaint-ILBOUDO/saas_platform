<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnseignantProfil;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Modules\Finance\Http\Resources\PeriodeComptableResource;
use App\Modules\Pedagogie\Http\Requests\ApercuRapportRequest;
use App\Modules\Pedagogie\Http\Requests\FilterRapportMensuelRequest;
use App\Modules\Pedagogie\Http\Requests\RejeterRapportMensuelRequest;
use App\Modules\Pedagogie\Http\Requests\StoreRapportMensuelApiRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateRapportMensuelApiRequest;
use App\Modules\Pedagogie\Http\Resources\RapportMensuelResource;
use App\Modules\Pedagogie\Services\RapportMensuelCalculator;
use App\Modules\Pedagogie\Services\RapportMensuelPdfService;
use App\Modules\Pedagogie\Services\RapportMensuelQueryService;
use App\Modules\Pedagogie\Services\RapportMensuelService;
use App\Modules\Pedagogie\Services\RapportModeleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API rapport mensuel enseignant (T7A.7).
 *
 * Deux périmètres distincts, une seule ressource :
 *
 *  - l'**enseignant** dépose, corrige, re-soumet, supprime et télécharge
 *    ses propres rapports (groupe `role:enseignant`, prefix `pedagogie`) ;
 *  - l'**administration** liste tout le cabinet et valide/rejette les
 *    rapports soumis (groupe `role:admin_cabinet`, prefix `admin`).
 *
 * Le rapport validé est la source de la facture parent et du bulletin de
 * paie (D-051) : c'est pourquoi la validation et le rejet sont des routes
 * explicites, jamais un `PUT` générique sur `statut` — la machine à états
 * reste dans le service, pas dans le frontend.
 */
class RapportMensuelApiController extends Controller
{
    public function __construct(
        private readonly RapportMensuelService $service,
        private readonly RapportMensuelQueryService $queries,
        private readonly RapportMensuelPdfService $pdfs,
        private readonly RapportModeleService $modele,
        private readonly RapportMensuelCalculator $calculator,
    ) {}

    /**
     * Liste d'administration : tous les rapports du cabinet.
     *
     * La prise du kPI par statut se fait dans la même requête de comptage
     * que la liste — jamais de requête supplémentaire par tuile.
     */
    public function indexAdmin(
        FilterRapportMensuelRequest $request
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', RapportMensuelEnseignant::class);

        $rapports = $this->queries->paginate(
            $request->validated(),
            (int) $request->integer('per_page', $request->integer('par_page', 20)),
        );

        $rapports->withQueryString();

        return RapportMensuelResource::collection($rapports);
    }

    /**
     * Liste de l'enseignant : ses seuls rapports.
     */
    public function index(
        FilterRapportMensuelRequest $request
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', RapportMensuelEnseignant::class);

        $profils = EnseignantProfil::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $profils) {
            // Pas de profil enseignant : liste vide, pas une erreur. L'écran
            // affiche un état « Aucun rapport » au lieu d'un 500.
            return RapportMensuelResource::collection(
                RapportMensuelEnseignant::query()->whereRaw('1 = 0')->paginate(1)
            );
        }

        $rapports = $this->queries->paginateForEnseignant(
            $profils->id,
            $request->validated(),
            (int) $request->integer('per_page', $request->integer('par_page', 20)),
        );

        return RapportMensuelResource::collection($rapports);
    }

    /**
     * Périodes ouvertes proposées au dépôt.
     *
     * Un enseignant a besoin de la liste des périodes pour déposer un rapport,
     * mais sans accéder à l'API admin de gestion des périodes (`/finance`).
     * D-051 : seules les périodes ouvertes peuvent accueillir un dépôt — le
     * serveur les filtre ici, le client n'a rien à retraduire.
     */
    public function periodes(): AnonymousResourceCollection
    {
        /**
         * Toutes les périodes, même clôturées : le filtre d'historique de
         * l'enseignant doit pouvoir cibler un ancien rapport. C'est au dépôt
         * que la règle D-051 (période ouverte) est tranchée, par le service et
         * le StoreRequest, jamais ici : une lecture ne gèle rien.
         */
        $periodes = PeriodeComptable::query()
            ->orderBy('date_debut')
            ->get();

        return PeriodeComptableResource::collection($periodes);
    }

    /**
     * Modèle de rapport pour le formulaire de l'enseignant : les sections et
     * éléments ACTIFS que l'administration a configurés. Les informations
     * générales et le bilan des activités ne figurent pas ici — ils sont
     * automatiques et affichés par l'écran.
     */
    public function modele(): JsonResponse
    {
        return response()->json([
            'data' => $this->modele->auFormat(actifsSeuls: true),
        ]);
    }

    /**
     * Aperçu avant dépôt : mêmes calculs que le rapport final (heures,
     * ventilation, bilan, séances), lus depuis le cahier de texte. Aucune
     * écriture — l'enseignant vérifie ce qui sera figé avant de soumettre.
     */
    public function apercu(ApercuRapportRequest $request): JsonResponse
    {
        $profil = EnseignantProfil::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $stats = $this->calculator->calculate(
            contratId: (int) $request->validated('contrat_cours_id'),
            enseignantId: (int) $profil->id,
            periodeId: (int) $request->validated('periode_id'),
        );

        return response()->json(['data' => [
            'volume_horaire' => $stats['volume_horaire'],
            'nombre_seances' => $stats['nombre_seances'],
            'bilan' => $stats['bilan'],
            'ventilation' => array_map(
                fn (array $ligne) => [
                    'matiere' => $ligne['matiere'],
                    'nombre_seances' => $ligne['nombre_seances'],
                    'nombre_heures' => $ligne['nombre_heures'],
                ],
                $stats['ventilation'],
            ),
            'cahiers' => $stats['cahiers']
                ->map(fn ($cahier) => [
                    'date_seance' => $cahier->date_seance?->toDateString(),
                    'matiere' => $cahier->affectation?->matiere?->nom,
                    'heure_debut' => $cahier->heure_debut,
                    'heure_fin' => $cahier->heure_fin,
                    'contenu' => $cahier->contenu_cours,
                ])
                ->values()
                ->all(),
        ]]);
    }

    /**
     * Dépôt d'un rapport. Le volume horaire et la ventilation viennent du
     * cahier de texte via le Calculator : le client n'envoie que les
     * réponses au modèle de rapport et le couple contrat/période.
     */
    public function store(StoreRapportMensuelApiRequest $request): JsonResponse
    {
        $profil = EnseignantProfil::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $rapport = $this->service->generate(
            $request->validated(),
            (int) $profil->id,
        );
        $rapport->avecSections = true;

        return RapportMensuelResource::make($rapport)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(RapportMensuelEnseignant $rapport): RapportMensuelResource
    {
        $this->authorize('view', $rapport);

        return RapportMensuelResource::make(
            $this->detail($rapport)
        );
    }

    /**
     * Correction d'un rapport encore soumis (D-051). Ventilation recalculée
     * par le service — jamais reçue du client.
     */
    public function update(
        UpdateRapportMensuelApiRequest $request,
        RapportMensuelEnseignant $rapport
    ): JsonResponse {
        $rapport = $this->service->corriger(
            $rapport,
            $request->validated(),
        );

        return RapportMensuelResource::make($this->detail($rapport))
            ->toResponse(request());
    }

    /**
     * Re-soumission d'un rapport rejeté.
     */
    public function resoumettre(
        UpdateRapportMensuelApiRequest $request,
        RapportMensuelEnseignant $rapport
    ): JsonResponse {
        $rapport = $this->service->resoumettre(
            $rapport,
            $request->validated(),
        );

        return RapportMensuelResource::make($this->detail($rapport))
            ->toResponse(request());
    }

    /**
     * Suppression — impossible sur un rapport validé (déjà facturé/payé).
     */
    public function destroy(
        RapportMensuelEnseignant $rapport
    ): JsonResponse {
        $this->authorize('supprimer', $rapport);

        $this->service->supprimer($rapport);

        return response()->json(['message' => 'Rapport supprimé.'], Response::HTTP_OK);
    }

    /**
     * Validation administrative. C'est elle qui débloque la facture parent et
     * le bulletin de paie : le service notifie l'enseignement via
     * `NotificationDispatcher::reportValidated()`.
     */
    public function valider(
        RapportMensuelEnseignant $rapport
    ): JsonResponse {
        $this->authorize('valider', $rapport);

        $rapport = $this->service->valider($rapport);

        return RapportMensuelResource::make($this->detail($rapport))
            ->toResponse(request());
    }

    /**
     * Rejet motivé — notifie l'enseignant (`reportRejected`).
     */
    public function rejeter(
        RejeterRapportMensuelRequest $request,
        RapportMensuelEnseignant $rapport
    ): JsonResponse {
        $this->authorize('rejeter', $rapport);

        $rapport = $this->service->rejeter(
            $rapport,
            $request->validated('motif_rejet'),
        );

        return RapportMensuelResource::make($this->detail($rapport))
            ->toResponse(request());
    }

    /**
     * PDF (stream) d'un rapport. L'accès passe par la policy : l'enseignant
     * propriétaire et les admins `rapport.view` peuvent seuls le voir.
     */
    public function pdf(
        RapportMensuelEnseignant $rapport
    ): Response {
        $this->authorize('view', $rapport);

        return $this->pdfs->stream($this->detail($rapport));
    }

    /**
     * Charge la profondeur nécessaire au détail d'un rapport.
     */
    private function detail(RapportMensuelEnseignant $rapport): RapportMensuelEnseignant
    {
        $rapport->avecSections = true;

        return $rapport->load([
            'enseignant.user',
            'contratCours.eleve.user',
            'contratCours.eleve.classe',
            'contratCours.typeCours',
            'periode',
            'lignes.affectation.matiere',
            'valideur',
        ]);
    }
}