<?php

namespace App\Modules\Bibliotheque\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentBibliotheque;
use App\Modules\Bibliotheque\Http\Requests\FilterDocumentRequest;
use App\Modules\Bibliotheque\Http\Requests\StoreCommentaireRequest;
use App\Modules\Bibliotheque\Http\Requests\StoreDocumentRequest;
use App\Modules\Bibliotheque\Http\Requests\StoreNoteRequest;
use App\Modules\Bibliotheque\Http\Requests\StoreSignalementRequest;
use App\Modules\Bibliotheque\Http\Requests\UpdateDocumentRequest;
use App\Modules\Bibliotheque\Http\Resources\DocumentBibliothequeResource;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * API bibliothèque — espace membre (Angular).
 *
 * Remplace les vues Blade privées de `BibliothequeController` pour l'espace
 * Angular : dashboard, mes documents, CRUD (avec upload FormData), favoris,
 * commentaires, notes, signalements, téléchargement. Toute la logique métier
 * vit dans `DocumentBibliothequeService`, partagé avec le parcours Blade —
 * les règles (slug unique, compteur de favoris) n'existent donc qu'une fois.
 *
 * Le fichier est sur un disque privé : aucune URL n'est exposée. La lecture
 * et le téléchargement passent par `fichier` / `telecharger`, qui appliquent
 * la policy et incrémentent les compteurs.
 */
class BibliothequeApiController extends Controller
{
    public function __construct(
        private readonly DocumentBibliothequeService $service
    ) {}

    public function dashboard(): JsonResponse
    {
        $stats = $this->service->getAuteurStats((int) Auth::id());

        // Les derniers commentaires embarquent leur document : on le réduit
        // à l'essentiel pour que le tableau de bord reste léger.
        $stats['derniers_commentaires'] = collect($stats['derniers_commentaires'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'contenu' => $c->contenu,
                'created_at' => $c->created_at?->toIso8601String(),
                'user' => $c->user ? [
                    'id' => $c->user->id,
                    'nom' => $c->user->nom,
                    'prenom' => $c->user->prenom,
                ] : null,
                'document' => $c->document ? [
                    'id' => $c->document->id,
                    'titre' => $c->document->titre,
                    'slug' => $c->document->slug,
                ] : null,
            ])
            ->values();

        $stats['note_moyenne'] = round((float) $stats['note_moyenne'], 2);

        return response()->json(['data' => $stats]);
    }

    public function mesDocuments(FilterDocumentRequest $request): AnonymousResourceCollection
    {
        $filters = array_filter(
            $request->validated(),
            fn ($v) => $v !== null && $v !== ''
        );

        $documents = $this->service->paginateForUser(
            (int) Auth::id(),
            $filters,
            (int) $request->integer('per_page', $request->integer('par_page', 15))
        );

        return DocumentBibliothequeResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', DocumentBibliotheque::class);

        $document = $this->service->create(
            $request->validated(),
            $request->file('fichier')
        );

        return DocumentBibliothequeResource::make($this->service->getById($document->id))
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('view', $documentBibliotheque);

        $this->service->logAccess($documentBibliotheque, 'view');

        return DocumentBibliothequeResource::make(
            $this->service->getById($documentBibliotheque->id)
        )->toResponse(request());
    }

    public function update(UpdateDocumentRequest $request, DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('update', $documentBibliotheque);

        $data = $request->validated();

        // Un document publié ne repasse brouillon/en_attente que par les
        // actions de modération dédiées : un simple enregistrement du formulaire
        // ne doit pas pouvoir dépublier (garde identique au parcours Blade).
        if (
            ($data['statut'] ?? null) !== 'en_attente'
            || $documentBibliotheque->statut !== 'brouillon'
        ) {
            unset($data['statut']);
        }

        $document = $this->service->update(
            $documentBibliotheque,
            $data,
            $request->file('fichier')
        );

        return DocumentBibliothequeResource::make(
            $this->service->getById($document->id)
        )->toResponse(request());
    }

    public function destroy(DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('delete', $documentBibliotheque);

        $this->service->delete($documentBibliotheque);

        return response()->json(['message' => 'Document supprimé.']);
    }

    public function favoris(FilterDocumentRequest $request): AnonymousResourceCollection
    {
        $filters = array_filter(
            $request->validated(),
            fn ($v) => $v !== null && $v !== ''
        );

        $documents = $this->service->getFavoris(
            (int) Auth::id(),
            $filters,
            (int) $request->integer('per_page', $request->integer('par_page', 15))
        );

        return DocumentBibliothequeResource::collection($documents);
    }

    public function toggleFavori(DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('favorite', $documentBibliotheque);

        $ajoute = $this->service->toggleFavori($documentBibliotheque);

        return response()->json([
            'message' => $ajoute
                ? 'Document ajouté aux favoris.'
                : 'Document retiré des favoris.',
            'data' => [
                'is_favori' => $ajoute,
                'nombre_favoris' => (int) $documentBibliotheque->fresh()->nombre_favoris,
            ],
        ]);
    }

    public function commenter(StoreCommentaireRequest $request, DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('comment', $documentBibliotheque);

        $commentaire = $this->service->addCommentaire(
            $documentBibliotheque,
            $request->validated()
        );

        return response()->json([
            'message' => 'Commentaire ajouté.',
            'data' => [
                'id' => $commentaire->id,
                'contenu' => $commentaire->contenu,
                'parent_id' => $commentaire->parent_id,
                'created_at' => $commentaire->created_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function noter(StoreNoteRequest $request, DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('rate', $documentBibliotheque);

        $this->service->addNote($documentBibliotheque, (int) $request->validated()['note']);
        $documentBibliotheque->refresh();

        return response()->json([
            'message' => 'Votre note a été enregistrée.',
            'data' => [
                'ma_note' => (int) $request->validated()['note'],
                'note_moyenne' => (float) $documentBibliotheque->note_moyenne,
                'nb_notes' => (int) $documentBibliotheque->nb_notes,
            ],
        ]);
    }

    public function signaler(StoreSignalementRequest $request, DocumentBibliotheque $documentBibliotheque): JsonResponse
    {
        $this->authorize('report', $documentBibliotheque);

        $this->service->addSignalement($documentBibliotheque, $request->validated());

        return response()->json([
            'message' => 'Signalement envoyé. Merci.',
        ], Response::HTTP_CREATED);
    }

    public function fichier(DocumentBibliotheque $documentBibliotheque): BinaryFileResponse
    {
        $this->authorize('view', $documentBibliotheque);

        $media = $documentBibliotheque->getFirstMedia('document');
        abort_unless($media && file_exists($media->getPath()), 404, 'Aucun fichier disponible.');

        $this->service->logAccess($documentBibliotheque, 'view');

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $documentBibliotheque->slug . '.' . $media->extension . '"',
        ]);
    }

    public function telecharger(DocumentBibliotheque $documentBibliotheque): BinaryFileResponse
    {
        $this->authorize('download', $documentBibliotheque);

        $media = $documentBibliotheque->getFirstMedia('document');
        abort_unless($media && file_exists($media->getPath()), 404, 'Aucun fichier disponible.');

        $this->service->logAccess($documentBibliotheque, 'download');

        return response()->download(
            $media->getPath(),
            $documentBibliotheque->slug . '.' . $media->extension
        );
    }

    /**
     * Référentiels du formulaire document (types, classes, matières, périodes).
     *
     * Un seul appel pour peupler les quatre sélecteurs du formulaire — les
     * pages espace n'ont pas accès aux routes admin de référentiels.
     */
    public function referentiels(): JsonResponse
    {
        return response()->json([
            'data' => [
                'types' => $this->service->getTypesDocument(),
                'classes' => $this->service->getClasses(),
                'matieres' => $this->service->getMatieres(),
                'periodes' => $this->service->getPeriodes(),
            ],
        ]);
    }
}
