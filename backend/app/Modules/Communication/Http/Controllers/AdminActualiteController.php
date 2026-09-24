<?php

namespace App\Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Actualite;
use App\Modules\Communication\Http\Requests\PublishActualiteRequest;
use App\Modules\Communication\Http\Requests\StoreActualiteRequest;
use App\Modules\Communication\Http\Requests\UpdateActualiteRequest;
use App\Modules\Communication\Services\ActualiteService;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Http\Request;

class AdminActualiteController extends Controller
{
    public function __construct(
        protected ActualiteService $service,
        protected NotificationDispatcher $dispatcher
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Actualite::class);

        $actualites = $this->service->listAdmin(
            $request->query('statut', ''),
            $request->query('search', '')
        );

        return view('communication.actualites.index', [
            'actualites' => $actualites,
            'filtreStatut' => $request->query('statut', ''),
            'recherche' => $request->query('search', ''),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Actualite::class);

        return view('communication.actualites.create');
    }

    public function store(StoreActualiteRequest $request)
    {
        $this->authorize('create', Actualite::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->service->create(
            $data,
            $request->user()->id,
            $request->file('image_principale'),
            $request->file('galerie') ?? [],
            $request->file('document')
        );

        return redirect()
            ->route('admin.actualites.index')
            ->with('success', 'Actualité créée avec succès.');
    }

    public function edit(Actualite $actualite)
    {
        $this->authorize('update', $actualite);

        return view('communication.actualites.edit', compact('actualite'));
    }

    public function update(UpdateActualiteRequest $request, Actualite $actualite)
    {
        $this->authorize('update', $actualite);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->service->update(
            $actualite,
            $data,
            $request->file('image_principale'),
            $request->file('galerie') ?? [],
            $request->file('document')
        );

        return redirect()
            ->route('admin.actualites.index')
            ->with('success', 'Actualité modifiée avec succès.');
    }

    public function publishForm(Actualite $actualite)
    {
        $this->authorize('update', $actualite);

        return view('communication.actualites.publish', compact('actualite'));
    }

    public function publish(PublishActualiteRequest $request, Actualite $actualite)
    {
        $this->authorize('update', $actualite);

        $destinataires = $request->validated('destinataires', []);
        $canal = $request->validated('canal', 'interne');

        $this->service->publish($actualite, $destinataires, $canal);

        $message = 'Actualité publiée avec succès.';

        if (! empty($destinataires)) {
            $this->dispatcher->actualitePublished($actualite, $destinataires, $canal);
            $message .= ' Notification envoyée.';
        } else {
            $message .= ' Aucune notification envoyée.';
        }

        return redirect()
            ->route('admin.actualites.index')
            ->with('success', $message);
    }

    public function unpublish(Actualite $actualite)
    {
        $this->authorize('update', $actualite);

        $this->service->unpublish($actualite);

        return redirect()
            ->route('admin.actualites.index')
            ->with('success', 'Actualité retirée de la publication.');
    }

    public function toggle(Actualite $actualite)
    {
        $this->authorize('update', $actualite);

        $this->service->toggleActive($actualite);

        return redirect()
            ->route('admin.actualites.index')
            ->with('success', $actualite->fresh()->is_active
                ? 'Actualité activée.'
                : 'Actualité désactivée.');
    }

    public function destroy(Actualite $actualite)
    {
        $this->authorize('delete', $actualite);

        $this->service->delete($actualite);

        return redirect()
            ->route('admin.actualites.index')
            ->with('success', 'Actualité supprimée.');
    }
}
