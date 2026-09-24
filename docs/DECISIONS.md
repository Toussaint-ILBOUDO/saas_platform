# DECISIONS — Journal des décisions du projet Saas_plateforme

> Chaque décision importante est journalisée ici (date, référence, décision, pourquoi).
> Source de vérité d'architecture : `CONCEPTION.md`. Feuille de route : `PLAN.md`.

---

## 24/09/2026 — Cadrage initial (session de compréhension)

| # | Décision | Détail | Réf. |
|---|---|---|---|
| D-001 | **Structure du dépôt = `backend/` + `frontend/`** | Le code Laravel (copie KEduc) est déplacé dans `backend/` ; `frontend/` (workspace Angular) sera créé. Racine = `AGENTS.md`, `CONCEPTION.md`, `PLAN.md`, `docs/`, `backend/`, `frontend/`. | B1 |
| D-002 | **Facturation plateforme refondue (conception §7.5/7.8)** | Nouvelles tables `factures_cabinet`, `lignes_facture_cabinet`, `paiements_cabinet` en base **centrale**, lignes debit/credit, `montant_paye`, statuts brouillon/emise/partiellement_payee/payee/annulee. Ancien module KEduc (`facture_cabinets`/`ligne_facture_cabinets`/`paiement_cabinets`/`type_commissions` + `FactureCabinetService`) **ignoré/dormant** (référence historique, non réutilisé par le nouveau backoffice). | B2 |
| D-003 | **Tests : tout PostgreSQL** | Base centrale de test `keduc_test` ; bases tenants de test dynamiques `keduc_test_<slug>` créées/supprimées par les tests (compte `postgres`). `phpunit.xml` (sqlite) ne suffit plus. | B3 |
| D-004 | **Web Blade KEduc gelé** | Vues/routes web non touchées, non servies sur les domaines cabinets ; référence fonctionnelle uniquement. Le Landlord possède un interrupteur d'accès global aux écrans web Keduc (T2.10). | B4 |
| D-005 | **ID tenant = slug** | `id_generator => null` dans stancl ; `id` fourni manuellement (= slug). | Q1 |
| D-006 | **Domaines de dev** | `cabinet<N>.localhost` (tenants), `admin.localhost` (Landlord). | Q2 |
| D-007 | **Rôles tenant (5)** | `admin_cabinet`, `enseignant`, `parent`, `eleve`, `gestionnaire_librairie` (mapping `admin`→`admin_cabinet`, `gestionnaire`→`gestionnaire_librairie`). Aucun `super-admin` côté tenant. | Q3 |
| D-008 | **Un seul compte PostgreSQL** | Un compte applicatif `postgres` avec `CREATEDB` ; pas d'identifiants par cabinet ni de chiffrement individuel. | Q4 |
| D-009 | **API en français, pas de versioning** | `/api/auth/connexion`, ... ; erreurs `{message, code, erreurs}` + `CABINET_SUSPENDU` (403). Scramble ajouté au P3. | Q5/Q6/Q7 |
| D-010 | **Frontend : workspace Angular vanilla** | Dernière version stable, standalone + signals, Tailwind + Angular CDK, bibliothèque `core`, une app par cabinet (gabarit `modele-cabinet`). | Q8 |
| D-011 | **Hors-ligne MVP borné** | Lecture en cache (liste blanche GET) + saisie cahier de texte avec `uuid_client` ; conflits : dernière écriture avec avertissement. | Q9/H10 |
| D-012 | **Thème à l'exécution** | Couleurs/polices via variables CSS chargées par `GET /api/public/cabinet`. | Q10 |
| D-013 | **Décimaux librairie conservés provisoirement** | `decimal(10,2)` pour produits/commandes ; harmonisation entiers FCFA au M11. | Q11 |
| D-014 | **Config cabinet non statique** | `config('keduc.cabinet')` (coordonnées, téléphones, logo, directrice) transféré en base **tenant** (thème/pied de page/paramètres). `HomeController` (SQL brut) remplacé par des services. | consigne |
| D-015 | **Snap super-admin unique (plateforme)** | Table `super_admins` + guard `landlord` ; un seul compte ; le rôle `super-admin` spatie de la copie n'existe pas dans les bases tenant. | consigne |
| D-016 | **Périmètre de la copie** | L'ancienne `docs/conception.md` est supprimée/ignorée ; KEduc historique (prod, Blade) reste intact et hors plateforme. | consigne |
| D-017 | **Nom de la base centrale (dev)** | `saascd_plateforme` (créée par le propriétaire le 24/09 ; initialement prévue `saas_plateforme`, cf. `.env`). La copie KEduc historique reste sur `keduc` ; tests sur `keduc_test`. | T0.5 |

## 24/09/2026 — P1 (multi-tenancy stancl v3.10)

| # | Décision | Détail | Réf. |
|---|---|---|---|
| D-018 | **Nommage des bases cabinets** | `prefix='cabinet_'` + slug ⇒ `cabinet_<slug>`. Bootstrappers actifs : Database, Filesystem, Queue ; cache isolé par tenant **uniquement** si Redis (`file` → non inclu). | T1.2/T1.4 |
| D-019 | **Queue centrale + sessions/cache file + timezone** | `DB_QUEUE_CONNECTION=pgsql` (jobs en base Landlord, indépendante du tenant courant) ; `SESSION_DRIVER=file`, `CACHE_STORE=file` ; `APP_TIMEZONE=Africa/Ouagadougou`. | T1.4 |
| D-020 | **Découpage routes** | `routes/landlord.php` (domaine central, web) ; `routes/tenant.php` = 10 fichiers web KEduc sous `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains` ; groupe `api` tenant-scope (landlord API à préfixer séparément en P3). | T1.5 |
| D-021 | **Migrations : 4 landlord / 75 tenant** | Landlord = stancl (`tenants`/`domains` + colonnes cabinet) + infra (`cache`, `jobs`) ; tenant = schéma KEduc dans `database/migrations/tenant/`, `cabinet_id` retiré de `faq_sections`, `uuid_client` (unique) ajouté à `cahier_textes`. | T1.3 |
| D-022 | **Données cabinet du tenant (D-014)** | Table tenant `parametres_publics` (1 ligne : `theme` json, `footer` json, `data`) + `CabinetInfo` (source tenant, repli temporaire `keduc.cabinet`). Les 4 services PDF vivants, `FactureWebController` et 7 vues ne lisent plus la config statique. | T1.6/T1.7 |
| D-023 | **Tests d'isolation : base `keduc_test` non transactée** | `phpunit.xml` pgsql : `DB_DATABASE=keduc_test`, `TENANCY_DB_PREFIX=keduc_test_` (D-003), envs en `force="true"` (surcharger le `.env` chargé par `artisan test`). PostgreSQL interdit `CREATE DATABASE` dans une transaction → `connectionsToTransact()=[]` + `migrate:fresh` avant **chaque** test (`RefreshDatabaseState::$migrated=false`) ; bases tenant supprimées en tearDown. | T1.9 |
| D-024 | **`Cabinet::getIncrementing()=false`** | Avec `id_generator => null`, stancl ne lie pas le générateur ⇒ Eloquent traite l'id comme auto-incrément et insère sans `id` (colonne varchar → `0`). L'id (= slug, D-005) est donc toujours fourni manuellement, jamais auto-incrémenté. | T1.9 |
| D-025 | **Tenant inconnu → 404** | `TenantCouldNotBeIdentifiedException` rendue en `404` (JSON `{message, code: CABINET_INCONNU}` si `expectsJson`, sinon 404). Le `/` appartient aux cabinets : `routes/landlord.php` est vide (trappé par `PreventAccessFromCentralDomains` sur les domaines centraux) pour éviter la collision avec le `/` tenant. UI Landlord en P2. | T1.5/T1.9 |
| D-026 | **Suite Legacy KEduc mise de côté** | 19 fichiers / 195 tests monolithe web déplacés dans `tests/Feature/Legacy/` (exclus de la suite par défaut) — obsolètes sous tenancy (rôles/permissions en base tenant, domaines). Réécriture par module API dès P3. | T1.9 |
| D-027 | **Seed automatique à la création + page publique blindée** | `Jobs\SeedDatabase` ajouté au pipeline `TenantCreated` (CreateDatabase → MigrateDatabase → SeedDatabase, sync en dev) : tout nouveau cabinet est provisionné complet (rôles/permissions/référentiels/`parametres_publics`, T1.7). `HomeController` : la requête `role('enseignant')` n'est exécutée que si le rôle existe (collect sinon) — un cabinet non seedé n'explose plus en 500. | T1.7/T2.4 |

---

## Conventions permanentes

1. Toute ambiguïté → `docs/QUESTIONS.md` avec option par défaut, appliquée, continuée.
2. Ne jamais exécuter de commande modifiant la base de données (AGENTS.md) — les formuler pour le propriétaire.
3. Ne jamais supprimer le code KEduc existant sans demande explicite → le geler et le documenter.
4. Une tâche = tests verts + case cochée dans `PLAN.md` + éventuelle ligne ici + commit.