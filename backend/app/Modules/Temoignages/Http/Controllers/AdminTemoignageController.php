<?php

namespace App\Modules\Temoignages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Temoignage;
use App\Models\TemoignageSignalement;
use App\Modules\Temoignages\Services\TemoignageNotificationService;
use App\Modules\Temoignages\Services\TemoignageService;
use Illuminate\Http\Request;

class AdminTemoignageController extends Controller
{
    public function __construct(
        protected TemoignageService $service,
        protected TemoignageNotificationService $notifications
    ) {}

    public function index(Request $request)
    {
        $this->authorize('moderate', Temoignage::class);

        $temoignages = $this->service->listeAdmin(
            $request->query('statut', ''),
            $request->query('search', '')
        );

        return view('temoignages.index', [
            'temoignages' => $temoignages,
            'filtreStatut' => $request->query('statut', ''),
            'recherche' => $request->query('search', ''),
        ]);
    }

    public function show(Temoignage $temoignage)
    {
        $this->authorize('moderate', Temoignage::class);

        $temoignage->load([
            'auteur:id,prenom,nom,email',
            'signalements.user:id,prenom,nom',
            'commentaires.user:id,prenom,nom',
        ]);

        $temoignage->setAttribute('score', $this->service->scoreOf($temoignage));

        $reactionCounts = $this->service->reactionCounts($temoignage);

        return view('temoignages.show', compact('temoignage', 'reactionCounts'));
    }

    public function masquer(Temoignage $temoignage)
    {
        $this->authorize('moderate', Temoignage::class);

        $this->service->masquer($temoignage);
        $this->notifications->masque($temoignage);

        return redirect()
            ->route('admin.temoignages.show', $temoignage)
            ->with('success', 'Témoignage masqué et son auteur a été averti.');
    }

    public function restaurer(Temoignage $temoignage)
    {
        $this->authorize('moderate', Temoignage::class);

        $this->service->restaurer($temoignage);

        return redirect()
            ->route('admin.temoignages.show', $temoignage)
            ->with('success', 'Témoignage restauré et de nouveau visible sur le site.');
    }

    public function destroy(Temoignage $temoignage)
    {
        $this->authorize('moderate', Temoignage::class);

        $this->service->delete($temoignage);
        $this->notifications->supprime($temoignage);

        return redirect()
            ->route('admin.temoignages.index')
            ->with('success', 'Témoignage supprimé et son auteur a été averti.');
    }

    public function signalements(Request $request)
    {
        $this->authorize('moderate', Temoignage::class);

        $signalements = $this->service->signalements(
            $request->query('statut', '')
        );

        return view('temoignages.signalements', [
            'signalements' => $signalements,
            'filtreStatut' => $request->query('statut', ''),
        ]);
    }

    public function traiterSignalement(Request $request, TemoignageSignalement $signalement)
    {
        $this->authorize('moderate', Temoignage::class);

        $request->validate([
            'action' => ['required', 'in:traite,rejete'],
        ]);

        $action = $request->input('action');

        $this->service->traiterSignalement($signalement, $action, $action === 'traite');

        $message = $action === 'traite'
            ? 'Signalement traité : le témoignage a été masqué.'
            : 'Signalement rejeté : le témoignage reste en ligne.';

        return redirect()
            ->route('admin.temoignages.signalements')
            ->with('success', $message);
    }
}
