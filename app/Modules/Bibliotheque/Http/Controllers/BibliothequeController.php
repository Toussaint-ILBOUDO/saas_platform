<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DocumentBibliotheque;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use App\Modules\Bibliotheque\Http\Requests\StoreDocumentRequest;
use App\Modules\Bibliotheque\Http\Requests\UpdateDocumentRequest;
use App\Modules\Bibliotheque\Http\Requests\FilterDocumentRequest;
use App\Modules\Bibliotheque\Http\Requests\StoreCommentaireRequest;
use App\Modules\Bibliotheque\Http\Requests\StoreSignalementRequest;
use Illuminate\Support\Facades\Auth;

class BibliothequeController extends Controller
{
    public function __construct(
        protected DocumentBibliothequeService $service
    ) {}

    public function dashboard()
    {
        $stats = $this->service->getAuteurStats(Auth::id());

        return view('bibliotheque.dashboard', compact('stats'));
    }

    public function index(FilterDocumentRequest $request)
    {
        $filters = $request->validated();
        $documents = $this->service->paginateForUser(Auth::id(), $filters);

        return view('bibliotheque.index', compact('documents', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', DocumentBibliotheque::class);

        $typesDocument = $this->service->getTypesDocument();
        $classes = $this->service->getClasses();
        $matieres = $this->service->getMatieres();
        $periodes = $this->service->getPeriodes();

        return view('bibliotheque.create', compact('typesDocument', 'classes', 'matieres', 'periodes'));
    }

    public function store(StoreDocumentRequest $request)
    {
        $this->authorize('create', DocumentBibliotheque::class);

        $document = $this->service->create(
            $request->validated(),
            $request->file('fichier')
        );

        if ($request->input('statut') === 'en_attente') {
            $document->update(['statut' => 'en_attente']);
        }

        return redirect()
            ->route('bibliotheque.show', $document)
            ->with('success', 'Document publié avec succès.');
    }

    public function show(DocumentBibliotheque $document)
    {
        $this->authorize('view', $document);

        $document->load(['auteur', 'typeDocument', 'classe', 'matiere', 'tags', 'commentaires.user', 'commentaires.replies.user', 'notes']);

        $this->service->logAccess($document, 'view');

        $userNote = null;
        $isFavori = false;
        if (Auth::check()) {
            $userNote = $document->notes()->where('user_id', Auth::id())->value('note');
            $isFavori = $document->favoris()->where('user_id', Auth::id())->exists();
        }

        return view('bibliotheque.show', compact('document', 'userNote', 'isFavori'));
    }

    public function edit(DocumentBibliotheque $document)
    {
        $this->authorize('update', $document);

        $document->load('tags');
        $typesDocument = $this->service->getTypesDocument();
        $classes = $this->service->getClasses();
        $matieres = $this->service->getMatieres();
        $periodes = $this->service->getPeriodes();

        return view('bibliotheque.edit', compact('document', 'typesDocument', 'classes', 'matieres', 'periodes'));
    }

    public function update(UpdateDocumentRequest $request, DocumentBibliotheque $document)
    {
        $this->authorize('update', $document);

        $document = $this->service->update(
            $document,
            $request->validated(),
            $request->file('fichier')
        );

        if ($request->input('statut') === 'en_attente' && $document->statut === 'brouillon') {
            $document->update(['statut' => 'en_attente']);
        }

        return redirect()
            ->route('bibliotheque.show', $document)
            ->with('success', 'Document mis à jour avec succès.');
    }

    public function destroy(DocumentBibliotheque $document)
    {
        $this->authorize('delete', $document);

        $this->service->delete($document);

        return redirect()
            ->route('bibliotheque.index')
            ->with('success', 'Document supprimé avec succès.');
    }

    public function toggleFavori(DocumentBibliotheque $document)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vous devez être connecté.');
        }

        $this->authorize('favorite', $document);

        $ajoute = $this->service->toggleFavori($document);

        return redirect()->back()->with(
            'success',
            $ajoute ? 'Document ajouté aux favoris.' : 'Document retiré des favoris.'
        );
    }

    public function toggleFavoriAjax(DocumentBibliotheque $document)
    {
        $this->authorize('favorite', $document);

        $ajoute = $this->service->toggleFavori($document);

        return response()->json([
            'is_favori' => $ajoute,
            'nombre_favoris' => $document->fresh()->nombre_favoris,
        ]);
    }

    public function favoris(FilterDocumentRequest $request)
    {
        $filters = $request->validated();
        $documents = $this->service->getFavoris(Auth::id(), $filters);
        $typesDocument = $this->service->getTypesDocument();
        $classes = $this->service->getClasses();
        $matieres = $this->service->getMatieres();

        return view('bibliotheque.favoris', compact('documents', 'typesDocument', 'classes', 'matieres'));
    }

    public function comment(DocumentBibliotheque $document, StoreCommentaireRequest $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vous devez être connecté pour commenter.');
        }

        $this->authorize('comment', $document);

        $this->service->addCommentaire($document, $request->validated());

        return redirect()->back()->with('success', 'Commentaire ajouté.');
    }

    public function report(DocumentBibliotheque $document, StoreSignalementRequest $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vous devez être connecté.');
        }

        $this->authorize('report', $document);

        $this->service->addSignalement($document, $request->validated());

        return redirect()->back()->with('success', 'Signalement envoyé. Merci.');
    }
}
