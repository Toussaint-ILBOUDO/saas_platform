<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\BulletinPaieLigne;
use App\Models\ContratCours;
use App\Models\EnseignantProfil;
use App\Models\LigneFacture;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Modules\Systeme\Services\NotificationDispatcher;

class ContratCoursService
{
    public const ACTIF = 'actif';

    public const SUSPENDU = 'suspendu';

    public const TERMINE = 'termine';

    public const STATUTS = [self::ACTIF, self::SUSPENDU, self::TERMINE];

    /**
     * Mise à jour de l'en-tête du contrat (période de validité, suivi des frais,
     * notes). Les affectations ne se modifient pas ici : elles ont leur propre
     * service, parce que leur taux horaire est une donnée financière figée dès
     * qu'une facture existe.
     *
     * Un contrat suspendu ou terminé est gelé : réactiver une affectation
     * suspendue dessus n'aurait aucun sens, et modifier les dates décalerait les
     * rapports et factures déjà rattachés.
     */
    public function update(ContratCours $contrat, array $data): ContratCours
    {
        if ($contrat->statut !== self::ACTIF) {
            throw ValidationException::withMessages([
                'contrat' => sprintf(
                    'Ce contrat est %s : il n\'est plus modifiable. Créez un nouveau contrat si l\'élève reprend les cours.',
                    $contrat->statut
                ),
            ]);
        }

        $contrat->update([
            'date_debut' => $data['date_debut'] ?? $contrat->date_debut,
            'date_fin' => $data['date_fin'] ?? $contrat->date_fin,
            'autres_frais_suivi' => $data['autres_frais_suivi'] ?? $contrat->autres_frais_suivi,
            'notes_admin' => $data['notes_admin'] ?? $contrat->notes_admin,
        ]);

        return $contrat->fresh();
    }

    /**
     * Fait passer le contrat (et par cascade logique ses affectations) dans un
     * statut non actif.
     *
     * On ne « termine » jamais un contrat qui a déjà produit une facture : les
     * heures facturées resteraient rattachées à un contrat censé être clos, et
     * la réouverture serait alors impossible.
     */
    public function changerStatut(ContratCours $contrat, string $statut): ContratCours
    {
        if (! in_array($statut, self::STATUTS, true)) {
            throw ValidationException::withMessages([
                'statut' => 'Statut de contrat inconnu.',
            ]);
        }

        if ($contrat->statut === $statut) {
            return $contrat;
        }

        if ($statut === self::TERMINE && $this->aDesFacturesLiees($contrat)) {
            throw ValidationException::withMessages([
                'statut' => 'Ce contrat porte des factures : il ne peut pas être terminé. Suspendez-le si nécessaire.',
            ]);
        }

        $contrat->update([
            'statut' => $statut,
            'date_fin' => $statut === self::TERMINE
                ? ($contrat->date_fin ?? now()->toDateString())
                : $contrat->date_fin,
        ]);

        // Un contrat suspendu ne doit pas laisser des affectations actives :
        // la facturation ignore les affectations non actives, mais laisser
        // « actif » sur un contrat suspendu donnerait l'illusion du contraire
        // dans les listes admin.
        if ($statut !== self::ACTIF) {
            $contrat->affectations()
                ->where('statut', AffectationService::ACTIF)
                ->update(['statut' => AffectationService::SUSPENDU]);
        }

        return $contrat->fresh();
    }

    private function aDesFacturesLiees(ContratCours $contrat): bool
    {
        if (LigneFacture::where('contrat_cours_id', $contrat->id)->exists()) {
            return true;
        }

        // D-048 : le bulletin est par enseignant + période, pas par contrat.
        // L'empreinte d'un contrat passe donc par ses affectations.
        return BulletinPaieLigne::whereIn(
            'affectation_enseignant_id',
            $contrat->affectations()->select('id')
        )->exists();
    }
    public function create(array $data): ContratCours
    {
        return DB::transaction(function () use ($data) {

            $notifier = app(NotificationDispatcher::class);
            $affectations = app(AffectationService::class);

            // ======================
            // 1. CREATE CONTRAT
            // ======================
            $contrat = ContratCours::create([
                'eleve_id' => $data['eleve_id'],
                'type_cours_id' => $data['type_cours_id'],
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'] ?? null,
                'autres_frais_suivi' => $data['autres_frais_suivi'] ?? 0,
                'notes_admin' => $data['notes_admin'] ?? null,
                'statut' => 'actif',
            ]);

            // ======================
            // 2. NOTIF CONTRAT CRÉÉ
            // ======================
            if ($contrat->eleve?->parent_id) {
                $notifier->contractCreated($contrat);
            }

            $parentNotified = false;

            // ======================
            // 3. AFFECTATIONS ENSEIGNANTS
            // ======================
            foreach ($data['affectations'] as $aff) {

                // ======================
                // 3.1 VALIDATION BUSINESS RULE
                // ======================
                // La règle « l'enseignant doit être compétent sur la matière »
                // est celle du service affectation : elle ne vit qu'à un seul
                // endroit (D-053). Elle remonte désormais en ValidationException
                // → 422 propre, au lieu d'un InvalidArgumentException → 500.
                $affectations->exigerCompetence(
                    (int) $aff['enseignant_id'],
                    (int) $aff['matiere_id'],
                );

                // ======================
                // 3.2 CREATE AFFECTATION
                // ======================
                $affectation = $affectations->create($contrat, $aff);

                // ======================
                // 3.3 ENSEIGNANT INFO
                // ======================
                $enseignant = EnseignantProfil::find($aff['enseignant_id']);

                // ======================
                // 3.4 NOTIF ENSEIGNANT
                // ======================
                if ($enseignant?->user_id) {
                    $notifier->teacherAssigned($enseignant->user_id, $contrat);
                }

                // ======================
                // 3.5 NOTIF PARENT (UNE SEULE FOIS)
                // ======================
                if (!$parentNotified) {
                    $notifier->parentTeacherAssigned($contrat);
                    $parentNotified = true;
                }
            }

            // ======================
            // 4. RETURN CONTRAT COMPLET
            // ======================
            return $contrat->load([
                'eleve',
                'typeCours',
                'affectations.enseignant',
                'affectations.matiere',
            ]);
        });
    }
}