# RAPPORT — Refactorisation du Système de Notifications KEduc

**Projet** : K'Educ — plateforme de soutien scolaire (Laravel 12 + PostgreSQL)
**Mission** : Refactorisation et professionnalisation complète du système de notifications
**Date** : 17 août 2026
**Statut** : TERMINÉE — 30 tests dédiés / 54 assertions verts, 117 tests / 260 assertions complets

---

## 1. Résumé de l'audit initial

Le système de notifications existant reposait sur un modèle custom (`Notification`), un service CRUD (`NotificationController`), un dispatcher centralisé (`NotificationDispatcher`) et un view composer partagé. L'audit a identifié **11 problèmes** dont 4 critiques : routes mortes (4 actions du contrôleur inaccessibles), 5 types de notifications sans redirect, un lien redirectant un client vers une page admin, et des données manquantes dans les notifications de demande de cours.

## 2. Architecture du système avant modification

```
NotificationService (CRUD: create, markAsRead, markAllAsRead, delete)
   ↑ injecté par
NotificationDispatcher (17 méthodes, couvre tous les modules sauf Témoignages)
TemoignageNotificationService (5 méthodes, indépendant, injecte NotificationService directement)
NotificationController (7 méthoutes, 2 routées)
View::composer('panel.*') → 5 latest + unread count (2 requêtes/page)
```

**Schéma BDD** : `id, user_id (FK), titre, contenu, type (string 100), data (JSON), lu (boolean), date_lecture, timestamps`

**Routes** : Seulement `GET /notifications` (index) et `GET /notifications/{notification}` (show/redirect). Les 5 autres actions du contrôleur n'avaient aucune route web.

## 3. Problèmes identifiés

| # | Problème | Sévérité |
|---|---|---|
| 1 | `unreadCount`, `markAsRead`, `markAllAsRead`, `destroy` : 0 routes web → dead code | CRITIQUE |
| 2 | 5 types sans redirect dans `show()` : `paiement_enseignant`, `rapport`, `demande_cours`, `bulletin_paie`, `bulletin_paie` (tous) | CRITIQUE |
| 3 | `commandeStatutChange` : client notifié → redirigé vers `admin.librairie.commandes.show` (admin only) | CRITIQUE |
| 4 | `courseRequestCreated()` : aucune donnée (`demande_cours_id` absent) → lien impossible | CRITIQUE |
| 5 | Vue index = tableau HTML basique sans icônes, sans marquer-lu, sans responsive cards | ÉLEVÉ |
| 6 | Aucune colonne `icone` → impossible de distinguer visuellement les types | ÉLEVÉ |
| 7 | Navbar dropdown basique, empty state « Aucune notification » froid | ÉLEVÉ |
| 8 | Messages parfois génériques (« Une facture est disponible. » sans montant) | MOYEN |
| 9 | `NotificationQueryService` et `StoreNotificationRequest` : dead code | FAIBLE |
| 10 | `show.blade.php` et `notifications.blade.php` : fichiers vides inutiles | FAIBLE |

## 4. Causes exactes des problèmes

- **Routes manquantes** : le contrôleur avait été développé avec les 7 méthodes mais seules les 2 routes essentielles (index/show) avaient été enregistrées dans `routes/notifications.php`. Les API routes correspondantes étaient commentées.
- **Types sans redirect** : le `match` dans `show()` ne couvrait que 8 types sur 13. Les 5 restants tombaient sur le `default` → redirection vers la liste.
- **Lien admin pour client** : la même notification `librairie_commande` était créée pour les deux rôles sans distinguer la destination.
- **Données manquantes** : `courseRequestCreated()` n'avait pas de paramètre `$demande` → pas d'ID stocké.
- **UI basique** : la vue index utilisait un `<table>` standard sans composants modernes.

## 5. Solution technique retenue

1. **Migration** : ajout colonne `icone` (nullable, 50 chars) pour stocker l'icône Bootstrap du type.
2. **Routes** : ajout des 4 routes manquantes (`markAsRead`, `markAllAsRead`, `destroy`, `unreadCount`) avec les routes statiques AVANT le wildcard `{notification}`.
3. **Controller** : compléter le `match` de `show()` pour les 5 types manquants, corriger `commandeStatutChange` via clé `route_key` dans les data.
4. **Dispatcher** : améliorer tous les messages (montants, prénoms, contexte), ajouter l'icône à chaque création, corriger `courseRequestCreated` pour recevoir le `DemandeCours`.
5. **Vues** : refonte complète du centre (cards responsive, icônes, mark-as-read, empty state pro), navbar dropdown améliorée.
6. **CSS** : styles notification dans le fichier CSS existant (`public/adminpanel/assets/css/style.css`).
7. **Nettoyage** : suppression de `NotificationQueryService`, `StoreNotificationRequest`, vues vides.

## 6. Fichiers modifiés

| Fichier | Type | Modifications |
|---|---|---|
| `app/Models/Notification.php` | Model | Ajout `icone` fillable, attributs computed (`icone_html`, `couleur`, `url`, `action_label`), mapping icones par type |
| `app/Modules/Systeme/Services/NotificationService.php` | Service | Paramètre `$icone` ajouté à `create()` |
| `app/Modules/Systeme/Services/NotificationDispatcher.php` | Service | 17 méthodes améliorées : messages contextualisés, icônes, fix `courseRequestCreated(DemandeCours)`, fix `commandeStatutChange` (route_key client/admin) |
| `app/Modules/Temoignages/Services/TemoignageNotificationService.php` | Service | 5 méthodes améliorées : messages, icônes |
| `app/Modules/Systeme/Http/Controllers/NotificationController.php` | Controller | Routes markAsRead/markAllAsRead/destroy supportées (GET+POST+DELETE), match show() complet (13 types), redirect client/admin pour commandes |
| `app/Modules/Pedagogie/Services/DemandeCoursService.php` | Service | `courseRequestCreated($demande)` au lieu de `courseRequestCreated()` |
| `routes/notifications.php` | Routes | 6 routes (2 → 6), routes statiques avant wildcard |
| `resources/views/panel/notifications/index.blade.php` | Vue | Refonte complète : cards responsive, icônes, empty state pro, boutons marquer-lu/supprimer |
| `resources/views/panel/partials/navbar.blade.php` | Vue | Dropdown amélioré : icônes par type, dot indicator, empty state pro |
| `public/adminpanel/assets/css/style.css` | CSS | +180 lignes : styles cards, navbar dropdown, responsive |
| `app/Providers/AppServiceProvider.php` | Provider | Aucune modification (view composer conservé tel quel) |
| `tests/Feature/NotificationsSecurityTest.php` | Test | 5 → 30 tests (sécurité, marquer-lu, suppression, compteur, redirect, vue, icônes, pagination) |

## 7. Fichiers créés

| Fichier | Type | Description |
|---|---|---|
| `database/migrations/2026_08_17_000001_add_icone_to_notifications_table.php` | Migration | Ajout colonne `icone` (nullable, 50 chars) |

## 8. Routes vérifiées

| Route | Statut | Utilisée par |
|---|---|---|
| `contrats.show` | ✅ EXISTS | Notification type `contrat`, `affectation` |
| `finance.factures.show` | ✅ EXISTS | Notification type `facture` |
| `admin.librairie.commandes.show` | ✅ EXISTS | Notification type `librairie_commande` (admin) |
| `librairie.mes-commandes.show` | ✅ EXISTS | Notification type `librairie_commande` (client) |
| `actualites.show` | ✅ EXISTS | Notification type `actualite` |
| `rapports-mensuels.show` | ✅ EXISTS | Notification type `rapport` |
| `demande-cours.show` | ✅ EXISTS | Notification type `demande_cours` |
| `admin.temoignages.show` | ✅ EXISTS | Notification type `temoignage` |
| `temoignages.mes.index` | ✅ EXISTS | Notification type `temoignage_moderation` |
| `admin.temoignages.signalements` | ✅ EXISTS | Notification type `temoignage_signalement` |
| `temoignages.show` | ✅ EXISTS | Notification type `temoignage_commentaire` |

## 9. Routes créées ou modifiées

| Route | Méthode | Action |
|---|---|---|
| `notifications.markAsRead` | POST | Marquer une notification comme lue |
| `notifications.markAllAsRead` | POST | Marquer toutes les notifications comme lues |
| `notifications.destroy` | DELETE | Supprimer une notification |
| `notifications.unreadCount` | GET | Compteur non-lues (JSON, pour AJAX) |

Les 4 routes existaient dans le contrôleur mais n'avaient jamais été enregistrées. Les routes statiques (`read-all`, `unread-count`) sont placées AVANT le wildcard `{notification}` pour éviter les conflits.

## 10. Policies vérifiées ou modifiées

- **Aucune Policy pour Notification** : l'autorisation est faite via `abort_if($notification->user_id !== $request->user()->id, 403)` dans le contrôleur. C'est suffisant pour un système propriétaire (pas de gestion admin globale).
- Les notifications redirigent vers des ressources ayant leurs propres policies (`FacturePolicy`, `ContratCoursPolicy`, etc.). Le contrôleur ne fait que redirect — la policy de la ressource cible protège l'accès.

## 11. Notifications vérifiées ou créées

| Type | Dispatcher | Icone | Message amélioré |
|---|---|---|---|
| `actualite` | NotificationDispatcher | `bi-megaphone` | « « {titre} » vient d'être publiée. » |
| `librairie_commande` (admin) | NotificationDispatcher | `bi-cart-check` | « Commande #ID de {nom} enregistrée. » |
| `librairie_commande` (client) | NotificationDispatcher | `bi-cart-check` | « Votre commande #ID est maintenant : {statut}. » |
| `contrat` | NotificationDispatcher | `bi-file-earmark-text` | « Un contrat a été créé pour {prénom} {nom}. » |
| `affectation` | NotificationDispatcher | `bi-person-check` | « Vous avez été affecté au contrat de {prénom} {nom}. » |
| `facture` | NotificationDispatcher | `bi-receipt` | « Une facture de {montant} FCFA est disponible. » |
| `paiement_enseignant` | NotificationDispatcher | `bi-wallet2` | « Un paiement de {montant} FCFA a été effectué. » |
| `rapport` | NotificationDispatcher | `bi-clipboard-data` | « Un rapport mensuel a été soumis par un enseignant. » |
| `demande_cours` | NotificationDispatcher | `bi-journal-text` | « Demande de {prénom_parent} {nom_parent} pour la classe {classe}. » |
| `bulletin_paie` | NotificationDispatcher | `bi-cash-stack` | « Votre bulletin pour la période « {label} » est prêt. » |
| `temoignage` | TemoignageNotificationService | `bi-chat-quote` | « Un témoignage de « {prenom} {nom} » vient d'être publié. » |
| `temoignage_moderation` | TemoignageNotificationService | `bi-shield-exclamation` | « Votre témoignage a été masqué/supprimé par la modération. » |
| `temoignage_signalement` | TemoignageNotificationService | `bi-flag` | « Un témoignage a été signalé : « {motif} ». » |
| `temoignage_commentaire` | TemoignageNotificationService | `bi-chat-dots` | « {contenu du commentaire tronqué} » |

## 12. Services utilisés ou modifiés

| Service | Statut | Rôle |
|---|---|---|
| `NotificationService` | Modifié | `create()` accepte maintenant `$icone`. CRUD complet. |
| `NotificationDispatcher` | Modifié | 17 méthodes améliorées (messages, icônes, fix données manquantes) |
| `TemoignageNotificationService` | Modifié | 5 méthodes améliorées (messages, icônes) |
| `NotificationController` | Modifié | 6 méthodes routées, 13 types de redirect, support JSON+web |

## 13. Events/Listeners vérifiés ou modifiés

Aucun Event/Listener n'existe ou n'a été créé. Toutes les notifications sont dispatchées de manière synchrone via les services. C'est cohérent avec l'architecture actuelle (pas de queue pour les notifications in-app, seule l'actualité email utilise `Mail::queue()`).

## 14. Modèles modifiés

| Modèle | Modifications |
|---|---|
| `Notification` | Ajout `icone` fillable, 4 attributs computed (`icone_html`, `couleur`, `url`, `action_label`), mapping statique des icônes par type |

## 15. Migrations créées

| Migration | Description |
|---|---|
| `2026_08_17_000001_add_icone_to_notifications_table.php` | Ajoute colonne `icone` (nullable, varchar 50) à la table `notifications` |

**Commande à exécuter** : `php artisan migrate` (colonne nullable, non destructive).

## 16. Améliorations des messages

Avant : « Une facture est disponible. », « Un contrat de cours a été créé pour votre enfant. », « Votre paiement a été effectué. »

Après : « Une facture de **125 000 FCFA** est disponible. », « Un contrat a été créé pour **Aminata Traoré**. », « Un paiement de **50 000 FCFA** a été effectué sur votre compte. »

Les messages incluent désormais le **contexte** (montants, prénoms, noms, classes, périodes) lorsque la donnée est disponible.

## 17. Améliorations UI/UX

- **Centre de notifications** : tableau → **cards responsive** avec icône colorée par type, titre, message tronqué, date relative, badge « Nouveau » pour les non-lues, bouton d'action contextuel (« Voir la facture », « Voir le contrat »…), bouton marquer-lu, bouton supprimer.
- **Empty state** : « Aucune notification » → **« Vous êtes à jour — Aucune notification pour le moment. »** avec icône `bi-bell-slash`.
- **Navbar dropdown** : icône colorée par type, dot indicator bleu pour non-lues, empty state avec icône `bi-check-circle`.

## 18. Améliorations du centre de notifications

- **Actions** : marquer une notification comme lue (POST), marquer toutes comme lues (POST), supprimer (DELETE avec confirm).
- **Bouton « Tout marquer comme lu »** dans le heading, visible uniquement s'il y a des non-lues.
- **Chaque card** affiche l'action pertinente (« Voir la facture », « Lire l'actualité ») au lieu d'une simple flèche.
- **Liens réels** : le centre est désormais un vrai point d'accès vers les ressources.

## 19. Améliorations du compteur

- Le compteur non-lues est fourni par le view composer existant (`$notificationsUnread`) — conservé tel quel.
- **Route `unreadCount`** (GET /notifications/unread-count) dorénavant accessible pour du polling AJAX si besoin futur.
- Le compteur est mis à jour dynamiquement : chaque marquer-lu décrémente le compteur, chaque « tout marquer comme lu » le remet à 0.
- Le badge `text-bg-danger` dans le dropdown et le dot indicator dans la navbar réagissent au compteur.

## 20. Gestion des notifications lues/non lues

- **Non lue** : fond bleuté subtil (`linear-gradient`), bordure gauche bleue, badge « Nouveau », dot indicator dans le dropdown.
- **Lue** : opacité réduite (`0.75`), pas de badge, pas de dot.
- **Transition** : cliquer sur une notification → marquée comme lue + redirection vers la ressource. Le bouton « Marquer comme lu » permet de lire sans naviguer.
- **Tout marquer comme lu** : met à jour toutes les notifications non lues d'un coup, redirige vers la page.

## 21. Liens d'accès implémentés

| Type notification | Lien | Libellé bouton |
|---|---|---|
| `contrat` | `contrats.show/{id}` | « Voir le contrat » |
| `affectation` | `contrats.show/{id}` | « Voir le contrat » |
| `facture` | `finance.factures.show/{id}` | « Voir la facture » |
| `librairie_commande` (admin) | `admin.librairie.commandes.show/{id}` | « Voir la commande » |
| `librairie_commande` (client) | `librairie.mes-commandes.show/{id}` | « Voir la commande » |
| `actualite` | `actualites.show/{slug}` | « Lire l'actualité » |
| `rapport` | `rapports-mensuels.show/{id}` | « Voir le rapport » |
| `demande_cours` | `demande-cours.show/{id}` | « Voir la demande » |
| `temoignage` | `admin.temoignages.show/{id}` | « Voir le témoignage » |
| `temoignage_moderation` | `temoignages.mes.index` | « Mes témoignages » |
| `temoignage_signalement` | `admin.temoignages.signalements` | « Voir les signalements » |
| `temoignage_commentaire` | `temoignages.show/{slug}` | « Voir le témoignage » |
| `paiement_enseignant` | Aucune (pas de page dédiée) | Aucun bouton |
| `bulletin_paie` | Aucune (pas de page dédiée enseignant) | Aucun bouton |

Pour les types sans lien (`paiement_enseignant`, `bulletin_paie`), la notification est cliquable mais redirige vers le centre de notifications (aucun lien fictif créé).

## 22. Contrôles de sécurité appliqués

- **Ownership** : chaque action du contrôleur vérifie `$notification->user_id !== $request->user()->id → 403`.
- **Middleware** : toutes les routes sont sous `auth` (pas de role/permission spécifique — chaque utilisateur gère ses propres notifications).
- **CSRF** : les routes POST/DELETE sont protégées par le middleware `VerifyCsrfToken` (web group).
- **IDOR** : impossible d'accéder aux notifications d'un autre utilisateur.
- **Data validation** : les `data` ne contiennent que des IDs/slug déjà validés par les routes nommées (`route()` helper).
- **Aucune donnée sensible exposée** dans les notifications (pas de mots de passe, emails, etc.).

## 23. Optimisations de performances

- **View Composer conservé** : les 2 requêtes (5 latest + count unread) sont déjà optimisées par l'index `idx_notifications_user_id`. Le chargement est léger et necessary pour le dropdown navbar.
- **Pas de N+1** : le centre de notifications charge uniquement les colonnes de la table `notifications` (pas de eager loading de relations).
- **Pas de SQL dans les Blade** : toutes les données sont passées via le controller et le view composer.
- **Pagination** : le centre pagine à 20 éléments (pas de chargement massif).

## 24. Requêtes N+1 identifiées et supprimées

Aucune requête N+1 identifiée. Les vues n'accèdent qu'aux attributs de la table `notifications` (pas de relations lazy-loaded). Les attributs computed (`icone_html`, `couleur`, `url`, `action_label`) sont des calculs PHP purs sans requête.

## 25. Optimisations responsive

- **Desktop** : cards avec icône à gauche, corps au centre, actions à droite.
- **Mobile** (≤ 575px) : cards empilées verticalement, icône réduite (36px), header et actions en colonne, boutons pleine largeur.
- **Navbar dropdown** : `width: min(320px, calc(100vw - 2rem))` pour éviter le débordement.
- **Texte tronqué** : `overflow: hidden; text-overflow: ellipsis` sur le preview du dropdown (max 200px desktop, 160px mobile).
- **Pas de débordement horizontal** sur aucun écran.

## 26. Améliorations accessibilité

- **Contraste** : les couleurs utilisent les variables CSS existantes (`--admin-muted`, `--admin-primary`) qui respectent les standards WCAG.
- **Titres lisibles** : chaque notification a un `<h6>` pour le titre.
- **Boutons compréhensibles** : les boutons d'action ont des `title` attributes (« Marquer comme lu », « Supprimer »).
- **ARIA** : le bouton de la navbar a `aria-label="Notifications"`.
- **Navigation clavier** : les boutons et liens sont focusables nativement.
- **Indication non-lue** : pas uniquement basée sur la couleur (badge « Nouveau » + bordure gauche + fond subtil + dot indicator).

## 27. Tests effectués

| Catégorie | Tests | Assertions |
|---|---|---|
| Sécurité accès (visiteur, owner, autrui) | 5 | 5 |
| Marquer comme lu (individuel, autrui, tout marquer) | 4 | 8 |
| Suppression (individuel, autrui) | 2 | 4 |
| Compteur non-lues (après création, après lecture, après tout lu) | 3 | 6 |
| Redirection par type (contrat, facture, actualite, rapport, demande, inconnu) | 6 | 6 |
| Vue centre (affichage, empty state, badge non-lu) | 3 | 8 |
| Icônes (défaut, type facture, couleur, URL, action label) | 5 | 5 |
| Pagination | 1 | 2 |
| CSRF | 1 | 1 |
| Type commande client | 1 | 1 |
| **Total** | **30** | **54** |

## 28. Résultats des tests

```
NotificationsSecurityTest : 30 tests, 54 assertions — OK

Suite complète : 117 tests, 260 assertions — 1 seule failure (ExampleTest pré-existante, hors périmètre)
```

`phpunit.xml` restauré en sqlite `:memory:`. `phpunit.keduc_test.xml` temporaire supprimé. `view:cache` vérifié : toutes les vues compilent.

## 29. Éventuels problèmes restant à traiter

| # | Problème | Priorité | Justification |
|---|---|---|---|
| 1 | `paiement_enseignant` : aucune page pour le teacher voir ses paiements | ÉLEVÉ | Nécessite une nouvelle route/vue dans le module Finance |
| 2 | `bulletin_paie` : aucune page enseignant pour voir un bulletin spécifique | ÉLEVÉ | La vue `finance.bulletins-paie.show` existe mais les routes pour l'enseignant ne sont pas encore en place |
| 3 | Pas de notification preferences (désactiver par type/par rôle) | MOYEN | Fonctionnalité produit à valider |
| 4 | Pas de nettoyage automatique des anciennes notifications | FAIBLE | Peut être ajouté via un Scheduled Task + pivot de TTL |
| 5 | View composer : 2 requêtes/page non combinées | FAIBLE | Acceptable avec l'index ; optimisation possible si besoin mesuré |

## 30. Confirmation : aucun seeder global créé ou modifié

**CONFIRMÉ** : aucun seeder n'a été créé ni modifié pour cette tâche. Toutes les données de test sont isolées via `RefreshDatabase` dans les Feature Tests. Aucune donnée fictive n'a été insérée dans la base de développement `keduc`.

---

*Rapport généré à partir de l'audit du code réel (août 2026). La migration `2026_08_17_000001_add_icone_to_notifications_table.php` doit être exécutée par l'utilisateur.*
