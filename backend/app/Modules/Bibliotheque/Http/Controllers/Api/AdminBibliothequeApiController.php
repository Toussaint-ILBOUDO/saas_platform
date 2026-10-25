<?php

namespace App\Modules\Bibliotheque\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentBibliotheque;
use App\Models\DocumentSignalement;
use App\Modules\Bibliotheque\Http\Requests\MajSignalementRequest;
use App\Modules\Bibliotheque\Http\Resources\DocumentBibliothequeResource;
use App\Modules\Bibliotheque\Http\Resources\SignalementResource;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API bibliothèque — modération (admin du cabinet, Angular).
 *
 * Remplace les vues Blade d'`AdminBibliothequeController` : liste filtrable
 * avec compteurs de tête, détail enrichi (signalements + commentaires),
 * valider / refuser / archiver / supprimer, file des signalements. Les
 * transitions de statut passent par le service partagé.
 */
class AdminBibliothequeApiController extends Controller
{
    public function __construct(
        private readonly DocumentBibliothequeService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $filters = array_filter([
            'search' => $request->filled('search') ? $request->string('search')->toString() : null,
            'statut' => $request->has('statut') && $request->query('statut') !== ''
                ? $request->string('statut')->toString()
                : null,
        ], fn ($v) => $v !== null);

        $documents = $this->service->paginateAdmin(
            $filters,
            (int) $request->integer('per_page', $request->integer('par_page', 15))
        );

        // Compteurs globaux dans `meta` : filtrer la liste ne doit pas faire
        // disparaître le nombre de documents en attente (même convention que
        // les demandes de cours).
        return DocumentBibliothequeResource::collection($documents)->additional([
            'meta' => [
                'stats' => $this->service->getAdminStats(),
            ],
        ]);
    }

    public function show(DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $document = $this->service->getById($documentBibliotheque->id);
        $document->load(['signalements.user', 'signalements.traitePar']);

        return DocumentBibliothequeResource::make($document)->toResponse(request());
    }

    public function valider(DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $this->service->valider($documentBibliotheque);

        return response()->json([
            'message' => 'Document validé et publié.',
            'data' => DocumentBibliothequeResource::make($documentBibliotheque->refresh()->load(['auteur', 'typeDocument', 'classe', 'matiere'])),
        ]);
    }

    public function refuser(DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $this->service->refuser($documentBibliotheque);

        return response()->json([
            'message' => 'Document refusé.',
            'data' => DocumentBibliothequeResource::make($documentBibliotheque->refresh()->load(['auteur', 'typeDocument', 'classe', 'matiere'])),
        ]);
    }

    public function archiver(DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $this->service->archiver($documentBibliotheque);

        return response()->json([
            'message' => 'Document archivé.',
            'data' => DocumentBibliothequeResource::make($documentBibliotheque->refresh()->load(['auteur', 'typeDocument', 'classe', 'matiere'])),
        ]);
    }

    public function destroy(DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $this->service->delete($documentBibliotheque);

        return response()->json(['message' => 'Document supprimé.']);
    }

    /**
     * File des signalements — filtres : statut, motif, période.
     */
    public function signalements(Request $request): AnonymousResourceCollection
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $signalements = DocumentSignalement::query()
            ->with(['user', 'traitePar', 'document.auteur'])
            ->when(
                $request->filled('statut'),
                fn ($q) => $q->where('statut', $request->string('statut')->toString())
            )
            ->when(
                $request->filled('motif'),
                fn ($q) => $q->where('motif', $request->string('motif')->toString())
            )
            ->when(
                $request->filled('date_debut'),
                fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_debut')->toString())
            )
            ->when(
                $request->filled('date_fin'),
                fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_fin')->toString())
            )
            ->latest()
            ->paginate((int) $request->integer('per_page', $request->integer('par_page', 15)))
            ->withQueryString();

        // Compteurs globaux (non filtrés) : la file reste lisible même quand
        // un filtre est actif — on voit toujours ce qui est en attente et
        // ce qui a déjà été traité.
        $compter = fn (string $statut) => DocumentSignalement::where('statut', $statut)->count();

        return SignalementResource::collection($signalements)->additional([
            'meta' => [
                'stats' => [
                    'total' => DocumentSignalement::count(),
                    'en_attente' => $compter('en_attente'),
                    'en_cours' => $compter('en_cours'),
                    'traite' => $compter('traite'),
                    'rejete' => $compter('rejete'),
                ],
            ],
        ]);
    }

    /**
     * Détail d'un signalement (examen avant décision).
     */
    public function showSignalement(DocumentSignalement $signalement): JsonResponse
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $signalement->load(['user', 'traitePar', 'document.auteur', 'document.typeDocument', 'document.matiere']);

        return SignalementResource::make($signalement)->toResponse(request());
    }

    /**
     * Mise à jour du statut / de la note d'un signalement.
     * Raccourci historique « traiter » conservé (statut → traite).
     */
    public function majSignalement(MajSignalementRequest $request, DocumentSignalement $signalement): JsonResponse
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $signalement = $this->service->majSignalement($signalement, $request->validated());

        return response()->json([
            'message' => 'Signalement mis à jour : '.DocumentSignalement::libelleStatut($signalement->statut).'.',
            'data' => SignalementResource::make($signalement),
        ]);
    }

    public function traiterSignalement(DocumentSignalement $signalement): JsonResponse
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $signalement = $this->service->majSignalement($signalement, ['statut' => 'traite']);

        return response()->json([
            'message' => 'Signalement traité.',
            'data' => SignalementResource::make($signalement),
        ]);
    }
}
