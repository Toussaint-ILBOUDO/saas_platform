<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\ObjectifPedagogique;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ObjectifPedagogiqueService
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = ObjectifPedagogique::query()
            ->with(['eleve.user', 'eleve.classe', 'objectifsMatieres.matiere']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('eleve.user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['periode'])) {
            $query->where('periode', $filters['periode']);
        }

        if ($user->hasRole('enseignant') && $user->enseignantProfil) {
            $query->whereHas('eleve.contrats.affectations', function ($q) use ($user) {
                $q->where('enseignant_id', $user->enseignantProfil->id);
            });
        }

        if ($user->hasRole('parent')) {
            $query->whereHas('eleve', function ($q) use ($user) {
                $q->where('parent_id', $user->id);
            });
        }

        if ($user->hasRole('eleve') && $user->eleve) {
            $query->where('eleve_id', $user->eleve->id);
        }

        return $query->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function getElevesForTeacher(User $user)
    {
        return \App\Models\AffectationEnseignant::query()
            ->with('contrat.eleve.user')
            ->where('enseignant_id', $user->enseignantProfil->id)
            ->where('statut', 'actif')
            ->get()
            ->pluck('contrat.eleve')
            ->filter()
            ->unique('id')
            ->values();
    }

    public function create(array $data): ObjectifPedagogique
    {
        return DB::transaction(function () use ($data) {

            $objectif = ObjectifPedagogique::create([
                'eleve_id' => $data['eleve_id'],
                'periode' => $data['periode'],
                'moyenne_visee' => $data['moyenne_visee'] ?? 0,
                'materiel_disponible' => $data['materiel_disponible'] ?? null,
                'materiel_manquant' => $data['materiel_manquant'] ?? null,
            ]);

            if (!empty($data['matieres'])) {
                foreach ($data['matieres'] as $matiereData) {
                    $objectif->objectifsMatieres()->create([
                        'matiere_id' => $matiereData['matiere_id'],
                        'moyenne_visee' => $matiereData['moyenne_visee'] ?? 0,
                    ]);
                }
            }

            return $objectif->load(['eleve.user', 'eleve.classe', 'objectifsMatieres.matiere']);
        });
    }

    public function update(ObjectifPedagogique $objectif, array $data): ObjectifPedagogique
    {
        return DB::transaction(function () use ($objectif, $data) {

            $objectif->update([
                'periode' => $data['periode'] ?? $objectif->periode,
                'moyenne_visee' => $data['moyenne_visee'] ?? $objectif->moyenne_visee,
                'moyenne_obtenue' => $data['moyenne_obtenue'] ?? $objectif->moyenne_obtenue,
                'materiel_disponible' => $data['materiel_disponible'] ?? $objectif->materiel_disponible,
                'materiel_manquant' => $data['materiel_manquant'] ?? $objectif->materiel_manquant,
                'commentaire_admin' => $data['commentaire_admin'] ?? $objectif->commentaire_admin,
            ]);

            if (isset($data['matieres'])) {
                $objectif->objectifsMatieres()->delete();

                foreach ($data['matieres'] as $matiereData) {
                    $objectif->objectifsMatieres()->create([
                        'matiere_id' => $matiereData['matiere_id'],
                        'moyenne_visee' => $matiereData['moyenne_visee'] ?? 0,
                        'moyenne_obtenue' => $matiereData['moyenne_obtenue'] ?? null,
                        'commentaire' => $matiereData['commentaire'] ?? null,
                    ]);
                }
            }

            return $objectif->fresh(['eleve.user', 'eleve.classe', 'objectifsMatieres.matiere']);
        });
    }

    public function delete(ObjectifPedagogique $objectif): void
    {
        $objectif->delete();
    }
}
