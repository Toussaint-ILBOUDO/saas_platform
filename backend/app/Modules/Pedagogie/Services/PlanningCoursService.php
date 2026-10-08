<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\AffectationEnseignant;
use App\Models\EnseignantProfil;
use App\Models\Eleve;
use App\Models\PlanningCours;
use App\Modules\Pedagogie\Enums\PlanningJourSemaine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PlanningCoursService
{
    /*
    |--------------------------------------------------------------------------
    | Enseignant (gestion de son planning)
    |--------------------------------------------------------------------------
    */

    public function listForEnseignant(EnseignantProfil $profil): Collection
    {
        return PlanningCours::query()
            ->with([
                'affectation.matiere',
                'affectation.contrat.eleve.user',
            ])
            ->where('enseignant_id', $profil->id)
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get();
    }

    /**
     * Créneaux des AUTRES enseignants qui interviennent dans les MÊMES contrats
     * que cet enseignant (ses propres créneaux sont exclus).
     *
     * Le périmètre est le contrat, pas l'élève : deux enseignants peuvent
     * suivre le même enfant sur deux contrats différents (deux offres, deux
     * périodes) sans pour autant avoir à connaître le planning de l'un pour
     * l'autre. Restreindre au contrat partagé est aussi la lecture la moins
     * bavarde de l'information.
     */
    public function listSharedForEnseignant(EnseignantProfil $profil): Collection
    {
        $contratIds = AffectationEnseignant::query()
            ->where('enseignant_id', $profil->id)
            ->where('statut', 'actif')
            ->whereHas('contrat', fn ($query) => $query->where('statut', 'actif'))
            ->pluck('contrat_cours_id')
            ->unique()
            ->values();

        if ($contratIds->isEmpty()) {
            return collect();
        }

        return PlanningCours::query()
            ->with([
                'enseignant.user',
                'affectation.matiere',
                'affectation.contrat.eleve.user',
            ])
            ->where('enseignant_id', '!=', $profil->id)
            ->whereHas('affectation.contrat', function ($query) use ($contratIds) {
                $query->whereIn('id', $contratIds);
            })
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get();
    }

    public function availableAffectations(EnseignantProfil $profil): Collection
    {
        return AffectationEnseignant::query()
            ->with(['matiere', 'contrat.eleve.user'])
            ->where('enseignant_id', $profil->id)
            ->where('statut', 'actif')
            ->latest()
            ->get();
    }

    public function createForEnseignant(EnseignantProfil $profil, array $data): PlanningCours
    {
        $affectation = $this->resolveAffectation($profil, (int) $data['affectation_enseignant_id']);

        $this->assertConflitHoraire(
            $profil,
            $affectation,
            (int) $data['jour_semaine'],
            (string) $data['heure_debut'],
            (string) $data['heure_fin'],
        );

        return PlanningCours::create([
            'enseignant_id' => $profil->id,
            'affectation_enseignant_id' => $affectation->id,
            'jour_semaine' => $data['jour_semaine'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
        ]);
    }

    public function updateForEnseignant(
        EnseignantProfil $profil,
        PlanningCours $creneau,
        array $data
    ): PlanningCours {
        $affectation = $this->resolveAffectation($profil, (int) $data['affectation_enseignant_id']);

        $this->assertConflitHoraire(
            $profil,
            $affectation,
            (int) $data['jour_semaine'],
            (string) $data['heure_debut'],
            (string) $data['heure_fin'],
            $creneau->id,
        );

        $creneau->update([
            'affectation_enseignant_id' => $affectation->id,
            'jour_semaine' => $data['jour_semaine'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
        ]);

        return $creneau->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Élève / Parent (consultation)
    |--------------------------------------------------------------------------
    */

    /**
     * Créneaux réguliers des enseignants pour une liste d'élèves.
     */
    public function listForEleves(array $eleveIds): Collection
    {
        $eleveIds = array_values(array_filter(array_map('intval', $eleveIds)));

        if (empty($eleveIds)) {
            return collect();
        }

        return PlanningCours::query()
            ->with([
                'enseignant.user',
                'affectation.matiere',
                'affectation.contrat.eleve.user',
            ])
            ->whereHas('affectation.contrat', function ($query) use ($eleveIds) {
                $query->whereIn('eleve_id', $eleveIds);
            })
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get();
    }

    /**
     * Créneaux d'un élève dont le compte est actif, avec ses enseignants.
     *
     * Le planning dit quel enseignant vient, à quelle heure : c'est une
     * information d'organisation du domicile de l'élève. Elle n'est donc
     * exposée que si le compte de l'élève est réellement activé
     * (`eleves.statut` ET `users.statut`, cf. `EleveService::activateAccount`).
     */
    public function listForEleve(Eleve $eleve): Collection
    {
        if (! $this->compteEleveEstActif($eleve)) {
            return collect();
        }

        return $this->listForEleves([$eleve->id]);
    }

    private function compteEleveEstActif(Eleve $eleve): bool
    {
        return (bool) $eleve->statut
            && (bool) $eleve->user?->statut;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function resolveAffectation(EnseignantProfil $profil, int $affectationId): AffectationEnseignant
    {
        $affectation = AffectationEnseignant::query()
            ->whereKey($affectationId)
            ->where('enseignant_id', $profil->id)
            ->where('statut', 'actif')
            ->first();

        if (!$affectation) {
            throw ValidationException::withMessages([
                'affectation_enseignant_id' => 'Affectation invalide ou non active.',
            ]);
        }

        return $affectation;
    }

    /**
     * Détection des conflits horaires d'un créneau récurrent (T7A.4).
     *
     * Le contrôle historique ne comparait que les heures de **début** sur la
     * même affectation : il laissait donc passer tous les conflits réels.
     *
     *  - un enseignant « 18h-19h » puis « 18h30-19h30 » sur deux élèves
     *    différents : impossible, il ne peut pas être à deux endroits ;
     *  - un élève « 18h-19h » puis « 18h30-19h30 » avec deux enseignants :
     *    impossible, les cours se chevauchent.
     *
     * On raisonne donc sur des **intervalles** : deux créneaux se chevauchent si
     * `debut_A < fin_B` et `fin_A > debut_B`. Deux créneaux qui se touchent
     * (18h-19h et 19h-20h) sont acceptés : c'est la continuité d'une journée.
     *
     *      * @throws ValidationException
     */
    protected function assertConflitHoraire(
        EnseignantProfil $profil,
        AffectationEnseignant $affectation,
        int $jourSemaine,
        string $heureDebut,
        string $heureFin,
        ?int $ignoreId = null,
    ): void {
        $conflits = $this->conflits(
            $profil,
            $affectation,
            $jourSemaine,
            $heureDebut,
            $heureFin,
            $ignoreId,
        );

        if ($conflits['enseignant'] !== null) {
            throw ValidationException::withMessages([
                'heure_debut' => sprintf(
                    'Vous avez déjà un cours de %s à %s (%s – %s) auprès d\'un autre élève.',
                    PlanningJourSemaine::label($jourSemaine),
                    substr($heureDebut, 0, 5),
                    substr((string) $conflits['enseignant']->heure_debut, 0, 5),
                    substr((string) $conflits['enseignant']->heure_fin, 0, 5),
                ),
            ]);
        }

        if ($conflits['eleve'] !== null) {
            $memeEnseignant = (int) $conflits['eleve']->enseignant_id === (int) $profil->id;

            throw ValidationException::withMessages([
                'heure_debut' => $memeEnseignant
                    ? sprintf(
                        'Cet élève a déjà cours de %s à %s (%s – %s).',
                        PlanningJourSemaine::label($jourSemaine),
                        substr($heureDebut, 0, 5),
                        substr((string) $conflits['eleve']->heure_debut, 0, 5),
                        substr((string) $conflits['eleve']->heure_fin, 0, 5),
                    )
                    : sprintf(
                        'Cet élève a déjà cours de %s à %s avec un autre enseignant.',
                        PlanningJourSemaine::label($jourSemaine),
                        substr($heureDebut, 0, 5),
                    ),
            ]);
        }
    }

    /**
     * @return array{enseignant: ?PlanningCours, eleve: ?PlanningCours}
     */
    protected function conflits(
        EnseignantProfil $profil,
        AffectationEnseignant $affectation,
        int $jourSemaine,
        string $heureDebut,
        string $heureFin,
        ?int $ignoreId = null,
    ): array {
        $chevauchement = function ($query) use ($jourSemaine, $heureDebut, $heureFin) {
            $query->where('jour_semaine', $jourSemaine)
                // debut_A < fin_B  ET  fin_A > debut_B
                ->where('heure_debut', '<', $heureFin)
                ->where('heure_fin', '>', $heureDebut);
        };

        $base = fn () => PlanningCours::query()
            ->where('jour_semaine', $jourSemaine)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId));

        // 1. L'enseignant est-il déjà pris sur ce créneau, pour un autre élève ?
        //    On parcourt toutes ses affectations : l'enseignant ne peut pas
        //    être au domicile de deux élèves au même moment. La même affectation
        //    est écartée pour que le message ne parle pas d'un « autre élève »
        //    quand il s'agit du même — ce cas est couvert par la règle 2.
        $conflitEnseignant = $base()
            ->tap($chevauchement)
            ->where('enseignant_id', $profil->id)
            ->where('affectation_enseignant_id', '!=', $affectation->id)
            ->orderBy('heure_debut')
            ->first();

        // 2. L'élève est-il déjà en cours sur ce créneau ?
        //    Le périmètre est l'élève, pas l'affectation : le même enfant peut
        //    être suivi sur plusieurs contrats (et donc plusieurs affectations,
        //    avec des enseignants différents). C'est le sens du métier — un
        //    élève ne peut pas suivre deux cours dans la même tranche.
        $eleveId = $affectation->contrat?->eleve_id;

        $conflitEleve = $eleveId === null ? null : $base()
            ->tap($chevauchement)
            ->whereHas('affectation.contrat', fn ($query) => $query->where('eleve_id', $eleveId))
            ->orderBy('heure_debut')
            ->first();

        return [
            'enseignant' => $conflitEnseignant,
            'eleve' => $conflitEleve,
        ];
    }
}