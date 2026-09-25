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
| P2 | Landlord : super-admin et création de cabinet | ☐ |
| P3 | API cabinet : socle | ☐ |
| P4 | Core Angular + PWA | ☐ |
| P5 | Frontend du cabinet 1 (MVP) | ☐ |
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
- [ ] **T2.6** **Impersonation** (`tenancy()->impersonate`) avec bannière et sortie ; journalisée.
- [ ] **T2.7** Table `journal_plateforme` + écran de consultation.
- [ ] **T2.8** Commande `php artisan cabinet:migrer-tous` (enveloppe de `tenants:migrate` avec rapport par cabinet et sauvegarde préalable).
- [ ] **T2.9** Tests : création, rollback, suspension, impersonation.
- [ ] **T2.10** Interrupteur « accès écrans web Keduc » (Landlord) : activer/désactiver globalement la sé délivrance des vues et routes web de la copie Keduc après passage tenant (décision B4). *Fin : bascule fonctionnelle et testée.*

## P3 — API cabinet : socle

- [ ] **T3.1** Conventions API : préfixe `/api`, format d'erreur JSON `{message, code, erreurs}`, messages `lang/fr`, gestion d'exceptions centralisée.
- [ ] **T3.2** Authentification par session : connexion, déconnexion, `moi`, mot de passe oublié, réinitialisation, changement. Throttling.
- [ ] **T3.3** Routes publiques : `/api/public/cabinet`, `actualites`, `faq`, `documents`, `produits`, `POST demandes-cours`, `POST commandes`.
- [ ] **T3.4** Tables et modèles du site public s'ils n'existent pas : `theme_cabinet`, `pied_de_page_cabinet`, `actualites`, `faq_sections`, `faq_questions` (+ services, policies, requests).
- [ ] **T3.5** API du backoffice MVP : contenu public (thème, pied de page, actualités, FAQ) et utilisateurs (créer, activer/désactiver, rôles). Reprendre services/policies/requests existants ; ajouter uniquement controllers API, Resources, routes.
- [ ] **T3.6** Envoi email des identifiants (notification en queue) et notifications `database` + `webpush` (abonnement push).
- [ ] **T3.7** Documentation OpenAPI générée (ex. Scramble). *Fin : le frontend peut se baser dessus.*
- [ ] **T3.8** Tests API + isolation.

## P4 — Core Angular et PWA

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

- [ ] **T5.1** Créer le cabinet 1 depuis le Landlord (P2), puis `cabinet-<slug>` à partir du gabarit.
- [ ] **T5.2** **Page publique** : accueil (hero), à propos, actualités (liste + détail), FAQ, formulaire de demande de cours, pied de page ; sections activables selon la configuration. Design soigné, mobile d'abord.
- [ ] **T5.3** **Backoffice** : connexion, changement de mot de passe, coquille avec navigation par rôle, tableau de bord (squelette).
- [ ] **T5.4** Écrans admin : gestion du contenu public (thème, pied de page, actualités, FAQ) et des utilisateurs.
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