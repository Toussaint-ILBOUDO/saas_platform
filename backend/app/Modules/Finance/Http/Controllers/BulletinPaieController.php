<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BulletinPaie;
use App\Models\BulletinPaieAjustement;
use App\Models\PeriodeComptable;
use App\Models\TypeAjustement;
use App\Modules\Finance\Http\Requests\StoreAjustementRequest;
use App\Modules\Finance\Http\Requests\PayerBulletinRequest;
use App\Modules\Finance\Services\BulletinPaieAdjustmentService;
use App\Modules\Finance\Services\BulletinPaieCalculationService;
use App\Modules\Finance\Services\BulletinPaieGenerationService;
use App\Modules\Finance\Services\BulletinPaiePdfService;
use App\Modules\Finance\Services\BulletinPaieValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BulletinPaieController extends Controller
{
    public function __construct(
        private BulletinPaieGenerationService $generationService,
        private BulletinPaieCalculationService $calculator,
        private BulletinPaieAdjustmentService $adjustmentService,
        private BulletinPaieValidationService $validationService,
        private BulletinPaiePdfService $pdfService,
    ) {}

    /**
     * Liste des bulletins de paie.
     */
    public function index(Request $request): View
    {
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
            ->with(['enseignant.user', 'periode'])
            ->when(
                $periodeSelectionnee,
                fn($q) => $q->where(
                    'periode_id',
                    $periodeSelectionnee->id
                )
            )
            ->when(
                $request->statut,
                fn($q, $s) => $q->where('statut', $s)
            )
            ->when(
                $request->search,
                fn($q, $s) => $q->whereHas(
                    'enseignant.user',
                    function ($sub) use ($s) {
                        $sub->where('nom', 'like', "%{$s}%")
                            ->orWhere('prenom', 'like', "%{$s}%");
                    }
                )
            )
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('finances.bulletins-paie.index', compact(
            'bulletins',
            'periodes',
            'periodeSelectionnee'
        ));
    }

    /**
     * Apercu initial : chargement GET avec valeurs par defaut.
     */
    public function preview(Request $request): View
    {
        $periodes = $this->getPeriodesOuvertes();
        $periodeSelectionnee = null;
        $preview = [];
        $totalEnseignants = 0;
        $totalHeures = 0;
        $totalMontant = 0;

        $typesAjustement = TypeAjustement::where('is_active', true)
            ->orderBy('direction')
            ->orderBy('libelle')
            ->get();

        if ($request->filled('periode_id')) {
            $periodeSelectionnee = $periodes->firstWhere(
                'id',
                $request->periode_id
            );

            if ($periodeSelectionnee) {
                $preview = $this->generationService->preview(
                    $periodeSelectionnee->id
                );

                $totalEnseignants = count($preview);
                $totalHeures = array_sum(
                    array_map(fn($p) => $p['total_heures'], $preview)
                );
                $totalMontant = array_sum(
                    array_map(fn($p) => $p['montant_net'], $preview)
                );
            }
        }

        return view('finances.bulletins-paie.preview', compact(
            'periodes',
            'periodeSelectionnee',
            'preview',
            'typesAjustement',
            'totalEnseignants',
            'totalHeures',
            'totalMontant'
        ));
    }

    /**
     * Actualisation POST : recalcule l'apercu avec les valeurs soumises.
     */
    public function actualiser(Request $request): View
    {
        $request->validate([
            'periode_id' => 'required|exists:periode_comptables,id',
        ]);

        $periodes = $this->getPeriodesOuvertes();
        $periodeSelectionnee = $periodes->firstWhere(
            'id',
            $request->periode_id
        );

        $typesAjustement = TypeAjustement::where('is_active', true)
            ->orderBy('direction')
            ->orderBy('libelle')
            ->get();

        $fraisSuivis = $this->castIntArray($request->frais_suivi ?? []);
        $ajustements = $this->castAjustementsArray($request->ajustements ?? []);

        $preview = $this->generationService->preview(
            $periodeSelectionnee->id,
            $fraisSuivis,
            $ajustements
        );

        $totalEnseignants = count($preview);
        $totalHeures = array_sum(
            array_map(fn($p) => $p['total_heures'], $preview)
        );
        $totalMontant = array_sum(
            array_map(fn($p) => $p['montant_net'], $preview)
        );

        return view('finances.bulletins-paie.preview', compact(
            'periodes',
            'periodeSelectionnee',
            'preview',
            'typesAjustement',
            'totalEnseignants',
            'totalHeures',
            'totalMontant'
        ));
    }

    /**
     * Genere les bulletins pour une periode.
     */
    public function generer(Request $request): RedirectResponse
    {
        $request->validate([
            'periode_id' => 'required|exists:periode_comptables,id',
        ]);

        $fraisSuivis = $this->castIntArray($request->frais_suivi ?? []);
        $ajustements = $this->castAjustementsArray($request->ajustements ?? []);

        $bulletins = $this->generationService->generer(
            $request->periode_id,
            $fraisSuivis,
            $ajustements
        );

        return redirect()
            ->route('finance.bulletins-paie.index')
            ->with('success', count($bulletins)
                . ' bulletin(s) genere(s) avec succes.');
    }

    /**
     * Detail d'un bulletin.
     */
    public function show(BulletinPaie $bulletin): View
    {
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

        return view('finances.bulletins-paie.show', compact(
            'bulletin',
            'totalPrimes',
            'totalRetenues'
        ));
    }

    /**
     * Vue paiement d'un bulletin.
     */
    public function payer(BulletinPaie $bulletin): View
    {
        $bulletin->load([
            'enseignant.user',
            'periode',
            'lignes',
            'ajustements',
        ]);

        return view('finances.bulletins-paie.payer', compact('bulletin'));
    }

    /**
     * Enregistrer le paiement.
     */
    public function marquerPaye(
        PayerBulletinRequest $request,
        BulletinPaie $bulletin
    ): RedirectResponse {
        $this->validationService->payer(
            $bulletin,
            $request->validated()
        );

        return redirect()
            ->route('finance.bulletins-paie.show', $bulletin)
            ->with('success', 'Paiement enregistre avec succes.');
    }

    /**
     * PDF du bulletin.
     */
    public function pdf(BulletinPaie $bulletin)
    {
        return $this->pdfService->stream($bulletin);
    }

    /**
     * PDF en telechargement.
     */
    public function pdfDownload(BulletinPaie $bulletin)
    {
        return $this->pdfService->download($bulletin);
    }

    /**
     * Formulaire ajout d'un ajustement.
     */
    public function createAjustement(BulletinPaie $bulletin): View
    {
        $bulletin->load(['enseignant.user', 'periode']);

        $typesAjustement = TypeAjustement::where('is_active', true)
            ->orderBy('direction')
            ->orderBy('libelle')
            ->get();

        return view(
            'finances.bulletins-paie.ajustement-form',
            compact('bulletin', 'typesAjustement')
        );
    }

    /**
     * Enregistrer un ajustement.
     */
    public function storeAjustement(
        StoreAjustementRequest $request,
        BulletinPaie $bulletin
    ): RedirectResponse {
        $this->adjustmentService->ajouter(
            $bulletin,
            $request->validated()
        );

        return redirect()
            ->route('finance.bulletins-paie.show', $bulletin)
            ->with('success', 'Ajustement ajoute.');
    }

    /**
     * Supprimer un ajustement.
     */
    public function destroyAjustement(
        BulletinPaie $bulletin,
        BulletinPaieAjustement $ajustement
    ): RedirectResponse {
        $this->adjustmentService->supprimer($ajustement);

        return redirect()
            ->route('finance.bulletins-paie.show', $bulletin)
            ->with('success', 'Ajustement supprime.');
    }

    /**
     * Vue contestation.
     */
    public function contesterForm(BulletinPaie $bulletin): View
    {
        $bulletin->load(['enseignant.user', 'periode']);

        return view(
            'finances.bulletins-paie.contester',
            compact('bulletin')
        );
    }

    /**
     * Soumettre une contestation.
     */
    public function contester(
        Request $request,
        BulletinPaie $bulletin
    ): RedirectResponse {
        $request->validate([
            'commentaire_enseignant' => 'required|string|max:1000',
        ]);

        $this->validationService->contester(
            $bulletin,
            $request->commentaire_enseignant
        );

        return redirect()
            ->route('finance.bulletins-paie.show', $bulletin)
            ->with('success', 'Contestation soumise.');
    }

    /**
     * Marquer comme consulte.
     */
    public function marquerConsulte(
        BulletinPaie $bulletin
    ): RedirectResponse {
        $this->validationService->consulter($bulletin);

        return redirect()
            ->route('finance.bulletins-paie.show', $bulletin)
            ->with('success', 'Bulletin marque comme consulte.');
    }

    /**
     * Valider par l'enseignant.
     */
    public function valider(BulletinPaie $bulletin): RedirectResponse
    {
        $this->validationService->valider($bulletin);

        return redirect()
            ->route('finance.bulletins-paie.show', $bulletin)
            ->with('success', 'Bulletin valide.');
    }

    /**
     * Corriger un bulletin conteste et relancer le cycle.
     */
    public function corriger(BulletinPaie $bulletin): RedirectResponse
    {
        $this->validationService->corriger($bulletin);

        return redirect()
            ->route('finance.bulletins-paie.show', $bulletin)
            ->with('success', 'Bulletin corrige. Le cycle de validation a ete relance.');
    }

    // ========================================================
    // HELPERS PRIVES
    // ========================================================

    private function getPeriodesOuvertes()
    {
        return PeriodeComptable::query()
            ->where('statut', 'ouverte')
            ->orderByDesc('date_debut')
            ->get();
    }

    private function castIntArray(array $data): array
    {
        return array_map('intval', $data);
    }

    /**
     * Cast les donnees d'ajustements :
     * [enseignant_id => [type_id => montant]] → [[int => int]]
     */
    private function castAjustementsArray(array $data): array
    {
        $result = [];

        foreach ($data as $enseignantId => $types) {
            $result[(int) $enseignantId] = [];

            foreach ($types as $typeId => $montant) {
                $result[(int) $enseignantId][(int) $typeId] = (int) $montant;
            }
        }

        return $result;
    }
}
