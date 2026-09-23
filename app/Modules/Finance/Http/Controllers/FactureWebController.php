<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContratCours;
use App\Models\Facture;
use App\Models\PeriodeComptable;
use App\Modules\Finance\Http\Requests\GenerateFactureRequest;
use App\Modules\Finance\Http\Requests\MarquerPayeRequest;
use App\Modules\Finance\Services\FacturationService;
use App\Modules\Finance\Services\FacturePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FactureWebController extends Controller
{
    public function __construct(
        private FacturationService $service,
        private FacturePdfService $pdfService
    ) {}

    /**
     * Liste des factures.
     */
    public function index(Request $request): View
    {
        $factures = Facture::query()
            ->with([
                'contrat.eleve.user',
                'parent',
                'periode',
            ])
            ->when(
                $request->statut,
                fn($q, $s) => $q->where('statut_paiement', $s)
            )
            ->when(
                $request->search,
                fn($q, $s) => $q->whereHas(
                    'contrat.eleve.user',
                    fn($u) => $u
                        ->where('nom', 'like', "%{$s}%")
                        ->orWhere('prenom', 'like', "%{$s}%")
                )
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view(
            'finances.facture-parent.index',
            compact('factures')
        );
    }

    /**
     * Liste des factures du parent connecté (espace « Mes factures »).
     */
    public function mesFactures(Request $request): View
    {
        $factures = Facture::query()
            ->with([
                'contrat.eleve.user',
                'parent',
                'periode',
            ])
            ->where('parent_id', auth()->id())
            ->when(
                $request->statut,
                fn($q, $s) => $q->where('statut_paiement', $s)
            )
            ->when(
                $request->eleve,
                fn($q, $id) => $q->where('eleve_id', (int) $id)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view(
            'finances.facture-parent.mes-index',
            compact('factures')
        );
    }

    /**
     * Détail d'une facture.
     */
    public function show(Facture $facture): View
    {
        $facture->load([
            'contrat.eleve.user',
            'contrat.affectations.enseignant.user',
            'contrat.affectations.matiere',
            'parent',
            'periode',
            'lignes.affectation.enseignant.user',
            'lignes.affectation.matiere',
        ]);

        $cabinet = config('keduc.cabinet');

        return view(
            'finances.facture-parent.show',
            compact('facture', 'cabinet')
        );
    }

    /**
     * Formulaire de création.
     */
    public function create(): View
    {
        $contrats = ContratCours::query()
            ->with([
                'eleve.user',
                'affectations.enseignant.user',
                'affectations.matiere',
            ])
            ->where('statut', 'actif')
            ->get();

        $periodes = PeriodeComptable::query()
            ->where('statut', 'ouverte')
            ->orderByDesc('date_debut')
            ->get();

        return view(
            'finances.facture-parent.create',
            compact('contrats', 'periodes')
        );
    }

    /**
     * Vérification AJAX des prérequis.
     */
    public function verifierPrerequis(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'contrat_cours_id' => 'required|exists:contrat_cours,id',
            'periode_id'        => 'required|exists:periode_comptables,id',
        ]);

        $contrat = ContratCours::findOrFail(
            $request->contrat_cours_id
        );

        $manquants = $this->service->verifierPrerequis(
            $contrat,
            (int) $request->periode_id
        );

        return response()->json([
            'prerequis_ok' => $manquants === null,
            'manquants'    => $manquants,
        ]);
    }

    /**
     * Preview complet de la facture avant création.
     */
    public function preview(
        Request $request
    ): \Illuminate\Http\JsonResponse {

        $request->validate([
            'contrat_cours_id' => 'required|exists:contrat_cours,id',
            'periode_id'        => 'required|exists:periode_comptables,id',
        ]);

        $contrat = ContratCours::with('eleve.user')
            ->findOrFail($request->contrat_cours_id);

        $data = $this->service->calculerPreview(
            $contrat,
            (int) $request->periode_id,
            [
                'frais_suivi'  => $request->input('frais_suivi', 0),
                'autres_frais' => $request->input('autres_frais', 0),
                'remise'       => $request->input('remise', 0),
            ]
        );

        return response()->json($data);
    }

    /**
     * Enregistrement de la facture.
     */
    public function store(
        GenerateFactureRequest $request
    ): RedirectResponse {

        $this->service->generer(
            $request->validated(),
            (int) $request->periode_id
        );

        return redirect()
            ->route('finance.factures.index')
            ->with(
                'success',
                'La facture a été générée avec succès.'
            );
    }

    /**
     * Formulaire de paiement.
     */
    public function payer(Facture $facture): View
    {
        $facture->load([
            'contrat.eleve.user',
            'parent',
            'periode',
        ]);

        $cabinet = config('keduc.cabinet');

        return view(
            'finances.facture-parent.payer',
            compact('facture', 'cabinet')
        );
    }

    /**
     * Marquer la facture comme payée.
     */
    public function marquerPaye(
        MarquerPayeRequest $request,
        Facture $facture
    ): RedirectResponse {

        $this->service->marquerPaye(
            $facture,
            $request->validated()
        );

        return redirect()
            ->route('finance.factures.show', $facture)
            ->with(
                'success',
                'La facture a été marquée comme payée.'
            );
    }

    /**
     * Afficher le PDF dans le navigateur.
     */
    public function pdf(Facture $facture): Response
    {
        return $this->pdfService->stream($facture);
    }

    /**
     * Télécharger le PDF.
     */
    public function pdfDownload(Facture $facture): Response
    {
        return $this->pdfService->download($facture);
    }
}
