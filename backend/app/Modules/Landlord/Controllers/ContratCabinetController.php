<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use App\Models\ContratCabinet;
use App\Modules\Plateforme\Services\ContratCabinetPdf;
use App\Modules\Plateforme\Services\ContratCabinetService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * P8 — Contrats commerciaux de cabinets (base Landlord) : versions,
 * validation, PDF. L'admin du cabinet consulte une copie lecture seule
 * (API admin, écran Angular « Mon contrat »).
 */
final class ContratCabinetController extends Controller
{
    /** Tarifs présents sur le formulaire (0/pas de tarif hors sélection). */
    private const CHAMPS_MONTANTS = [
        'frais_creation_contrat',
        'frais_contrat_actif',
        'abonnement_mensuel_enseignant',
        'abonnement_mensuel_parent',
        'abonnement_mensuel_eleve',
        'frais_rapport_mensuel',
        'abonnement_annuel_admin',
        'maintenance_annuelle_site',
        'taux_commission_librairie',
        'taux_commission_autres',
    ];

    /** Liste des versions + formulaire de nouvelle version. */
    public function index(Cabinet $cabinet): View
    {
        $service = app(ContratCabinetService::class);

        return view('landlord.contrats.show', [
            'cabinet' => $cabinet,
            'contrats' => $service->lister($cabinet->id),
            'contrat' => null,
            'services' => app(ContratCabinetService::class)->grilleServices(),
        ]);
    }

    /** Fiche d'une version : ligne par ligne tarif, uniquement. */
    public function show(Cabinet $cabinet, ContratCabinet $contrat): View
    {
        $this->asserteVersion($cabinet, $contrat);

        return view('landlord.contrats.show', [
            'cabinet' => $cabinet,
            'contrats' => app(ContratCabinetService::class)->lister($cabinet->id),
            'contrat' => $contrat,
            'services' => app(ContratCabinetService::class)->grilleServices(),
        ]);
    }

    /** CRÉE une nouvelle version (statut brouillon). */
    public function store(Cabinet $cabinet, Request $request): RedirectResponse
    {
        try {
            $contrat = app(ContratCabinetService::class)->ajouterVersion(
                $cabinet->id,
                $this->extraire($request),
                (int) auth('landlord')->id(),
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        request()->session()->flash(
            'success',
            'Version de contrat créée. Saisissez les tarifs puis validez pour figer la version (n° '.$contrat->numero.').'
        );

        return redirect()->route('landlord.contrats.show', [$cabinet, $contrat]);
    }

    /** Modifie une version brouillon. */
    public function update(Cabinet $cabinet, ContratCabinet $contrat, Request $request): RedirectResponse
    {
        $this->asserteVersion($cabinet, $contrat);

        try {
            app(ContratCabinetService::class)->modifier($contrat, $this->extraire($request));
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        request()->session()->flash('success', 'Version de contrat mise à jour.');

        return redirect()->route('landlord.contrats.show', [$cabinet, $contrat]);
    }

    /** Validation : tarifs figés, PDF archivé, versions passées archivées. */
    public function valider(Cabinet $cabinet, ContratCabinet $contrat): RedirectResponse
    {
        $this->asserteVersion($cabinet, $contrat);

        try {
            app(ContratCabinetService::class)->valider($contrat, (int) auth('landlord')->id());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        request()->session()->flash(
            'success',
            'Contrat validé : montants négociés figés. Document PDF disponible. Prochain avenant = nouvelle version.'
        );

        return redirect()->route('landlord.contrats.show', [$cabinet, $contrat]);
    }

    /** PDF du contrat (streams entière). */
    public function pdf(Cabinet $cabinet, ContratCabinet $contrat): Response
    {
        $this->asserteVersion($cabinet, $contrat);

        return new Response(
            app(ContratCabinetPdf::class)->contenu($contrat),
            headers: ['Content-Type' => 'application/pdf'],
        );
    }

    /** Supprime une version brouillon. */
    public function destroy(Cabinet $cabinet, ContratCabinet $contrat): RedirectResponse
    {
        $this->asserteVersion($cabinet, $contrat);

        try {
            app(ContratCabinetService::class)->supprimer($contrat);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        request()->session()->flash('success', 'Version de contrat supprimée.');

        return redirect()->route('landlord.contrats.index', $cabinet);
    }

    // ------------------------------------------------------------------

    /** Verrou d'appartenance : une version appartient à un cabinet unique. */
    private function asserteVersion(Cabinet $cabinet, ContratCabinet $contrat): void
    {
        if ((string) $contrat->cabinet_id !== (string) $cabinet->id) {
            abort(422, 'Le contrat demandé n\'appartient pas à ce cabinet.');
        }
    }

    /** Extraction du payload de contrat (null vs 0 vs taux, Q-A17). */
    private function extraire(Request $request): array
    {
        $tarifs = [];

        foreach (self::CHAMPS_MONTANTS as $champ) {
            $valeur = $request->input('tarifs-'.$champ);

            if (is_array($valeur)) {
                throw ValidationException::withMessages(['tarifs' => 'Valeurs invalides.']);
            }

            $valeur = is_string($valeur) ? str_replace([' ', chr(129), ' '], '', trim($valeur)) : $valeur;
            $tarifs[$champ] = ($valeur === null || $valeur === '') ? null : $valeur;
        }

        return [
            'tarifs' => $tarifs,
            'exonerations' => array_values((array) $request->input('exonerations', [])),
            'conditions_particulieres' => $request->filled('conditions_particulieres') ? $request->string('conditions_particulieres') : null,
            'modalites_paiement' => $request->filled('modalites_paiement') ? $request->string('modalites_paiement') : null,
            'responsable_nom' => $request->filled('responsable_nom') ? $request->string('responsable_nom') : null,
            'date_effet' => $request->filled('date_effet') ? $request->date('date_effet')->toDateString() : null,
            'date_fin_validite' => $request->filled('date_fin_validite') ? $request->date('date_fin_validite')->toDateString() : null,
        ];
    }
}
