# Rapport final — Partie 06 : Audit performance & scalabilité

**Projet :** K'Educ (Laravel 12, PostgreSQL, PHP Windows `C:\xampp\php\php.exe`)
**Date :** 15/08/2026 — **État :** corrections appliquées et vérifiées par tests réels.

> Méthode : cartographie de l'infrastructure asynchrone (queue/cache), puis audit parallèle à 4 axes (Controllers/Services, Blade, PDF/emails/queue/cache, index SQL). Chaque constat a été vérifié par lecture du code réel avant toute correction. Aucune commande impactant la base n'a été exécutée par l'agent (règle absolue `AGENTS.md`).

---

## 1. Synthèse exécutive

4 corrections **critiques**, 2 **moyennes**, 4 **faibles** appliquées. Bilan :
- Emails d'actualité déplacés en **file d'attente** (fin du SMTP synchrone en boucle dans la requête HTTP).
- **Fuite de stockage PDF** stoppée (un média par cahier de texte au lieu d'un à chaque téléchargement) et **crash de l'export historique corrigé**.
- **6 index SQL** ajoutés via une migration dédiée (le plus critique : `notifications(user_id)`, exécuté à chaque page du panel).
- Eager loading des médias (commandes, détail document), agrégation des compteurs de la page d'accueil en 1 requête, cache et requêtes répétées nettoyés.
- **Suite complète : 83 tests / 167 assertions — seule failure `ExampleTest` (pré-existante, hors périmètre).**

## 2. Périmètre & méthodologie

Périmètre : N+1 restants, requêtes Eloquent, pagination, eager loading, agrégations, cache, traitements synchrones lourds, scalabilité, PDF/emails/notifications, Queues/Jobs, index SQL.
Contraintes respectées : français ; code réel seul arbitre ; aucune optimisation « au feeling » ; aucune régression de sécurité ni de logique financière ; pas de refactor massif ; pas de nouvelle dépendance ; `DatabaseSeeder` intact ; tests réellement exécutés.

## 3. État de l'infrastructure asynchrone (Jobs/Notifications/Events/Listeners)

- `app/Jobs/`, `app/Notifications/`, `app/Listeners/`, `app/Events/` : **vides**.
- Aucune occurrence de `ShouldQueue`, `dispatch()` ou `dispatchSync()` dans le code.
- Le système de notification maison (`NotificationService` + `NotificationDispatcher`) crée des lignes `notifications` en **synchrone**.
- Conclusion : l'asynchrone n'était exploité nulle part, alors que l'infrastructure (tables `jobs`, `failed_jobs`, `cache`) est prête.

## 4. Architecture queue : état des lieux

- `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `MAIL_MAILER=smtp` dans `.env`.
- `config/queue.php` : défaut `database`. Tables migrées (`0001_01_01_000002_create_jobs_table.php`, `0001_01_01_000001_create_cache_table.php`).
- Aucun worker (pas de superviseur documenté) : un job mis en file restera en attente tant que `php artisan queue:work` ne tourne pas.

## 5. Architecture cache : état des lieux

Caches existants (tous avec invalidation `forget()` correcte) :
- `ActualiteService` : `actualites.recentes` (purgé à publication/dépublage) + réactions.
- `FaqService` : sections/questions actives (purge à la modification).
- `TemoignageService` : classement + réactions (purge à la création/modération).
- `NotificationDispatcher` : clé par rôle.
- Stockage `database` — pas de Redis : aucune dépendance ajoutée.

## 6. Emails : audit (sync vs async)

- 1 seul Mailable : `app/Mail/ActualitePublishedMail.php` (déjà `Queueable` + `SerializesModels`).
- `NotificationDispatcher::actualitePublished()` : boucle sur les destinataires → `Mail::to(...)->send()` **dans la requête HTTP** de publication.
- Impact : N envois SMTP synchrones → latence, risque de timeout/HTTP 500 dès que le nombre de destinataires croît.

## 7. Emails : correction appliquée (queue) — CRITIQUE

`NotificationDispatcher::actualitePublished()` : `->send()` → **`->queue()`**.
- Le Mailable (déjà `Queueable`) est poussé dans la table `jobs` (`QUEUE_CONNECTION=database`), traité par un worker.
- Comportement métier inchangé : mêmes destinataires, même modèle, mêmes données sérialisées.
- **Prérequis production** : lancer `php artisan queue:work` (voir section 30).

## 8. PDF : audit des 5 services

Tous DomPDF, générés **synchrones** dans la requête HTTP, données chargées petites :
- `FacturePdfService`, `CommandePdfService`, `BulletinPaiePdfService`, `RapportMensuelPdfService` (1 facture/bulletin/rapport à la fois) — **acceptable**.
- `CahierTextePdfService` : séance unique ou **historique par élève** (potentiellement volumineux) — traité en section 9/10.

## 9. PDF : fuite de stockage corrigée — CRITIQUE

- Problème : `CahierTextePdfService::buildPdf()` attachait un **nouveau média** (`addMedia`) à **chaque** téléchargement, alors que la collection `cahier_texte_pdf` n'est **jamais relue** par l'interface.
- Impact : une ligne `media` + un fichier disque créés à chaque download → croissance illimitée.
- Correction : le média n'est créé que si la collection est **vide** pour ce modèle (requête fraîche via `media()`, et non la relation chargée en mémoire qui devient obsolète après `addMedia` — piège reproduit et couvert par test).

## 10. PDF : crash export historique corrigé — CRITIQUE

- Problème : `downloadHistory()` passait `$eleve` à `buildPdf()` qui appelait `$eleve->addMedia()` — or `Eleve` **n'implémente pas `HasMedia`** → erreur fatale.
- Correction : `downloadHistory()` passe `null` (aucun attach) et pré-charge `$eleve->loadMissing('user')` pour le nom du fichier.
- Résultat : export historique fonctionnel, aucun média créé (aucune régression possible : le flux était cassé).

## 11. PDF : évaluation du passage en queue (non retenu, justifié)

Les 4 services restants génèrent des PDF **petits** (1 entité) ; la bascule en file n'apporterait aucun gain sans worker et ajouterait de la latence d'affichage. **Conservés synchrones.** Seul l'historique (volumineux) était critique, désormais sans fuite ni crash. Un basculement futur passerait par un Job + `Storage::disk('local')->put(...)` + lien de téléchargement (documenté, non implémenté).

## 12. Notifications : audit des flux

- `NotificationDispatcher` : méthodes par domaine (contractCreated, courseRequestCreated, teacherAssigned, parentTeacherAssigned, actualitePublished…), création de lignes `notifications` avec `data` JSON.
- Cache des admins par rôle **jamais invalidé** → listes re-requêtées à chaque envoi (action d'écriture peu fréquente) → **non corrigé** (invalidation difficile à garantir sans risque de données périmées).
- Création de notifications **dans des transactions** (`ContratCoursService::create()`) : 2 requêtes/affectation — volumétrie d'écriture minuscule → **non corrigé**.

## 13. Notifications : index et requêtes

- `notifications(user_id)` non indexée (FK seule) alors que `NotificationController` (paginate, unreadCount, markAllAsRead) et le **composer panel** (chaque page) filtrent par `user_id`.
- **Corrigé** : index `idx_notifications_user_id` (migration section 15).

## 14. Index SQL : état des lieux

Tables concernées et requêtes justificatives (vérifiées par lecture) :
| Table | Colonne | Requêtes justificatives | Priorité |
|---|---|---|---|
| `notifications` | `user_id` | composer panel, NotificationController, markAllAsRead | 🔴 Critique |
| `commandes` | `user_id` | `LibrairieService::getCommandesForUser()` (mes commandes) | 🟠 Élevé |
| `temoignages` | `user_id` | `TemoignageService` (témoignages du profil) | 🟠 Élevé |
| `temoignage_commentaires` | `temoignage_id` | listes de commentaires d'un témoignage | 🟠 Élevé |
| `document_commentaires` | `document_bibliotheque_id` | liste des commentaires d'un document | 🟡 Moyen |
| `document_notes` | `document_bibliotheque_id` | statistiques de notes par document | 🟡 Moyen |

(`document_notes` a déjà un index unique `(user_id, document_bibliotheque_id)` mais inutilisable pour un filtre par seul `document_bibliotheque_id`.)

## 15. Index SQL : corrections (migration dédiée)

`database/migrations/2026_08_15_000001_add_performance_indexes.php` — 6 index non destructifs, noms explicites (`idx_*`), `down()` complet. Aucune autre modification de schéma.

## 16. Index SQL : commande d'application (par l'utilisateur)

La migration est testée par `RefreshDatabase` sur `keduc_test` (aucune commande artisan requise pour les tests). Pour la base de développement `keduc`, **l'utilisateur doit exécuter** :
```bash
/mnt/c/xampp/php/php.exe artisan migrate
```

## 17. N+1 restants : audit Controllers/Services

- **`BulletinPaieGenerationService::preview()/generer()`** — ~2N+3 requêtes : 1 SELECT rapports + 1 test `exists`/`count` par enseignant. Refactor transverse risqué (logique financière : mutations en transaction + numéros) → **conservé volontairement**, à traiter si le nombre d'enseignants par période devient important.
- **`ContratCoursService::create()`** — 2 requêtes/affectation (validation `EnseignantMatiere::exists()` + `EnseignantProfil::find()`) dans une transaction d'écriture → volumétrie négligeable, **non corrigé**.

## 18. N+1 corrigés en Partie 06

- **Médias des commandes** : `LibrairieController::show()/pdf()` + `AdminLibrairieController::showCommande()` → `load('lignes.produit.media')` (3 endroits). Les vues affichaient `$ligne->produit->image_url` (Spatie `getFirstMediaUrl`) → 1 requête media par ligne.
- **Média du détail document** : `DocumentBibliothequeService::getById()/getBySlug()` + `AdminBibliothequeController::show()` → `'media'` ajouté (les vues `show`/`admin-show` appellent `getFirstMedia('document')`).
- Accès non nul-safe dans les vues (`$ligne->produit?->image_url`) : Produit est `SoftDeletes` → erreur fatale évitée.

## 19. Requêtes répétées corrigées

- **`FactureCabinetService::calculerPreview()`** : 3 `TypeCommission::where()->first()` → 1 `whereIn([...])` + `keyBy()`. Calculs **inchangés** (aucune modification métier).
- **`HomeController::index()`** : 5 `COUNT(*)` indépendants → **1 requête** `DB::selectOne` à 5 sous-requêtes scalaires (correcte même tables vides, contrairement à un `FROM <table>` vide).

## 20. Pagination : audit

- Toutes les listes volumineuses sont paginées (15/élément) : commandes, documents, notifications, factures, types…
- Les `<select>` de formulaire ne sont volontairement pas paginés (champs de saisie).
- Aucune liste non paginée sur gros volume détectée.

## 21. Agrégations & dashboards : audit

- `FactureCabinet` : `withSum('paiements')` (P05) + accessor 3 niveaux — aucun N+1.
- `getAuteurStats()`/`getAdminStats()` (bibliothèque) : dashboards basse fréquence — conservés.
- Page d'accueil publique : corrigée (section 19).
- Aucune agrégation inutile ou répétée supplémentaire détectée.

## 22. Page d'accueil : optimisations

- 5 compteurs → 1 requête (section 19).
- Liste des enseignants : eager `media` + `enseignantProfil.matieres` (déjà en place).
- FAQ et témoignages : caches existants respectés.
- `nb_users` conservé dans le contrat de vue (compatibilité) bien qu'aucune vue ne l'affiche.

## 23. Cache : audit fin

- **Corrigé** : clé `actualites.recentes` sans le `$limit` → collision si plusieurs limites → clé `CACHE_RECENTES . '.' . $limit`.
- **Vérifié** : toutes les invalidations `forget()` sont en place (publication/dépublage, création/modification).
- **Non corrigé** : cache des rôles admins jamais invalidé (envois peu fréquents).
- **Prudence** : aucune donnée privée mise en cache avec une clé globale (les caches sont métier/publics).

## 24. Blade : audit

- Aucune requête SQL directe dans les vues (les corrections P05 ont déplacé la dernière, `profil/edit-enseignant`).
- Composer panel restreint à `panel.*` (P05) — la navbar publique charge uniquement `photo_profil_url` (1 requête `media` par page connectée, **acceptable**, non corrigé).
- Vues PDF orphelines signalées (`pdf/cahier-texte`, `pdf/historique-pedagogique`, `pdf/cahiers-textes.form` manquante) — code mort, non supprimé (hors périmètre).
- Accès nul-safe corrigés (section 18).

## 25. Scalabilité : traitements lourds documentés

- **Résolu** : emails d'actualité (queue) et historique PDF (pas de fuite/crash).
- **Documentés, conservés synchrones** : 4 services PDF, notifications dans transactions, numéros `count()+1`.
- **Numéros `count()+1`** (`FactureCabinet::genererNumero()`, `BulletinPaieGenerationService::genererNumero()`) : course conditionnelle possible sous forte concurrence → accepté à l'échelle actuelle, à réviser (séquence PostgreSQL) si volume élevé.

## 26. Scalabilité : points sensibles restants

- Nécessité d'un **worker de queue** en production pour vider `jobs` (sinon les emails restent en file).
- Volume `media` historique (PDF attachés avant la correction) : non nettoyé — un ménage éventuel est hors périmètre (dépend de l'historique réel de la base `keduc`, réinitialisée).

## 27. Bugs fonctionnels signalés (hors périmètre performance)

- **`FactureCabinetService::calculerPreview()` ligne 59** : `$montantVentes = round($totalVentes * $tauxVente * 100 / 100 * 100)` → la commission « Vente » était multipliée par **100** (surfacturation). **Signalé en Partie 06, corrigé en Partie 08** (`(int) round($totalVentes * $tauxVente)`, vérifié par test).
- **Code mort** : `HistoriquePedagogiquePdfController` (méthode `downloadForEleve()` inexistante, aucune route) ; vue `pdf/cahiers-textes.form` manquante (lien commenté dans l'index). La route `cahiers-textes.pdf.store` était cassée avant la correction (section 10).

## 28. Tests ajoutés & résultats (6 — verts sur `keduc_test`)

`tests/Feature/Partie06OptimisationsTest.php` :
1. Publication d'actualité (canal email) → mails **en file** (`assertQueued`, `assertNotSent`).
2. 6 index de performance présents en base (`Schema::getIndexes`).
3. Page d'accueil : les 5 compteurs en **une seule requête** `count(*)` (`DB::listen`).
4. `mes-commandes.show` : médias des produits chargés en **une seule requête**.
5. `downloadSingle()` 2× → **1 seul média**.
6. `downloadHistory()` → `Response` `application/pdf`, aucun média créé.

## 29. Suite complète & régressions

- **Suite complète : 83 tests / 167 assertions** — seule failure `ExampleTest` (accès `/` sans seed, pré-existante, hors périmètre).
- **Régression Parties 01-05 : aucune** (sécurité, intégrité, performance tous verts).
- `php -l` OK sur les 14 fichiers PHP modifiés (12 corrections + migration + test).
- `phpunit.xml` restauré en sqlite (`:memory:`) ; config temporaire pgsql/keduc_test supprimée.
- **Note (pré-existante, hors périmètre P06)** : les tests **ne peuvent pas tourner en sqlite `:memory:`** — la migration historique `2026_07_23_000001` (`drop column matiere_id` sur `demande_cours`) est incompatible avec SQLite (erreur d'index). Constat indépendant de la P06 (vérifié sur `IntegriteDonneesTest` P04). Le workflow officiel (`AGENTS.md`) exécute les tests sur PostgreSQL `keduc_test` via `RefreshDatabase`.

## 30. Commandes à exécuter par l'utilisateur

> Aucune de ces commandes n'a été exécutée par l'agent (règle absolue).

1. **Appliquer les index de performance** (seule commande obligatoire) :
```bash
/mnt/c/xampp/php/php.exe artisan migrate
```
2. **Si un worker de queue est souhaité** (production : traiter les emails d'actualité en file) :
```bash
/mnt/c/xampp/php/php.exe artisan queue:work
```
3. **Rejouer la suite de tests** (sur `keduc_test`, sans aucune commande artisan — `RefreshDatabase` migre automatiquement) : swap temporaire de `phpunit.xml` vers une config pgsql/keduc_test, lancer phpunit, restaurer.

---
*Rapport généré à partir de l'audit du code réel (15/08/2026). Les 6 index et la logique d'email en queue sont couverts par des Feature Tests réellement exécutés.*
