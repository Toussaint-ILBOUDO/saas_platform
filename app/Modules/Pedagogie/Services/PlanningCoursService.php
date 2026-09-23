<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\AffectationEnseignant;
use App\Models\EnseignantProfil;
use App\Models\PlanningCours;
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
     * Créneaux des AUTRES enseignants qui interviennent auprès des mêmes
     * élèves que cet enseignant (exclusion de ses propres créneaux).
     */
    public function listSharedForEnseignant(EnseignantProfil $profil): Collection
    {
        $eleveIds = AffectationEnseignant::query()
            ->where('enseignant_id', $profil->id)
            ->where('statut', 'actif')
            ->with('contrat')
            ->get()
            ->pluck('contrat.eleve_id')
            ->filter()
            ->unique()
            ->values();

        if ($eleveIds->isEmpty()) {
            return collect();
        }

        return PlanningCours::query()
            ->with([
                'enseignant.user',
                'affectation.matiere',
                'affectation.contrat.eleve.user',
            ])
            ->where('enseignant_id', '!=', $profil->id)
            ->whereHas('affectation.contrat', function ($query) use ($eleveIds) {
                $query->whereIn('eleve_id', $eleveIds);
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

        $this->assertNoOverlap($affectation->id, $data['jour_semaine'], $data['heure_debut']);

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

        $this->assertNoOverlap(
            $affectation->id,
            $data['jour_semaine'],
            $data['heure_debut'],
            $creneau->id
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

    protected function assertNoOverlap(int $affectationId, int $jourSemaine, string $heureDebut, ?int $ignoreId = null): void
    {
        $existing = PlanningCours::query()
            ->where('affectation_enseignant_id', $affectationId)
            ->where('jour_semaine', $jourSemaine)
            ->where('heure_debut', $heureDebut)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'jour_semaine' => 'Un créneau existe déjà à cette heure pour ce cours.',
            ]);
        }
    }
}