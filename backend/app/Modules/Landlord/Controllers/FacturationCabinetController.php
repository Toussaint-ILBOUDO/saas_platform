<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use App\Models\FactureCabinet;
use App\Models\LigneFactureCabinet;
use App\Models\SuperAdmin;
use App\Modules\Plateforme\Services\ContratCabinetService;
use App\Modules\Plateforme\Services\FactureCabinetPdf;
use App\Modules\Plateforme\Services\FacturationCabinetService;
use App\Modules\Plateforme\Services\PaiementCabinetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * P8 — Console « Facturation » du super-admin (base Landlord) : factures
 * par cabinet et par période, lignes manuelles, émission, paiements.
 * Une facture ne mélange jamais deux cabinets.
 */
final class FacturationCabinetController extends Controller
{
    public function index(Request $request): View
    {
        $cabinetFiltre = trim((string) $request->query('cabinet'));
        $statutFiltre = trim((string) $request->query('statut'));

        $factures = FactureCabinet::query()
            ->when($cabinetFiltre !== '', fn ($q) => $q->where('cabinet_id', $cabinetFiltre))
            ->when($statutFiltre !== '', fn ($q) => $q->where('statut', $statutFiltre))
            ->orderByDesc('periode_debut')
            ->paginate(20)
            ->withQueryString();

        // Résumé plateforme (factures non annulées).
        $resume = FactureCabinet::query()
            ->whereIn('statut', [
                FactureCabinet::STATUT_EMISE,
                FactureCabinet::STATUT_PARTIELLEMENT_PAYEE,
                FactureCabinet::STATUT_PAYEE,
            ])
            ->selectRaw('COUNT(*) AS nb, COALESCE(SUM(montant_total), 0) AS facture, COALESCE(SUM(montant_paye), 0) AS paye')
            ->first();

        return view('landlord.facturation.index', [
            'factures' => $factures,
            'cabinetFiltre' => $cabinetFiltre,
            'statutFiltre' => $statutFiltre,
            'resume' => $resume,
            'cabinets' => Cabinet::query()->orderBy('nom')->get(['id', 'nom']),
            'statuts' => [
                FactureCabinet::STATUT_BROUILLON => 'Brouillon',
                FactureCabinet::STATUT_EMISE => 'Émise',
                FactureCabinet::STATUT_PARTIELLEMENT_PAYEE => 'Partiellement payée',
                FactureCabinet::STATUT_PAYEE => 'Payée',
                FactureCabinet::STATUT_ANNULEE => 'Annulée',
            ],
        ]);
    }

    /**
     * Formulaire d'une nouvelle facture : cabinet + période choisie.
     * 2 états : choix des critères ; puis aperçu (activités facturables
     * auto-calculées + saisie de lignes manuelles).
     */
    public function preparer(Request $request): View
    {
        $cabinetId = trim((string) $request->query('cabinet'));
        $debut = $request->query('debut');
        $fin = $request->query('fin');

        $cabinet = $cabinetId !== '' ? Cabinet::query()->find($cabinetId) : null;
        $service = app(FacturationCabinetService::class);
        $contrat = $cabinet !== null ? $service->contratValide($cabinet->id) : null;

        $periode = null;
        $apercu = null;
        $erreur = null;

        if ($cabinet !== null && $debut !== null && $fin !== null) {
            try {
                $periode = [
                    'debut' => Carbon::parse($debut),
                    'fin' => Carbon::parse($fin),
                ];

                $apercu = $service->calculerApercu($cabinet->id, $periode['debut'], $periode['fin'], $contrat);
            } catch (ValidationException $e) {
                $erreur = implode(' — ', array_map(
                    fn ($l) => is_array($l) ? implode(' ', (array) $l) : (string) $l,
                    $e->errors()
                ));
            }
        }

        return view('landlord.facturation.preparer', [
            'cabinets' => Cabinet::query()->orderBy('nom')->get(['id', 'nom']),
            'cabinet' => $cabinet,
            'contrat' => $contrat,
            'periode' => $periode,
            'apercu' => $apercu,
            'erreurPeriode' => $erreur,
            'periodeDebut' => $debut,
            'periodeFin' => $fin,
            'sens' => ['debit' => 'Débit (facturé au cabinet)', 'credit' => 'Crédit (à déduire)'],
            'lignesTypes' => app(ContratCabinetService::class)->lignesTypes(),
        ]);
    }

    /** Création du brouillon de facture (lignes auto + manuelles). */
    public function creerBrouillon(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cabinet' => ['required', 'string', 'max:64', 'exists:tenants,id'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'conditions_paiement' => ['nullable', 'string', 'max:1000'],
            'note_interne' => ['nullable', 'string', 'max:1000'],
            'ligne' => ['nullable', 'array', 'max:20'],
        ]);

        $lignesManuelles = $this->extraireLignesManuelles($data['ligne'] ?? []);

        try {
            $facture = app(FacturationCabinetService::class)->creerFactureBrouillon(
                $data['cabinet'],
                new Carbon($data['date_debut']),
                new Carbon($data['date_fin']),
                app(FacturationCabinetService::class)->contratValide($data['cabinet']),
                $lignesManuelles,
                $data['conditions_paiement'] ?? null,
                $data['note_interne'] ?? null,
                (int) auth('landlord')->id(),
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        request()->session()->flash('success', 'Brouillon de facture créé — montants figés à l\'émission.');

        return redirect()->route('landlord.facturation.show', $facture);
    }

    /** Fiche facture : lignes, paiements, actions (émettre, annuler, PDF). */
    public function show(FactureCabinet $facture): View
    {
        $facture->load(['lignes', 'paiements', 'evenements']);

        $idsAuteurs = $facture->lignes
            ->pluck('cree_par_user')
            ->filter()
            ->unique()
            ->values();

        return view('landlord.facturation.show', [
            'facture' => $facture,
            'modes' => PaiementCabinetService::MODES,
            // Briques du catalogue éditable ( même mise en forme que « Préparer »).
            'lignesTypes' => app(ContratCabinetService::class)->lignesTypes(),
            // Auteurs des lignes manuelles (id → nom du super admin).
            'auteurs' => SuperAdmin::query()->whereIn('id', $idsAuteurs)->pluck('nom', 'id'),
        ]);
    }

    /** Émission : montants figés, numérotée, événement consommés àbles et marqués. */
    public function emettre(FactureCabinet $facture): RedirectResponse
    {
        try {
            app(FacturationCabinetService::class)->emettre($facture, (int) auth('landlord')->id());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        $facture->refresh();

        request()->session()->flash('success', 'Facture émise et numérotée ('.$facture->numero_facture.') — montants figés.');

        return redirect()->route('landlord.facturation.show', $facture);
    }

    /** Annulation restrictive : facture émise sans aucun encaissement (Q-A16). */
    public function annuler(FactureCabinet $facture, Request $request): RedirectResponse
    {
        $motif = trim((string) $request->input('motif'));

        try {
            app(FacturationCabinetService::class)->annuler($facture, $motif, (int) auth('landlord')->id());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        request()->session()->flash('success', 'Facture annulée ; ses événements facturables reviennent disponibles.');

        return redirect()->route('landlord.facturation.show', $facture);
    }

    /** Suppression d'un brouillon (événements relâchés). */
    public function supprimerBrouillon(FactureCabinet $facture): RedirectResponse
    {
        try {
            app(FacturationCabinetService::class)->supprimerBrouillon($facture);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        request()->session()->flash('success', 'Brouillon supprimé ; les événements facturables reviennent disponibles.');

        return redirect()->route('landlord.facturation.index');
    }

    /** Ligne manuelle : libellé, sens, quantité, montant (entier FCFA), motif. */
    public function ajouterLigne(FactureCabinet $facture, Request $request): RedirectResponse
    {
        try {
            $data = $request->validate([
                'libelle' => ['required', 'string', 'max:250'],
                'sens' => ['required', Rule::in(['debit', 'credit'])],
                'montant' => ['required', 'integer', 'min:1'],
                'quantite' => ['nullable', 'integer', 'min:1'],
                'motif' => ['nullable', 'string', 'max:300'],
            ]);

            if ($facture->statut != FactureCabinet::STATUT_BROUILLON) {
                return redirect()->back()->withErrors(['facture' => 'Seul un brouillon accepte des lignes.']);
            }

            app(FacturationCabinetService::class)->ajouterLigneManuelle($facture, $data, (int) auth('landlord')->id());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        request()->session()->flash('success', 'Ligne ajoutée.');

        return redirect()->route('landlord.facturation.show', $facture);
    }

    public function supprimerLigne(FactureCabinet $facture, int $ligneId): RedirectResponse
    {
        if ($facture->statut !== FactureCabinet::STATUT_BROUILLON) {
            return redirect()->back()->withErrors(['facture' => 'Seul un brouillon est modifiable.']);
        }

        app(FacturationCabinetService::class)->supprimerLigne($facture, $ligneId);

        request()->session()->flash('success', 'Ligne supprimée.');

        return redirect()->route('landlord.facturation.show', $facture);
    }

    /** Encaissement partiel/total d'une facture émise. */
    public function ajouterPaiement(FactureCabinet $facture, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'montant' => ['required', 'integer', 'min:1'],
            'mode' => ['required', 'string', 'max:30'],
            'reference' => ['nullable', 'string', 'max:200'],
            'date_paiement' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            app(PaiementCabinetService::class)->enregistrer(
                $facture,
                (int) $data['montant'],
                $data['mode'],
                $data['reference'] ?? null,
                $data['commentaire'] ?? null,
                (int) auth('landlord')->id(),
                $data['date_paiement'],
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        request()->session()->flash('success', 'Paiement enregistré.');

        return redirect()->route('landlord.facturation.show', $facture);
    }

    /** PDF de la facture (brouillon possible, mention apparente). */
    public function pdf(FactureCabinet $facture): Response
    {
        $contenu = app(FactureCabinetPdf::class)->rendre($facture);

        return new Response($contenu, headers: ['Content-Type' => 'application/pdf']);
    }

    // ------------------------------------------------------------------

    /** Grille de saisie des lignes manuelles (libelle/sens/montant/motif). */
    private function extraireLignesManuelles(array $grille): array
    {
        $lignes = [];

        foreach (array_values(array_filter($grille, fn ($l) => is_array($l))) as $i => $l) {
            $libelle = trim((string) ($l['libelle'] ?? ''));
            $montant = (string) ($l['montant'] ?? '');

            if ($libelle === '' || $montant === '') {
                continue;
            }

            if (! in_array($l['sens'] ?? 'debit', ['debit', 'credit'], true)) {
                throw ValidationException::withMessages(['ligne' => 'Sens de ligne invalide (ligne '.($i + 1).').']);
            }

            $lignes[] = [
                'type' => LigneFactureCabinet::TYPE_LIBRE,
                'libelle' => $libelle,
                'sens' => $l['sens'],
                'quantite' => max(1, (int) ($l['quantite'] ?? 1)),
                'montant' => (int) $montant,
                'motif' => (string) ($l['motif'] ?? ''),
            ];
        }

        return $lignes;
    }
}
