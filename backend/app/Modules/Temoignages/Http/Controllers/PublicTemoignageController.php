<?php

namespace App\Modules\Temoignages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Temoignage;
use App\Modules\Temoignages\Http\Requests\StoreTemoignageCommentaireRequest;
use App\Modules\Temoignages\Http\Requests\StoreTemoignageReactionRequest;
use App\Modules\Temoignages\Http\Requests\StoreTemoignageSignalementRequest;
use App\Modules\Temoignages\Services\TemoignageNotificationService;
use App\Modules\Temoignages\Services\TemoignageService;
use Illuminate\Http\Request;

class PublicTemoignageController extends Controller
{
    public function __construct(
        protected TemoignageService $service,
        protected TemoignageNotificationService $notifications
    ) {}

    public function index(Request $request)
    {
        $temoignages = $this->service->listeClassement(6);

        if ($request->boolean('fragment')) {
            return view('temoignages._cartes', [
                'temoignages' => $temoignages->items(),
            ]);
        }

        return view('publicpages.pages.temoignages', [
            'temoignages' => $temoignages,
            'total' => $temoignages->total(),
        ]);
    }

    public function show(Temoignage $temoignage)
    {
        abort_unless($temoignage->est_publie && $temoignage->is_active, 404);

        $temoignage->load(['auteur:id,prenom,nom']);

        $temoignage->setAttribute('score', $this->service->scoreOf($temoignage));

        $reactionCounts = $this->service->reactionCounts($temoignage);
        $currentReaction = $this->service->currentReaction($temoignage, request()->ip());
        $commentaires = $temoignage->commentaires()
            ->with(['user:id,prenom,nom', 'replies.user:id,prenom,nom'])
            ->whereNull('parent_id')
            ->latest()
            ->get();
        $autres = $this->service->autres(4, $temoignage->id);

        return view('publicpages.pages.temoignage-show', compact(
            'temoignage',
            'reactionCounts',
            'currentReaction',
            'commentaires',
            'autres'
        ));
    }

    public function reaction(Temoignage $temoignage, StoreTemoignageReactionRequest $request)
    {
        abort_unless($temoignage->est_publie && $temoignage->is_active, 404);

        $result = $this->service->toggleReaction(
            $temoignage,
            $request->validated()['reaction'],
            $request->ip(),
            $request->user()?->id
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->back()->with('success', 'Votre réaction a été enregistrée.');
    }

    public function commentaire(Temoignage $temoignage, StoreTemoignageCommentaireRequest $request)
    {
        abort_unless($temoignage->est_publie && $temoignage->is_active, 404);

        $commentaire = $this->service->commenter(
            $temoignage,
            $request->user(),
            $request->validated('contenu'),
            $request->validated('parent_id')
        );

        $this->notifications->commentaire($temoignage, $commentaire);

        return redirect()
            ->route('temoignages.show', $temoignage->slug)
            ->with('success', 'Votre commentaire a été publié.');
    }

    public function signalement(Temoignage $temoignage, StoreTemoignageSignalementRequest $request)
    {
        abort_unless($temoignage->est_publie && $temoignage->is_active, 404);

        $signalement = $this->service->signale(
            $temoignage,
            $request->user(),
            $request->validated('motif'),
            $request->validated('description')
        );

        $this->notifications->signale($signalement);

        return redirect()
            ->route('temoignages.show', $temoignage->slug)
            ->with('success', 'Merci, votre signalement a été pris en compte.');
    }
}
