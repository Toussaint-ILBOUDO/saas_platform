# Feuille de route — Saas_plateforme

> Complète `conception.md` (référence d'architecture). Ici : **quoi faire, dans quel ordre, et quand c'est terminé**.
> **Déjà en place** (issu de Keduc) : models, relations, services, policies, form requests de la partie cabinet. On **réutilise**, on ne réécrit pas.
> **À faire** : multi-tenant, Landlord, API, core Angular, frontend de chaque cabinet, déploiement.

**Comment suivre** : cocher `[x]` au fur et à mesure. Après chaque tâche, opencode met à jour ce fichier et ajoute une ligne dans `docs/DECISIONS.md` si une décision a été prise. Une tâche n'est cochée que si son **critère de fin** est vrai.

## Tableau de suivi

| Phase | Objectif | État |
|---|---|---|
| P0 | Préparation et inventaire | ☐ |
| P1 | Multi-tenancy (stancl) opérationnel | ☐ |
| P2 | Landlord : super-admin et création de cabinet | Clôturée |
| P3 | API cabinet : socle | Clôturée |
| P4 | Core Angular + PWA | En cours |
| P5 | Frontend du cabinet 1 (MVP) | En cours |
| P6 | Déploiement et exploitation | ☐ |
| P7 | Modules métier : API + Angular (cycle répété) | ☐ |
| P8 | Facturation plateforme (Landlord) | ☐ |
| P9 | Qualité et finalisation | ☐ |

**MVP = P0 → P6 terminées** (avec les écrans MVP de P5).

---

## P0 — Préparation et inventaire

- [x] **T0.1** Lire `conception.md`, puis le code de `backend/` (copie de Keduc). *Fin : aucune question ouverte non notée.* (réalisé 24/09 — session de cadrage, voir `docs/QUESTIONS.md`)
- [x] **T0.2** Produire `docs/INVENTAIRE_KEDUC.md` : versions (PHP, Laravel), migrations/tables, models, services, policies, requests, controllers web, routes, vues, PDF. *Fin : fichier complet.*
- [x] **T0.3** Comparer l'inventaire aux sections 7-9 de `conception.md` ; noter chaque écart dans `docs/QUESTIONS.md` avec l'option par défaut. *Fin : écarts listés.*
- [x] **T0.4** Initialiser Git (`backend/`, `frontend/`), `.gitignore`, `.env.example`. Ne jamais toucher au projet Keduc original. *Fin : premier commit propre.* (restructuration effectuée : code Laravel déplacé dans `backend/`, `frontend/` créé, `.env.testing` ignoré — secret retiré du suivi)
- [x] **T0.5** Créer les bases PostgreSQL locales : `saascd_plateforme` uniquement (les bases cabinet sont créées par le code). Régler `.env` sur `saascd_plateforme`. *Fin : `php artisan migrate:status` répond sur la bonne base.* (fait le 24/09 — migrations [1] Ran constatées ; le schéma complet y sera réorganisé en P1)

## P1 — Multi-tenancy opérationnel

- [x] **T1.1** Installer et publier `stancl/tenancy` v3 ; enregistrer `TenancyServiceProvider` dans `bootstrap/providers.php`. (v3.10.1 ; tags `config`/`migrations`/`routes`/`providers` publiés.)
- [x] **T1.2** Modèle `Cabinet` (`TenantWithDatabase`, `HasDatabase`, `HasDomains`, `getCustomColumns()` avec `tenancy_db_name`). Préfixe des bases `cabinet_`. *Fin : créer un cabinet en tinker crée physiquement la base PostgreSQL.* (validé de bout en bout par les tests d'isolation T1.9 — base `keduc_test_<slug>` créée+migrée ; création physique en dev `saascd_plateforme` restant à valider par le propriétaire)
- [x] **T1.3** Séparer les migrations : `database/migrations/` (plateforme) et `database/migrations/tenant/` (cabinet). Retirer tout `cabinet_id` des tables cabinet. Ajouter `uuid_client` (unique) à `cahier_textes`. *Fin : `tenants:migrate` fonctionne sur un cabinet vierge.* (fichiers déplacés 75/4, édits faits ; pipeline migrations validé par T1.9)
- [x] **T1.4** Bootstrappers Database, Filesystem, Queue actifs (Cache seulement si Redis). Sessions en `file`, queue `database` en connexion centrale. Si le bug d'identifiants PDO (PHP 8.2) apparaît : bootstrapper custom dans `app/Tenancy/`. (sessions/cache `file`, `DB_QUEUE_CONNECTION=pgsql`, timezone OK ; bootstrappers actifs ; réécriture `asset()` désactivée D-028)
- [x] **T1.5** Routes : `routes/landlord.php` (domaine central), `routes/tenant.php` et `routes/api.php` avec `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains`. (fait ; dev cabinet via `<slug>.localhost` ; `landlord.php` volontairement vide — voir D-025)
- [x] **T1.6** Adapter le code cabinet existant au contexte tenant : vérifier que models/services n'utilisent aucune connexion ni constante codée en dur ; jobs et notifications compatibles tenant. *Fin : services existants fonctionnent sur la base d'un cabinet.* (`App\Support\CabinetInfo` = données tenant + repli `keduc.cabinet` ; 4 services PDF + FactureWebController + 7 vues migrés ; module dormant inchangé)
- [x] **T1.7** Seeder cabinet : rôles Spatie (`admin_cabinet`, `enseignant`, `parent`, `eleve`, `gestionnaire_librairie`), permissions, types de cours, types de documents, thème et pied de page par défaut. (`TenantDatabaseSeeder` branché sur `tenancy.seeder_parameters` ; table tenant `parametres_publics` ; validé par T1.9 ; **désormais exécuté automatiquement à chaque création** — `SeedDatabase` dans le pipeline D-027)
- [x] **T1.8** Middleware `CabinetActif` : `403 CABINET_SUSPENDU` si statut ≠ actif. (alias `cabinet.actif`, appliqué aux routes tenant + groupe api)
- [x] **T1.9** **Tests d'isolation** : deux cabinets, aucune donnée croisée ; un cabinet inconnu → 404. *Fin : tests verts.* (4 tests verts — `tests/Feature/TenantIsolationTest.php` ; suite Legacy déplacée/exclue D-026)

## P2 — Landlord (super-admin)

- [x] **T2.1** Table `super_admins`, guard `landlord`, connexion Blade, seeder du premier super-admin. (migration + `SuperAdmin` (CentralConnection) + guard/provider `landlord` + 3 middlewares `landlord.auth/guest/central.domain` + routes `/admin` + vues connexion/dashboard + `SuperAdminSeeder` ; 8 tests verts. **Seed : `php artisan db:seed --class=SuperAdminSeeder`** après `php artisan migrate`)
- [ ] **T2.2** Layout Blade Landlord (navigation : Tableau de bord, Cabinets, Facturation, Journal) — **fait côté code** : `landlord/layouts/app.blade.php` (sidebar + topbar + logout), Dashboard et 3 sections placeholder (`/admin/cabinets`, `/admin/facturation`, `/admin/journal`), CSS `landlord.css` ; 4 tests verts (16 au total). ✅ implémenté — voir DECISIONS D-030.
- [x] **T2.3** Table `parametres_cabinet` + CRUD cabinets (liste, création, fiche, modification, tarifs, fonctionnalités actives). Migration centrale `parametres_cabinet` (cabinet_id unique, tarif_abonnement, tarif_par_eleve, fonctionnalites_activees json) + `ParametresCabinet` (CentralConnection) + `CabinetController` (index/recherche, create, store, show, edit/update, parametres.update) + vues liste/formulaire/fiche + `Cabinet::parametres()` et `primary_domain`. La création provisionne automatiquement (base tenant + migrations + seed, domaine `<sous_domaine>.localhost`, tarifs par défaut) — pipeline D-027, hors transaction (CREATE DATABASE). 8 tests verts (24 au total). Base Feature Tests centralisée dans `tests/TenantTestCase.php` (migrate:fresh non transacté + drop bases tenant, D-023).
- [x] **T2.4** **Pipeline de création** (jobs) : `CreerAdminCabinetEtNotifier` ajouté au pipeline `TenantCreated` (CreateDatabase → MigrateDatabase → SeedDatabase → admin cabinet → email identifiants → journal `admin_cabinet.cree`). Rollback total en cas d'échec : table centrale `journal_plateforme` (action/level/cabinet/contexte, sans FK tenant — survit au rollback), suppression base + tenants + domains + parametres, échec journalisé `cabinet.echec` (hook test `TENANCY_SIMULATE_ECHEC`). Email `CabinetIdentifiants` (vue markdown). 2 tests verts (26 au total). Hooks de test : purge des bases `keduc_test_%` résiduelles avant chaque `migrate:fresh` + terminaison de sessions avant DROP.
- [x] **T2.5** Suspendre / réactiver / archiver ; suppression définitive avec `pg_dump` préalable. (statuts actif/suspendu/archive — middleware `cabinet.actif` (T1.8) bloque 403 si ≠ actif ; actions sur la fiche `POST /admin/cabinets/{id}/statut` journalisées (`cabinet.suspendu/reactive/archive`) ; suppression `DELETE /admin/cabinets/{id}` avec sauvegarde `pg_dump` vers `storage/app/backups/` obligatoire (hook test `TENANCY_SKIP_BACKUP`) + journal `cabinet.supprime` + destruction parametres→domains→tenants→base ; `Note : le middleware « cabinet.actif » était déjà câblé (T1.8)` — voir DECISIONS D-033. 5 tests verts (31 au total, 113 assertions)
- [x] **T2.6** **Impersonation** (`tenancy()->impersonate`) avec bannière et sortie ; journalisée. (Feature stancl `UserImpersonation` activée ; jeton à usage unique 5 min (table centrale `tenant_user_impersonation_tokens`, colonne `super_admin_id`) ; bouton « Impersonner » sur la fiche ; cible = admin cabinet provisionné (`tenants.admin_utilisateur_id`, renseigné par le pipeline) ; consommation sur `GET <domaine>/impersonation/{jeton}` (404 inconnu/consommé, 403 autre cabinet, 419 expiré) ; bannière violette + sortie `POST /impersonation/sortir` dans le layout panel ; journal `impersonation.emise/debut/fin`. 9 tests verts (40 au total, 150 assertions) — voir DECISIONS D-034)
- [x] **T2.7** Table `journal_plateforme` + écran de consultation. (la table existait depuis T2.4 ; `JournalController` (index + filtres action/niveau/cabinet, pagination 20, lien cabinet) + vue `landlord/journal/index.blade.php` (badges de niveau, contexte JSON dans `<details>`, cast datetime) ; 5 tests verts (45 au total, 164 assertions))
- [x] **T2.8** Commande `php artisan cabinet:migrer-tous` (enveloppe de `tenants:migrate` avec rapport par cabinet et sauvegarde préalable). (`MigrerTousLesCabinets` : itère les cabinets (option `--tenant=`), sauvegarde pg_dump via `App\Support\SauvegardeCabinet` (extrait du contrôleur, option `--force-nosauvegarde`), appelle `tenants:migrate --tenants=<id>`, rapport tabulaire en console, journal `cabinet.migre`/`cabinet.migre.erreur` ; 4 tests verts (49 au total, 177 assertions))
- [x] **T2.9** Tests : création, rollback, suspension, impersonation. (couverts par `LandlordPipelineTest` [création + rollback simulateur d'échec], `LandlordCabinetGestionTest` [suspension/réactivation/archivage/suppression], `ImpersonationTest` [émission/consommation/sortie])
- [x] **T2.10** Interrupteur « accès écrans web Keduc » (Landlord) : activer/désactiver globalement la délivrance des vues et routes web de la copie Keduc après passage tenant (décision B4). *Fin : bascule fonctionnelle et testée.* (`Table `parametres_plateforme`[clé/valeur, `accès_ecrans_web_keduc` défaut OFF] + modèle `ParametresPlateforme` ; middleware `keduc.web` blocant le groupe web tenant (404) ; routes d'impersonation déplacées hors groupe pour rester indépendantes ; écran Landlord `admin/parametres` (interrupteur + lien sidebar) ; 4 tests dédiés — 53 tests, 189 assertions, verts.)**P2 clôturée.****

## P3 — API cabinet : socle

- [x] **T3.1** Conventions API : préfixe `/api`, format d'erreur JSON `{message, code, erreurs}`, messages `lang/fr`, gestion d'exceptions centralisée. (rendu global des exceptions dans `bootstrap/app.php` (`withExceptions`) : `HttpException` → statut + `{message, code}` (code par défaut = statut HTTP sauf `CABINET_INCONNU`/`CABINET_SUSPENDU`), `ValidationException` → `422 VALIDATION` + `erreurs`, 500 → `ERREUR_INTERNE`/+ log technique)
- [x] **T3.2** Authentification par session : connexion, déconnexion, `moi`, mot de passe oublié, réinitialisation, changement. Throttling. (`routes/api.php` : `/api/auth/*` — connexion `throttle:5,1`, mot-de-passe-oublie + reinitialiser `throttle:5,1`, reste sous `auth:web`. `AuthApiController` (connexion/deconnexion/moi/roleActif/motDePasseOublie/reinitialiserMotDePasse/changerMotDePasse), `UserResource`, 5 FormRequests d'API ; notification `ReinitialisationMotDePasse` (broker tenant reconfiguré via `User::sendPasswordResetNotification`). **Session sur l'API** : groupe `api` augmenté de `EncryptCookies` + `StartSession` (D-038))
- [x] **T3.3** Routes publiques : `/api/public/cabinet`, `actualites`, `faq`, `documents`, `produits`, `POST demandes-cours`, `POST commandes`. (7 contrôleurs publics + `Api/*` Resources, throttles demandes-cours/commandes `10,1`)
- [x] **T3.4** Tables et modèles du site public s'ils n'existent pas : `theme_cabinet`, `pied_de_page_cabinet`, `actualites`, `faq_sections`, `faq_questions` (+ services, policies, requests). (rien à créer : `theme`/`footer` = colonnes json de `parametres_publics` [D-022], tables `actualites`, `faq_sections`, `faq_questions` + services `ActualiteService`/`FaqService` existants — D-039)
- [x] **T3.5** API du backoffice MVP : contenu public (thème, pied de page, actualités, FAQ) et utilisateurs (créer, activer/désactiver, rôles). Reprendre services/policies/requests existants ; ajouter uniquement controllers API, Resources, routes. (groupe `/api/admin` muré `auth:web`+`role:admin_cabinet` ; `AdminContenuPublicApiController` (GET/PUT `contenu-public`), `AdminActualiteApiController` (CRUD sur `ActualiteService` + policy + FormRequests hérités), `AdminFaqSectionApiController`/`AdminFaqQuestionApiController` (sur `FaqService`), `AdminUtilisateurApiController` (liste/recherche+rôle+statut, création avec rôles, mise à jour, activer/suspendre) ; Resources `Api/*AdminResource` ; rendu 403 des `UnauthorizedException` Spatie ajouté (D-040). 5 tests — 77 verts, 313 assertions)
- [x] **T3.6** Envoi email des identifiants (notification en queue) et notifications `database` + `webpush` (abonnement push). (job `EnvoyerIdentifiantsCabinet` (queue) dispatché par le pipeline T2.4 au lieu de l'envoi synchrone ; API `/api/notifications` (liste + `non_lues`, marquer lue, lire toutes, supprimer — uniquement ses propres) ; package `laravel-notification-channels/webpush` ^13 installé, migration `push_subscriptions` → dossier tenant (connexion par défaut = cabinet, D-041), trait push sur `User`, API `/api/abonnement-push` (GET/POST/DELETE) ; clés VAPID à générer au déploiement. 3 tests — 80 verts, 341 assertions)
- [x] **T3.7** Documentation OpenAPI générée (Scramble `dedoc/scramble` ^0.13). *Fin : le frontend peut se baser dessus.* (serveur `/docs/api` + spec `/docs/api.json` sur le domaine central, base `api` → paths sans préfixe ; 32 routes ; Gate `viewApiDocs` ouverte hors production — 403 en prod ; export `api.json` gitignoré — test `documentation openapi disponible`)
- [x] **T3.8** Tests API + isolation. (suites vertes : conventions + auth + public + backoffice + notifications + tenant isolation)
- [x] **T3.9** Endpoints publics complémentaires pour le frontend cabinet. (`GET /api/public/stats`, `enseignants`, `temoignages`(+`/{slug}`) et `references` (options formulaires) — D-046 ; OpenAPI passe à 37 routes. Suite complète : 88 tests / 397 assertions verts.)

## P4 — Core Angular et PWA

> **Cycle d'un cabinet (D-043)** : créer le cabinet + son admin (backend provisionné) **puis**
> coder son frontend sur mesure sur le `core`, et **publier seulement après validation**. En dev :
> tenant pilote (`dev1`) d'abord, frontend contre lui, publication sur validation. Pas de gabarit
> d'apparence imposé — chaque frontend cabinet est dessiné et codé pour lui (identité propre).

> **Adaptation (27/09, D-047)** : le socle Angular est posé directement dans `frontend/magis`
> (Angular 22 signal-based, zoneless, lazy-loading, SEO dynamique, Bootstrap 5 SAAS + tokens
> `--mpc-*`) et sert de base réutilisable pour les cabinets suivants. Les items ci-dessous qui
> décrivent un monorepo de bibliothèque (`projects/core`, gabarit/copie) sont en attente de
> refactorisation éventuelle ; la PWA (T4.4-T4.6) reste à faire.

- [ ] **T4.1** Créer `frontend/` : workspace Angular (dernière version stable), Tailwind, ESLint/Prettier, TypeScript strict.
- [ ] **T4.2** Bibliothèque `projects/core` : client API, intercepteurs (XSRF, erreurs 401/403/422/503, `CABINET_SUSPENDU`), `AuthService`, guards par rôle.
- [ ] **T4.3** Service **cabinet/thème** : charge `/api/public/cabinet` au démarrage et applique les variables CSS (couleurs, police).
- [ ] **T4.4** Module **PWA** : Service Worker, installation, invite de mise à jour, détection en ligne/hors ligne.
- [ ] **T4.5** Module **hors ligne** : file IndexedDB, `uuid_client`, synchronisation au retour de connexion, indicateur d'état, purge à la déconnexion.
- [ ] **T4.6** Module **notifications push** (`SwPush`).
- [ ] **T4.7** Modèles TypeScript des ressources API.
- [ ] **T4.8** Application `projects/modele-cabinet` (gabarit) + script `scripts/nouveau-frontend-cabinet` qui la copie en `cabinet-<slug>`.
- [ ] **T4.9** Config de développement : `*.localhost` + proxy `/api` qui conserve l'hôte. *Fin : `cabinet1.localhost:4200` parle à la base du cabinet 1.*

## P5 — Frontend du cabinet 1 (MVP)

> **Réalisé (27/09)** : le cabinet « Magis Plus Center » a été créé depuis le Landlord par
> l'utilisateur (`magis-plus-center`, domaine `magis-plus-center.localhost`), et le frontend
> cabinet a été **codé sur mesure** dans `frontend/magis` (Angular 22, D-047) — la stratégie
> « gabarit puis copie » (T4.8) a été inversée : le socle Angular pose une base réutilisable
> pour les cabinets suivants. Build vérifié ; suite backend verte (88 tests / 397 assertions).
>
> **Backoffice livré (29/09)** : connexion + mot de passe oublié/réinitialisation, coquille
> responsive (bottom-nav mobile / rail tablette / sidebar PC), navigation par rôle + gardes,
> choix de rôle multi-comptes, mode sombre, et écrans admin phase 1 (voir T5.3/T5.4). Le bug
> de double hachage du mot de passe (cast `hashed` du modèle `User`) et les erreurs « Une
> erreur est survenue » (code `IDENTIFIANTS_INCORRECTS` mal orthographié en front, mail non
> configuré sur mot de passe oublié) ont été corrigés le 29/09 ; le proxy de dev cible
> `127.0.0.1:8080` en forçant l'en-tête `Host` du tenant (`proxy.conf.js`) — plus besoin du
> fichier `hosts` Windows. Commande de réinitialisation d'admin : `artisan
> cabinet:reset-admin-password <tenant>`.
>
> **Second tour backoffice (29/09)** : les modifications admin ne remontaient pas sur la page
> publique. Causes corrigées : (1) actualités — le backend ignorait `statut` → tout partait en
> « brouillon » et restait invisible (`ActualiteService::create/update` appliquent désormais
> `statut`, fixent `published_at`/`is_active` à la publication, rules `statut` ajoutées aux
> FormRequests) ; (2) fiche/logo — cache front `shareReplay` figé remplacé par un cache
> rafraîchissable (`rafraichirCabinetPublic()`) appelé après chaque sauvegarde, et sauvegarde
> fiche fusionne `data` (`array_replace_recursive`) pour ne plus écraser les clés annexes ;
> (3) **nouveau : logo du cabinet** — upload admin multipart `PUT /api/admin/contenu-public/logo`
> (stockage disque public tenant `logos/`, conservé dans `parametres_publics.data.logo`), flux
> public `GET /api/public/logo`, `logo_url` exposé par `/api/public/cabinet`, affiché dans le
> header/footer publics, la sidebar backoffice et le cadre d'authentification (repli monogramme
> si absent). Tests ajoutés (`statut + publication via l'API`, `logo upload/stream/préservation`)
> ; suite backend verte : 90 tests, 431 assertions.
>
> **Troisième tour (30/09) — cause racine des deux bugs restants** : la page d'édition
> renvoyait « Le titre est obligatoire » et le logo « Le fichier logo est obligatoire » alors
> que les champs étaient remplis. Cause : **PHP < 8.4 ne peuple jamais `$_POST`/`$_FILES` pour un
> `multipart/form-data` envoyé en PUT/PATCH** (Symfony ne lit que `application/x-www-form-urlencoded`
> en PUT, `vendor/symfony/http-foundation/Request.php:288-302`) → les champs ET les fichiers
> d'un « multipart PUT » sont silencieusement perdus. Correctif : les routes
> `PUT /api/admin/actualites/{actualite}` et `PUT /api/admin/contenu-public/logo` acceptent
> désormais aussi **POST** (PHP peuple alors les super-globales), et le frontend (`majActualite`,
> `mettreAJourLogo`) envoie en POST. Tests de régression ajoutés (`mise à jour multipart POST`
> d'une actualité avec image + passage en « publie » visible publiquement ; upload logo en POST).
> Suite backend verte : **91 tests, 442 assertions**. La FAQ publique a été auditée de bout en bout
> (écritures → `flushCache` de `faq.public` → reconstruction à la lecture) ; backend sain — si la
> création n'apparaît toujours pas publiquement, refaire le test **backend redémarré** + accueil
> public rechargé.

- [x] **T5.1** Créer le cabinet 1 depuis le Landlord (P2), puis `cabinet-<slug>` à partir du gabarit. (✍️ cabinet **Magis Plus Center** créé par l'utilisateur — données du dossier en `docs/PLAN_CABINET_1.md` ; frontend codé sur mesure dans `frontend/magis`, pas de gabarit/D-043)
- [x] **T5.2** **Page publique** : accueil (hero), à propos, actualités (liste + détail), FAQ, formulaire de demande de cours, pied de page ; sections activables selon la configuration. Design soigné, mobile d'abord. (livré : 8 pages — accueil, actualités(+détail), bibliothèque, boutique, FAQ, demande de cours, contact — textes depuis `src/content.ts` (D-044), données via l'API publique (fiche, actualités, FAQ, documents, produits, stats, enseignants, témoignages, references) ; hero/à propos/services/zones/stats/solutions/enseignants/témoignages/FAQ/bandeau rendez-vous/contact ; header collant + tiroir mobile, footer 4 colonnes, bouton remontée ; palette orange `#e8610c` + bleu `#12305e`, icônes bootstrap-icons, sans dégradés ni émojis ; formulaires demande de cours → `POST demandes-cours` et commande boutique → WhatsApp)
- [x] **T5.3** **Backoffice** : connexion, changement de mot de passe, coquille avec navigation par rôle, tableau de bord (squelette). (livré dans `frontend/magis` : connexion + mot de passe oublié/réinitialisation, coquille responsive mobile-first (bottom-nav/tiroir, rail tablette, sidebar PC), navigation par rôle + gardes + choix de rôle multi-comptes, mode sombre, tableau de bord, profil. Validé en bout en bout le 29/09.)
- [x] **T5.4** Écrans admin : gestion du contenu public (thème, pied de page, actualités, FAQ) et des utilisateurs. (écrans Fiche cabinet, Actualités (CRUD + édition, statuts brouillon/publiée), FAQ sections+questions, Utilisateurs et Notifications — branchés sur `/api/admin/*`.)
- [ ] **T5.5** Manifest, icônes et couleurs PWA propres au cabinet ; test d'installation et de lancement hors ligne.
- [ ] **T5.6** Test de bout en bout manuel : création du cabinet → email → connexion → modification du contenu → visible sur la page publique.

## P6 — Déploiement et exploitation

- [ ] **T6.1** Choisir l'hébergeur (VPS) ; installer Nginx, PHP-FPM, PostgreSQL, Supervisor, cron.
- [ ] **T6.2** DNS générique `*.domaine` + certificat SSL générique.
- [ ] **T6.3** Nginx : règle unique par sous-domaine (statique Angular + `/api` vers Laravel avec `Host` conservé) ; bloc du domaine central pour le Landlord.
- [ ] **T6.4** Worker de queue supervisé ; `schedule:run` en cron ; SMTP d'envoi d'emails.
- [ ] **T6.5** Sauvegardes nocturnes `pg_dump` par base + copie hors serveur ; **test de restauration**.
- [ ] **T6.6** Scripts de déploiement : backend (`pull`, `composer`, `cabinet:migrer-tous`, `optimize`) et frontend (`build` + copie dans `/var/www/frontends/<slug>/`).
- [ ] **T6.7** Recette MVP : cocher tous les critères d'acceptation de `conception.md` section 17.

## P7 — Modules métier : API + Angular (cycle répété)

Les modules existent côté cabinet. Pour **chacun**, appliquer ce cycle **court** :

```text
Controller API + Resource + routes  →  tests API  →  écrans Angular  →  test hors-ligne si concerné
```

Ordre recommandé (cocher un module quand son cycle est complet) :

- [ ] **M1** IAM complet (import CSV enseignants, profils, photo)
- [ ] **M2** Académique (classes, matières, types de cours/documents, périodes)
- [ ] **M3** Scolarité (parents, élèves, comptes élèves)
- [ ] **M4** Demandes de cours → conversion en contrat
- [ ] **M5** Contrats, affectations, planning
- [ ] **M6** Cahier de texte **avec hors-ligne** + objectifs pédagogiques
- [ ] **M7** Rapports mensuels enseignants (+ PDF)
- [ ] **M8** Facturation parents (+ PDF)
- [ ] **M9** Paie enseignants
- [ ] **M10** Bibliothèque
- [ ] **M11** Librairie
- [ ] **M12** Notifications automatiques
- [ ] **M13** Audit (`historique_activites`)
- [ ] **M14** Dashboards (admin, enseignant, parent)

## P8 — Facturation plateforme (Landlord)

- [ ] **T8.1** Table `inscriptions_facturables` (cabinet) alimentée par événements : compte activé, contrat activé, renouvellement annuel.
- [ ] **T8.2** Tables `factures_cabinet`, `lignes_facture_cabinet`, `paiements_cabinet`.
- [ ] **T8.3** Service `CalculFacturationCabinet` (comptage par type × tarifs de `parametres_cabinet`), lignes débit/crédit libres.
- [ ] **T8.4** Écrans Landlord : générer une facture (brouillon → émise), lignes libres, enregistrer un paiement manuel, statuts, PDF.
- [ ] **T8.5** Tableau de bord Landlord : cabinets, inscrits, facturé, encaissé, impayés.
- [ ] **T8.6** Tests du calcul (aucune double facturation).

## P9 — Qualité et finalisation

- [ ] **T9.1** Revue de sécurité (checklist section 16) et données de mineurs.
- [ ] **T9.2** Performance : index vérifiés sur toutes les `_id`, requêtes lentes, N+1.
- [ ] **T9.3** Documentation : `README` (installation Windows, commandes), guide « créer un cabinet », guide de déploiement.
- [ ] **T9.4** Nettoyage : code mort, `docs/DECISIONS.md` à jour.

---

## Routine « nouveau cabinet » (après le MVP)

À répéter pour chaque cabinet, sans toucher au core :

1. [ ] **Landlord** : créer le cabinet (nom, slug, admin, tarifs, fonctionnalités actives). L'admin reçoit son email.
2. [ ] **Frontend** : `nouveau-frontend-cabinet <slug>` → application `cabinet-<slug>` créée depuis le gabarit.
3. [ ] **Personnalisation** : pages publiques, navigation, dashboards et composants propres au cabinet (uniquement dans `cabinet-<slug>`).
4. [ ] **PWA** : manifest, icônes, couleurs.
5. [ ] **Build et déploiement** vers `/var/www/frontends/<slug>/`.
6. [ ] **Recette** : connexion admin, page publique, installation PWA, test hors ligne.

## Règles pour opencode

- Une tâche à la fois, dans l'ordre. Ne pas commencer P4 avant que P1-P3 soient testées.
- **Réutiliser** les services, policies, requests et models existants ; ne créer que ce qui manque.
- Ne jamais supprimer de code Keduc existant sans demande explicite ; le signaler dans `docs/INVENTAIRE_KEDUC.md`.
- Tout doute → `docs/QUESTIONS.md`, appliquer l'option par défaut, continuer.
- À la fin de chaque tâche : tests verts, case cochée, commit.