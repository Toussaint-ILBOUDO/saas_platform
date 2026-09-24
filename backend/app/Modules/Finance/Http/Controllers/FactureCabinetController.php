<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FactureCabinet;
use App\Models\PaiementCabinet;
use App\Models\TypeCommission;
use App\Modules\Finance\Http\Requests\StoreFactureCabinetRequest;
use App\Modules\Finance\Http\Requests\StorePaiementCabinetRequest;
use App\Modules\Finance\Services\FactureCabinetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FactureCabinetController extends Controller
{
    public function __construct(
        private FactureCabinetService $service
    ) {}

    /**
     * Liste des factures cabinet.
     */
    public function index(Request $request): View
    {
        $factures = FactureCabinet::query()
            ->withCount('paiements')
            ->withSum(
                ['paiements as montant_paye' => fn ($q) => $q->where('statut', 'valide')],
                'montant_paye'
            )
            ->when(
                $request->statut,
                fn($q, $s) => $q->where('statut', $s)
            )
            ->when(
                $request->search,
                fn($q, $s) => $q->where('date_facture', 'like', "%{$s}%")
                    ->orWhere('periode_debut', 'like', "%{$s}%")
                    ->orWhere('periode_fin', 'like', "%{$s}%")
            )
            ->latest('date_facture')
            ->paginate(15)
            ->withQueryString();

        return view(
            'finances.facture-cabinet.index',
            compact('factures')
        );
    }

    /**
     * Détail d'une facture cabinet.
     */
    public function show(FactureCabinet $facture): View
    {
        $facture->load([
            'lignes.typeCommission',
            'paiements',
        ]);

        $cabinet = config('keduc.cabinet');

        return view(
            'finances.facture-cabinet.show',
            compact('facture', 'cabinet')
        );
    }

    /**
     * Formulaire de création.
     */
    public function create(): View
    {
        $typeCommissions = TypeCommission::all();

        return view(
            'finances.facture-cabinet.create',
            compact('typeCommissions')
        );
    }

    /**
     * Preview AJAX des commissions.
     */
    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after_or_equal:date_debut',
        ]);

        $data = $this->service->calculerPreview(
            [
                'date_debut' => $request->date_debut,
                'date_fin'   => $request->date_fin,
            ],
            [
                'taux_cours'       => $request->input('taux_cours', 2000),
                'taux_inscription' => $request->input('taux_inscription', 3000),
                'taux_vente'       => $request->input('taux_vente', 10),
            ]
        );

        return response()->json($data);
    }

    /**
     * Enregistrement de la facture cabinet.
     */
    public function store(StoreFactureCabinetRequest $request): RedirectResponse
    {
        $this->service->generer($request->validated());

        return redirect()
            ->route('finance.facture-cabinet.index')
            ->with(
                'success',
                'La facture cabinet a été générée avec succès.'
            );
    }

    /**
     * Formulaire de paiement.
     */
    public function payer(FactureCabinet $facture): View
    {
        $this->authorize('payer', $facture);

        $facture->load('paiements');

        $cabinet = config('keduc.cabinet');

        return view(
            'finances.facture-cabinet.payer',
            compact('facture', 'cabinet')
        );
    }

    /**
     * Enregistrer un paiement.
     */
    public function storePaiement(
        StorePaiementCabinetRequest $request,
        FactureCabinet $facture
    ): RedirectResponse {

        $this->authorize('payer', $facture);

        $this->service->enregistrerPaiement(
            $facture,
            $request->validated()
        );

        return redirect()
            ->route('finance.facture-cabinet.show', $facture)
            ->with(
                'success',
                'Le paiement a été enregistré avec succès.'
            );
    }

    /**
     * Valider un paiement.
     */
    public function validerPaiement(
        PaiementCabinet $paiement
    ): RedirectResponse {

        $this->authorize('payer', $paiement->factureCabinet);

        $this->service->validerPaiement($paiement);

        return redirect()
            ->route(
                'finance.facture-cabinet.show',
                $paiement->facture_cabinet_id
            )
            ->with(
                'success',
                'Le paiement a été validé.'
            );
    }

    /**
     * Annuler un paiement.
     */
    public function annulerPaiement(
        PaiementCabinet $paiement
    ): RedirectResponse {

        $this->authorize('payer', $paiement->factureCabinet);

        $this->service->annulerPaiement($paiement);

        return redirect()
            ->route(
                'finance.facture-cabinet.show',
                $paiement->facture_cabinet_id
            )
            ->with(
                'success',
                'Le paiement a été annulé.'
            );
    }

    /**
     * Annuler une facture.
     */
    public function annuler(FactureCabinet $facture): RedirectResponse
    {
        $this->authorize('annuler', $facture);

        $this->service->annuler($facture);

        return redirect()
            ->route('finance.facture-cabinet.show', $facture)
            ->with(
                'success',
                'La facture a été annulée.'
            );
    }

    /**
     * Afficher le PDF dans le navigateur (facture simple).
     */
    public function pdf(FactureCabinet $facture): Response
    {
        $facture->load([
            'lignes.typeCommission',
            'paiements',
        ]);

        $cabinet = config('keduc.cabinet');

        $html = view('finances.facture-cabinet.pdf', compact('facture', 'cabinet'))->render();

        return response($html)
            ->header('Content-Type', 'text/html');
    }
}
