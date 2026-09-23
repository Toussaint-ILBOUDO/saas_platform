<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContratCours;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Modules\Pedagogie\Http\Requests\StoreRapportMensuelRequest;
use App\Modules\Pedagogie\Services\RapportMensuelCalculator;
use App\Modules\Pedagogie\Services\RapportMensuelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RapportMensuelWebController extends Controller
{
    public function __construct(
        protected RapportMensuelService $service,
        protected RapportMensuelCalculator $calculator
    ) {
    }

    /**
     * Liste des contrats de l'enseignant, filtrée par période.
     */
    public function index(Request $request): View
    {
        $enseignantId = auth()->user()->enseignantProfil->id;

        // ── Période sélectionnée ──
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

        if (!$periodeSelectionnee) {
            $periodeSelectionnee = $periodes
                ->where('statut', 'ouverte')
                ->first();
        }

        // ── Contrats de l'enseignant ──
        $contrats = ContratCours::query()
            ->with([
                'eleve.user',
                'affectations' => function ($query) use ($enseignantId) {
                    $query->where('enseignant_id', $enseignantId)
                        ->with(['matiere', 'enseignant.user']);
                },
            ])
            ->whereHas('affectations', function ($query) use ($enseignantId) {
                $query->where('enseignant_id', $enseignantId);
            })
            ->when($request->search, function ($query, $search) {
                $query->whereHas('eleve.user', function ($q) use ($search) {
                    $q->where('prenom', 'like', "%{$search}%")
                        ->orWhere('nom', 'like', "%{$search}%");
                });
            })
            ->paginate(15)
            ->withQueryString();

        // ── Rapports pour la période sélectionnée ──
        $rapportsParContrat = collect();

        if ($periodeSelectionnee) {
            $rapportsParContrat = RapportMensuelEnseignant::query()
                ->where('enseignant_id', $enseignantId)
                ->where('periode_id', $periodeSelectionnee->id)
                ->whereIn(
                    'contrat_cours_id',
                    $contrats->pluck('id')
                )
                ->with('periode')
                ->get()
                ->keyBy('contrat_cours_id');
        }

        // ── KPI ──
        $totalContrats = $contrats->total();
        $rapportCrees = $rapportsParContrat->count();
        $rapportEnAttente = $totalContrats - $rapportCrees;

        return view(
            'pedagogie.rapport-mensuel.index',
            compact(
                'contrats',
                'periodes',
                'periodeSelectionnee',
                'rapportsParContrat',
                'totalContrats',
                'rapportCrees',
                'rapportEnAttente'
            )
        );
    }

    /**
     * Formulaire création rapport.
     */
    public function create(ContratCours $contrat): View
    {
        $this->authorizeTeacherContract($contrat);

        $contrat->load([
            'eleve.user',
            'affectations' => function ($query) {
                $query->with([
                    'matiere',
                    'enseignant.user'
                ]);
            }
        ]);

        $periodes = PeriodeComptable::query()
            ->where('statut', 'ouverte')
            ->orderByDesc('date_debut')
            ->get();

        return view(
            'pedagogie.rapport-mensuel.create',
            compact('contrat', 'periodes')
        );
    }

    /**
     * Aperçu automatique après choix période.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'contrat_cours_id' => ['required', 'exists:contrat_cours,id'],
            'periode_id' => ['required', 'exists:periode_comptables,id'],
        ]);

        $enseignantId = auth()->user()->enseignantProfil->id;

        $stats = $this->calculator->calculate(
            $request->contrat_cours_id,
            $enseignantId,
            $request->periode_id
        );

        return response()->json([
            'volume_horaire' => $stats['volume_horaire'],
            'nombre_seances' => $stats['nombre_seances'],
            'bilan' => $stats['bilan'],
        ]);
    }

    /**
     * Enregistrement du rapport.
     */
    public function store(
        StoreRapportMensuelRequest $request
    ): RedirectResponse {

        $rapport = $this->service->generate(
            $request->validated(),
            auth()->user()->enseignantProfil->id
        );


        $rapport->load([
            'enseignant.user',
            'contratCours.eleve.user',
            'periode',
        ]);


        return redirect()
            ->route('rapports-mensuels.show', $rapport)
            ->with(
                'success',
                'Le rapport mensuel a été créé avec succès.'
            );
    }

    /**
     * Affichage détail.
     */
    public function show(
        RapportMensuelEnseignant $rapport
    ): View {

        $rapport->load([
            'enseignant.user',
            'contratCours.eleve.user',
            'contratCours.affectations.matiere',
            'periode',
        ]);

        return view(
            'pedagogie.rapport-mensuel.show',
            compact('rapport')
        );
    }

    /**
     * Edition.
     */
    public function edit(RapportMensuelEnseignant $rapport): View
    {
        $rapport->load([
            'enseignant.user',
            'contratCours.eleve.user',
            'contratCours.affectations.matiere',
            'periode',
        ]);

        $periodes = PeriodeComptable::query()
            ->where('statut', 'ouverte')
            ->orderByDesc('date_debut')
            ->get();

        return view(
            'pedagogie.rapport-mensuel.edit',
            compact('rapport', 'periodes')
        );
    }

    /**
     * Mise à jour.
     */
    public function update(
        StoreRapportMensuelRequest $request,
        RapportMensuelEnseignant $rapport
    ): RedirectResponse {
        $rapport->update($request->validated());

        return redirect()
            ->route('rapports-mensuels.show', $rapport)
            ->with('success', 'Rapport mis à jour.');
    }

    /**
     * Suppression.
     */
    public function destroy(
        RapportMensuelEnseignant $rapport
    ): RedirectResponse {
        $rapport->delete();

        return redirect()
            ->route('rapports-mensuels.index')
            ->with('success', 'Rapport supprimé.');
    }

    /**
     * Validation d'un rapport par l'administration.
     */
    public function valider(
        RapportMensuelEnseignant $rapport
    ): RedirectResponse {
        $rapport->update(['statut' => 'valide']);

        return redirect()
            ->route('rapports-mensuels.show', $rapport)
            ->with('success', 'Rapport validé.');
    }

    /**
     * Rejet d'un rapport par l'administration.
     */
    public function reject(
        RapportMensuelEnseignant $rapport
    ): RedirectResponse {
        $rapport->update(['statut' => 'rejete']);

        return redirect()
            ->route('rapports-mensuels.show', $rapport)
            ->with('success', 'Rapport rejeté.');
    }

    /**
     * Vérifie que le contrat appartient bien à l'enseignant connecté.
     */
    protected function authorizeTeacherContract(
        ContratCours $contrat
    ): void {
        $enseignantId = auth()->user()->enseignantProfil->id;

        abort_unless(
            $contrat->affectations()
                ->where('enseignant_id', $enseignantId)
                ->exists(),
            403
        );
    }
}