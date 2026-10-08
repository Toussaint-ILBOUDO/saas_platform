<?php

namespace App\Modules\Finance\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Models\BulletinPaie;
use App\Models\PeriodeComptable;
use App\Modules\Finance\Services\BulletinPaiePdfService;
use App\Modules\Finance\Services\BulletinPaieValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BulletinPaieController extends Controller
{
    public function __construct(
        private BulletinPaiePdfService $pdfService,
        private BulletinPaieValidationService $validationService,
    ) {}

    /**
     * Liste des bulletins de paie de l'enseignant connecté.
     */
    public function index(Request $request): View
    {
        $enseignantId = auth()->user()->enseignantProfil->id;

        $periodes = PeriodeComptable::query()
            ->orderByDesc('date_debut')
            ->get();

        $periodeSelectionnee = null;

        if ($request->filled('periode_id')) {
            $periodeSelectionnee = $periodes->firstWhere(
                'id',
                $request->periode_id
            );
        }

        $bulletins = BulletinPaie::query()
            ->with(['periode'])
            ->where('enseignant_id', $enseignantId)
            ->when(
                $periodeSelectionnee,
                fn($q) => $q->where('periode_id', $periodeSelectionnee->id)
            )
            ->when(
                $request->statut,
                fn($q, $s) => $q->where('statut', $s)
            )
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('finances.mes-bulletins.index', compact(
            'bulletins',
            'periodes',
            'periodeSelectionnee'
        ));
    }

    /**
     * Detail d'un bulletin (enseignant).
     */
    public function show(BulletinPaie $bulletin): View
    {
        $this->authorize('view', $bulletin);

        $bulletin->load([
            'enseignant.user',
            'periode',
            'lignes.eleve.user',
            'lignes.matiere',
            'lignes.affectation',
            'ajustements',
        ]);

        $totalPrimes = $bulletin->ajustements
            ->where('type', 'prime')
            ->sum('montant');

        $totalRetenues = $bulletin->ajustements
            ->where('type', 'retenue')
            ->sum('montant');

        return view('finances.mes-bulletins.show', compact(
            'bulletin',
            'totalPrimes',
            'totalRetenues'
        ));
    }

    /**
     * PDF du bulletin (stream dans le navigateur).
     */
    public function pdf(BulletinPaie $bulletin)
    {
        $this->authorize('view', $bulletin);

        return $this->pdfService->stream($bulletin);
    }

    /**
     * PDF du bulletin (telechargement).
     */
    public function pdfDownload(BulletinPaie $bulletin)
    {
        $this->authorize('view', $bulletin);

        return $this->pdfService->download($bulletin);
    }

    /**
     * Marquer le bulletin comme consulte.
     */
    public function marquerConsulte(BulletinPaie $bulletin): RedirectResponse
    {
        $this->authorize('update', $bulletin);

        $this->validationService->consulter($bulletin);

        return redirect()
            ->route('mes-bulletins.show', $bulletin)
            ->with('success', 'Bulletin marque comme consulte.');
    }

    /**
     * Valider le bulletin.
     */
    public function valider(BulletinPaie $bulletin): RedirectResponse
    {
        $this->authorize('update', $bulletin);

        $this->validationService->valider($bulletin);

        return redirect()
            ->route('mes-bulletins.show', $bulletin)
            ->with('success', 'Bulletin valide.');
    }

    /**
     * Vue contestation.
     */
    public function contesterForm(BulletinPaie $bulletin): View
    {
        $this->authorize('update', $bulletin);

        $bulletin->load(['enseignant.user', 'periode']);

        return view(
            'finances.mes-bulletins.contester',
            compact('bulletin')
        );
    }

    /**
     * Soumettre une contestation (D-052).
     *
     * Motif structuré : une catégorie imposée (liste fermée du modèle) et un
     * détail libre d'au moins 20 caractères. Le service revalide les deux —
     * l'API et le web passent par le même garde.
     */
    public function contester(
        Request $request,
        BulletinPaie $bulletin
    ): RedirectResponse {
        $this->authorize('update', $bulletin);

        $valides = $request->validate([
            'motif_contestation' => [
                'required',
                Rule::in(array_keys(BulletinPaie::MOTIFS_CONTESTATION)),
            ],
            'commentaire_enseignant' => 'required|string|max:1000',
        ], [
            'motif_contestation.required' =>
                'Indiquez ce que vous contestez sur ce bulletin.',
            'motif_contestation.in' =>
                'Motif de contestation inconnu.',
            'commentaire_enseignant.required' =>
                'Décrivez la contestation : l\'administration a besoin de savoir '
                . 'ce qui est contesté pour vous répondre.',
        ]);

        $this->validationService->contester(
            $bulletin,
            $valides['motif_contestation'],
            $valides['commentaire_enseignant'],
        );

        return redirect()
            ->route('mes-bulletins.show', $bulletin)
            ->with('success', 'Contestation soumise : l\'administration est notifiée.');
    }

    /**
     * D-052 — L'enseignant confirme avoir reçu son paiement.
     *
     * Le versement se fait hors plateforme : c'est le seul moment où
     * l'enseignant atteste que l'argent lui est parvenu, ce qui clôt le
     * cycle de paie et previent l'administration.
     */
    public function confirmerReception(
        BulletinPaie $bulletin
    ): RedirectResponse {
        $this->authorize('update', $bulletin);

        $this->validationService->confirmerReception($bulletin);

        return redirect()
            ->route('mes-bulletins.show', $bulletin)
            ->with('success', 'Réception du paiement confirmée. Merci.');
    }
}
