<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DocumentBibliotheque;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use App\Modules\Bibliotheque\Http\Requests\FilterDocumentRequest;
use App\Modules\Bibliotheque\Http\Requests\StoreNoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicBibliothequeController extends Controller
{
    public function __construct(
        protected DocumentBibliothequeService $service
    ) {}

    public function index(FilterDocumentRequest $request)
    {
        $filters = $request->validated();
        $documents = $this->service->paginatePublic($filters);
        $typesDocument = $this->service->getTypesDocument();
        $classes = $this->service->getClasses();
        $matieres = $this->service->getMatieres();
        $periodes = $this->service->getPeriodes();
        $tags = $this->service->getTagsPopulaires();
        $stats = $this->service->getHomepageStats();

        $documentsRecents = $this->service->getDocumentsRecents(6);
        $documentsPopulaires = $this->service->getDocumentsPopulaires(6);
        $documentsMieuxNotes = $this->service->getDocumentsMieuxNotes(6);

        return view('publicpages.pages.bibliotheque', compact(
            'documents',
            'typesDocument',
            'classes',
            'matieres',
            'periodes',
            'tags',
            'stats',
            'documentsRecents',
            'documentsPopulaires',
            'documentsMieuxNotes',
            'filters'
        ));
    }

    public function show(string $slug)
    {
        $document = $this->service->getBySlug($slug);

        if (!($document->is_public && $document->statut === 'publie')) {
            $this->authorize('view', $document);
        }

        $this->service->logAccess($document, 'view');

        $documentsSimilaires = $this->service->getDocumentsSimilaires($document, 4);

        $userNote = null;
        $isFavori = false;
        if (Auth::check()) {
            $userNote = $document->notes()->where('user_id', Auth::id())->value('note');
            $isFavori = $document->favoris()->where('user_id', Auth::id())->exists();
        }

        return view('publicpages.pages.bibliotheque-show', compact(
            'document',
            'documentsSimilaires',
            'userNote',
            'isFavori'
        ));
    }

    public function view(DocumentBibliotheque $document)
    {
        if (!($document->is_public && $document->statut === 'publie')) {
            $this->authorize('view', $document);
        }

        $media = $document->getFirstMedia('document');
        if (!$media) {
            abort(404, 'Aucun fichier disponible.');
        }

        $path = $media->getPath();
        if (!file_exists($path)) {
            abort(404, 'Fichier introuvable sur le serveur.');
        }

        $this->service->logAccess($document, 'view');

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->slug . '.' . $media->extension . '"',
        ]);
    }

    public function download(DocumentBibliotheque $document)
    {
        if (!($document->is_public && $document->statut === 'publie')) {
            $this->authorize('download', $document);
        }

        $media = $document->getFirstMedia('document');
        if (!$media) {
            abort(404, 'Aucun fichier disponible.');
        }

        $path = $media->getPath();
        if (!file_exists($path)) {
            abort(404, 'Fichier introuvable sur le serveur.');
        }

        $this->service->logAccess($document, 'download');

        $fileName = $document->slug . '.' . $media->extension;

        return response()->download($path, $fileName);
    }

    public function note(DocumentBibliotheque $document, StoreNoteRequest $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vous devez être connecté pour noter.');
        }

        $this->authorize('rate', $document);

        $this->service->addNote($document, $request->validated()['note']);

        return redirect()->back()->with('success', 'Votre note a été enregistrée.');
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $hasFilters = !empty($query) || $request->hasAny(['type_document_id', 'classe_id', 'matiere_id', 'tag', 'sort']);

        if (!$hasFilters) {
            return redirect()->route('bibliothequepub.index');
        }

        $filters = array_filter([
            'search' => $query,
            'type_document_id' => $request->input('type_document_id'),
            'classe_id' => $request->input('classe_id'),
            'matiere_id' => $request->input('matiere_id'),
            'tag' => $request->input('tag'),
            'sort' => $request->input('sort'),
        ]);

        $documents = $this->service->paginatePublic($filters);
        $typesDocument = $this->service->getTypesDocument();
        $classes = $this->service->getClasses();
        $matieres = $this->service->getMatieres();
        $periodes = $this->service->getPeriodes();
        $tags = $this->service->getTagsPopulaires();
        $stats = $this->service->getHomepageStats();

        return view('publicpages.pages.bibliotheque', compact(
            'documents',
            'typesDocument',
            'classes',
            'matieres',
            'periodes',
            'tags',
            'stats',
            'filters'
        ));
    }
}
