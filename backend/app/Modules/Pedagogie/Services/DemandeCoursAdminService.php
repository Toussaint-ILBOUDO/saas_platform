<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\ContratCours;
use App\Models\DemandeCours;
use App\Models\Eleve;
use App\Models\User;
use App\Modules\Users\Services\EleveService;
use App\Modules\Users\Services\ParentService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Traitement d'une demande de cours : statut, puis constitution du dossier.
 *
 * Chaque action est **idempotente**. Une demande Drives le backoffice, où
 * l'admin peut double-cliquer, recharger la page ou rejouer une action déjà
 * faite. Le filet de sécurité est `demande_cours.parent_id` / `eleve_id` /
 * `contrat_cours_id` : si la cible est déjà créée, l'action renvoie l'existant
 * au lieu d'en fabriquer un second. Une exception à cette règle : « traiter »
 * puis « refuser » se contredisent, c'est un vrai conflit d'intention, pas un
 * doublon — il est donc refusé explicitement.
 */
class DemandeCoursAdminService
{
    public function __construct(
        private readonly ParentService $parents,
        private readonly EleveService $eleves,
    ) {}

    public function paginate(int $perPage = 15)
    {
        return DemandeCours::query()
            ->with([
                'classe',
                'typeCours',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function getStats(): array
    {
        return [
            'total' => DemandeCours::count(),

            'en_attente' => DemandeCours::where(
                'statut',
                'en_attente'
            )->count(),

            'traitees' => DemandeCours::where(
                'statut',
                'traitee'
            )->count(),
        ];
    }

    /**
     * Marque la demande comme traitée.
     *
     * Idempotent : l'admin peut rejouer l'action (double-clic, retour arrière
     * du navigateur) sans provoquer d'erreur ni écraser `updated_at` d'une
     * demande déjà traitée.
     */
    public function valider(
        DemandeCours $demandeCours
    ): DemandeCours {
        if ($demandeCours->statut !== DemandeCours::TRAITEE) {
            $this->refuserSiDejaAnnulee($demandeCours);

            $demandeCours->update([
                'statut' => DemandeCours::TRAITEE,
            ]);
        }

        return $demandeCours->refresh();
    }

    /**
     * Refuse la demande : le cabinet ne donne pas suite.
     *
     * `annulee` est le statut déjà documenté sur la table pour ce cas ; on ne
     * le remplace pas par un nouveau `rejetee` qui dirait la même chose.
     */
    public function refuser(DemandeCours $demandeCours): DemandeCours
    {
        if ($demandeCours->statut !== DemandeCours::ANNULEE) {
            // Une demande déjà traitée a donné lieu à un contrat : la retirer
            // laisserait un engagement de facturation sans trace côté demande.
            if ($demandeCours->contrat_cours_id !== null) {
                throw ValidationException::withMessages([
                    'statut' => 'Cette demande a déjà produit un contrat : suspendez ou terminez le contrat plutôt que de refuser la demande.',
                ]);
            }

            $demandeCours->update([
                'statut' => DemandeCours::ANNULEE,
            ]);
        }

        return $demandeCours->refresh();
    }

    /**
     * Crée le parent de la demande.
     *
     * Renvoie l'existant si `parent_id` est déjà renseigné : c'est le garde-fou
     * anti-doublon, et il permet à l'écran d'afficher « déjà créé ».
     */
    public function creerParent(DemandeCours $demandeCours, array $data): User
    {
        if ($demandeCours->parent_id !== null) {
            return User::findOrFail($demandeCours->parent_id);
        }

        $this->refuserSiDejaAnnulee($demandeCours);

        // `telephone_whatsapp` est nullable dans la demande : on retombe sur le
        // téléphone pour que le parent créé soit joignable, comme pour le lien
        // WhatsApp de la fiche.
        $whatsapp = $data['telephone_whatsapp'] ?? $demandeCours->numeroWhatsApp();
        $appel = $data['telephone_appel'] ?? $demandeCours->telephone;

        $this->refuserSiParentDejaConnu($whatsapp, $appel);

        $parent = $this->parents->create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone_whatsapp' => $whatsapp,
            'telephone_appel' => $appel,
            'email' => $data['email'] ?? null,
            // `ParentService` hache lui-même ; on lui passe le mot de passe en
            // clair, pas déjà haché.
            'password' => $data['password'],
            'profession' => $data['profession'] ?? null,
            'adresse_domicile' => $data['adresse_domicile'] ?? null,
            // Un parent créé depuis une demande a au moins cet enfant en vue ;
            // `nombre_enfants` reste Adjustable ensuite depuis sa fiche.
            'nombre_enfants' => 1,
        ]);

        $demandeCours->update(['parent_id' => $parent->id]);

        return $parent;
    }

    /**
     * Crée l'élève rattaché au parent de la demande.
     *
     * `classe_id` retombe sur la classe demandée : le parent a indiqué la
     * classe de son enfant, c'est l'information la plus fiable dont on dispose.
     */
    public function creerEleve(DemandeCours $demandeCours, array $data): Eleve
    {
        if ($demandeCours->eleve_id !== null) {
            return Eleve::findOrFail($demandeCours->eleve_id);
        }

        $this->refuserSiDejaAnnulee($demandeCours);

        $parentId = $data['parent_id'] ?? $demandeCours->parent_id;
        if ($parentId === null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Créez d\'abord le parent : un élève doit être rattaché à un parent du cabinet.',
            ]);
        }

        $parent = User::findOrFail($parentId);

        // `eleves.parent_id` référence `users.id`. Vérifier le rôle évite qu'un
        // `users.id` erroné fasse porter cet élève par un enseignant, ce qui
        // ouvrirait à celui-ci les contrats d'une autre famille
        // (`ContratCoursPolicy::view`).
        if (! $parent->hasRole('parent')) {
            throw ValidationException::withMessages([
                'parent_id' => 'Le compte rattaché à cette demande n\'a pas le rôle parent.',
            ]);
        }

        $user = $this->eleves->create([
            'nom' => $data['nom'] ?? $demandeCours->nom_parent,
            'prenom' => $data['prenom'],
            'classe_id' => $data['classe_id'] ?? $demandeCours->classe_id,
            'parent_id' => $parent->id,
            'date_naissance' => $data['date_naissance'] ?? null,
            'lieu_naissance' => $data['lieu_naissance'] ?? null,
            'ecole' => $data['ecole'] ?? null,
            // Le compte élève reste inactif : `EleveService` met `statut = false`
            // et l'activation passe par un mot de passe choisi par le parent.
            'password' => $data['password'] ?? Str::random(16),
        ]);

        $demandeCours->update(['eleve_id' => $user->eleve->id]);

        // On lie aussi la demande au parent choisi (si différent) pour conserver
        // la cohérence du dossier et permettre de poursuivre le parcours même si
        // l'admin revient plus tard.
        if ($demandeCours->parent_id !== $parent->id) {
            $demandeCours->update(['parent_id' => $parent->id]);
        }

        return $user->eleve;
    }

    /**
     * Crée le contrat de cours de l'élève.
     *
     * Délégué à `ContratCoursService` : il gère la transaction, la règle de
     * compétence enseignant/matière et les notifications. On ne le réimplémente
     * pas ici, sinon les règles de facturation existeraient en deux endroits.
     */
    public function creerContrat(DemandeCours $demandeCours, array $data): ContratCours
    {
        if ($demandeCours->contrat_cours_id !== null) {
            return ContratCours::findOrFail($demandeCours->contrat_cours_id);
        }

        $this->refuserSiDejaAnnulee($demandeCours);

        if ($demandeCours->eleve_id === null) {
            throw ValidationException::withMessages([
                'eleve_id' => 'Créez d\'abord l\'élève : un contrat de cours porte toujours sur un élève.',
            ]);
        }

        $contrat = app(ContratCoursService::class)->create([
            'eleve_id' => $demandeCours->eleve_id,
            // Repli sur le type demandé : le parent a coché « domicile » ou
            // « en ligne », l'admin peut en changer avant de valider.
            'type_cours_id' => $data['type_cours_id'] ?? $demandeCours->type_cours_id,
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'] ?? null,
            'autres_frais_suivi' => $data['autres_frais_suivi'] ?? 0,
            'notes_admin' => $data['notes_admin'] ?? null,
            'affectations' => $data['affectations'],
        ]);

        $demandeCours->update(['contrat_cours_id' => $contrat->id]);

        // Un contratmarque la demande comme traitée : le dossier est constitué,
        // il n'y a plus rien à qualifier.
        if ($demandeCours->statut !== DemandeCours::TRAITEE) {
            $demandeCours->update(['statut' => DemandeCours::TRAITEE]);
        }

        return $contrat;
    }

/**
     * Refuse de créer un second compte pour un numéro déjà connu.
     *
     * La même famille demande des cours régulièrement ; il faut donc rattacher
     * la nouvelle demande au parent existant, pas fabriquer un doublon. Le
     * message nomme le compte trouvé pour que l'écran puisse l'afficher tel
     * quel. La vérification vit ici et non dans le FormRequest parce que les
     * numéros effectifs ne sont connus qu'après repli sur ceux de la demande.
     */
    private function refuserSiParentDejaConnu(?string $whatsapp, ?string $appel): void
    {
        $whatsapp = trim((string) $whatsapp);
        $appel = trim((string) $appel);

        if ($whatsapp === '' && $appel === '') {
            return;
        }

        $existant = User::role('parent')
            ->where(function ($query) use ($whatsapp, $appel): void {
                $query
                    ->when($whatsapp !== '', fn ($q) => $q->where('telephone_whatsapp', $whatsapp))
                    ->when(
                        $appel !== '',
                        fn ($q) => $q->when(
                            $whatsapp !== '',
                            fn ($q2) => $q2->orWhere('telephone_appel', $appel),
                            fn ($q2) => $q2->where('telephone_appel', $appel)
                        )
                    );
            })
            ->first();

        if ($existant) {
            throw ValidationException::withMessages([
                'telephone_whatsapp' => sprintf(
                    'Un parent est déjà enregistré avec ce numéro (%s). Utilisez-le plutôt que d\'en créer un second.',
                    $existant->email ?: 'sans email'
                ),
            ]);
        }
    }

    /**
     * « Traiter » sur une demande refusée est un contresens : la demande est
 * sortie du pipeline. On le dit explicitement plutôt que de laisser le
     * statut basculer en silence.
 */
    private function refuserSiDejaAnnulee(DemandeCours $demandeCours): void
    {
        if ($demandeCours->statut === DemandeCours::ANNULEE) {
            throw ValidationException::withMessages([
                'statut' => 'Cette demande a été refusée : elle ne peut plus être traitée.',
            ]);
        }
    }
}