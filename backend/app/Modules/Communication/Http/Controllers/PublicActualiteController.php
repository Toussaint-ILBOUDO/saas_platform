<?php

namespace App\Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Actualite;
use App\Modules\Communication\Http\Requests\StorePartageRequest;
use App\Modules\Communication\Http\Requests\StoreReactionRequest;
use App\Modules\Communication\Services\ActualiteService;
use Illuminate\Http\Request;

class PublicActualiteController extends Controller
{
    public function __construct(
        protected ActualiteService $service
    ) {}

    public function index()
    {
        $actualites = $this->service->listPublic(9);

        return view('publicpages.pages.actualites', compact('actualites'));
    }

    public function show(Actualite $actualite, Request $request)
    {
        abort_unless($actualite->estPubliee && $actualite->is_active, 404);

        if ($actualite->estInterne && $request->user()?->hasAnyRole(['eleve', 'enseignant', 'parent'])) {
            return redirect()->route('actualites.internes');
        }

        $this->service->recordView($actualite, $request);

        $reactionCounts = $this->service->reactionCounts($actualite);
        $currentReaction = $this->service->currentReaction($actualite, $request->ip());
        $recentes = $this->service->recentes(4)
            ->reject(fn (Actualite $a) => $a->id === $actualite->id);

        return view('publicpages.pages.actualite-show', compact(
            'actualite',
            'reactionCounts',
            'currentReaction',
            'recentes'
        ));
    }

    public function reaction(Actualite $actualite, StoreReactionRequest $request)
    {
        abort_unless($actualite->estPubliee && $actualite->is_active, 404);

        $result = $this->service->toggleReaction(
            $actualite,
            $request->validated()['reaction'],
            $request->ip(),
            $request->user()?->id
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->back()->with('success', 'Votre réaction a été enregistrée.');
    }

    public function partager(Actualite $actualite, StorePartageRequest $request)
    {
        abort_unless($actualite->estPubliee && $actualite->is_active, 404);

        $this->service->recordShare($actualite);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('success', 'Merci pour votre partage !');
    }
}
