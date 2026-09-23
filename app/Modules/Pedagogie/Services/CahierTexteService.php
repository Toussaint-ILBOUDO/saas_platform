<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\User;
use Carbon\Carbon;
use App\Models\CahierTexte;
use App\Models\Eleve;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use App\Models\AffectationEnseignant;

class CahierTexteService
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = CahierTexte::query()->with([
            'affectation.matiere',
            'affectation.enseignant.user',
            'affectation.contrat.eleve.user',
        ]);

        // Filtres recherche
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('contenu_cours', 'like', "%{$search}%")
                    ->orWhereHas('affectation.matiere', function ($m) use ($search) {
                        $m->where('nom', 'like', "%{$search}%");
                    })
                    ->orWhereHas('affectation.contrat.eleve.user', function ($e) use ($search) {
                        $e->where('nom', 'like', "%{$search}%")
                          ->orWhere('prenom', 'like', "%{$search}%");
                    });
            });
        }

        // Restrictions selon rôle
        if ($user->hasRole('enseignant')) {
            $query->whereHas('affectation', function ($q) use ($user) {
                $q->where('enseignant_id', $user->enseignantProfil->id);
            });
        }

        if ($user->hasRole('parent')) {
            $query->whereHas('affectation.contrat.eleve', function ($q) use ($user) {
                $q->whereIn('id', $user->enfants->pluck('id'));
            });
        }

        if ($user->hasRole('eleve') && $user->eleve) {
            $query->whereHas('affectation.contrat', function ($q) use ($user) {
                $q->where('eleve_id', $user->eleve->id);
            });
        }

        // Admin : pas de filtre

        return $query->latest('date_seance')
            ->paginate(20)
            ->withQueryString();
    }

    public function getAvailableAffectations(User $user)
    {
        return AffectationEnseignant::query()
            ->with(['matiere', 'contrat.eleve.user'])
            ->where('enseignant_id', $user->enseignantProfil->id)
            ->where('statut', 'actif')
            ->latest()
            ->get();
    }

    public function create(array $data): CahierTexte
    {
        $enseignant = auth()->user()->enseignantProfil;

        $affectation = AffectationEnseignant::query()
            ->whereKey($data['affectation_enseignant_id'])
            ->where('enseignant_id', $enseignant->id)
            ->where('statut', 'actif')
            ->first();

        if (!$affectation) {
            throw ValidationException::withMessages([
                'affectation_enseignant_id' => 'Affectation invalide.',
            ]);
        }

        if (
            $affectation->date_fin &&
            Carbon::parse($data['date_seance'])->gt(Carbon::parse($affectation->date_fin))
        ) {
            throw ValidationException::withMessages([
                'date_seance' => 'Cette affectation est terminée.',
            ]);
        }

        $debut = Carbon::parse($data['heure_debut']);
        $fin   = Carbon::parse($data['heure_fin']);

        if ($fin->lessThanOrEqualTo($debut)) {
            throw ValidationException::withMessages([
                'heure_fin' => "L'heure de fin doit être supérieure à l'heure de début.",
            ]);
        }

        $data['duree_heures'] = $debut->diffInMinutes($fin) / 60;

        return DB::transaction(fn () => CahierTexte::create($data));
    }

    public function update(CahierTexte $cahier, array $data): CahierTexte
    {
        if (!Carbon::parse($cahier->date_seance)->isToday()) {
            throw ValidationException::withMessages([
                'date_seance' => 'La modification est autorisée uniquement le jour de la séance.',
            ]);
        }

        $debut = Carbon::parse($data['heure_debut']);
        $fin   = Carbon::parse($data['heure_fin']);

        if ($fin->lessThanOrEqualTo($debut)) {
            throw ValidationException::withMessages([
                'heure_fin' => "L'heure de fin doit être supérieure à l'heure de début.",
            ]);
        }

        $data['duree_heures'] = $debut->diffInMinutes($fin) / 60;

        return DB::transaction(function () use ($cahier, $data) {
            $cahier->update($data);
            return $cahier->fresh();
        });
    }

    public function loadDetails(CahierTexte $cahier): CahierTexte
    {
        return $cahier->load([
            'affectation.matiere',
            'affectation.enseignant.user',
            'affectation.contrat.eleve.user',
        ]);
    }

    public function getElevesForTeacher(User $user)
    {
        return AffectationEnseignant::query()
            ->with('contrat.eleve.user')
            ->where('enseignant_id', $user->enseignantProfil->id)
            ->where('statut', 'actif')
            ->get()
            ->pluck('contrat.eleve')
            ->filter()
            ->unique('id')
            ->values();
    }

    public function getAffectationsForEleve(User $user, Eleve $eleve)
    {
        return AffectationEnseignant::query()
            ->with(['contrat.eleve.user', 'matiere'])
            ->where('enseignant_id', $user->enseignantProfil->id)
            ->where('statut', 'actif')
            ->whereHas('contrat.eleve', function ($q) use ($eleve) {
                $q->where('id', $eleve->id);
            })
            ->get();
    }

    public function getWeekForEleve(
        Eleve $eleve,
        Carbon $from,
        Carbon $to
    ) {
        return $this->getWeekForEleves(collect([$eleve]), $from, $to);
    }

    /**
     * Séances de la semaine pour un ensemble d'élèves (planning parent).
     */
    public function getWeekForEleves(
        $eleves,
        Carbon $from,
        Carbon $to
    ) {
        $eleveIds = collect($eleves)->pluck('id');

        return CahierTexte::query()
            ->with([
                'affectation.matiere',
                'affectation.enseignant.user',
                'affectation.contrat.eleve.user',
            ])
            ->whereBetween('date_seance', [$from->startOfDay(), $to->endOfDay()])
            ->whereHas('affectation.contrat', function ($q) use ($eleveIds) {
                $q->whereIn('eleve_id', $eleveIds);
            })
            ->orderBy('date_seance')
            ->orderBy('heure_debut')
            ->get();
    }
}