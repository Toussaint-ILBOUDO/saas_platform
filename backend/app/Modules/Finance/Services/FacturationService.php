<?php

namespace App\Modules\Finance\Services;

use App\Models\Facture;
use App\Models\LigneFacture;
use App\Models\ContratCours;
use App\Models\PeriodeComptable;
use App\Models\AffectationEnseignant;
use App\Models\RapportMensuelEnseignant;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FacturationService
{
    public function __construct(
        private NotificationDispatcher $notifier
    ) {}

    /**
     * Vérifie que tous les enseignants affectés au contrat
     * ont déposé leur rapport mensuel pour la période donnée.
     *
     * Retourne null si tous les rapports existent,
     * sinon retourne la Collection des enseignants manquants.
     */
    public function verifierPrerequis(
        ContratCours $contrat,
        int $periodeId
    ): ?Collection {

        $affectations = AffectationEnseignant::query()
            ->where('contrat_cours_id', $contrat->id)
            ->where('statut', 'actif')
            ->with(['enseignant.user', 'matiere'])
            ->get();

        if ($affectations->isEmpty()) {
            return null;
        }

        $enseignantsAffectes = $affectations
            ->pluck('enseignant_id')
            ->unique();

        $rapportsExistants = RapportMensuelEnseignant::query()
            ->where('contrat_cours_id', $contrat->id)
            ->where('periode_id', $periodeId)
            ->whereIn('enseignant_id', $enseignantsAffectes)
            ->whereIn('statut', ['soumis', 'valide'])
            ->pluck('enseignant_id')
            ->map(fn($id) => (int) $id)
            ->values();

        $manquants = $affectations
            ->filter(
                fn($a) => !$rapportsExistants->contains(
                    (int) $a->enseignant_id
                )
            )
            ->map(fn($a) => [
                'enseignant_id' => $a->enseignant_id,
                'nom_complet'   => trim(
                    ($a->enseignant->user->prenom ?? '')
                    . ' '
                    . ($a->enseignant->user->nom ?? '')
                ),
                'matiere'       => $a->matiere->nom ?? '—',
            ])
            ->unique('enseignant_id')
            ->values();

        return $manquants->isEmpty() ? null : $manquants;
    }

    /**
     * Calcule le preview complet de la facture sans rien créer.
     *
     * Retourne les lignes calculées, les totaux, et les infos élève/parent.
     */
    public function calculerPreview(
        ContratCours $contrat,
        int $periodeId,
        array $frais = []
    ): array {

        $periode = PeriodeComptable::findOrFail($periodeId);

        $affectations = AffectationEnseignant::query()
            ->where('contrat_cours_id', $contrat->id)
            ->where('statut', 'actif')
            ->with([
                'enseignant.user',
                'matiere',
            ])
            ->get();

        $rapports = RapportMensuelEnseignant::query()
            ->where('contrat_cours_id', $contrat->id)
            ->where('periode_id', $periode->id)
            ->whereIn('statut', ['soumis', 'valide'])
            ->get()
            ->keyBy(fn($r) => (int) $r->enseignant_id);

        $volumeHoraireTotal = 0;
        $montantCours = 0;
        $lignes = [];

        foreach ($affectations as $affectation) {

            $rapport = $rapports->get(
                (int) $affectation->enseignant_id
            );

            if (!$rapport) {
                continue;
            }

            $heures = (float) $rapport->volume_horaire_cumule;

            if ($heures <= 0) {
                continue;
            }

            $tauxHoraire = (int)
                $affectation->taux_horaire_enseignant;

            $montantLigne = $heures * $tauxHoraire;

            $volumeHoraireTotal += $heures;
            $montantCours += $montantLigne;

            $lignes[] = [
                'enseignant' => trim(
                    ($affectation->enseignant->user->prenom ?? '')
                    . ' '
                    . ($affectation->enseignant->user->nom ?? '')
                ),
                'matiere'
                    => $affectation->matiere->nom ?? '—',
                'nombre_heures'
                    => round($heures, 2),
                'taux_horaire'
                    => $tauxHoraire,
                'montant'
                    => $montantLigne,
            ];
        }

        $fraisSuivi = (int) ($frais['frais_suivi'] ?? 0);
        $autresFrais = (int) ($frais['autres_frais'] ?? 0);
        $remise = (int) ($frais['remise'] ?? 0);

        $montantTotal =
            $montantCours
            + $fraisSuivi
            + $autresFrais
            - $remise;

        $eleveUser = $contrat->eleve->user ?? null;
        $parentUser = $contrat->eleve->parent?->user ?? null;

        return [
            'eleve' => $eleveUser
                ? trim($eleveUser->prenom . ' ' . $eleveUser->nom)
                : '—',
            'parent' => $parentUser
                ? trim($parentUser->prenom . ' ' . $parentUser->nom)
                : '—',
            'periode_label' => $periode->label,
            'lignes' => $lignes,
            'volume_horaire_total'
                => round($volumeHoraireTotal, 2),
            'montant_cours'
                => $montantCours,
            'frais_suivi' => $fraisSuivi,
            'autres_frais' => $autresFrais,
            'remise' => $remise,
            'montant_total' => $montantTotal,
        ];
    }

    /**
     * Génère la facture pour un contrat et une période.
     *
     * Les heures proviennent de RapportMensuelEnseignant.volume_horaire_cumule.
     * Cette table est la seule source de vérité.
     */
    public function generer(
        array $data,
        int $periodeId
    ): Facture {

        return DB::transaction(function () use ($data, $periodeId) {

            $contrat = ContratCours::with('eleve')
                ->findOrFail($data['contrat_cours_id']);

            $periode = PeriodeComptable::findOrFail($periodeId);

            // ─── Duplicata ───
            $factureExistante = Facture::query()
                ->where('contrat_cours_id', $contrat->id)
                ->where('periode_id', $periode->id)
                ->exists();

            if ($factureExistante) {
                throw ValidationException::withMessages([
                    'facture' =>
                        'Une facture existe déjà pour ce contrat et cette période.',
                ]);
            }

            // ─── Préalable : tous les rapports déposés ───
            $manquants = $this->verifierPrerequis(
                $contrat,
                $periode->id
            );

            if ($manquants !== null) {
                $noms = $manquants
                    ->pluck('nom_complet')
                    ->implode(', ');

                throw ValidationException::withMessages([
                    'rapports' =>
                        "Rapports manquants pour : {$noms}.",
                ]);
            }

            // ─── Récupération des rapports ───
            $affectations = AffectationEnseignant::query()
                ->where('contrat_cours_id', $contrat->id)
                ->where('statut', 'actif')
                ->get();

            if ($affectations->isEmpty()) {
                throw ValidationException::withMessages([
                    'contrat' =>
                        'Aucune affectation active trouvée pour ce contrat.',
                ]);
            }

            $rapports = RapportMensuelEnseignant::query()
                ->where('contrat_cours_id', $contrat->id)
                ->where('periode_id', $periode->id)
                ->whereIn('statut', ['soumis', 'valide'])
                ->get()
                ->keyBy(fn($r) => (int) $r->enseignant_id);

            // ─── Calcul des lignes ───
            $volumeHoraireTotal = 0;
            $montantCours = 0;
            $lignesFacture = [];

            foreach ($affectations as $affectation) {

                $rapport = $rapports->get($affectation->enseignant_id);

                if (!$rapport) {
                    continue;
                }

                $heures = (float) $rapport->volume_horaire_cumule;

                if ($heures <= 0) {
                    continue;
                }

                $tauxHoraire = (int)
                    $affectation->taux_horaire_enseignant;

                $montantLigne = $heures * $tauxHoraire;

                $volumeHoraireTotal += $heures;
                $montantCours += $montantLigne;

                $lignesFacture[] = [
                    'affectation_enseignant_id'
                        => $affectation->id,

                    'nombre_heures'
                        => $heures,

                    'taux_horaire'
                        => $tauxHoraire,

                    'montant'
                        => $montantLigne,
                ];
            }

            if (empty($lignesFacture)) {
                throw ValidationException::withMessages([
                    'periode' =>
                        'Aucune heure réalisée trouvée pour cette période.',
                ]);
            }

            // ─── Calculs globaux ───
            $fraisSuivi = (int) ($data['frais_suivi'] ?? 0);
            $autresFrais = (int) ($data['autres_frais'] ?? 0);
            $remise = (int) ($data['remise'] ?? 0);

            $montantTotal =
                $montantCours
                + $fraisSuivi
                + $autresFrais
                - $remise;

            // ─── Création de la facture ───
            $facture = Facture::create([

                'contrat_cours_id'
                    => $contrat->id,

                'parent_id'
                    => $contrat->eleve->parent_id,

                'eleve_id'
                    => $contrat->eleve_id,

                'periode_id'
                    => $periode->id,

                'numero_facture'
                    => $this->genererNumeroFacture(),

                'volume_horaire_total'
                    => $volumeHoraireTotal,

                'frais_suivi'
                    => $fraisSuivi,

                'autres_frais'
                    => $autresFrais,

                'remise'
                    => $remise,

                'montant_total'
                    => $montantTotal,

                'commentaire'
                    => $data['commentaire'] ?? null,

                'date_limite_paiement'
                    => $data['date_limite_paiement'] ?? null,

                'statut_paiement'
                    => 'en_attente',

                'statut_paiement_enseignants'
                    => 'en_attente',
            ]);

            // ─── Création des lignes ───
            foreach ($lignesFacture as $ligne) {

                LigneFacture::create([
                    'facture_id'
                        => $facture->id,

                    'affectation_enseignant_id'
                        => $ligne['affectation_enseignant_id'],

                    'nombre_heures'
                        => $ligne['nombre_heures'],

                    'taux_horaire'
                        => $ligne['taux_horaire'],

                    'montant'
                        => $ligne['montant'],
                ]);
            }

            // ─── Notification au parent ───
            $this->notifier->invoiceCreated($facture);

            return $facture->load([
                'contrat.eleve.user',
                'parent',
                'periode',
                'lignes.affectation.enseignant.user',
                'lignes.affectation.matiere',
            ]);
        });
    }

    /**
     * Marque une facture comme payée.
     */
    public function marquerPaye(
        Facture $facture,
        array $data
    ): Facture {

        $facture->update([
            'statut_paiement'  => 'payee',
            'date_paiement'    => $data['date_paiement'],
            'mode_paiement'    => $data['mode_paiement'],
            'reference_paiement' => $data['reference_paiement'] ?? null,
            'commentaire'      => $data['commentaire'] ?? $facture->commentaire,
        ]);

        $this->notifier->invoicePaid(
            $facture->fresh([
                'contrat.eleve.user',
                'parent',
                'periode',
                'lignes',
            ])
        );

        return $facture->fresh();
    }

    /**
     * Génère un numéro de facture unique.
     * Format : FAC-YYYYMM-NNNNN
     */
    private function genererNumeroFacture(): string
    {
        $prefixe = 'FAC-' . now()->format('Ym') . '-';

        $dernierNumero = Facture::query()
            ->where('numero_facture', 'like', $prefixe . '%')
            ->count();

        return $prefixe
            . str_pad($dernierNumero + 1, 5, '0', STR_PAD_LEFT);
    }
}
