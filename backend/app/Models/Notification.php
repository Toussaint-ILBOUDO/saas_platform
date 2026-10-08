<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'titre',
        'contenu',
        'type',
        'icone',
        'data',
        'lu',
        'date_lecture',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'date_lecture' => 'datetime',
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Icônes Bootstrap par type de notification.
     */
    public static function iconesParType(): array
    {
        return [
            'actualite'               => 'bi-megaphone',
            'librairie_commande'      => 'bi-cart-check',
            'contrat'                 => 'bi-file-earmark-text',
            'affectation'             => 'bi-person-check',
            'facture'                 => 'bi-receipt',
            'rapport'                 => 'bi-clipboard-data',
            'demande_cours'           => 'bi-journal-text',
            'bulletin_paie'           => 'bi-cash-stack',
            'temoignage'              => 'bi-chat-quote',
            'temoignage_moderation'   => 'bi-shield-exclamation',
            'temoignage_signalement'  => 'bi-flag',
            'temoignage_commentaire'  => 'bi-chat-dots',
        ];
    }

    /**
     * Retourne l'icône Bootstrap pour ce type de notification.
     */
    public function getIconeHtmlAttribute(): string
    {
        $classes = static::iconesParType();
        $icone = $this->icone ?? ($classes[$this->type] ?? 'bi-bell');

        return $icone;
    }

    /**
     * Couleur associée au type de notification (nom de classe Bootstrap text-*).
     */
    public function getCouleurAttribute(): string
    {
        return match ($this->type) {
            'facture'                 => 'text-warning',
            'bulletin_paie'           => 'text-success',
            'librairie_commande'      => 'text-primary',
            'rapport'                 => 'text-info',
            'demande_cours'           => 'text-info',
            'temoignage_signalement'  => 'text-danger',
            'temoignage_moderation'   => 'text-danger',
            default                   => 'text-primary',
        };
    }

    /**
     * URL de la ressource associée, basée sur le type + data.
     * Retourne null si aucune route pertinente n'existe.
     */
    public function getUrlAttribute(): ?string
    {
        $data = $this->data ?? [];

        return match ($this->type) {
            'contrat', 'affectation' => isset($data['contrat_id'])
                ? route('contrats.show', $data['contrat_id'])
                : null,

            'facture' => isset($data['facture_id'])
                ? route('finance.factures.show', $data['facture_id'])
                : null,

            'librairie_commande' => isset($data['commande_id'])
                ? route('admin.librairie.commandes.show', $data['commande_id'])
                : null,

            'actualite' => match (true) {
                isset($data['actualite_slug']) => route('actualites.show', $data['actualite_slug']),
                isset($data['actualite_id']) => route('actualites.show', $data['actualite_id']),
                default => null,
            },

            'temoignage' => isset($data['temoignage_id'])
                ? route('admin.temoignages.show', $data['temoignage_id'])
                : null,

            'temoignage_moderation' => route('temoignages.mes.index'),

            'temoignage_signalement' => route('admin.temoignages.signalements'),

            'temoignage_commentaire' => isset($data['slug'])
                ? route('temoignages.show', $data['slug'])
                : null,

            'rapport' => isset($data['rapport_id'])
                ? route('rapports-mensuels.show', $data['rapport_id'])
                : null,

            'demande_cours' => isset($data['demande_cours_id'])
                ? route('demande-cours.show', $data['demande_cours_id'])
                : null,

            /*
            | Le même bulletin s'ouvre dans deux espaces : celui de
            | l'enseignant (« mes-bulletins »), qui est le destinataire
            | habituel de ces notifications, et celui de l'administration.
            | L'URL est donc résolue pour l'utilisateur authentifié.
            */
            'bulletin_paie' => isset($data['bulletin_paie_id'])
                ? $this->urlBulletin((int) $data['bulletin_paie_id'])
                : null,

            default => null,
        };
    }

    /**
     * Route Angular de la ressource, à utiliser par l'espace (front).
     *
     * L'attribut `url` ci-dessus produit des URL **Blade** (`/factures/12`),
     * utiles au backoffice historique mais fausses dans l'UI Angular : elles
     *sortiraient de l'application et viseraient le domaine central au lieu du
     * domaine du cabinet. On expose donc un chemin distinct, relatif et
     * interne à l'espace, que le front préfixe par `/`.
     *
     * Retourne null quand aucun écran n'existe encore pour ce type — l'écran
     * notifications affiche alors la notification sans lien, ce qui est
     * préférable à un lien mort.
     */
    public function getRouteAngularAttribute(): ?string
    {
        $data = $this->data ?? [];

        return match ($this->type) {
            'contrat', 'affectation' => isset($data['contrat_id'])
                ? $this->routeAngularContrat((int) $data['contrat_id'])
                : null,

            'facture' => isset($data['facture_id'])
                ? $this->routeAngularFacture((int) $data['facture_id'])
                : null,

            // La boutique Angular n'existe pas encore (placeholder `modules/:slug`) :
            // un lien vers `boutique/commandes/{id}` ne matcherait aucune route et
            // renverrait sur le site public. On affiche la notification sans lien.
            'librairie_commande' => null,

            'actualite' => isset($data['actualite_id'])
                ? '/espace/actualites/' . $data['actualite_id']
                : null,

            // Idem : aucun écran témoignage dans l'espace Angular.
            'temoignage' => null,

            'rapport' => isset($data['rapport_id'])
                ? $this->routeAngularRapport((int) $data['rapport_id'])
                : null,

            'demande_cours' => isset($data['demande_cours_id'])
                ? '/espace/pedagogie/demandes-cours/' . $data['demande_cours_id']
                : '/espace/pedagogie/demandes-cours',

            'bulletin_paie' => isset($data['bulletin_paie_id'])
                ? $this->routeAngularBulletin((int) $data['bulletin_paie_id'])
                : null,

            default => null,
        };
    }

    /**
     * Une facture est émise **au parent débiteur** (`invoiceCreated` /
     * `invoicePaid` passent tous les deux par `facture->parent_id`) : sa
     * destination naturelle est le portail famille. L'administration, si elle
     * lit un jour cette notification, passe par l'écran de gestion.
     */
    private function routeAngularFacture(int $factureId): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (Roles::estParent($user)) {
            return '/espace/modules/mes-factures/' . $factureId;
        }

        if (Roles::estAdmin($user)) {
            return '/espace/finance/factures/' . $factureId;
        }

        return null;
    }

    /**
     * `reportSubmitted` prévient l'administration ; `reportValidated` /
     * `reportRejected` préviennent l'enseignant. Chacun a son écran.
     */
    private function routeAngularRapport(int $rapportId): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (Roles::estAdmin($user)) {
            return '/espace/pedagogie/rapports-mensuels/' . $rapportId;
        }

        if (Roles::estEnseignant($user)) {
            return '/espace/modules/rapports-mensuels/' . $rapportId;
        }

        return null;
    }

    /**
     * Un contrat vit dans trois écrans : `pedagogie/contrats` (admin),
     * `mes-contrats` (parent) et `mes-cours` (enseignant / élève).
     *
     * Viser la route d'administration depuis une notification parente ne
     * menait nulle part : ce chemin n'existe côté Angular que derrière
     * `roleAdminGuard` ET sans segment `:id`, donc le router ne trouvait
     * aucun enfant, retombait sur le `**` racine et affichait le site public.
     *
     * Retourne null hors contexte authentifié : mieux vaut une notification
     * sans lien qu'un lien qui mène ailleurs.
     */
    private function routeAngularContrat(int $contratId): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (Roles::estAdmin($user)) {
            return '/espace/pedagogie/contrats/' . $contratId;
        }

        if (Roles::estParent($user)) {
            return '/espace/modules/mes-contrats/' . $contratId;
        }

        return '/espace/modules/mes-cours';
    }

    /**
     * Le même bulletin s'ouvre dans deux espaces (voir `urlBulletin`), donc la
     * route dépend du rôle de l'utilisateur connecté.
     */
    private function routeAngularBulletin(int $bulletinId): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return match (true) {
            Roles::estEnseignant($user) => '/espace/modules/mes-bulletins/' . $bulletinId,
            Roles::estAdmin($user) => '/espace/finance/bulletins-paie/' . $bulletinId,
            default => null,
        };
    }

    /**
     * Libellé de l'action proposée, adapté au contexte.
     */
    public function getActionLabelAttribute(): ?string
    {
        return match ($this->type) {
            'facture'                 => 'Voir la facture',
            'bulletin_paie'           => null,
            'librairie_commande'      => 'Voir la commande',
            'rapport'                 => 'Voir le rapport',
            'demande_cours'           => 'Voir la demande',
            'contrat'                 => 'Voir le contrat',
            'affectation'             => 'Voir le contrat',
            'actualite'               => 'Lire l\'actualité',
            'temoignage'              => 'Voir le témoignage',
            'temoignage_moderation'   => 'Mes témoignages',
            'temoignage_signalement'  => 'Voir les signalements',
            'temoignage_commentaire'  => 'Voir le témoignage',
            default                   => null,
        };
    }

    /**
     * D-052 — URL du bulletin selon l'espace de l'utilisateur connecté.
     *
     * Renvoie `null` hors contexte authentifié (tâche de fond, file d'attente)
     * plutôt que de fabriquer une URL qui ne correspond à aucun lecteur.
     */
    private function urlBulletin(int $bulletinId): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return Roles::estEnseignant($user)
            ? route('mes-bulletins.show', $bulletinId)
            : route('finance.bulletins-paie.show', $bulletinId);
    }
}
