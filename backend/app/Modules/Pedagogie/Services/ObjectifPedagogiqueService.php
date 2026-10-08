<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\AffectationEnseignant;
use App\Models\ObjectifPedagogique;
use App\Models\PeriodeComptable;
use App\Models\User;
use App\Modules\Finance\Services\GardePeriodeOuverte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ObjectifPedagogiqueService
{
    public function __construct(
        private GardePeriodeOuverte $garde
    ) {}

    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = ObjectifPedagogique::query()
            ->with([
                'eleve.user',
                'eleve.classe',
                'periode',
                'enseignant.user',
                'objectifsMatieres.matiere',
            ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->whereHas('eleve.user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        // D-050 : le filtre porte désormais sur une PÉRIODE COMPTABLE.
        if (! empty($filters['periode_id'])) {
            $query->where('periode_id', $filters['periode_id']);
        }

        if ($user->hasRole('enseignant') && $user->enseignantProfil) {
            // Un enseignant ne voit que SES objectifs.
            $query->where('enseignant_id', $user->enseignantProfil->id);
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

    /**
     * D-050 — un objectif par (élève, PÉRIODE COMPTABLE, enseignant).
     */
    public function create(array $data): ObjectifPedagogique
    {
        return DB::transaction(function () use ($data) {
            $periode = PeriodeComptable::findOrFail($data['periode_id']);

            $this->garde->exigerOuverte($periode, "La saisie d'objectifs");

            $enseignantId = $this->resoudreEnseignant($data);

            $this->refuserDoublon(
                (int) $data['eleve_id'],
                $periode->id,
                $enseignantId
            );

            $objectif = ObjectifPedagogique::create([
                'eleve_id' => $data['eleve_id'],
                'periode_id' => $periode->id,
                'enseignant_id' => $enseignantId,
                'moyenne_visee' => $data['moyenne_visee'] ?? 0,
                'materiel_disponible' => $data['materiel_disponible'] ?? null,
                'materiel_manquant' => $data['materiel_manquant'] ?? null,
            ]);

            $this->remplacerMatieres($objectif, $data['matieres'] ?? []);

            return $this->charger($objectif);
        });
    }

    public function update(ObjectifPedagogique $objectif, array $data): ObjectifPedagogique
    {
        return DB::transaction(function () use ($objectif, $data) {
            /*
            | D-051 : la période d'origine est gelée. Un objectif déjà POSÉ sur
            | une période close ne peut donc pas en être déplacé — sans ce
            | contrôle, il suffisait d'indiquer une autre période ouverte pour
            | vider une période clôturée.
            */
            $this->garde->exigerOuverte(
                PeriodeComptable::findOrFail($objectif->periode_id),
                'La modification des objectifs'
            );

            $periodeId = (int) ($data['periode_id'] ?? $objectif->periode_id);

            /*
            | La période et l'enseignant constituent l'identité de l'objectif
            | (D-050) : un changement de période reste autorisé — c'est la
            | correction d'une saisie faite dans la mauvaise période — mais il
            | doit respecter l'unicité (élève, période, enseignant) et ne peut
            | viser qu'une période ouverte.
            */
            if ($periodeId !== (int) $objectif->periode_id) {
                $this->garde->exigerOuverte(
                    PeriodeComptable::findOrFail($periodeId),
                    'Le déplacement de l\'objectif vers une autre période'
                );

                $this->refuserDoublon(
                    (int) $objectif->eleve_id,
                    $periodeId,
                    $objectif->enseignant_id
                );

                $objectif->periode_id = $periodeId;
            }

            $objectif->fill([
                'moyenne_visee' => $data['moyenne_visee'] ?? $objectif->moyenne_visee,
                'moyenne_obtenue' => $data['moyenne_obtenue'] ?? $objectif->moyenne_obtenue,
                'materiel_disponible' => $data['materiel_disponible'] ?? $objectif->materiel_disponible,
                'materiel_manquant' => $data['materiel_manquant'] ?? $objectif->materiel_manquant,
                'commentaire_admin' => $data['commentaire_admin'] ?? $objectif->commentaire_admin,
            ])->save();

            if (isset($data['matieres'])) {
                $this->remplacerMatieres($objectif, $data['matieres']);
            }

            return $this->charger($objectif);
        });
    }

    public function delete(ObjectifPedagogique $objectif): void
    {
        // D-051 : une période close est gelée, objectifs compris.
        $this->garde->exigerOuverte(
            PeriodeComptable::findOrFail($objectif->periode_id),
            'La suppression des objectifs'
        );

        $objectif->delete();
    }

    /**
     * Périodes ouvertes, pour alimenter le sélecteur (D-050).
     */
    public function periodesOuvertes(): Collection
    {
        return PeriodeComptable::query()
            ->ouvertes()
            ->orderByDesc('date_debut')
            ->get();
    }

    /**
     * L'enseignant de l'objectif : celui de l'utilisateur s'il est enseignant,
     * sinon celui explicitement désigné, à condition qu'il soit affecté à l'élève.
     */
    private function resoudreEnseignant(array $data): ?int
    {
        $user = auth()->user();

        if ($user->hasRole('enseignant') && $user->enseignantProfil) {
            return (int) $user->enseignantProfil->id;
        }

        $enseignantId = $data['enseignant_id'] ?? null;

        if (! $enseignantId) {
            // Repli : premier enseignant affecté à l'élève, comme le backfill
            // de la migration D-050.
            return AffectationEnseignant::query()
                ->join('contrat_cours', 'contrat_cours.id', '=', 'affectation_enseignants.contrat_cours_id')
                ->where('contrat_cours.eleve_id', $data['eleve_id'])
                ->where('affectation_enseignants.statut', 'actif')
                ->orderBy('affectation_enseignants.id')
                ->value('affectation_enseignants.enseignant_id');
        }

        $affecte = AffectationEnseignant::query()
            ->join('contrat_cours', 'contrat_cours.id', '=', 'affectation_enseignants.contrat_cours_id')
            ->where('contrat_cours.eleve_id', $data['eleve_id'])
            ->where('affectation_enseignants.enseignant_id', $enseignantId)
            ->where('affectation_enseignants.statut', 'actif')
            ->exists();

        if (! $affecte) {
            throw ValidationException::withMessages([
                'enseignant_id' => 'Cet enseignant n\'est pas affecté à cet élève.',
            ]);
        }

        return (int) $enseignantId;
    }

    /**
     * Unicité : un seul objectif par élève, période comptable et enseignant.
     */
    private function refuserDoublon(int $eleveId, int $periodeId, ?int $enseignantId): void
    {
        $existe = ObjectifPedagogique::query()
            ->where('eleve_id', $eleveId)
            ->where('periode_id', $periodeId)
            ->when(
                $enseignantId,
                fn ($q) => $q->where('enseignant_id', $enseignantId),
                fn ($q) => $q->whereNull('enseignant_id')
            )
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'periode_id' => 'Un objectif existe déjà pour cet élève sur cette période.',
            ]);
        }
    }

    private function remplacerMatieres(ObjectifPedagogique $objectif, array $matieres): void
    {
        $objectif->objectifsMatieres()->delete();

        foreach ($matieres as $matiereData) {
            $objectif->objectifsMatieres()->create([
                'matiere_id' => $matiereData['matiere_id'],
                'moyenne_visee' => $matiereData['moyenne_visee'] ?? 0,
                'moyenne_obtenue' => $matiereData['moyenne_obtenue'] ?? null,
                'commentaire' => $matiereData['commentaire'] ?? null,
            ]);
        }
    }

    private function charger(ObjectifPedagogique $objectif): ObjectifPedagogique
    {
        return $objectif->load([
            'eleve.user',
            'eleve.classe',
            'periode',
            'enseignant.user',
            'objectifsMatieres.matiere',
        ]);
    }
}