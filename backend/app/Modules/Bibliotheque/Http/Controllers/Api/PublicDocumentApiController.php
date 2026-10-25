<?php

namespace App\Modules\Bibliotheque\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DocumentPublicResource;
use App\Models\DocumentReaction;
use App\Models\DocumentSignalement;
use App\Modules\Bibliotheque\Http\Requests\SignalerDocumentRequest;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicDocumentApiController extends Controller
{
    public function __construct(protected DocumentBibliothequeService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 12), 50);

        $documents = $this->service->paginatePublic([
            'search' => $request->input('search'),
            'type_document_id' => $request->input('type_document_id'),
            'classe_id' => $request->input('classe_id'),
            'matiere_id' => $request->input('matiere_id'),
            'periode_id' => $request->input('periode_id'),
            'tag' => $request->input('tag'),
            'sort' => $request->input('sort'),
        ], $perPage);

        return response()->json([
            'data' => DocumentPublicResource::collection($documents),
            'meta' => [
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
            ],
        ]);
    }

    /**
     * Référentiels de la bibliothèque publique (filtres de la page) :
     * types de documents, matières et classes réellement présents.
     */
    public function referentiels(): JsonResponse
    {
        return response()->json([
            'data' => [
                'types' => $this->service->getTypesDocument(),
                'matieres' => $this->service->getMatieres(),
                'classes' => $this->service->getClasses(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $document = $this->service->getBySlug($slug);

        abort_unless($document->is_public && $document->statut === 'publie', 404);

        // Ouverture de la fiche : une consultation comptabilisée (la
        // déduplication session interne évite les doubles comptes).
        $this->service->logAccess($document, 'view');

        return response()->json(new DocumentPublicResource($document));
    }

    /**
     * Fichier servi EN LIGNE pour le lecteur intégré (Content-Disposition
     * inline) : le téléchargement n'est déclenché que par l'utilisateur.
     * La collection média étant sur disque privé, aucune URL `/storage`
     * n'est exposée.
     */
    public function fichier(string $slug): BinaryFileResponse
    {
        $document = $this->service->getBySlug($slug);

        abort_unless($document->is_public && $document->statut === 'publie', 404);

        $media = $document->getFirstMedia('document');
        abort_unless($media && file_exists($media->getPath()), 404, 'Aucun fichier disponible.');

        $this->service->logAccess($document, 'view');

        return response()
            ->file($media->getPath(), [
                'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            ]);
    }

    /**
     * Téléchargement d'un document public — la collection média étant sur un
     * disque privé, le binaire passe par une route contrôlée plutôt qu'une
     * URL `/storage`. Compte le téléchargement comme le fait le parcours Blade.
     */
    public function telecharger(string $slug): BinaryFileResponse
    {
        $document = $this->service->getBySlug($slug);

        abort_unless($document->is_public && $document->statut === 'publie', 404);

        $media = $document->getFirstMedia('document');
        abort_unless($media && file_exists($media->getPath()), 404, 'Aucun fichier disponible.');

        $this->service->logAccess($document, 'download');

        return response()->download(
            $media->getPath(),
            $document->slug . '.' . $media->extension
        );
    }

    /**
     * Réaction d'une identité (membre connecté ou visiteur de session).
     * Le compteur renvoyé est recalculé côté serveur : aucune écriture
     * n'est jamais créditée d'après le seul état du navigateur.
     */
    public function reactions(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'reaction' => ['required', 'string', 'in:'.implode(',', DocumentReaction::VALIDES)],
        ], [
            'reaction.required' => 'La réaction est obligatoire.',
            'reaction.in' => 'Cette réaction n\'est pas valide.',
        ]);

        $document = $this->service->getBySlug($slug);

        abort_unless($document->is_public && $document->statut === 'publie', 404);

        $resultat = $this->service->reagir($document, $request->input('reaction'));

        return response()->json([
            'message' => $resultat['reaction'] === null
                ? 'Réaction retirée.'
                : 'Réaction enregistrée.',
            ...$resultat,
        ]);
    }

    /**
     * Ajout / retrait d'un favori depuis la bibliothèque publique.
     * Réservé aux membres connectés : un visiteur est redirigé vers la
     * connexion (401) — les favoris sont personnels et liés au compte.
     */
    public function favori(string $slug): JsonResponse
    {
        $document = $this->service->getBySlug($slug);

        abort_unless($document->is_public && $document->statut === 'publie', 404);

        $ajoute = $this->service->toggleFavori($document);

        return response()->json([
            'message' => $ajoute ? 'Document ajouté aux favoris.' : 'Document retiré des favoris.',
            'is_favori' => $ajoute,
            'nombre_favoris' => (int) $document->fresh()->nombre_favoris,
        ]);
    }

    /**
     * Signalement public d'un document — ouvert aux visiteurs anonymes.
     * Protections : throttle + anti-abus IP (24 h) et membre (1 en cours).
     * Un signalement n'entraîne jamais le retrait automatique du document.
     */
    public function signaler(SignalerDocumentRequest $request, string $slug): JsonResponse
    {
        $document = $this->service->getBySlug($slug);

        abort_unless($document->is_public && $document->statut === 'publie', 404);

        $signalement = $this->service->addSignalement($document, $request->validated());

        return response()->json([
            'message' => 'Signalement enregistré. Un administrateur l\'examinera. Merci de votre vigilance.',
            'data' => [
                'id' => $signalement->id,
                'motif' => $signalement->motif,
                'motif_libelle' => DocumentSignalement::libelleMotif($signalement->motif),
                'statut' => $signalement->statut,
            ],
        ], 201);
    }
}
