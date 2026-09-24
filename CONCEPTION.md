# Saas_plateforme — Document de conception

> **Destinataire principal : l'IA de développement (opencode).**
> Ce document est la source de vérité du projet. Lis-le en entier avant d'écrire la moindre ligne de code.
> Langue du projet : **français uniquement** (noms de tables, colonnes, classes métier, routes, messages, interface). Le vocabulaire technique du framework reste en anglais (`Controller`, `Service`, `Middleware`...).

---

## 0. Règles d'or (à relire avant chaque tâche)

1. **Ne jamais modifier le projet Keduc original** (celui qui tourne en production pour la première agence). Il reste tel quel, en Blade, pour toujours. Le dossier `backend/` de ce projet est une **copie** de Keduc que l'on restructure. Le code Keduc sert de **référence métier**.
2. **Une base de données PostgreSQL par cabinet** (isolation physique). Aucune table métier ne porte de colonne `cabinet_id` / `tenant_id`.
3. **Le cabinet est toujours déterminé par le nom de domaine de la requête** (sous-domaine), jamais par une donnée envoyée par le client (ni corps, ni en-tête, ni paramètre).
4. **Backend d'abord** : migration → modèle → relations → policy → service métier → controller → FormRequest → API Resource → tests → frontend.
5. **Toutes les clés étrangères (`*_id`) sont contraintes ET indexées** (`->constrained()` + `->index()` dans la migration).
6. **Montants en FCFA, toujours en entiers** (aucun décimal, aucune autre devise).
7. **Les controllers restent minces** : la logique métier vit dans des `Services` (`app/Services`).
8. **Aucun secret dans le code ni dans Git** (`.env` ignoré, mots de passe de base chiffrés).
9. **Tout ce qui est ambigu dans ce document ou dans Keduc est noté dans `docs/QUESTIONS.md`** au lieu d'être deviné en silence.
10. **Chaque fonctionnalité livrée est accompagnée de tests**, dont au moins un test prouvant l'isolation entre deux cabinets.

### En cas de conflit entre sources

| Sujet | Source qui prime |
|---|---|
| Architecture (multi-tenant, API, frontend, auth) | **Ce document** |
| Règles métier (calculs d'heures, facturation parents, paie enseignants, statuts) | **Code Keduc existant**, sauf contradiction explicite avec ce document |
| Contradiction non résolue | Noter dans `docs/QUESTIONS.md`, appliquer l'option marquée « par défaut », continuer |

---

## 1. Vision du projet

**Saas_plateforme** transforme Keduc (plateforme de gestion pédagogique d'un cabinet d'appui scolaire) en **plateforme SaaS multi-cabinets**.

- Un **super-admin** (le propriétaire de la plateforme) crée et administre des **cabinets** (synonyme : *agences*) depuis une interface web Laravel Blade appelée **Landlord**.
- Chaque cabinet dispose de :
  - sa **propre base PostgreSQL** ;
  - son **propre sous-domaine** (ex. `cabinet1.k-educ.com`) ;
  - son **propre frontend Angular/PWA** (page publique + backoffice), avec une apparence et une navigation qui lui sont propres ;
  - ses **propres utilisateurs** (un utilisateur appartient à un seul cabinet).
- Tous les cabinets **partagent le même backend Laravel** (une seule base de code, une seule API).
- **Échelle visée** : jusqu'à **30 cabinets × ~150 utilisateurs ≈ 4 500 utilisateurs**. Ce volume est modeste pour PostgreSQL et Laravel ; le vrai enjeu est l'isolation et la maintenabilité, pas la performance brute.
- Le cabinet Keduc historique **continue de fonctionner à l'identique**, hors de ce projet.

### Ce que fait un cabinet (rappel métier)

Un cabinet met en relation des **parents/élèves** et des **enseignants** pour des cours de soutien : demande de cours → contrat → affectation d'enseignants → séances (cahier de texte) → rapports mensuels → facturation aux parents → paie des enseignants. Il gère aussi une **bibliothèque** de documents pédagogiques et une **librairie** de fournitures.

---

## 2. Glossaire

| Terme | Signification |
|---|---|
| **Cabinet** = **Agence** = **Tenant** | Une organisation cliente de la plateforme. Un seul terme officiel dans le code : **cabinet**. |
| **Landlord** | Partie « plateforme » : base centrale, super-admin, gestion des cabinets, facturation des cabinets. |
| **Tenant** | Partie « cabinet » : base dédiée, données métier, utilisateurs du cabinet. |
| **Super-admin** | Propriétaire de la plateforme. Vit dans la base centrale. |
| **Admin de cabinet** | Responsable d'un cabinet. Vit dans la base du cabinet. |
| **Base centrale / plateforme** | Base PostgreSQL `saas_plateforme`. |
| **Base cabinet** | Base PostgreSQL `cabinet_<slug>`. |
| **Core (Angular)** | Bibliothèque Angular commune à tous les cabinets (auth, PWA, hors ligne, API...). |
| **MVP** | Première version livrable (voir section 17). |

---

## 3. Périmètre

### Inclus
- Landlord Blade : connexion super-admin, CRUD cabinets, **création automatique complète d'un cabinet**, suspension/réactivation, domaines, paramètres et tarifs, facturation des cabinets, paiements des cabinets, impersonation, journal.
- Backend multi-tenant avec `stancl/tenancy` (v3) + PostgreSQL.
- API REST JSON par cabinet, consommée par Angular.
- Un workspace Angular avec **core** + **une application par cabinet**.
- PWA : installable, lecture hors ligne, **saisie du cahier de texte hors ligne avec synchronisation ultérieure**, notifications push.
- Notifications internes (push + base) et **email**.
- Tous les modules métier de Keduc (voir sections 8 et 17).

### Exclu (ne rien prévoir, ne rien coder)
- Paiement mobile automatisé (Orange Money, Moov Money...) : les paiements sont **saisis manuellement**. Les libellés de mode de paiement restent de simples textes.
- Essai gratuit, plans/abonnements de la plateforme au sens SaaS classique (pas de table `plans`/`subscriptions` au MVP).
- Multi-langue, multi-devise.
- Docker (non nécessaire à ce stade).
- Application mobile native (Flutter) : la PWA la remplace.
- Migration du cabinet Keduc historique vers la plateforme.
- Un utilisateur rattaché à plusieurs cabinets.

---

## 4. Décisions d'architecture

| # | Décision | Détail |
|---|---|---|
| D1 | Isolation | **1 base PostgreSQL par cabinet**, sur un même serveur PostgreSQL au départ. |
| D2 | Multi-tenancy | `stancl/tenancy` v3, mode « multi-database », identification par **domaine**. |
| D3 | Base centrale | `saas_plateforme` : cabinets, domaines, paramètres, facturation plateforme, super-admins, journal. |
| D4 | Nommage des bases | `cabinet_<slug>` (préfixe configuré dans `config/tenancy.php`). |
| D5 | ID de cabinet | Identifiant **chaîne unique = slug** (comportement par défaut de stancl), ex. `ouaga-centre`. Pas de `bigint`. |
| D6 | Backend | Un seul projet Laravel contenant : Web (Blade : Landlord) + API (cabinets). |
| D7 | Frontend | **Angular** (dernière version stable, composants standalone, signals) transformé en **PWA**. Un workspace : `core` + une application par cabinet. |
| D8 | Auth cabinet | **Session + cookie** (guard `web`), email + mot de passe. Angular et API partagent la **même origine** (voir 6.5). Sanctum est installé mais ses jetons ne sont pas utilisés au MVP. |
| D9 | Auth super-admin | Guard dédié `landlord`, table centrale `super_admins`. |
| D10 | Rôles/permissions | `spatie/laravel-permission` dans chaque base cabinet. |
| D11 | Fichiers | `spatie/laravel-medialibrary` (table `media` standard) + isolation par cabinet via le bootstrapper de fichiers de stancl. |
| D12 | Notifications | Système natif Laravel : canaux `database`, `mail`, `webpush` (Web Push VAPID). |
| D13 | Files d'attente | Driver `database` (connexion centrale) + worker supervisé. |
| D14 | Sessions | Driver `file` (ou `redis` plus tard). **Pas de sessions en base cabinet** (la session est lue avant que le cabinet soit initialisé). |
| D15 | Interface Angular | Tailwind CSS + Angular CDK, thème par variables CSS (couleurs du cabinet chargées à l'exécution). *(par défaut, à valider)* |
| D16 | Impersonation | Fonction native de stancl (`tenancy()->impersonate`), réservée au super-admin, journalisée. |
| D17 | Hébergement | Non figé : voir section 15 pour la recommandation simple et peu chère. |

---

## 5. Architecture globale

```text
                          INTERNET
                              │
        ┌─────────────────────┼─────────────────────────┐
        │                     │                         │
  admin.k-educ.com     cabinet1.k-educ.com       cabinet2.k-educ.com
  (Landlord, Blade)    (Angular PWA cabinet 1)   (Angular PWA cabinet 2)
        │                     │  /api/*                 │  /api/*
        │                     ▼                         ▼
        │              ┌─────────────────────────────────────┐
        └─────────────►│         NGINX (même serveur)         │
                       │  fichiers statiques Angular par hôte │
                       │  /api  →  PHP-FPM (Laravel)          │
                       └──────────────────┬──────────────────┘
                                          │
                                ┌─────────▼─────────┐
                                │  Laravel (unique)  │
                                │  Web  : Landlord   │
                                │  API  : cabinets   │
                                └─────────┬─────────┘
                                          │  stancl/tenancy
                       ┌──────────────────┼──────────────────┐
                       ▼                  ▼                  ▼
              saas_plateforme     cabinet_cabinet1     cabinet_cabinet2   ...
              (base centrale)     (base cabinet 1)     (base cabinet 2)
```

Points clés :
- `admin.k-educ.com` (domaine central, configurable) ne sert **que** le Landlord.
- Chaque cabinet est servi sur **son** domaine : Nginx sert l'application Angular du cabinet et **relaie `/api` vers Laravel avec le même `Host`**. Résultat : même origine → pas de CORS, cookies de session simples, cabinet identifié par le domaine.
- Le nom de domaine `k-educ.com` est un **paramètre** (`DOMAINE_PLATEFORME` dans `.env`), pas une valeur codée en dur.

---

## 6. Multi-tenancy en détail

### 6.1 Composants stancl à utiliser

- Modèle `App\Models\Cabinet` (étend `Stancl\Tenancy\Database\Models\Tenant`) implémentant `TenantWithDatabase`, avec les traits `HasDatabase` et `HasDomains`.
- Table centrale `cabinets` (voir 7.1) ; table `domains` (standard stancl).
- Identification : `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains` sur `routes/tenant.php` et sur les routes API cabinet.
- Bootstrappers activés : **Database**, **Filesystem**, **Queue**. Le bootstrapper **Cache** n'est activé que si Redis est en place (le cache `file`/`database` ne supporte pas les tags). **Au MVP : ne pas mettre en cache de données métier.**
- Migrations cabinet : `database/migrations/tenant/`. Migrations plateforme : `database/migrations/` (ou `database/migrations/plateforme/`).
- Seeders cabinet : `database/seeders/tenant/`.

### 6.2 Points de vigilance connus (stancl v3 + PostgreSQL + PHP 8.2)

À vérifier explicitement, ce sont des pièges fréquents :

1. Le modèle `Cabinet` **doit** implémenter `TenantWithDatabase` et utiliser `HasDatabase`, sinon la base n'est jamais créée.
2. Les colonnes propres (`nom_cabinet`, `statut`, `tenancy_db_name`, etc.) doivent être déclarées dans `getCustomColumns()`, sinon le trait `VirtualColumn` les range dans la colonne JSON `data` ou les ignore silencieusement (la valeur de `tenancy_db_name` peut alors être perdue).
3. Vérifier que la base PostgreSQL est **réellement créée** (`CREATE DATABASE`) et que l'utilisateur PostgreSQL a le droit `CREATEDB`.
4. Sur PHP 8.2, le `DatabaseTenancyBootstrapper` peut ne pas transmettre correctement les identifiants à PDO : prévoir, si le problème est constaté, un bootstrapper personnalisé (`app/Tenancy/DatabaseTenancyBootstrapper.php`) qui force les identifiants après `parent::bootstrap()`, **sans jamais modifier `vendor/`**.
5. Enregistrer `TenancyServiceProvider` dans `bootstrap/providers.php` (Laravel 11+).
6. Appliquer les middlewares de tenancy sur **toutes** les routes cabinet (web et API), sinon la requête tourne sur la base centrale par erreur.
7. `tenants:migrate` doit cibler `database/migrations/tenant`, pas les migrations centrales.
8. Files d'attente : les jobs cabinet doivent porter l'identifiant du cabinet (`QueueTenancyBootstrapper`) ; la table `jobs` est en base **centrale**.

### 6.3 Deux contextes bien séparés

| | Contexte Landlord | Contexte Cabinet |
|---|---|---|
| Domaine | domaine central (`admin.k-educ.com`) | domaine du cabinet |
| Base | `saas_plateforme` | `cabinet_<slug>` |
| Utilisateurs | `super_admins` | `users` du cabinet |
| Guard | `landlord` | `web` |
| Routes | `routes/landlord.php` (Blade) | `routes/tenant.php` (Blade éventuel) + `routes/api.php` (API) |

### 6.4 Organisation du code (Web vs API)

Voire structure du projet actuel et l'adapter proprement 

Règle : **un controller Web et un controller API n'appellent jamais l'un l'autre** ; ils appellent le même `Service`.

### 6.5 Identification du cabinet et même origine

- Le navigateur charge Angular depuis `https://cabinet1.k-educ.com` et appelle **`/api/...` en chemin relatif**. Nginx envoie `/api` à Laravel en conservant l'en-tête `Host: cabinet1.k-educ.com`.
- `InitializeTenancyByDomain` lit le `Host`, retrouve le cabinet dans `domains`, et bascule la connexion PostgreSQL.
- Interdit : accepter un nom de base, un `cabinet_id` ou un slug envoyés par le client pour choisir les données.
- Un cabinet `suspendu` ou `archive` répond `403` avec le code `CABINET_SUSPENDU` (Angular affiche un message clair ; la page publique affiche « site indisponible »).

### 6.6 Création automatique d'un cabinet (pipeline)

Déclenchée par le formulaire « Nouveau cabinet » du Landlord. **Un clic → cabinet prêt.**

```text
1. Valider le formulaire (nom, responsable, téléphone, email, slug, email de l'admin)
2. Créer le Cabinet (statut = actif) + Domain principal  <slug>.<DOMAINE_PLATEFORME>
3. Créer la base PostgreSQL  cabinet_<slug>
4. Lancer les migrations cabinet
5. Lancer le seeder cabinet :
     - rôles et permissions (admin_cabinet, enseignant, parent, eleve, gestionnaire_librairie)
     - référentiels par défaut : types de cours, types de documents        (par défaut)
     - thème par défaut, footer vide, paramètres par défaut
6. Créer l'admin du cabinet avec un mot de passe généré
7. Envoyer par email à l'admin : identifiants + lien de son site
8. Enregistrer les paramètres/tarifs du cabinet (parametres_cabinet)
9. Écrire dans le journal plateforme
```

- Tout doit passer par des **jobs** (`JobPipeline` de stancl) pour ne pas bloquer la requête.
- **En cas d'échec** à une étape : annuler proprement (supprimer la base et les enregistrements créés), marquer l'état, afficher l'erreur dans le Landlord. Jamais de cabinet à moitié créé.
- L'admin reçoit son mot de passe par email (souhait du propriétaire). Il **peut** le changer ensuite ; le changement n'est pas imposé. *(Alternative plus sûre, à proposer plus tard : lien d'activation à usage unique.)*
- Classes, matières : **vides par défaut**, chaque cabinet crée les siennes. *(par défaut, à valider)*

### 6.7 Suspension, archivage, suppression

- **Suspendre** : `statut = suspendu`. Les utilisateurs du cabinet ne peuvent plus se connecter ni utiliser l'API ; la page publique affiche un message. Le super-admin peut toujours entrer par impersonation. Réversible.
- **Archiver** : `statut = archive`, mêmes effets, la base est conservée.
- **Supprimer définitivement** : action distincte, confirmation forte, **sauvegarde `pg_dump` obligatoire avant**, puis suppression de la base et des enregistrements. *(par défaut)*

### 6.8 Migrations et évolutions

- Toute nouvelle migration cabinet s'applique à **toutes** les bases via `php artisan tenants:migrate`.
- Procédure de déploiement : sauvegarde → mise à jour du code → `tenants:migrate` (en journalisant les erreurs par cabinet) → vérification.
- Une migration ne doit **jamais** être écrite pour un cabinet en particulier.

---

## 7. Modèle de données — Landlord (base `saas_plateforme`)

Conventions : `id` bigint auto-incrémenté (sauf `cabinets.id`), `created_at/updated_at` partout, montants en `integer` (FCFA).

### 7.1 `cabinets` (modèle stancl)
- `id` : string, clé primaire = slug unique
- `nom_cabinet` : string
- `nom_du_responsable` : string
- `telephone` : string
- `email_contact` : string
- `statut` : string — `actif`, `suspendu`, `archive` (index)
- `tenancy_db_name` : string (déclaré dans `getCustomColumns()`)
- `data` : json nullable (réservé stancl)
- `created_at`, `updated_at`

### 7.2 `domains` (standard stancl)
- `id`, `domain` (unique), `tenant_id` (→ `cabinets.id`, index), `is_primary` boolean, `is_active` boolean, timestamps
- Un cabinet a **au moins un** domaine actif primaire. Les domaines personnalisés (`cabinet1.com`) sont prévus mais hors MVP.

### 7.3 `parametres_cabinet` (1–1 avec cabinet)
- `id`, `cabinet_id` (unique, FK, index)
- Tarifs plateforme (FCFA, entiers) : `frais_annuel_enseignant`, `frais_annuel_parent`, `frais_annuel_eleve`, `frais_contrat_actif`
- `taux_commission_vente` : numeric(5,2), en **pourcentage** (ex. `5.00`), défaut `5.00`
- `taux_commission_cabinet` : numeric(5,2), en pourcentage
- `fonctionnalites` : json — ex. `{"librairie_active": true, "bibliotheque_active": true}` (activation/désactivation de modules par cabinet)
- timestamps

> Les couleurs, logo, textes de la page publique **ne sont pas ici** : ils vivent dans la base du cabinet (section 8.9). Le Landlord peut fournir des valeurs initiales au moment de la création.

### 7.4 `super_admins`
- `id`, `nom`, `prenom`, `email` (unique), `password`, `statut` boolean, `remember_token`, timestamps

### 7.5 Facturation des cabinets par la plateforme

**Principe** : la plateforme compte les inscrits de chaque cabinet (enseignants, parents, élèves, contrats actifs), applique les tarifs de `parametres_cabinet`, ajoute d'éventuelles lignes libres, et émet une facture au cabinet. Le cabinet paie la plateforme ; **commissions et autres frais passent aussi par la plateforme**. Tout est consultable dans le Landlord.

`factures_cabinet`
- `id`, `cabinet_id` (FK, index)
- `numero` : string unique (ex. `FC-2026-0001`)
- `periode_debut`, `periode_fin` : date
- `statut` : `brouillon`, `emise`, `partiellement_payee`, `payee`, `annulee` (index)
- `montant_total` : integer (= somme des lignes **débit** − somme des lignes **crédit**)
- `montant_paye` : integer (somme des paiements, mis à jour par le service)
- `date_facture` : date, `date_echeance` : date nullable, `date_paiement` : date nullable
- `notes` : text nullable
- `created_by` : bigint (→ `super_admins`, index)
- timestamps

`lignes_facture_cabinet`
- `id`, `facture_cabinet_id` (FK, index)
- `code` : string — `abonnement_enseignant`, `abonnement_parent`, `abonnement_eleve`, `contrat_actif`, `commission`, `libre`
- `libelle` : string
- `sens` : string — **`debit`** (le cabinet doit ce montant) ou **`credit`** (remise, avoir, déduction)
- `quantite` : integer (défaut 1), `prix_unitaire` : integer, `montant` : integer (= quantité × prix)
- timestamps

`paiements_cabinet`
- `id`, `facture_cabinet_id` (FK, index)
- `montant_paye` : integer
- `mode_paiement` : string (`virement`, `especes`, `cheque`, `autre`) — saisie manuelle
- `reference_paiement` : string nullable
- `date_paiement` : date
- `enregistre_par` : bigint (→ `super_admins`, index)
- timestamps

### 7.6 `journal_plateforme`
- `id`, `super_admin_id` nullable (index), `action` (ex. `cabinet.cree`, `cabinet.suspendu`, `impersonation.debut`, `facture.emise`), `cabinet_id` nullable (index), `details` jsonb nullable, `adresse_ip`, `user_agent`, `created_at`

### 7.7 Tables techniques centrales
`migrations`, `jobs`, `failed_jobs`, `password_reset_tokens` (super-admins), `sessions` **non** (sessions en fichier).

### 7.8 Règle de calcul de facturation *(proposition par défaut, à valider)*

Pour ne jamais facturer deux fois la même chose, **chaque cabinet enregistre dans sa base** un fait facturable dès qu'un compte devient actif ou qu'un contrat devient actif (table `inscriptions_facturables`, section 8.10). Au moment de générer une facture :

1. Le service `CalculFacturationCabinet` parcourt, dans la base du cabinet (`tenancy()->run()`), les enregistrements **non encore facturés** dont la date tombe dans la période.
2. Il regroupe par type (enseignant, parent, élève, contrat actif) et crée une ligne par type avec la quantité et le tarif de `parametres_cabinet`.
3. À l'émission de la facture, il marque ces enregistrements comme facturés (référence de la facture).
4. L'abonnement étant **annuel**, un renouvellement annuel génère un nouvel enregistrement facturable par compte actif (à définir avec le propriétaire).
5. Les lignes `commission` et `libre` sont ajoutées manuellement ou par règle.

---

## 8. Modèle de données — Cabinet (chaque base `cabinet_<slug>`)

Confere et bien lire le travail deja fait et qui est fonctionnel 

## 9. Relations (résumé)

```text
User N—N Role (spatie)
Confere le travail fait ! le projet 

LANDLORD
Cabinet 1—1 ParametresCabinet     Cabinet 1—N Domain
Cabinet 1—N FactureCabinet 1—N LigneFactureCabinet
FactureCabinet 1—N PaiementCabinet
```

---

## 10. Authentification, rôles et sécurité d'accès

### 10.1 Cabinet
- Connexion : **email + mot de passe**, session cookie (`web` guard). Angular envoie le jeton CSRF (`XSRF-TOKEN` → `X-XSRF-TOKEN`, géré nativement par `HttpClient`).
- Endpoints : `POST /api/auth/connexion`, `POST /api/auth/deconnexion`, `GET /api/auth/moi`, `POST /api/auth/mot-de-passe-oublie`, `POST /api/auth/reinitialiser-mot-de-passe`, `POST /api/auth/changer-mot-de-passe`.
- Durée de session longue (option « rester connecté », ex. 30 jours) : indispensable pour le mode hors ligne.
- **Un utilisateur = un cabinet** : l'email est unique **dans la base du cabinet** ; le même email peut exister dans deux cabinets (deux bases différentes).
- **Compte élève** : créé/activé par l'admin de cabinet (mot de passe temporaire ou lien d'activation).
- Limitation de débit (`throttle`) sur la connexion et la réinitialisation de mot de passe.

### 10.2 Landlord
- Connexion Blade classique, guard `landlord`, table `super_admins`.

### 10.3 Impersonation (super-admin)
- Bouton « Entrer dans le cabinet » (Landlord) → `tenancy()->impersonate(...)` avec jeton à usage unique et courte durée → redirection vers le domaine du cabinet, connecté en tant qu'`admin_cabinet`.
- **Journalisé** (début/fin, super-admin, cabinet) dans `journal_plateforme` **et** dans `historique_activites` du cabinet.
- Une bannière « Session d'impersonation » est visible pendant toute la durée ; sortie explicite.

### 10.4 Autorisations
- Une **Policy** par modèle sensible ; middleware/gates Spatie sur les routes.
- Règles de visibilité types : un parent ne voit que ses enfants ; un enseignant ne voit que ses affectations ; un élève ne voit que son propre dossier ; les fiches santé/religion ne sont visibles que par l'admin et les enseignants affectés.

---

## 11. API

- Préfixe `/api`, JSON, **noms de ressources en français**, pluriel (`/api/eleves`, `/api/contrats`, `/api/cahier-textes`).
- Réponses via **API Resources** ; pagination standard Laravel ; dates ISO 8601 ; fuseau `Africa/Ouagadougou` côté affichage, UTC en base *(par défaut)*.
- Erreurs : JSON `{ "message": "...", "code": "CODE_MACHINE", "erreurs": { ... } }` ; `422` validation, `401` non connecté, `403` interdit, `404`, `409` doublon d'idempotence, `429` trop de requêtes, `503` maintenance.
- Messages d'erreur et de validation **en français** (`lang/fr`).
- Documentation OpenAPI générée (ex. `dedoc/scramble`) pour aider le frontend.

### Routes publiques (sans connexion, résolues par domaine)
```text
GET  /api/public/cabinet              thème, pied de page, fonctionnalités actives
GET  /api/public/actualites           actualités publiées
GET  /api/public/actualites/{slug}
GET  /api/public/faq                  sections + questions actives
GET  /api/public/documents            documents is_public = true (si module actif)
GET  /api/public/produits             (si librairie active)
POST /api/public/demandes-cours       création d'une demande de cours (visiteur)
POST /api/public/commandes            commande invité (si librairie active)
```

### Routes authentifiées
Une famille par module (voir section 17), toutes derrière `auth` + rôles/permissions + policies.

---

## 12. Frontend Angular / PWA

### 12.1 Structure du workspace

```text
frontend/
├── angular.json
├── projects/
│   ├── core/                     ← BIBLIOTHÈQUE commune à tous les cabinets
│   │   └── src/lib/
│   │       ├── auth/             AuthService, guards (rôles), intercepteurs
│   │       ├── api/              client HTTP, gestion d'erreurs (401/403/422/503 + CABINET_SUSPENDU)
│   │       ├── cabinet/          chargement du thème public, application des variables CSS
│   │       ├── pwa/              installation, mise à jour, détection en ligne/hors ligne
│   │       ├── hors-ligne/       file d'attente IndexedDB, synchronisation
│   │       ├── notifications/    abonnement Web Push (SwPush)
│   │       ├── models/           interfaces TypeScript des ressources API
│   │       └── utils/
│   ├── modele-cabinet/           ← application GABARIT copiée pour chaque nouveau cabinet
│   └── cabinet-<slug>/           ← UNE application par cabinet (ex. cabinet-ouaga-centre)
│       └── src/app/
│           ├── public/           page publique du cabinet (accueil, à propos, actualités, FAQ, demande de cours, bibliothèque/librairie si actives)
│           ├── backoffice/       connexion + espaces par rôle (admin, enseignant, parent, élève, librairie)
│           ├── navigation/       menus propres au cabinet
│           └── shared/           composants propres au cabinet
```

- **Core** = tout ce qui est technique et commun (PWA, authentification, gestion du sous-domaine, hors-ligne, notifications).
- **Application cabinet** = composants, pages, navigation et dashboards **propres au cabinet**. Deux cabinets peuvent avoir des interfaces très différentes tout en s'appuyant sur le même core.
- Un cabinet **ne duplique pas** le core ; il l'importe.
- Créer un nouveau cabinet côté frontend = copier `modele-cabinet` vers `cabinet-<slug>`, l'adapter, construire, déployer (un script `scripts/nouveau-frontend-cabinet` est à créer).

### 12.2 PWA
- Chaque application cabinet a son propre `manifest.webmanifest` (nom, icônes, couleurs du cabinet) et son `ngsw-config.json` (Angular Service Worker).
- Le core fournit les providers et services partagés ; les fichiers de configuration PWA restent au niveau de l'application (contrainte Angular).
- Bouton « Installer l'application » et invite de mise à jour quand une nouvelle version est disponible.

### 12.3 Hors ligne (exigence forte)
- **Lecture** : les données consultées (affectations, planning, cahiers de texte, listes) sont mises en cache pour être relues sans connexion (stratégie de cache du service worker sur une **liste blanche** d'endpoints `GET`).
- **Saisie du cahier de texte hors ligne** : l'enseignant remplit la séance ; l'enregistrement est placé dans une **file IndexedDB** avec un `uuid_client`. Dès le retour de la connexion (et session valide), la file est envoyée dans l'ordre.
- **Idempotence** : le backend rejette/ignore un doublon d'`uuid_client` (`409` traité comme succès côté client).
- Indicateur visible : « hors ligne », « X éléments en attente de synchronisation », « synchronisé ».
- À la déconnexion : **vider** les caches et files locales de l'utilisateur.
- Si la session a expiré pendant la période hors ligne : conserver la file, demander une reconnexion, puis synchroniser.
- Les conflits sont limités car le cahier de texte est en ajout seul ; toute modification hors ligne d'une séance existante suit la règle « dernière écriture, avec avertissement » *(par défaut)*.

### 12.4 Notifications push
- Web Push (VAPID) via `SwPush`. Abonnement enregistré dans `push_subscriptions` du cabinet.
- Attention : sur iOS, le push web ne fonctionne que pour une PWA **installée sur l'écran d'accueil**.

### 12.5 Page publique du cabinet
- Soignée, moderne, responsive, mobile d'abord.
- Contenu piloté par l'admin du cabinet depuis son backoffice (thème, hero, à propos, actualités, FAQ, pied de page).
- Sections activables/désactivables (témoignages, actualités, bibliothèque, librairie, cours).
- Formulaire public « Demande de cours ».
- Hébergée avec le backoffice dans la **même application Angular** du cabinet.
- SEO de base : titres/meta par page, `robots.txt`, `sitemap`. *(Le rendu serveur Angular n'est pas prévu au MVP.)*

### 12.6 Développement local (Windows)
- Utiliser des sous-domaines `*.localhost` (résolus automatiquement vers `127.0.0.1`) : `cabinet1.localhost`.
- Le serveur de développement Angular relaie `/api` vers `http://localhost:8000` en **conservant l'hôte d'origine** (proxy sans réécriture du `Host`).
- Le domaine du cabinet de test doit exister dans la table `domains` (`cabinet1.localhost`).

---

## 13. Notifications

- Envoi via le système `Notification` de Laravel : canaux `database` + `webpush` + `mail`.
- Emails via **queue** ; expéditeur configurable ; modèles en français.
- Événements notifiés : compte créé (identifiants + lien), facture générée, rapport validé/rejeté, paiement effectué, nouveau document, nouveau contrat, demande de cours reçue.
- Ne **jamais** inclure de données de santé ou d'informations sensibles sur mineurs dans une notification.

---

## 14. Fichiers et médias

- `spatie/laravel-medialibrary` ; stockage isolé par cabinet (bootstrapper Filesystem de stancl → dossier par cabinet).
- Disque `local` au départ ; conception compatible **stockage S3 compatible** plus tard sans changement de code.
- Types : logos, photos de profil, PDF pédagogiques, images produits, documents de la bibliothèque.
- Contrôles à l'upload : taille maximale, types MIME autorisés, noms générés (pas de nom fourni par le client sur le disque).
- Génération de PDF (cahiers de texte, factures, rapports) : conserver la solution qui fonctionne dans Keduc (`barryvdh/laravel-dompdf`).

---

## 15. Exploitation et déploiement (simple, professionnel, peu coûteux)

Recommandation de départ (à adapter selon l'hébergeur choisi plus tard) :

**Infrastructure**
- **Un seul serveur VPS** Linux (Ubuntu LTS), ~4 vCPU / 8 Go de RAM suffisent largement pour 30 cabinets.
- **Nginx** + **PHP-FPM** + **PostgreSQL** + **Supervisor** (worker de queue) + **cron** (`php artisan schedule:run` chaque minute). Redis optionnel plus tard.
- Pas de Docker au départ.

**Domaines et HTTPS**
- Un **DNS générique** : `*.k-educ.com` pointe vers le serveur.
- Un **certificat SSL générique** (Let's Encrypt, validation DNS) : les nouveaux cabinets sont en HTTPS sans aucune intervention.
- Nginx utilise une règle **unique** pour tous les cabinets, sans modification à chaque nouvel ajout :
  - `server_name ~^(?<cabinet>[a-z0-9-]+)\.k-educ\.com$;`
  - fichiers statiques depuis `/var/www/frontends/$cabinet/` (repli sur `index.html` pour Angular) ;
  - `location /api` → PHP-FPM, avec conservation du `Host`.
- Le domaine central `admin.k-educ.com` a son propre bloc.

**Sauvegardes** (indispensables)
- Chaque nuit : `pg_dump` **de chaque base** (format custom) + de la base centrale + dossiers de fichiers.
- Copie **hors du serveur** (stockage objet S3 compatible bon marché).
- Rétention proposée : 14 sauvegardes quotidiennes + 8 hebdomadaires.
- **Test de restauration** au moins une fois par mois.
- Sauvegarde automatique **avant** chaque `tenants:migrate` en production.

**Déploiement**
1. Sauvegarde. 2. Mise à jour du code (`git pull`, `composer install --no-dev`). 3. `php artisan tenants:migrate` (journal par cabinet). 4. `php artisan optimize`. 5. Redémarrage du worker. 6. Build Angular des applications concernées et copie vers `/var/www/frontends/<slug>/`.

**Observabilité**
- Logs Laravel quotidiens, `failed_jobs` surveillés, une sonde de disponibilité gratuite, journal d'audit (`historique_activites`, `journal_plateforme`).

**Montée en charge future** : déplacer la base d'un gros cabinet vers un autre serveur PostgreSQL est possible car chaque cabinet a `tenancy_db_name` (et, si besoin plus tard, host/identifiants propres).

---

## 16. Sécurité (checklist)

- Cabinet déterminé **uniquement** par le domaine ; tests d'isolation obligatoires (un utilisateur du cabinet A ne peut rien lire du cabinet B, même en manipulant les identifiants).
- Mots de passe hachés (bcrypt/argon), politique minimale de mot de passe, limitation de débit sur la connexion.
- CSRF activé ; cookies `Secure`, `HttpOnly`, `SameSite=Lax`.
- Identifiants PostgreSQL des cabinets **chiffrés** si stockés (ou un seul utilisateur PostgreSQL applicatif défini dans `.env`).
- Validation stricte via FormRequests ; échappement des contenus HTML (actualités) ; `css_personnalise` limité au super-admin ou filtré.
- Autorisations par policies ; principe du moindre privilège.
- Données de mineurs (santé, religion, restrictions) : accès restreint, non journalisées en clair, non envoyées dans les notifications.
- Impersonation journalisée ; suppression de cabinet avec sauvegarde préalable.
- En-têtes de sécurité (Nginx) et HTTPS obligatoire.

---

## 17. Feuille de route

### MVP — objectif de la première livraison

1. **Landlord** : connexion super-admin ; liste/création/modification de cabinets ; **création automatique complète** (6.6) ; suspension/réactivation ; impersonation.
2. **Multi-tenancy opérationnel** : `cabinet1.localhost` (puis domaine réel) charge la base `cabinet_cabinet1`.
3. **API minimale** : authentification (connexion, déconnexion, moi, mot de passe oublié/changement), routes publiques (`/api/public/cabinet`, actualités, FAQ, demande de cours).
4. **Frontend** : workspace Angular avec **core** (PWA, auth, cabinet/thème, hors-ligne de base) + application **cabinet 1** :
   - **page publique** complète et soignée ;
   - **backoffice** : connexion, changement de mot de passe, tableau de bord (squelette), gestion du contenu public (thème, pied de page, actualités, FAQ), gestion basique des utilisateurs (créer, activer/désactiver, rôles).

**Critères d'acceptation du MVP**
- [ ] Créer un cabinet dans le Landlord produit, sans autre action manuelle : base + migrations + admin + email + site accessible sur son sous-domaine.
- [ ] Deux cabinets créés ont des données strictement séparées (test automatisé).
- [ ] L'admin du cabinet se connecte avec les identifiants reçus par email et peut changer son mot de passe.
- [ ] La page publique du cabinet 1 s'affiche avec son propre thème et son propre contenu.
- [ ] L'application est installable en PWA et se relance hors ligne (coquille + pages déjà visitées).
- [ ] Suspendre le cabinet bloque immédiatement l'accès ; le réactiver le rétablit.
- [ ] L'impersonation fonctionne et est journalisée.
- [ ] `tenants:migrate` applique une nouvelle migration à tous les cabinets.

### Après le MVP (ordre recommandé, chaque module = backend puis API puis Angular)

| Phase | Module | Contenu |
|---|---|---|
| 1 | IAM complet | Import CSV d'enseignants, profils, photo de profil, permissions fines |
| 2 | Académique | CRUD classes, matières, types de cours, types de documents, périodes |
| 3 | Scolarité | Parents, élèves, rattachement parent/enfant, activation compte élève |
| 4 | CRM | Demandes de cours (liste, acceptation/refus, conversion en contrat) |
| 5 | Opérations | Contrats, affectations, taux horaires, planning, changement d'enseignant |
| 6 | Pédagogique | **Cahier de texte (avec hors-ligne)**, objectifs pédagogiques |
| 7 | Suivi | Rapports mensuels enseignants, validation, PDF |
| 8 | Facturation parents | Factures, lignes, calcul automatique des heures, PDF, notifications |
| 9 | Paie enseignants | Paie mensuelle, validation, historique, statistiques |
| 10 | Bibliothèque | Upload, recherche, filtres, favoris, téléchargements, journal d'accès |
| 11 | Librairie | Produits, stock, commandes, panier invité |
| 12 | Facturation plateforme | Génération des factures cabinet, lignes débit/crédit, paiements, tableau de bord Landlord |
| 13 | Notifications | Toutes les notifications automatiques (push + email) |
| 14 | Audit | Journalisation complète (création, modification, suppression, connexion, paiements) |
| 15 | Dashboards | Admin (CA, heures, top enseignants, impayés), enseignant (revenus, heures), parent (progression) |

### Méthode de travail pour chaque module
```text
Migration → Modèle → Relations → Policy → Service → Controller API → FormRequest
→ API Resource → Tests (dont isolation) → Écrans Angular → Test hors-ligne si concerné
```

---

## 18. Structure du dossier de travail

```text
Saas_plateforme/                 ← dossier donné à opencode
├── conception.md                ← ce document
├── docs/
│   ├── INVENTAIRE_KEDUC.md      ← inventaire du code Keduc (à produire en premier)
│   ├── QUESTIONS.md             ← ambiguïtés et décisions à valider
│   └── DECISIONS.md             ← journal des décisions prises pendant le développement
├── backend/                     ← Laravel (copie restructurée de Keduc)
└── frontend/                    ← workspace Angular (core + applications cabinets)
```

*(À valider : `frontend/` frère de `backend/` (recommandé) ou imbriqué dans `backend/`. Le premier choix évite de mélanger deux projets dans un même dépôt.)*

---

## 19. Conventions de code

- **Français** pour tout : tables, colonnes, modèles, routes, variables métier, messages, commentaires. Aucune traduction anglaise des termes métier.
- Tables au **pluriel snake_case** (`cahier_textes`, `contrat_cours`) ; modèles au singulier `PascalCase` (`CahierTexte`, `ContratCours`). Toute exception à la pluralisation Laravel est déclarée avec `$table`.
- FK nommées `<singulier>_id`, **toujours** `constrained()` + `index()`.
- Une classe = une responsabilité ; controllers minces ; services testés.
- PSR-12 (Laravel Pint) ; Angular : ESLint + Prettier ; TypeScript strict.
- Angular : composants standalone, signals, chargement paresseux des routes, un dossier par fonctionnalité.
- Commits petits et fréquents (Conventional Commits, en français).
- Tests : Pest ou PHPUnit côté Laravel ; tests d'isolation multi-tenant ; tests des services de calcul (heures, factures, paie).
- Seeders et factories pour chaque modèle.

---

## 20. Hypothèses prises par défaut (à valider par le propriétaire)

| # | Sujet | Choix par défaut |
|---|---|---|
| H1 | Domaine de production | `k-educ.com` (paramétrable) ; Landlord sur `admin.k-educ.com` |
| H2 | Structure des dossiers | `backend/` et `frontend/` côte à côte |
| H3 | Règle de facturation plateforme | Comptage des faits facturables non encore facturés (7.8) ; renouvellement annuel à définir |
| H4 | Tarif « contrat actif » | Une seule fois par contrat, à son activation (à préciser : mensuel ou unique) |
| H5 | Sens débit/crédit | Débit = le cabinet doit ; crédit = remise ou avoir |
| H6 | Données initiales d'un cabinet | Rôles, types de cours, types de documents ; classes et matières vides |
| H7 | Mot de passe admin | Envoyé par email, modifiable, changement non obligatoire |
| H8 | UI Angular | Tailwind CSS + Angular CDK |
| H9 | Suppression d'un cabinet | Archivage d'abord ; suppression définitive avec sauvegarde |
| H10 | Notes hors ligne | Modification hors ligne d'une séance existante : dernière écriture, avertissement |
| H11 | Hébergement | Un VPS, DNS et SSL génériques, sauvegardes hors serveur |
| H12 | Cabinet Keduc historique | Reste hors plateforme ; pourra devenir un cabinet plus tard, sans engagement |

---

## 21. Premier travail demandé à opencode

Dans cet ordre, **sans coder de fonctionnalité avant la fin de l'étape 3** :

1. **Lire ce document en entier**, puis lire le code du dossier `backend/` (copie de Keduc).
2. **Produire `docs/INVENTAIRE_KEDUC.md`** : version des dépendances, liste des migrations et tables, modèles et relations, controllers et routes (web), vues Blade, services/jobs, modules PDF, règles de calcul (heures, factures, paie). Lister tout écart avec les sections 7 à 9 de ce document.
3. **Produire `docs/QUESTIONS.md`** : toute ambiguïté ou contradiction rencontrée, avec l'option par défaut retenue.
4. Installer et configurer `stancl/tenancy`, `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `laravel/sanctum`, `laravel-notification-channels/webpush`, en appliquant la section 6.2.
5. Séparer les migrations en **plateforme** et **cabinet** (`database/migrations/tenant/`), en retirant tout `cabinet_id`.
6. Implémenter le **Landlord** et la **création automatique de cabinet** (6.6), avec tests.
7. Implémenter l'**API d'authentification** et les **routes publiques**, avec tests d'isolation.
8. Créer le workspace Angular (`core` + `modele-cabinet` + `cabinet-<slug>` du cabinet 1) et terminer le MVP (section 17).

**Règle finale** : ne supprime jamais de code Keduc existant (controllers web, vues Blade, routes) sans que ce soit explicitement demandé ; ce qui n'est pas repris dans la nouvelle architecture est simplement laissé de côté et signalé dans `docs/INVENTAIRE_KEDUC.md`.