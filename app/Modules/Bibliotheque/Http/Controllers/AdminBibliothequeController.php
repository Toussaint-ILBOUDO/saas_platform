<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DocumentBibliotheque;
use App\Models\DocumentSignalement;
use App\Modules\Bibliotheque\Services\DocumentBibliothequeService;
use App\Modules\Bibliotheque\Http\Requests\FilterDocumentRequest;
use Illuminate\Http\Request;

class AdminBibliothequeController extends Controller
{
    public function __construct(
        protected DocumentBibliothequeService $service
    ) {}

    public function index(FilterDocumentRequest $request)
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $filters = $request->validated();
        $documents = $this->service->paginateAdmin($filters);
        $stats = $this->service->getAdminStats();

        return view('bibliotheque.admin-index', compact('documents', 'stats', 'filters'));
    }

    public function show(DocumentBibliotheque $document)
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $document->load([
            'auteur',
            'typeDocument',
            'classe',
            'matiere',
            'periodeRef',
            'tags',
            'commentaires.user',
            'commentaires.replies.user',
            'signalements.user',
            'notes.user',
            'media',
        ]);

        return view('bibliotheque.admin-show', compact('document'));
    }

    public function valider(DocumentBibliotheque $document)
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $this->service->valider($document);

        return redirect()
            ->route('admin.bibliotheque.show', $document)
            ->with('success', 'Document validé et publié.');
    }

    public function refuser(DocumentBibliotheque $document)
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $this->service->refuser($document);

        return redirect()
            ->route('admin.bibliotheque.show', $document)
            ->with('success', 'Document refusé.');
    }

    public function archiver(DocumentBibliotheque $document)
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $this->service->archiver($document);

        return redirect()
            ->route('admin.bibliotheque.show', $document)
            ->with('success', 'Document archivé.');
    }

    public function destroy(DocumentBibliotheque $document)
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $this->service->delete($document);

        return redirect()
            ->route('admin.bibliotheque.index')
            ->with('success', 'Document supprimé.');
    }

    public function signalements()
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $signalements = DocumentSignalement::with(['document', 'user', 'document.auteur'])
            ->where('statut', 'en_attente')
            ->latest()
            ->paginate(15);

        return view('bibliotheque.admin-signalements', compact('signalements'));
    }

    public function traiterSignalement(DocumentSignalement $signalement)
    {
        $this->authorize('moderate', DocumentBibliotheque::class);

        $signalement->update(['statut' => 'traite']);

        return redirect()->back()->with('success', 'Signalement traité.');
    }
}
