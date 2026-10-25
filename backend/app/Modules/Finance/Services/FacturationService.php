<?php

namespace App\Modules\Finance\Services;

use App\Models\Facture;
use App\Models\FactureAjustement;
use App\Models\LigneFacture;
use App\Models\ContratCours;
use App\Models\PeriodeComptable;
use App\Models\AffectationEnseignant;
use App\Models\RapportMensuelEnseignant;
use App\Models\TypeAjustement;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Systeme\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FacturationService
{
    public function __construct(
        private NotificationDispatcher $notifier,
        private GardePeriodeOuverte $garde,
        private NumerotationDocuments $numerotation
    ) {}

    /**
     * Lignes de facturation d'un contrat pour une période.
     *
     * D-049 : la source est la VENTILATION du rapport mensuel validé
     * (`rapport_mensuel_enseignant_lignes`), c'est-à-dire les heures réellement
     * faites par matière. KEduc indexait les rapports par `enseignant_id` puis
     * bouclait sur les AFFECTATIONS, ce qui comptait les heures d'un enseignant
     * autant de fois qu'il enseignait de matières au même élève.
     *
     * Seuls les rapports `valide` sont retenus (D-051) : les heures payées par
     * le parent et par l'enseignant sont celles que l'administration a validées.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function lignesFacturables(ContratCours $contrat, PeriodeComptable $periode): \Illuminate\Support\Collection
    {
        $rapports = RapportMensuelEnseignant::query()
            ->where('contrat_cours_id', $contrat->id)
            ->where('periode_id', $periode->id)
            ->where('statut', 'valide')
            ->with([
                'lignes.affectation.enseignant.user',
                'lignes.affectation.matiere',
            ])
            ->get();

        $lignes = collect();

        foreach ($rapports as $rapport) {
            foreach ($rapport->lignes as $ligne) {
                $heures = (float) $ligne->nombre_heures;

                if ($heures <= 0) {
                    continue;
                }

                $affectation = $ligne->affectation;

                if (! $affectation || $affectation->statut !== 'actif') {
                    continue;
                }

                $tauxHoraire = (int) $affectation->taux_horaire_enseignant;

                $lignes->push([
                    'affectation_enseignant_id' => (int) $affectation->id,
                    'contrat_cours_id' => (int) $contrat->id,
                    'eleve_id' => (int) $contrat->eleve_id,
                    'matiere_id' => $ligne->matiere_id,
                    'matiere' => $affectation->matiere->nom ?? '—',
                    'enseignant_id' => (int) $affectation->enseignant_id,
                    'enseignant' => trim(
                        ($affectation->enseignant->user->prenom ?? '')
                        . ' '
                        . ($affectation->enseignant->user->nom ?? '')
                    ),
                    'nombre_heures' => round($heures, 2),
                    'taux_horaire' => $tauxHoraire,
                    'montant' => (int) round($heures * $tauxHoraire),
                ]);
            }
        }

        return $lignes;
    }

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

        // Aucun enseignant affecté : la facturation est impossible, et l'erreur
        // doit le dire ici (KEduc renvoyait « prérequis OK » puis échouait à la
        // génération, §2.3-8 de la spécification).
        if ($affectations->isEmpty()) {
            throw ValidationException::withMessages([
                'contrat' => 'Ce contrat n\'a aucune affectation active : impossible de le facturer.',
            ]);
        }

        $enseignantsAffectes = $affectations
            ->pluck('enseignant_id')
            ->unique();

        // D-051 : seuls les rapports VALIDÉS débloquent la facturation.
        $rapportsValides = RapportMensuelEnseignant::query()
            ->where('contrat_cours_id', $contrat->id)
            ->where('periode_id', $periodeId)
            ->whereIn('enseignant_id', $enseignantsAffectes)
            ->where('statut', 'valide')
            ->pluck('enseignant_id')
            ->map(fn($id) => (int) $id)
            ->values();

        $manquants = $affectations
            ->filter(
                fn($a) => !$rapportsValides->contains(
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
     * Les éventuelles lignes bonus/débit saisies dans le formulaire sont
     * intégrées au total comme elles le seront à la génération.
     */
    public function calculerPreview(
        ContratCours $contrat,
        int $periodeId,
        array $frais = []
    ): array {

        $periode = PeriodeComptable::findOrFail($periodeId);

        $lignes = $this->lignesFacturables($contrat, $periode);

        $volumeHoraireTotal = round(
            $lignes->sum('nombre_heures'),
            2
        );

        $montantCours = (int) round(
            $lignes->sum('montant')
        );

        $fraisSuivi = (int) ($frais['frais_suivi'] ?? 0);
        $autresFrais = (int) ($frais['autres_frais'] ?? 0);
        $remise = (int) ($frais['remise'] ?? 0);

        [$totalCredits, $totalDebits] = $this->totauxAjustements(
            $frais['ajustements'] ?? []
        );

        $montantTotal =
            $montantCours
            + $fraisSuivi
            + $autresFrais
            - $remise
            + $totalCredits
            - $totalDebits;

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
            'lignes' => $lignes->values()->all(),
            'volume_horaire_total'
                => $volumeHoraireTotal,
            'montant_cours'
                => $montantCours,
            'frais_suivi' => $fraisSuivi,
            'autres_frais' => $autresFrais,
            'remise' => $remise,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'montant_total' => $montantTotal,
        ];
    }

    /**
     * Totaux crédit/débit d'une liste de lignes bonus/débit soumises.
     *
     * @param  array<int, array<string, mixed>>  $ajustements
     * @return array{0: int, 1: int}  [total_credits, total_debits]
     */
    private function totauxAjustements(array $ajustements): array
    {
        $types = TypeAjustement::query()
            ->whereIn('id', collect($ajustements)->pluck('type_ajustement_id'))
            ->get()
            ->keyBy('id');

        $totalCredits = 0;
        $totalDebits = 0;

        foreach ($ajustements as $ligne) {
            $type = $types->get((int) ($ligne['type_ajustement_id'] ?? 0));
            $montant = max(1, (int) ($ligne['montant'] ?? 0));

            if ($type && $type->direction === 'debit') {
                $totalDebits += $montant;
            } else {
                $totalCredits += $montant;
            }
        }

        return [$totalCredits, $totalDebits];
    }

    /**
     * Crée les lignes bonus/débit d'une facture (appelé sous transaction).
     *
     * @param  array<int, array<string, mixed>>  $ajustements
     */
    private function creerAjustements(Facture $facture, array $ajustements): void
    {
        $types = TypeAjustement::query()
            ->whereIn('id', collect($ajustements)->pluck('type_ajustement_id'))
            ->get()
            ->keyBy('id');

        foreach ($ajustements as $ligne) {
            $typeId = (int) ($ligne['type_ajustement_id'] ?? 0);
            $type = $types->get($typeId);

            FactureAjustement::create([
                'facture_id' => $facture->id,
                'type_ajustement_id' => $typeId,
                'type' => $type && $type->direction === 'debit'
                    ? 'debit'
                    : 'credit',
                'libelle' => (string) ($ligne['libelle'] ?? $type?->libelle ?? 'Ajustement'),
                'montant' => max(1, (int) ($ligne['montant'] ?? 0)),
            ]);
        }
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

            // D-051 : une période close n'accepte aucune écriture financière.
            $this->garde->exigerOuverte($periode, 'La facturation');

            // ─── Duplicata ───
            // Le contrôle applicatif donne un message lisible ; l'index unique
            // `uq_factures_contrat_periode` (posé en base) est la vraie
            // protection contre deux générations concurrentes.
            $factureExistante = Facture::query()
                ->where('contrat_cours_id', $contrat->id)
                ->where('periode_id', $periode->id)
                ->first();

            if ($factureExistante) {
                throw ValidationException::withMessages([
                    'facture' =>
                        'Une facture existe déjà pour ce contrat et cette période.',
                ]);
            }

            // ─── Préalable : tous les rapports validés ───
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
                        "Rapports mensuels non validés pour : {$noms}.",
                ]);
            }

            // ─── Lignes depuis la ventilation des rapports validés (D-049) ───
            $lignesFacture = $this->lignesFacturables($contrat, $periode);

            if ($lignesFacture->isEmpty()) {
                throw ValidationException::withMessages([
                    'periode' =>
                        'Aucune heure validée pour cette période.',
                ]);
            }

            // ─── Calculs globaux ───
            $volumeHoraireTotal = round(
                $lignesFacture->sum('nombre_heures'),
                2
            );

            $montantCours = (int) round(
                $lignesFacture->sum('montant')
            );

            $fraisSuivi = (int) ($data['frais_suivi'] ?? 0);
            $autresFrais = (int) ($data['autres_frais'] ?? 0);
            $remise = (int) ($data['remise'] ?? 0);

            // Lignes bonus/débit emportées depuis l'aperçu.
            $ajustements = array_values($data['ajustements'] ?? []);

            [$totalCredits, $totalDebits] = $this->totauxAjustements(
                $ajustements
            );

            $montantTotal =
                $montantCours
                + $fraisSuivi
                + $autresFrais
                - $remise
                + $totalCredits
                - $totalDebits;

            // ─── Création de la facture ───
            // Numéro atomique : calculé sous verrou à partir du maximum
            // existant (et non d'un `count()+1`), la transaction étant déjà
            // ouverte ici.
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
                    => $this->numerotation->prochain(
                        'FAC',
                        'numero_facture',
                        now()
                    ),

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

            // ─── Lignes bonus/débit saisies dans l'aperçu ───
            if ($ajustements !== []) {
                $this->creerAjustements($facture, $ajustements);
            }

            // ─── Notification au parent ───
            $this->notifier->invoiceCreated($facture);

            AuditService::enregistrer(
                'facture.emise',
                'Facture ' . $facture->numero_facture . ' émise',
                $facture,
                ['montant_total' => $facture->montant_total]
            );

            return $facture->load([
                'contrat.eleve.user',
                'parent',
                'periode',
                'lignes.affectation.enseignant.user',
                'lignes.affectation.matiere',
                'ajustements',
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

        // D-051 : une facture déjà réglée ne peut pas être réglée une seconde
        // fois. KEduc ne contrôlait rien et autorisait n'importe quel
        // ré-enregistrement, y compris après un premier règlement.
        if ($facture->estPayee()) {
            throw ValidationException::withMessages([
                'facture' => 'Cette facture est déjà marquée comme payée.',
            ]);
        }

        $facture->update([
            'statut_paiement'  => 'payee',
            'date_paiement'    => $data['date_paiement'],
            'mode_paiement'    => $data['mode_paiement'],
            'reference_paiement' => $data['reference_paiement'] ?? null,
            // Le commentaire de règlement ne doit pas écraser le commentaire
            // administratif de la facture.
            'commentaire'      => $facture->commentaire,
        ]);

        $this->notifier->invoicePaid(
            $facture->fresh([
                'contrat.eleve.user',
                'parent',
                'periode',
                'lignes',
            ])
        );

        AuditService::enregistrer(
            'facture.reglee',
            'Facture ' . $facture->numero_facture . ' réglée',
            $facture,
            [
                'montant_total' => $facture->montant_total,
                'mode_paiement' => $data['mode_paiement'],
            ]
        );

        return $facture->fresh();
    }
}
