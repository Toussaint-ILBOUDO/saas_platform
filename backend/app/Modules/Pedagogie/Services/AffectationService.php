<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\AffectationEnseignant;
use App\Models\ContratCours;
use App\Models\EnseignantMatiere;
use App\Models\PlanningCours;
use App\Models\RapportMensuelEnseignantLigne;
use Illuminate\Validation\ValidationException;

/**
 * Affectations enseignant (T7A.3).
 *
 * L'affectation est la pièce qui relie un contrat (l'élève) à un enseignant et
 * une matière, et **toute la facturation en dépend** : D-049 en tire les heures,
 * D-048 en tire le taux de rémunération du bulletin. Une affectation porte donc
 * un `taux_horaire_enseignant` et un `nombre_heures_prevues` qui ne sont pas des
 * données d'affichage, et son `statut` filtre directement la facturation
 * (`FacturationService` ignore toute affectation non `active`).
 *
 * Règles retenues ici :
 *  - le couple (enseignant, matière) doit être déclaré compétent (table pivot
 *    `enseignant_matiere`) — c'est la condition posée par le service contrat ;
 *  - une affectation **n'est jamais supprimée** : le schéma fait
 *    `ON DELETE CASCADE` de `affectation_enseignants` vers `cahier_textes`,
 *    `ligne_factures`, `bulletin_paie_lignes`, `planning_cours` et
 *    `rapport_mensuel_enseignant_lignes`. Une suppression en cascade effacerait
 *    silencieusement des heures déjà facturées et payées. On passe donc par le
 *    statut (`termine` / `suspendu`), qui laisse l'historique intact ;
 *  - on ne touche pas à une affectation déjà utilisée par une facture ou un
 *    bulletin : ces lignes sont figées (D-049 / D-048).
 */
class AffectationService
{
    public const ACTIF = 'actif';

    public const SUSPENDU = 'suspendu';

    public const TERMINE = 'termine';

    public const STATUTS = [self::ACTIF, self::SUSPENDU, self::TERMINE];

    /**
     * Exige que l'enseignant ait la matière dans ses compétences.
     *
     * @throws ValidationException
     */
    public function exigerCompetence(int $enseignantId, int $matiereId): void
    {
        $competent = EnseignantMatiere::where('enseignant_profil_id', $enseignantId)
            ->where('matiere_id', $matiereId)
            ->exists();

        if (! $competent) {
            throw ValidationException::withMessages([
                'affectations' => "Cet enseignant n'est pas déclaré compétent sur cette matière.",
            ]);
        }
    }

    /**
     * Ajoute une affectation à un contrat existant.
     */
    public function create(ContratCours $contrat, array $data): AffectationEnseignant
    {
        $this->exigerCompetence($data['enseignant_id'], $data['matiere_id']);

        // Un même enseignant ne peut pas être affecté deux fois sur la même
        // matière pour un même contrat : sinon la facturation compterait ses
        // heures deux fois sur la même ligne de rapport.
        $doublon = $contrat->affectations()
            ->where('enseignant_id', $data['enseignant_id'])
            ->where('matiere_id', $data['matiere_id'])
            ->exists();

        if ($doublon) {
            throw ValidationException::withMessages([
                'affectations' => 'Cet enseignant est déjà affecté à cette matière sur ce contrat.',
            ]);
        }

        $affectation = $contrat->affectations()->create([
            'enseignant_id' => $data['enseignant_id'],
            'matiere_id' => $data['matiere_id'],
            'taux_horaire_enseignant' => $data['taux_horaire_enseignant'] ?? 0,
            'nombre_heures_prevues' => $data['nombre_heures_prevues'] ?? 0,
            'date_affectation' => $data['date_affectation'] ?? now(),
            'statut' => self::ACTIF,
        ]);

        return $affectation->load(['matiere', 'enseignant.user', 'contrat']);
    }

    /**
     * Met à jour les paramètres financiers ou la date de fin.
     *
     * Le `taux_horaire_enseignant` est la source du bulletin de paie (D-048) :
     * le modifier après coup changerait silencieusement la rémunération due. On
     * le bloque dès qu'une ligne de bulletin existe pour cette affectation.
     */
    public function update(AffectationEnseignant $affectation, array $data): AffectationEnseignant
    {
        $modifieCompetence = isset($data['matiere_id'])
            && (int) $data['matiere_id'] !== (int) $affectation->matiere_id;

        if ($modifieCompetence) {
            throw ValidationException::withMessages([
                'matiere_id' => "La matière d'une affectation existante ne peut pas être changée.",
            ]);
        }

        $nouveauTaux = $data['taux_horaire_enseignant'] ?? null;

        if ($nouveauTaux !== null && (int) $nouveauTaux !== (int) $affectation->taux_horaire_enseignant) {
            if ($this->aDejaUnImpactFinancier($affectation)) {
                throw ValidationException::withMessages([
                    'taux_horaire_enseignant' => 'Le taux horaire ne peut plus être modifié : cette affectation porte déjà des heures facturées ou payées.',
                ]);
            }
        }

        // Un contrat suspendu ou terminé ne reçoit plus d'affectation active.
        if ($affectation->contrat && $affectation->contrat->statut !== 'actif' && ($data['statut'] ?? null) === self::ACTIF) {
            throw ValidationException::withMessages([
                'statut' => "Ce contrat n'est plus actif : l'affectation ne peut pas être réactivée.",
            ]);
        }

        $affectation->update([
            'taux_horaire_enseignant' => $nouveauTaux ?? $affectation->taux_horaire_enseignant,
            'nombre_heures_prevues' => $data['nombre_heures_prevues'] ?? $affectation->nombre_heures_prevues,
            'date_fin' => $data['date_fin'] ?? $affectation->date_fin,
            'statut' => $data['statut'] ?? $affectation->statut,
        ]);

        return $affectation->fresh(['matiere', 'enseignant.user', 'contrat']);
    }

    /**
     * Fait passer l'affectation dans un statut non actif (suspendu / termine).
     *
     * Une affectation dont le contrat porte déjà une facture payée n'est pas
     * terminable : la suspension elle-même est en revanche toujours possible
     * (elle empêche simplement de nouvelles heures d'être facturées).
     */
    public function changerStatut(AffectationEnseignant $affectation, string $statut): AffectationEnseignant
    {
        if (! in_array($statut, self::STATUTS, true)) {
            throw ValidationException::withMessages([
                'statut' => 'Statut d\'affectation inconnu.',
            ]);
        }

        if ($affectation->statut === $statut) {
            return $affectation;
        }

        if ($statut === self::TERMINE && $this->aDesHeuresFacturees($affectation)) {
            throw ValidationException::withMessages([
                'statut' => 'Cette affectation porte des heures déjà facturées : elle ne peut pas être terminée. Suspendez-la si nécessaire.',
            ]);
        }

        $affectation->update([
            'statut' => $statut,
            // Une affectation terminée porte une date de fin.
            'date_fin' => $statut === self::TERMINE
                ? ($affectation->date_fin ?? now()->toDateString())
                : $affectation->date_fin,
        ]);

        return $affectation->fresh(['matiere', 'enseignant.user', 'contrat']);
    }

    /**
     * Vrai dès que l'affectation a produit une ligne de rapport validée, une
     * facture ou un bulletin : son taux est alors economicement figé.
     */
    public function aDejaUnImpactFinancier(AffectationEnseignant $affectation): bool
    {
        return $this->aDesHeuresFacturees($affectation)
            || $affectation->lignesBulletin()->exists()
            || $affectation->lignesFacture()->exists();
    }

    private function aDesHeuresFacturees(AffectationEnseignant $affectation): bool
    {
        return $affectation->lignesFacture()->exists()
            || $affectation->lignesBulletin()->exists()
            || RapportMensuelEnseignantLigne::where('affectation_enseignant_id', $affectation->id)->exists()
            || PlanningCours::where('affectation_enseignant_id', $affectation->id)->exists();
    }
}