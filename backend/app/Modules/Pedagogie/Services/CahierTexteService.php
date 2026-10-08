<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\User;
use App\Support\Recherche;
use Carbon\Carbon;
use App\Models\CahierTexte;
use App\Models\Eleve;
use App\Modules\Finance\Services\GardePeriodeOuverte;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Models\AffectationEnseignant;

class CahierTexteService
{
    public function __construct(
        private GardePeriodeOuverte $garde
    ) {}

    /**
     * Liste paginée des séances visibles par l'utilisateur.
     *
     * Le périmètre est déduit du rôle connecté, jamais d'un paramètre : un
     * `?eleve_id=` devinable ne doit pas ouvrir le cahier d'un autre enfant.
     */
    public function paginateForUser(
        User $user,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = CahierTexte::query()->with([
            'affectation.matiere',
            'affectation.enseignant.user',
            'affectation.contrat.eleve.user',
        ]);

        // Filtres recherche.
        if (!empty($filters['search'])) {
            // D-057 : `Recherche` normalise casse **et** accents. Le `LIKE`
            // nu d'ici comparait « Ouédraogo » et « Ouedraogo » comme
            // différentes — c'est-à-dire que la moitié des familles du projet
            // était introuvable à la recherche.
            $query->where(function ($q) use ($filters) {
                Recherche::likeInsensible($q, ['contenu_cours'], $filters['search']);

                $q->orWhereHas('affectation.matiere', function ($m) use ($filters) {
                    Recherche::likeInsensible($m, ['nom'], $filters['search']);
                });

                $q->orWhereHas('affectation.contrat.eleve.user', function ($e) use ($filters) {
                    Recherche::likeInsensible($e, ['nom', 'prenom'], $filters['search']);
                });
            });
        }

        // Un journal de cours se consulte par période : sans borne de date,
        // l'enseignant fait défiler plusieurs années de séances avant d'atteindre
        // la dernière.
        $query->betweenDates($filters['date_debut'] ?? null, $filters['date_fin'] ?? null);

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

        // D-058 : la taille de page était figée en dur, la demande du client
        // était ignorée sans le moindre message d'erreur.
        return $query->latest('date_seance')
            ->latest('id')
            ->paginate(max(1, min($perPage, 100)))
            ->withQueryString();
    }

    /**
     * Historique d'un élève donné, pour le parent et l'élève.
     *
     * L'autorisation est rendue par la policy `viewHistory` **avant** d'arriver
     * ici ; cette méthode ne fait qu'appliquer le périmètre. Elle est distincte
     * de `paginateForUser` car l'élève visé n'est pas forcément celui du
     * connecté (cas du parent).
     *
     * Le `latest('date_seance')` est accompagné d'un `latest('id')` : deux
     * séances peuvent partager une date (cours du matin et du soir, deux
     * matières le même jour) et l'ordre deviendrait alors indéfini — deux pages
     * successives pourraient afficher deux fois la même séance.
     */
    public function paginateForEleve(
        Eleve $eleve,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = CahierTexte::query()
            ->with([
                'affectation.matiere',
                'affectation.enseignant.user',
                'affectation.contrat.eleve.user',
            ])
            ->forEleve($eleve->id)
            ->betweenDates($filters['date_debut'] ?? null, $filters['date_fin'] ?? null);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                Recherche::likeInsensible($q, ['contenu_cours'], $filters['search']);

                $q->orWhereHas('affectation.matiere', function ($m) use ($filters) {
                    Recherche::likeInsensible($m, ['nom'], $filters['search']);
                });
            });
        }

        return $query->latest('date_seance')
            ->latest('id')
            ->paginate(max(1, min($perPage, 100)))
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

    /**
     * Saisie d'une séance.
     *
     * L'enseignant est **explicite** et non déduit de `auth()` : une méthode
     * qui lit la session ne peut être ni testée dans un contexte précis, ni
     * appelée par un job, ni réutilisée par l'API. Le contrôleur web passe
     * `auth()->user()`, l'API passe l'utilisateur authentifié — même règle,
     * même service.
     *
     * @return array{0: CahierTexte, 1: bool} la séance et le fait qu'elle
     *                                         ait été créée (false = reprise
     *                                         idempotente d'une séance déjà
     *                                         enregistrée)
     */
    public function create(User $user, array $data): array
    {
        $enseignant = $user->enseignantProfil;

        abort_if(! $enseignant, 403);

        // Idempotence hors-ligne : le client qui n'a pas eu l'accusé de
        // réception réémet la même création avec le même `uuid_client`. Le
        // doit être « au plus une fois » : renvoyer un doublon ferait
        // compter deux fois des heures qui alimentent la facture et la paie.
        $uuid = $data['uuid_client'] ?? null;

        if ($uuid !== null) {
            $existante = CahierTexte::query()
                ->where('uuid_client', $uuid)
                ->with(['affectation.enseignant'])
                ->first();

            if ($existante) {
                // Le même uuid sous un autre enseignant est une collision, pas
                // une reprise : on ne renvoie surtout pas la séance d'un collègue.
                if ((int) $existante->affectation?->enseignant_id !== (int) $enseignant->id) {
                    throw ValidationException::withMessages([
                        'uuid_client' => 'Cet identifiant de séance est déjà utilisé.',
                    ]);
                }

                return [$this->loadDetails($existante), false];
            }
        }

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

        // D-051 : une séance ne peut pas être saisie sur une période close, ni
        // hors de toute période comptable — sans période, ces heures ne
        // rejoindront aucun rapport mensuel et donc aucune facture ni bulletin.
        $this->garde->exigerPeriodeOuvertePourDate(
            $data['date_seance'],
            'La saisie de la séance'
        );

        try {
            return DB::transaction(fn () => [
                $this->loadDetails(CahierTexte::create($data)),
                true,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            /*
            | Le contrôle d'idempotence ci-dessus est un « lire avant d'écrire » :
            | deux réémissions **simultanées** du même `uuid_client` (client
            | hors-ligne qui réessaie pendant que la première requête est encore
            | en vol) le franchissent toutes les deux, et la seconde meurt sur
            | l'index unique. Rattraper la violation et relire la ligne est donc
            | ce qui rend la promesse « au plus une fois » réellement tenable —
            | l'index unique reste la seule source de vérité, le test amont n'est
            | qu'un raccourci pour le cas courant.
            */
            $existante = CahierTexte::query()
                ->where('uuid_client', $uuid)
                ->with(['affectation.enseignant'])
                ->first();

            if ($existante && (int) $existante->affectation?->enseignant_id === (int) $enseignant->id) {
                return [$this->loadDetails($existante), false];
            }

            throw $e;
        }
    }

    /**
     * Correction d'une séance.
     *
     * Comme pour la création, l'enseignant est explicite et la propriété de la
     * séance est vérifiée ici : un `PUT` sur l'identifiant d'une séance de
     * collègue ne doit pas être possible, même pour un enseignant authentifié.
     */
    public function update(User $user, CahierTexte $cahier, array $data): CahierTexte
    {
        $enseignant = $user->enseignantProfil;

        abort_if(! $enseignant, 403);

        $cahier->loadMissing('affectation');

        abort_unless(
            (int) $cahier->affectation?->enseignant_id === (int) $enseignant->id,
            403,
        );

        if (!Carbon::parse($cahier->date_seance)->isToday()) {
            throw ValidationException::withMessages([
                'date_seance' => 'La modification est autorisée uniquement le jour de la séance.',
            ]);
        }

        /*
        | La date d'une séance est immuable : `date_seance` est `fillable`, donc
        | un appelant autre que le formulaire web (API, job) pouvait décaler la
        | séance d'un jour — vers une période close, donc hors du gel. La seule
        | façon de corriger une date erronée reste de supprimer la séance et de
        | la ressaisir, ce que la période ouverte autorise.
        */
        if (isset($data['date_seance']) && Carbon::parse($data['date_seance'])->ne(Carbon::parse($cahier->date_seance))) {
            throw ValidationException::withMessages([
                'date_seance' => 'La date d\'une séance ne peut pas être modifiée : supprimez la séance et ressaisissez-la.',
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

        // `uuid_client` est l'identité de la séance pour le client hors-ligne :
        // le changer en cours de route ferait perdre à l'app la correspondance
        // entre sa copie locale et la ligne serveur, donc créer des doublons
        // au lieu de corriger. Il n'est jamais modifiable.
        unset($data['uuid_client']);

        // Une durée modifiée change les heures retenues pour la facture et la
        // paie : si la période est close, la correction n'est plus possible.
        $this->garde->exigerPeriodeOuvertePourDate(
            $cahier->date_seance,
            'La modification de la séance'
        );

        return DB::transaction(function () use ($cahier, $data) {
            $cahier->update($data);

            return $this->loadDetails($cahier->fresh());
        });
    }

    /**
     * Suppression d'une séance.
     *
     * Contrairement à un contrat (D-054), une ligne de `cahier_textes` **peut**
     * être supprimée : elle n'est référencée par aucune ligne de facture ni de
     * bulletin. Ces heures alimentent toutefois le rapport mensuel, qui peut
     * déjà avoir été validé — la suppression reste donc soumise au même garde
     * de période ouverte que la saisie.
     *
     * Une séance validée par l'administration est par ailleurs intouchable :
     * la retirer reviendrait à nier une validation.
     */
    public function delete(User $user, CahierTexte $cahier): void
    {
        $enseignant = $user->enseignantProfil;

        abort_if(! $enseignant, 403);

        $cahier->loadMissing('affectation');

        abort_unless(
            (int) $cahier->affectation?->enseignant_id === (int) $enseignant->id,
            403,
        );

        // La protection d'une séance validée est le gel de la période (D-051) : tant
        // que la période comptable est ouverte, l'enseignant peut corriger ou
        // retirer une saisie. Une fois close, aucune écriture n'est possible.
        $this->garde->exigerPeriodeOuvertePourDate(
            $cahier->date_seance,
            'La suppression de la séance'
        );

        $cahier->delete();
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