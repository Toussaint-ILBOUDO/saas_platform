<?php

namespace App\Support;

use App\Models\User;

/**
 * Chemins de l'espace Angular (T7A.10).
 *
 * Une notification n'a de valeur que si son lien mène vers un écran qui
 * existe **et** que le destinataire peut voir. Ce support est la source
 * unique de vérité pour ces chemins : l'accesseur `route_angular` du modèle
 * `Notification` (lecture API dans l'espace) et le canal mail du
 * `NotificationDispatcher` (lien cliqué depuis la boîte mail) l'utilisent
 * ensemble — sans cette extraction, chaque canal réécrirait sa propre copie
 * et les deux finiraient par diverger.
 *
 * Deux règles structurelles, héritées du modèle :
 *
 *  - le même objet vit dans deux espaces (facture : portail famille ou
 *    gestion ; rapport/bulletin : écran enseignant ou administration) — la
 *    route est donc tranchée par le **rôle du destinataire**, jamais par le
 *    type seul ;
 *  - un écran absent de l'espace (`boutique/commandes`, témoignages…) ne
 *    produit **aucun** lien : le routeur `**` racine renverrait autrement
 *    sur le site public.
 *
 * Les chemins sont relatifs (sans host) : le mail les préfixe avec le
 * domaine de la request en cours — celui du cabinet.
 */
final class RoutesAngular
{
    public static function pour(?User $user, string $type, array $data): ?string
    {
        if (! $user) {
            return null;
        }

        return match ($type) {
            'contrat', 'affectation' => isset($data['contrat_id'])
                ? self::contrat((int) $data['contrat_id'], $user)
                : null,

            'facture' => isset($data['facture_id'])
                ? self::facture((int) $data['facture_id'], $user)
                : null,

            // La boutique Angular n'existe pas encore (placeholder
            // `modules/:slug`) : pas de lien plutôt qu'un lien mort.
            'librairie_commande' => null,

            'actualite' => isset($data['actualite_id'])
                ? '/espace/actualites/' . $data['actualite_id']
                : null,

            // Idem : aucun écran témoignage dans l'espace Angular.
            'temoignage' => null,
            'temoignage_moderation' => null,
            'temoignage_signalement' => null,
            'temoignage_commentaire' => null,

            'rapport' => isset($data['rapport_id'])
                ? self::rapport((int) $data['rapport_id'], $user)
                : null,

            'demande_cours' => isset($data['demande_cours_id'])
                ? '/espace/pedagogie/demandes-cours/' . $data['demande_cours_id']
                : '/espace/pedagogie/demandes-cours',

            'bulletin_paie' => isset($data['bulletin_paie_id'])
                ? self::bulletin((int) $data['bulletin_paie_id'], $user)
                : null,

            default => null,
        };
    }

    /**
     * Une facture est émise au parent débiteur (`invoiceCreated` /
     * `invoicePaid` passent par `facture->parent_id`) : sa destination
     * naturelle est le portail famille. L'administration, si elle lit un
     * jour cette notification, passe par l'écran de gestion.
     */
    private static function facture(int $factureId, User $user): ?string
    {
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
    private static function rapport(int $rapportId, User $user): ?string
    {
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
     */
    private static function contrat(int $contratId, User $user): ?string
    {
        if (Roles::estAdmin($user)) {
            return '/espace/pedagogie/contrats/' . $contratId;
        }

        if (Roles::estParent($user)) {
            return '/espace/modules/mes-contrats/' . $contratId;
        }

        return '/espace/modules/mes-cours';
    }

    /**
     * Le même bulletin s'ouvre dans deux espaces : celui de l'enseignant
     * (destinataire habituel) et celui de l'administration.
     */
    private static function bulletin(int $bulletinId, User $user): ?string
    {
        return match (true) {
            Roles::estEnseignant($user) => '/espace/modules/mes-bulletins/' . $bulletinId,
            Roles::estAdmin($user) => '/espace/finance/bulletins-paie/' . $bulletinId,
            default => null,
        };
    }
}
