<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContratCours;
use App\Models\EnseignantProfil;
use App\Models\PaiementEnseignant;
use App\Models\PeriodeComptable;
use App\Modules\Finance\Http\Requests\StorePaiementEnseignantRequest;
use App\Modules\Finance\Services\PaiementEnseignantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaiementEnseignantController extends Controller
{
    public function __construct(
        private PaiementEnseignantService $service
    ) {}

    /**
     * Liste des paiements enseignants.
     */
    public function index(Request $request): View
    {
        $paiements = PaiementEnseignant::query()
            ->with([
                'enseignant.user',
                'contrat.eleve.user',
                'periode',
                'lignes',
            ])
            ->when(
                $request->enseignant_id,
                fn ($q, $id) => $q->where('enseignant_id', (int) $id)
            )
            ->when(
                $request->statut,
                fn ($q, $s) => $q->where('statut', $s)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $enseignants = EnseignantProfil::query()
            ->with('user')
            ->orderBy('id')
            ->get();

        return view(
            'finances.paiements-enseignants.index',
            compact('paiements', 'enseignants')
        );
    }

    /**
     * Formulaire de génération d'un paiement enseignant.
     */
    public function create(): View
    {
        $enseignants = EnseignantProfil::query()
            ->with('user')
            ->orderBy('id')
            ->get();

        $contrats = ContratCours::query()
            ->with('eleve.user')
            ->where('statut', 'actif')
            ->latest()
            ->get();

        $periodes = PeriodeComptable::query()
            ->where('statut', 'ouverte')
            ->orderByDesc('date_debut')
            ->get();

        return view(
            'finances.paiements-enseignants.create',
            compact('enseignants', 'contrats', 'periodes')
        );
    }

    /**
     * Génération du paiement enseignant.
     */
    public function store(
        StorePaiementEnseignantRequest $request
    ): RedirectResponse {

        $paiement = $this->service->generate(
            $request->validated()
        );

        return redirect()
            ->route('finance.paiements-enseignants.show', $paiement)
            ->with(
                'success',
                'Paiement de '
                    . number_format($paiement->montant_total, 0, ',', ' ')
                    . ' FCFA généré avec succès pour l\'enseignant.'
            );
    }

    /**
     * Détail d'un paiement enseignant.
     */
    public function show(PaiementEnseignant $paiement): View
    {
        $paiement->load([
            'enseignant.user',
            'contrat.eleve.user',
            'contrat.typeCours',
            'periode',
            'lignes.affectation.matiere',
        ]);

        return view(
            'finances.paiements-enseignants.show',
            compact('paiement')
        );
    }
}