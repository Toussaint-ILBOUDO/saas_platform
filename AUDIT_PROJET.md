# Audit global du projet KEduc

> Rapport d'audit consolidé, basé sur le **code réel** comme source de vérité (pas la conception initiale).
> Date de l'audit : août 2026 — Stack : Laravel 12.61.0, PHP ^8.2.

---

## Table des matières

1. [Synthèse exécutive](#1-synthèse-exécutive)
2. [Cartographie du projet](#2-cartographie-du-projet)
3. [Écarts conception vs réalité](#3-écarts-conception-vs-réalité)
4. [Audit sécurité](#4-audit-sécurité)
5. [Audit routes](#5-audit-routes)
6. [Audit performance](#6-audit-performance)
7. [Audit scalabilité](#7-audit-scalabilité)
8. [Audit SEO](#8-audit-seo)
9. [Audit UI/UX](#9-audit-uiux)
10. [Tests & qualité](#10-tests--qualité)
11. [Dépendances](#11-dépendances)
12. [Plan de correction priorisé](#12-plan-de-correction-priorisé)
13. [Corrections effectuées — Étape 1 (sécurité)](#13-corrections-effectuées--étape-1-sécurité)
14. [Corrections effectuées — Étape 2 (Partie 02)](#14-corrections-effectuées--étape-2-partie-02--bibliothèque-numérique--pdf)
15. [Corrections effectuées — Étape 3 (Partie 03)](#15-corrections-effectuées--étape-3-partie-03--audit-routes-vues-manquantes-route-login)
16. [Corrections effectuées — Étape 4 (Partie 04 : BD, Models, relations, indexes)](#16-corrections-effectuées--étape-4-partie-04--bd-models-relations-indexes)

---

## 1. Synthèse exécutive

Le projet KEduc est une plateforme Laravel 12 de gestion d'un cabinet de soutien scolaire. L'architecture est **modulaire** (`app/Modules/`) et couvre un périmètre fonctionnel large : pédagogie, finance, bibliothèque numérique, librairie (e-commerce), CMS (actualités/FAQ), notifications, témoignages et espace public.

### Verdict global

| Domaine | État |
|---|---|
| Architecture & organisation | Bonne (modulaire, services séparés, Form Requests) |
| Périmètre fonctionnel | Large et cohérent, cycles métier complets (demande → contrat → facture → paiement) |
| Sécurité | **Défaillant** : 2 failles critiques (CRUD Users exposé, IDOR librairie), 3 élevées, sans oublier les documents privés exposés |
| Routes | Correctif immédiat requis sur `routes/auth.php` ; quelques doublons et routes mortes |
| Performance | Correcte sur les services publics ; N+1 ciblés dans l'admin |
| Scalabilité | Traitements synchrones lourds (PDF, emails) ; aucune queue réellement utilisée |
| SEO | Base correcte sur les pages publiques principales ; manques (canonical/OG/Twitter/sitemap) |
| Tests | **Inexistants** (2 tests squelette) |
| UI/UX | Bonne base Bootstrap ; états vides et liens morts à traiter |

### Points forts confirmés

- Prix des commandes librairie **recalculés côté serveur** (aucun montant client accepté).
- Factures/bulletins admin protégés par `auth + role`.
- `BulletinPaiePolicy` vérifie bien la propriété `enseignant_id`.
- Cahiers de textes et objectifs pédagogiques protégés par `authorize()`.
- CSRF actif partout (aucun `withoutMiddleware(VerifyCsrfToken)`).
- `.env` non tracké par git.
- `ActualiteService` exemplaire (eager loading, caches, pas de N+1).
- Liste publique de la bibliothèque filtrée par scope `public()`.

### Correctifs déjà appliqués (missions antérieures)

- **HTTP 500 page d'accueil** : enum `TemoignageReaction` renommé `TemoignageReactionType` (fichier, classe, Service, Request, 2 vues) + `DB::raw()` autour de `TemoignageReactionType::scoreSql()` dans `scopeWithScore`.
- **Pagination** : `listeClassement()` corrigé (imports `LengthAwarePaginatorContract` + classe concrète `LengthAwarePaginator`).

---

## 2. Cartographie du projet

### 2.1 Stack technique (réelle)

| Élément | Valeur |
|---|---|
| Framework | Laravel 12.61.0 |
| PHP | ^8.2 (binaire local : XAMPP `/mnt/c/xampp/php/php.exe`) |
| Base de données | PostgreSQL (via `.env` ; `DB_CONNECTION=pgsql`) |
| Auth API | Laravel Sanctum 4.x |
| Permissions | Spatie Permission 6.25 (rôles + permissions) |
| Médias | Spatie Media Library 11.23 |
| PDF | barryvdh/laravel-dompdf 3.1 |
| Localisation | laravel-lang/common |
| Front | Bootstrap (Blade), Vite 7 + Tailwind 4 (dev deps) |
| Session/Cache/Queue | Configurables via `.env` (driver database dispo) |

### 2.2 Structure des modules (`app/Modules/`)

| Module | Contenu | État |
|---|---|---|
| `Auth` | Controllers (Auth, Dashboard, Profil), Requests, Services | Actif |
| `Users` | Controllers (Parent, Eleve, Enseignant), Services, Requests | Actif (⚠️ routes exposées, cf. sécurité) |
| `Pedagogie` | ~17 controllers (Classes, Matières, TypeCours, DemandeCours, Contrats, CahiersTextes, Objectifs, Rapports, PDF…) | Actif |
| `Finance` | Factures, FactureCabinet, BulletinsPaie, PaiementsEnseignants, Périodes, PDF | Actif |
| `Bibliotheque` | Public + Admin, TypeDocument, PeriodeDocument, Policy, Services | Actif (⚠️ policy permissive) |
| `Librairie` | Public + Admin, Produits, Commandes, PDF | Actif (⚠️ IDOR confirmation/PDF) |
| `Communication` | Actualités (public + admin), FAQ (sections + questions) | Actif |
| `Systeme` | Notifications | Actif (⚠️ middleware auth manquant) |
| `Temoignages` | Public + Admin + Controller privé, Enums, Policy, Services, Requests | Actif (récent) |
| `Public` | HomeController | Actif |
| `Academique` | — | **Vide (squelette)** |
| `Administration` | — | **Vide (squelette)** |
| `Audit` | — | **Vide (squelette)** |
| `Medias` | — | **Vide (squelette)** |
| `Scolarite` | — | **Vide (squelette)** |

> 5 modules déclarés sont des squelettes vides : `Academique`, `Administration`, `Audit`, `Medias`, `Scolarite`.

### 2.3 Modèles (`app/Models/` — 59 modèles centralisés)

`Actualite, ActualiteReaction, AffectationEnseignant, BulletinPaie, BulletinPaieAjustement, BulletinPaieLigne, CahierTexte, CategorieProduit, Classe, Commande, ContratCours, DemandeCours, DemandeCoursMatiere, DocumentAccessLog, DocumentBibliotheque, DocumentCommentaire, DocumentNote, DocumentSignalement, DocumentTag, Eleve, EnseignantMatiere, EnseignantProfil, EvaluationCours, Facture, FactureCabinet, FaqQuestion, FaqSection, FavoriBibliotheque, LigneCommande, LigneFacture, LigneFactureCabinet, LignePaiementEnseignant, Matiere, Notification, ObjectifMatiere, ObjectifPedagogique, PaiementCabinet, PaiementEnseignant, ParentProfil, PeriodeComptable, PeriodeDocument, Produit, RapportMensuelEnseignant, Tag, Temoignage, TemoignageCommentaire, TemoignageReaction, TemoignageSignalement, TypeAjustement, TypeCommission, TypeCours, TypeDocument, User`

### 2.4 Base de données (76 migrations)

- Tables noyau : `users, cache, jobs, personal_access_tokens, permission_tables (roles, permissions, model_has_roles…), media` (Spatie).
- Profils : `parent_profils, enseignant_profils, eleves` (+ `statut`).
- Référentiels : `classes, matieres, type_cours, type_documents, periode_documents, tags, categorie_produits, periodes_comptables, type_commissions, type_ajustements`.
- Pédagogie : `demande_cours(+_matieres), contrat_cours, affectation_enseignants, cahier_textes, objectif_pedagogiques, objectif_matieres, evaluation_cours, rapport_mensuel_enseignants, enseignant_matiere`.
- Finance : `factures, ligne_factures, paiement_enseignants, ligne_paiement_enseignants, facture_cabinets, ligne_facture_cabinets, paiement_cabinets, bulletins_paie, bulletin_paie_lignes, bulletin_paie_ajustements`.
- Bibliothèque : `document_bibliotheques, document_tags, document_notes, document_commentaires, document_signalements, favori_bibliotheques, document_access_logs`.
- Librairie : `produits, commandes, ligne_commandes`.
- CMS : `faq_sections, faq_questions, actualites, actualite_reactions`.
- Témoignages : `temoignages, temoignage_reactions, temoignage_commentaires, temoignage_signalements`.

### 2.5 Controllers (48)

17 modules couvrent ~48 contrôleurs (liste exhaustive en section [2.2](#22-structure-des-modules-appmodules)).

### 2.6 Vues (177 fichiers Blade)

Arborescence : `auth, bibliotheque, communication, documents, emails, finances, librairie, panel (dashboard/layouts/notifications/partials/sidebars), pdf, pedagogie, profil, publicpages, temoignages`.

Remarque : casse incohérente des dossiers `pedagogie/Classes`, `pedagogie/Matieres`, `pedagogie/Users` (vs `cahiers-textes`, `demande-cours` en minuscules) — voir §5.6.

### 2.7 Rôles & permissions

Rôles : `super-admin, admin, parent, enseignant, eleve, gestionnaire`.

Permissions déclarées (extraits) : `dashboard.view` ; `eleve.{view,create,update,delete}` ; `enseignant.{view,create,update,delete}` ; `contrat.{view,create,update,delete}` ; `facture.{view,create,update}` ; `paiement.{view,create}` ; `bibliotheque.{view,create,update,delete}` ; `librairie.view` ; `commande.update` ; `produit.{view?,create,update,delete}` ; `categorie.{create,update,delete}` ; `faq.{view,create,update,delete}` ; `actualite.{view,create,update,delete}` ; `rapport.{view,create}` ; `temoignage.{view,create}`.

---

## 3. Écarts conception vs réalité

| # | Document de conception (`docs/conception.md`) | Réalité du code | Impact |
|---|---|---|---|
| E1 | Modules listés : AUTH, PEDAGOGIE, FINANCE, BIBLIOTHEQUE, LIBRAIRIE, COMMUNICATION, ADMINISTRATION, MEDIAS, AUDIT | Modules réels : + `Users`, `Systeme`, `Public`, `Temoignages` ; `Academique`, `Scolarite` en squelette ; contenu de ADMINISTRATION/MEDIAS/AUDIT non implémenté (historique d'activités absent) | Doc obsolète |
| E2 | Routes listées : `academique.php, scolarite.php, systeme.php` | Fichiers réels : `web, auth, pedagogie, notifications, finance, bibliotheque, librairie, cms, actualites, temoignages, api, console`. **Pas de** `academique.php`, `scolarite.php`, `systeme.php` | Doc obsolète |
| E3 | Entités `PaiementCabinet`, `LigneFactureCabinet`, `TypeCommission` | Modèles présents | OK |
| E4 | `FavoriBibliotheque`, `DocumentAccessLog` (conception : 2 colonnes) | Présents, enrichis (adresse_ip, user_agent, action) | OK, conception incomplète |
| E5 | `Commande` sans frais de livraison | Migrations `2026_07_29` et `2026_07_30` ajoutent `statut_modifications` et `whatsapp` | Conception incomplète |
| E6 | `EnseignantProfil` sans bulletin | Bulletins de paie ajoutés (bulletin_paie_lignes, ajustements, type_ajustements) | Conception incomplète |
| E7 | DocumentBibliotheque « simplifié » (section informelle « Bibliothèque numérique ») | Table réelle enrichie : slug, resume, periode, visibilite, statut, thumbnail, nombre_pages, nombre_vues/téléchargements/favoris/notes, moyenne_notes, is_featured, published_at, deleted_at | Section informelle à fusionner |
| E8 | Le module Témoignages n'existe nulle part dans la conception | Module complet (4 tables + policy + enums + vues) | À ajouter |
| E9 | « Queues » annoncée dans la stack | Aucun job (`app/Jobs/` inexistant) ; traitements synchrones | Stack à nuancer |
| E10 | « HistoriqueActivite » (audit) annoncé | Aucun modèle/table/migration | Non implémenté |
| E11 | `Media` décrit comme table maison (uuid, mediaable…) | Utilisation réelle de **Spatie Media Library** (table `media` standard Spatie) | Conception à corriger |
| E12 | Notifications décrites comme table + notifications automatiques | Table `notifications` présente + `NotificationDispatcher` ; notifications automatiques partielles | Conception partielle |

---

## 4. Audit sécurité

### 🔴 Faille CRITIQUE

#### C1 — CRUD Parents / Élèves / Enseignants sans aucune authentification

- **Localisation** : `routes/auth.php:33-54` ; `app/Modules/Users/Http/Controllers/{Parent,Eleve,Enseignant}Controller.php` ; services `app/Modules/Users/Services/*`.
- **Cause** : les routes `Route::resource('parents'|'eleves'|'enseignants')` et `eleves/{eleve}/account` sont **hors** du groupe `middleware('auth')`. Aucun `authorize()` dans les controllers.
- **Impact** :
  - Fuite de données : `GET /parents`, `/eleves`, `/enseignants` (nom, prénom, téléphone, email, classe, date/lieu de naissance, allergies, religion…).
  - Création de comptes : `POST /parents`, `/eleves`, `/enseignants`.
  - **Prise de contrôle** : `PUT /parents/{id}` et `PUT /enseignants/{id}` permettent de **changer le mot de passe** de n'importe quel compte (takeover complet).
  - Activation d'un compte élève (`POST /eleves/{eleve}/account`) avec mot de passe choisi par l'attaquant.
- **Exploit** : simple visiteur ; CSRF contourné en récupérant le token sur la page `GET` correspondante (token de sa propre session).
- **Correctif** : envelopper ces routes dans `Route::middleware(['auth','role:admin|super-admin|gestionnaire'])` et ajouter les `authorize()` dans les controllers.

#### C2 — IDOR : confirmation et PDF de commande librairie accessibles à tous

- **Localisation** : `routes/librairie.php:36-42` ; `app/Modules/Librairie/Http/Controllers/PublicLibrairieController.php:108-120` (`confirmation()`, `pdf()`).
- **Cause** : aucun contrôle d'authentification ni de propriété (`user_id` jamais comparé à `Auth::id()`), alors que `LibrairieController::show` fait bien `authorize('view', $commande)`.
- **Impact** : les IDs étant séquentiels, un attaquant peut itérer et consulter toutes les confirmations (nom, téléphone, WhatsApp, adresse, détail, montant) et télécharger les PDF des commandes de tous les clients.
- **Correctif** : lier la commande à la session (guest) ou à l'utilisateur connecté, et appliquer `authorize('view', $commande)` sur ces 2 routes.

### 🟠 Faille ÉLEVÉE

#### E1 — Documents privés de la bibliothèque consultables/téléchargeables par tout utilisateur connecté

- **Localisation** : `app/Policies/DocumentBibliothequePolicy.php:24-31,64-71` ; `PublicBibliothequeController.php:76-121` ; `BibliothequeController.php:66-82`.
- **Cause** : `view()` et `download()` retournent `true` dans toutes les branches (le `return true` final court-circuite les conditions `is_public && statut === 'publie'`). `comment()`, `favorite()`, `rate()` retournent aussi `true`.
- **Impact** : tout utilisateur connecté (parent, élève) peut voir/télécharger un document **privé, brouillon, en_attente ou refusé**. IDs séquentiels.
- **Correctif** : pour les non-admin, `view`/`download`/`comment`/`favorite`/`rate` ne doivent passer que si `is_public && statut === 'publie'` (ou propriétaire).

#### E2 — PDF des rapports mensuels sauvegardés dans le stockage public

- **Localisation** : `app/Modules/Pedagogie/Services/RapportMensuelPdfService.php:57` ; `RapportMensuelPdfController.php:51`.
- **Cause** : `POST /rapports-mensuels/{rapport}/pdf/save` (protégé `can('view','rapport')`) écrit dans `storage/app/public/rapports-mensuels/` avec nom prévisible `rapport-mensuel-{enseignant}-{periode}.pdf`, accessible via `/storage/...` **sans authentification**, jamais supprimé. Données financières exposées.
- **Correctif** : stocker sur disque `local` (privé) et servir via une route contrôlée ; prévoir suppression à la révocation.

#### E3 — Fichiers de documents stockés sur le disque public avec nom d'origine

- **Localisation** : `config/filesystems.php` (disque `public`) ; `DocumentBibliothequeService::attachFile` (`usingFileName($file->getClientOriginalName())`).
- **Cause** : pas de `config/media-library.php` ; Spatie Media Library écrit donc sur le disque `public` avec le nom d'origine → URL directe `/storage/{media_id}/{nom}` sans aucune policy.
- **Impact** : tout document uploadé (même privé/brouillon) est adressable directement.
- **Correctif** : configurer Media Library sur un disque privé `local` + controller de streaming autorisé.

#### E4 — Routes notifications sans middleware `auth`

- **Localisation** : `routes/notifications.php` (aucun middleware) ; `app/Modules/Systeme/Http/Controllers/NotificationController.php`.
- **Cause** : dépendance à `$request->user()` (lignes 19, 35…) ; invité → `null` → erreur fatale (500 + stack trace en debug). La propriété `abort_if($notification->user_id !== $request->user()->id, 403)` existe mais pas le garde-fou d'auth.
- **Correctif** : `Route::prefix('notifications')->middleware('auth')`.

### 🟡 Faille MOYENNE

| # | Localisation | Description |
|---|---|---|
| M1 | `LibrairieService.php:262` ; `PasserCommandeRequest.php:33` | `is_livraison` / `frais_livraison` fournis par le navigateur → le client peut annuler ses frais de livraison (prix produits en revanche bien recalculés côté serveur) |
| M2 | `EleveService::create` | Mot de passe par défaut `password123` si absent (combiné à C1, un attaquant crée un compte avec mot de passe connu) |
| M3 | `routes/auth.php:12` | `POST /login` sans `throttle` → brute-force possible (message d'erreur générique, bon point) |
| M4 | `.env` | `APP_DEBUG=true` et identifiants BDD en clair (fichier non tracké, risque limité mais stack traces exposées en cas d'erreur) |
| M5 | `HomeController.php:38-43` | Compteurs métier exposés sur la page d'accueil publique (nb_users, nb_eleves, nb_contrats…) + liste des 10 derniers enseignants → fuite d'information |

### 🟢 Faille FAIBLE

| # | Localisation | Description |
|---|---|---|
| F1 | `ContratCoursController`, `CahierTexteController`, `FactureController` (API) | Controllers sans authorize/middleware mais **routes commentées** dans `routes/api.php` → à protéger avant réactivation |
| F2 | `FacturePdfService::save` (`Storage::disk('public')`) | Code mort actuellement, mais écrirait des factures financières en public s'il est réutilisé |
| F3 | PDF (DomPDF `isRemoteEnabled => true`) | Chargement de ressources distantes possible (SSRF latent) ; les templates actuels n'embarquent que des données internes |
| F4 | `GET /notifications` invité | 500 observable (cf. E4) |

### ✅ Vérifications positives

- Prix des commandes recalculés côté serveur depuis la BDD.
- Espace privé commandes : `authorize('view', $commande)` + `CommandePolicy` vérifie `user_id`.
- Factures & bulletins admin : `auth + role:super-admin|admin`.
- `BulletinPaiePolicy` : propriété `enseignant_id` vérifiée.
- Cahiers de textes : `authorize` partout.
- Objectifs pédagogiques : `->can(...)` sur chaque route.
- Rapports mensuels : `->can(...)` + `role:admin` sur validate/reject.
- FactureCabinet : `authorize('payer'/'annuler')`.
- Notifications : contrôle de propriété (mais auth manquante).
- `AuthService::setRole` vérifie `hasRole($role)` → pas d'escalade par session.
- Connexion bloquée pour élèves inactifs.
- CSRF actif partout.
- `.env` non tracké.
- Liste publique bibliothèque filtrée par scope `public()` pour les invités.
- Contrôleurs admin derrière `role:admin|super-admin|gestionnaire`.

---

## 5. Audit routes

### 5.1 Inventaire des fichiers de routes

| Fichier | Registré dans `bootstrap/app.php` | Contenu principal |
|---|---|---|
| `web.php` | ✅ | Home, `/connexion`, demande-cours, bibliothèque/librairie publiques |
| `auth.php` | ✅ | Login/logout, sélection de rôle, dashboard, **resources Users (⚠️ non protégées)** |
| `pedagogie.php` | ✅ | Classes, Matières, TypeCours, Demandes, Contrats, CahiersTextes, Objectifs, Rapports, PDF |
| `notifications.php` | ✅ | `notifications` (⚠️ sans middleware `auth`) |
| `finance.php` | ✅ | Factures, FactureCabinet, BulletinsPaie, Paiements, Périodes, TypeAjustements |
| `bibliotheque.php` | ✅ | Public (voir/télécharger/commenter/noter/favoris) + admin + type-documents/periodes |
| `librairie.php` | ✅ | Produits publics, panier, commandes, confirmation/PDF (⚠️ IDOR), admin |
| `cms.php` | ✅ | FAQ (sections/questions) |
| `actualites.php` | ✅ | Actualités publiques + admin |
| `temoignages.php` | ✅ | Témoignages publics + admin |
| `api.php` | ✅ (`api`) | Contrats/CahiersTextes/Factures **commentés** |
| `console.php` | ✅ (`commands`) | — |

> Les fichiers `academique.php`, `scolarite.php`, `systeme.php` annoncés dans `docs/conception.md` **n'existent pas**.

### 5.2 Problèmes de sécurité des routes

- Resources `parents`, `eleves`, `enseignants` et `eleves/{eleve}/account` **sans middleware** (`routes/auth.php:33-54`) → voir [C1](#-faille-critique).
- Groupe `notifications` **sans middleware `auth`** → voir [E4](#-faille-élevée).
- `librairie.confirmation` et `librairie.pdf` sans protection → voir [C2](#-faille-critique).

### 5.3 Actions manquantes

- `eleves.destroy`, `parents.destroy`, `enseignants.destroy` : les vues de liste proposent potentiellement la suppression, mais les routes `DELETE` ne sont pas définies (ou non liées).
- `finance.bulletins-paie.create` / `.store` : non définies alors que des vues/lien de création existent.

### 5.4 Vue manquante

- `resources/views/pedagogie/cahiers-textes/edit.blade.php` **absente** → toute route de modification d'un cahier de texte pointerait vers une vue inexistante (HTTP 500).

### 5.5 Doublons de noms de route

- **`login`** défini 2 fois : `routes/auth.php:11` (`GET /login`) et `routes/web.php:10` (`GET /connexion`, `Route::view`). Le dernier enregistré l'emporte — `route('login')` peut résoudre vers une URL inattendue.
- **`finance.facture-cabinet.create`** : doublon potentiel à vérifier (une route dans `finance.php` et une référence dans les vues).

### 5.6 Casse des dossiers de vues

- Dossiers `pedagogie/Classes`, `pedagogie/Matieres`, `pedagogie/Users` en majuscules alors que le reste est en minuscules. Laravel charge les vues avec le chemin exact → **casse en production sur un système de fichiers sensible à la casse**.

### 5.7 Liens morts identifiés (vues publiques)

- `publicpages/sections/services.blade.php` : 4 cartes sur 6 pointent vers `href="#"` (« Voir les produits » → librairie, « Participer », « Découvrir », « En savoir plus »).

### 5.8 Rôle `gestionnaire`

- Le rôle `gestionnaire` existe dans le seeder mais n'est pas toujours inclus dans les accès dashboard (à harmoniser avec `admin|super-admin`).

---

## 6. Audit performance

### Problèmes relevés

| # | Localisation | Problème | Recommandation |
|---|---|---|---|
| P1 | Vues/Controllers de listes (admin notamment) | Risque N+1 sur certaines listes (relations accédées en boucle sans `with()`) | Réviser les listes paginées avec eager loading |
| P2 | `HomeController` | Chargement de compteurs (`count`) et des 10 derniers enseignants sur chaque appel | Mettre en cache (retourne déjà partiellement en cache selon module) |
| P3 | Vues Blade | Quelques accès relationnels dans des `@foreach` sans préchargement | `->with()` systématique sur les listes |
| P4 | Pagination | Pages publiques paginées (actualites, bibliotheque, librairie, temoignages) ✅ ; dashboards non paginés sur agrégats | Paginer / limiter les agrégats lourds |

### Points corrects

- `ActualiteService` : aucun N+1, `recentes()` en cache 1h, `reactionCounts()` en cache 5 min, `currentReaction()` en 1 requête ciblée, `recordView()` dédupliqué par session.
- Cache utilisé sur le classement des témoignages et les actualités.
- `TemoignageService::listeClassement()` paginé et fonctionnel (tests HTTP 200 sur `?page=0|1|2|999|-1`).

---

## 7. Audit scalabilité

| # | Constat | Impact | Recommandation |
|---|---|---|---|
| S1 | Génération PDF (dompdf) **synchrone** dans le cycle de requête (factures, bulletins, rapports, cahiers de textes, commandes) | Latence + blocage du worker ; échec = 500 | Passer en queue (job) avec notification au téléchargement |
| S2 | Emails (mailer `log` configuré) : envois synchrones | Ralentissement sous volume | Queues + `ShouldQueue` |
| S3 | **Aucun job** (`app/Jobs/` inexistant), `QUEUE_CONNECTION` database configurée mais inutilisée | Infra queue présente mais jamais exploité | Créer les jobs pour les traitements lourds |
| S4 | Agrégations financières potentiellement coûteuses (bulletins, facture cabinet) | Perf dégradée sur gros historiques | Index + agrégats SQL (pas de boucles PHP) |

---

## 8. Audit SEO

### État par page publique

| Page | `<title>` | meta description | canonical | OG/Twitter | JSON-LD | H1 |
|---|---|---|---|---|---|---|
| Accueil | ✅ | ✅ (partiel) | ❌ | ❌ | ❌ | ✅ |
| Bibliothèque (liste) | ✅ | ~ | ❌ | ❌ | ❌ | ✅ |
| Détail document | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Librairie produits | ✅ | ~ | ❌ | ❌ | ❌ | ✅ |
| Panier librairie | ✅ | ❌ | ❌ | ❌ | `WebPage` (l.201-209) ✅ | ✅ |
| Actualités (liste/show) | ✅ | ✅ (show) | ❌ | ❌ | ✅ (show, JSON-LD Article) | ✅ |
| Témoignages (liste/show) | ✅ | ✅ (show) | ❌ | ❌ | ✅ (show) | ✅ |
| FAQ | ✅ | ~ | ❌ | ❌ | ❌ | ✅ |
| Demande de cours | ✅ | ~ | ❌ | ❌ | ❌ | ✅ |

### Constats

- ✅ Bases correctes : slugs SEO (documents, actualités, produits), JSON-LD sur `actualite.show`, `temoignage.show` et `librairie-panier` (WebPage) — JSON encodé avec `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT`.
- ❌ Aucune **canonical** (`rel="canonical"`), aucun **Open Graph / Twitter Card** sur les pages publiques.
- ❌ **Sitemap absent** (aucune route ni fichier).
- ❌ `public/robots.txt` minimal (User-agent + Disallow) : aucune directive `Sitemap`, aucune règle utile.
- ❌ Pagination des pages de listes : ni `rel=prev/next`, ni `noindex` sur `page>1`.
- ⚠️ `publicpages/sections/services.blade.php` : 4 liens `href="#"`.

### Recommandations

1. Ajouter canonical + OG + Twitter Card sur toutes les pages publiques (réutiliser le pattern JSON-LD existant).
2. Créer un `routes/sitemap.xml` (ou fichier statique) listant home, bibliothèque, librairie, actualités, témoignages, FAQ, demande-cours.
3. Enrichir `robots.txt` (disallow admin, `Sitemap:`).
4. Gérer `noindex,follow` sur les pages de pagination > 1.

---

## 9. Audit UI/UX

| # | Localisation | Problème | Recommandation |
|---|---|---|---|
| U1 | `about.blade.php:26` | Faute : « une cabinet éducatif » → « un cabinet » | Corriger |
| U2 | `about.blade.php:23` | Titre de section en `<h3>` alors que services/zones/departments utilisent `<h2>` | Harmoniser hiérarchie Hn (h1 hero → h2 sections → h3 sous-blocs) |
| U3 | `services.blade.php` | 4 liens morts `href="#"` | Router ou masquer |
| U4 | Global | États vides inégaux : certaines listes affichent « Aucun… », d'autres rien | Uniformiser les états vides |
| U5 | Mobile | Tableaux larges / grids fixes à vérifier sur les pages admin | Responsive passif ; privilégier le pattern card sur mobile |
| U6 | Global | Suppressions : confirmations inégales (certains `@click`/`onsubmit`, d'autres non) | Confirmation systématique sur les actions destructrices |
| U7 | Formulaire demande-cours | Moins riche que le checkout librairie (`@error` + `old()` + spinner + toast) | S'inspirer de `librairie-panier.blade.php` (modèle de référence) |
| U8 | Accessibilité | `aria-label` manquants sur certains boutons icônes, alt d'images inégaux | Audit d'accessibilité léger |

### Points positifs

- Formulaire de checkout `librairie-panier.blade.php` : `@error` + `invalid-feedback` sur chaque champ, `old()`, spinner pendant l'envoi, toast JS (`LibrairieCart.toast`), réouverture automatique après erreur (`session('errors')`).
- Lien WhatsApp cohérent dans `zones.blade.php` (`wa.me/22665435793`, aligné avec le téléphone de la section contact).
- Messages flash affichés dans les layouts.

---

## 10. Tests & qualité

| Constat | Détail |
|---|---|
| Tests | 70 Feature Tests (voir § 13-16) couvrant : CRUD sécurisé Users (22), librairie (11), notifications (5), bibliothèque/PDF (18), routes/vues (6), intégrité BD/relations/indexes (6) + les 2 squelettes `ExampleTest` |
| Couverture réelle | Feature Tests sur les parcours critiques (sécurité, vues, permissions, intégrité) — verts sur base PostgreSQL dédiée `keduc_test` |
| Lint | `laravel/pint` présent (dev) mais pas de CI |
| Risque | Les suites doivent s'exécuter sur `keduc_test` (les migrations du projet ne passent pas sur sqlite `:memory:`) |

**Recommandation** : écrire au minimum des Feature Tests sur les parcours critiques : login/roles, demande de cours → contrat, facture, paiement, bibliothèque (visibilité privé/public), librairie (montant recalculé), témoignages (classement, réactions), et les 2 failles de sécurité critiques (C1, C2) pour verrouiller le correctif.

---

## 11. Dépendances

### Composer (prod)
`laravel/framework ^12`, `laravel/sanctum ^4.3`, `spatie/laravel-permission ^6.25`, `spatie/laravel-medialibrary ^11.23`, `barryvdh/laravel-dompdf ^3.1`, `laravel-lang/common ^6.8`, `laravel/tinker`.

### Composer (dev)
`pestphp` non présent ; `phpunit ^11.5`, `pint ^1.24`, `sail`, `pail`, `mockery`, `collision`, `faker`.

### NPM (dev)
`vite ^7`, `laravel-vite-plugin ^2`, `tailwindcss ^4`, `@tailwindcss/vite`, `axios`, `concurrently`.

### Inutilisés / à vérifier
- `laravel-lang/common` : utilité à confirmer (fichiers `lang/` présents).
- `laravel/sail` : environnement non Docker (XAMPP sous Windows).
- Tailwind 4 : les vues utilisent **Bootstrap** ; vérifier si Tailwind est réellement buildé/utile.

---

## 12. Plan de correction priorisé

### 🔴 Urgent (sécurité — à faire en premier)
1. **C1** — Protéger les resources `parents`/`eleves`/`enseignants` + `eleves/{eleve}/account` (`auth` + `role:admin|super-admin|gestionnaire`) et ajouter les `authorize()` dans les controllers.
2. **C2** — Protéger `librairie.confirmation` et `librairie.pdf` (session + `authorize('view', $commande)`).
3. **E4** — Ajouter `middleware('auth')` au groupe notifications.

### 🟠 Rapide
4. **E1** — Corriger `DocumentBibliothequePolicy` (retourner `false` pour les non-admin hors `is_public && publie`, sauf propriétaire).
5. **E2/E3** — Déplacer le stockage des PDF de rapports et des fichiers de documents sur un disque **privé** (`local`) + routes de streaming contrôlées ; supprimer à la révocation.
6. **R5.5** — Résoudre le doublon de nom de route `login` et `finance.facture-cabinet.create`.
7. **R5.4** — Créer la vue `pedagogie/cahiers-textes/edit.blade.php`.
8. **R5.6** — Normaliser la casse des dossiers `pedagogie/Classes|Matieres|Users` → minuscules.
9. **R5.8** — Ajouter `gestionnaire` aux accès dashboard cohérents.

### 🟡 À planifier
10. **M1** — Calcul serveur des frais de livraison.
11. **M2** — Supprimer le mot de passe par défaut `password123`.
12. **M3** — `throttle` sur le login.
13. **M5** — Retirer les compteurs métier de la home publique (ou les rendre configurables).
14. **M4 / F3** — `APP_DEBUG=false` en prod ; `isRemoteEnabled => false` dans DomPDF.
15. **F1/F2** — Protéger les controllers API avant toute réactivation.

### 🔵 Qualité & perf
16. Créer les Feature Tests de sécurité (C1, C2) et des parcours critiques.
17. Corriger le N+1 des listes admin ; cacher les compteurs de la home.
18. Queues pour PDF/emails (S1, S2, S3).
19. SEO : canonical + OG/Twitter + sitemap + robots.txt.
20. UI : liens morts services, faute « une cabinet », états vides, casse des dossiers.

---

## 13. Corrections effectuées — Étape 1 (sécurité)

> État : **faites** et **vérifiées** (Feature Tests verts). Voir § 4 pour le détail des vulnérabilités.

### ✅ C1 — CRUD `parents` / `eleves` / `enseignants` exposé
- **`routes/auth.php`** : les resources `parents`, `eleves` (dont `eleves/{eleve}/account`, `eleves.account.form`, `eleves.account.activate`) et `enseignants` sont désormais dans un groupe `Route::middleware(['auth', 'role:admin|super-admin'])`. La ressource `enseignants` avait déjà ce groupe — ajout d'une protection identique cohérente pour `parents`/`eleves`.
- **`EleveController`** : ajout des `authorize()` (via `ElevePolicy`) : `viewAny`, `create`, `store`, `show`, `edit`, `update`, `accountForm`, `activateAccount`.
- Non modifiés : `ParentController`, `EnseignantController` (protégés par le middleware de groupe, cohérent avec les controllers admin existants).

### ✅ C2 — IDOR confirmation / PDF de commande
- Nouvelles routes `librairie.commandes.{id}.{token}.confirmation` et `.../pdf` (jeton requis, 64 caractères aléatoires).
- **Migration** `2026_08_13_000001_add_token_to_commandes_table.php` : colonne `token` + index + rétro-remplissage des commandes existantes.
- **`LibrairieService::passCommande`** : génération du jeton à la création.
- **`PublicLibrairieController`** : `authorizeCommandeAccess()` — `CommandePolicy::view()` si connecté, sinon `hash_equals` du jeton (404 si mismatch).
- Redirection post-commande avec jeton ; lien PDF de la confirmation mis à jour.

### ✅ E4 — Notifications sans authentification
- **`routes/notifications.php`** : `->middleware('auth')` ajouté au groupe.
- Controller inchangé (propriété `user_id` déjà vérifiée par `abort_if(..., 403)`).

### 🧪 Vérifications
- `php -l` OK sur les 8 fichiers modifiés.
- `route:list --json` : middlewares `auth` + `role:admin|super-admin` bien appliqués.
- **38 Feature Tests ajoutés et verts** (base de test PostgreSQL dédiée `keduc_test`) :
  - `tests/Feature/UsersCrudSecurityTest.php` (22) — visiteurs → redirection login, non-admin → 403, admin → 200/302.
  - `tests/Feature/LibrairieConfirmationSecurityTest.php` (11) — sans jeton → 404, mauvais jeton → 404, bon jeton → 200, commande d'autrui → 403, propriétaire/admin → 200, parcours invité complet.
  - `tests/Feature/NotificationsSecurityTest.php` (5) — visiteur → login, notification d'autrui → 403, propriétaire → OK.
- **Note infra** : les migrations du projet ne passent pas sur sqlite `:memory:` (ex. `drop column` de `demande_cours`). La suite s'exécute sur une base PostgreSQL dédiée :
  ```sql
  CREATE DATABASE keduc_test;
  ```
  puis `DB_CONNECTION=pgsql DB_DATABASE=keduc_test ./vendor/bin/phpunit tests/Feature/*SecurityTest.php`.

### ⚠️ Problèmes hors périmètre signalés (à traiter en Étape 2+)
- `ExampleTest::test_the_application_returns_a_successful_response` échoue (accès `/` sans données seedées) — pré-existant.
- Pas de `throttle` sur `login` (M3), mot de passe par défaut `password123` (M2), PDF stockés sur disque public (E2/E3), `DocumentBibliothequePolicy` permissive (E1), routes API commentées (F1/F2) — voir § 12.

---

## 14. Corrections effectuées — Étape 2 (Partie 02 : Bibliothèque numérique & PDF)

> État : **faites** et **vérifiées** (Feature Tests verts). Périmètre strict : Policy bibliothèque, stockage privé, téléchargement contrôlé, PDF des rapports mensuels.

### ✅ E1 — `DocumentBibliothequePolicy` permissive
- **`app/Policies/DocumentBibliothequePolicy.php`** : `view()` est désormais stricte — accès si **publié et public** (`is_public && statut === 'publie'`), **ou propriétaire**, **ou** permission `bibliotheque.moderate` (admin) ; sinon refus. `download()`, `comment()`, `favorite()`, `rate()` délèguent à `view()`.
- **`BibliothequeController`** : `authorize('favorite')` ajouté dans `toggleFavori()` et `toggleFavoriAjax()`.

### ✅ E3 — Fichiers de documents sur disque public
- **`config/filesystems.php`** : nouveau disque **`private_media`** (root `storage/app/private/media`, `visibility => private`).
- **`DocumentBibliotheque::registerMediaCollections()`** : collection `document` → `singleFile()->useDisk('private_media')` (les nouveaux uploads vont sur le disque privé).
- **`getFichierUrlAttribute`** : retourne `null` si le média n'est pas sur le disque `public` (plus aucune URL directe `/storage/...` générée pour les fichiers privés).
- **`resources/views/bibliotheque/admin-show.blade.php`** : le lien remplace `$media->getUrl()` par `route('bibliothequepub.view', ...)` (flux contrôlé par la Policy).

### ✅ E2 — PDF des rapports mensuels sauvegardés en public
- **`RapportMensuelPdfService::save()`** : écrit désormais sur `Storage::disk('local')` (privé) au lieu de `public`. Les routes `stream`/`download` étaient déjà protégées (`auth` + `can('view','rapport')`) et passent par le flux mémoire de DomPDF — **aucune modification de route nécessaire**.

### 🧪 Tests ajoutés (18) — verts sur `keduc_test`
- `tests/Feature/BibliothequeSecurityTest.php` (12) — invité (public publié OK, privé/brouillon → 403), connecté (privé publié OK, brouillon d'autrui → 403, commentaire/favori brouillon d'autrui → 403), auteur brouillon OK, admin tout OK, fichiers stockés sur `private_media` uniquement, `/storage/...` ne sert pas les fichiers privés.
- `tests/Feature/RapportMensuelPdfSecurityTest.php` (6) — invité → redirection login, utilisateur non autorisé → 403, propriétaire/admin → 200, Policy (Gate), `save()` stocke sur disque `local` (privé).

### 🧪 Vérifications
- `php -l` OK sur tous les fichiers modifiés.
- `config:show filesystems` : disque `private_media` bien déclaré (`local`, `storage/app/private/media`, `private`).
- `route:list` : routes `bibliothequepub.view/download` inchangées (flux `getPath()` + `authorize('view')` existant).
- **Régression Partie 01** : les 38 Feature Tests précédents restent verts.
- **Suite complète** : 58 tests — seule failure `ExampleTest` (accès `/` sans seed, pré-existante, hors périmètre).

### ⚠️ Note migration (NON destructive — à documenter)
Les documents déjà uploadés (3 fichiers sous `storage/app/public/{media_id}/...`) restent sur le disque `public` : ils ne sont plus adressables via `getUrl()` (nul), mais pour être totalement hors d'atteinte, les copier vers `storage/app/private/media/{media_id}/...` et mettre à jour la colonne `disk` de la table `media`. Aucune migration destructive n'a été exécutée.

---

## 15. Corrections effectuées — Étape 3 (Partie 03 : audit routes, vues manquantes, route `login`)

> État : **faites** et **vérifiées** (Feature Tests verts sur `keduc_test`, suite complète 64 tests — seule failure `ExampleTest` pré-existante). Périmètre strict : doublon de route, vues référencées par les controllers, protection des routes critiques.

### 🔎 Constats de l'audit
- **280 routes** analysées via `artisan route:list --json`. **Un seul doublon** : le nom de route `login` enregistré deux fois — `routes/web.php` (`GET /connexion` → vue statique `auth.login`) et `routes/auth.php` (`GET /login` → `AuthController::showLogin`). `web.php` étant chargé avant `auth.php`, `route('login')` résolvait déjà `/login` ; la route `/connexion` était **morte** (aucune vue ne la référençait).
- **5 vues référencées par des controllers mais absentes du disque** (vérification case-sensitive, comme sur Linux) :
  1. `pedagogie.cahiers-textes.edit` — **liée dans l'UI** (index + show) → 500 réel → **vue créée**.
  2. `documents.rapports` — **liée dans l'UI** (carte « Rapports mensuels » de `documents/index`) → 500 réel → **vue créée**.
  3. `pedagogie.classes.show` / `pedagogie.matieres.show` — routes `show` jamais liées dans les vues → **documentées, vue non créée** (pas de vue inutile).
  4. `pdf.cahiers-textes.form` — lien **commenté** dans `cahiers-textes/index.blade.php` (ligne 34), route `cahiers-textes.pdf.create` inactive → **documentée, vue non créée**.

### ✅ Corrections
- **`routes/web.php`** : suppression de `Route::view('/connexion', 'auth.login')->name('login')`. Le nom `login` est désormais unique → `GET /login`. Aucune vue n'utilisait `/connexion`.
- **`resources/views/pedagogie/cahiers-textes/edit.blade.php`** (créée) : réplique de `create.blade.php` (layout `panel.layouts.app`, page-heading, formulaire PUT vers `cahiers-textes.update`, champs `heure_debut`/`heure_fin`/`contenu_cours`/`objectifs_atteints`/`observations` pré-remplis via `old(..., $cahier->...)`, sidebar Matière/Élève/Date, calcul de durée). Cohérent avec `UpdateCahierTexteRequest` (affectation et date non modifiables — seuls les champs valides du service `update()` sont soumis).
- **`resources/views/documents/rapports.blade.php`** (créée) : réplique de `documents/cahiers.blade.php` (liste des PDF collection `rapport_mensuel_pdf` + téléchargement).

### 🧪 Tests ajoutés (6) — verts sur `keduc_test`
- `tests/Feature/RoutesSecurityTest.php` : route `login` unique et pointant sur `/login`, `/connexion` → 404, **toutes les vues référencées par les controllers existent** (avec liste blanche des 3 vues documentées), vue `edit` d'un cahier de texte rendue (200), vue `documents.rapports` rendue (200), invité redirigé vers `login` sur `documents.rapports`, `documents.cahiers-textes`, `cahiers-textes.edit`.

### 🧪 Vérifications
- `php -l` OK sur les fichiers modifiés.
- `route:list` : plus aucun doublon de nom de route.
- **Régression Parties 01/02** : verts.
- **Suite complète** : **64 tests, 114 assertions** — seule failure `ExampleTest` (accès `/` sans seed, pré-existante, hors périmètre).
- `phpunit.xml` restauré en sqlite (`:memory:`) après les runs PostgreSQL sur `keduc_test`.

---

## 16. Corrections effectuées — Étape 4 (Partie 04 : BD, Models, relations, indexes)

> État : **faites** et **vérifiées** (Feature Tests verts sur `keduc_test`, suite complète **70 tests / 137 assertions** — seule failure `ExampleTest` pré-existante). Périmètre strict : cohérence BD ↔ Models, relations Eloquent, indexes et contraintes manquants — **sans toucher au `DatabaseSeeder`**.

### 🔎 Constats de l'audit

- **Inventaire** : 53 Models dans `app/Models/`, 62 tables dans `database/migrations/`, 0 factory, 15 seeders (dont `RolePermissionSeeder` idempotent — rôles `super-admin`/`admin`/`parent`/`enseignant`/`eleve`/`gestionnaire` et permissions).
- **`$fillable`** : les 53 Models analysés ont tous leurs champs présents dans les migrations — aucun problème.
- **`$casts`** (array + méthode `casts()`) : tous les champs castés existent en base — aucun cast fantôme.
- **SoftDeletes** : les 11 Models avec le trait correspondent aux tables possédant `deleted_at` ; les tables financières/pédagogiques n'ont pas (à juste titre) de soft delete — aucune incohérence.
- **Enums** : vérifiés (`TemoignageStatut`, `ActualiteStatut`, `ActualiteReaction`, etc.) — valeurs et constantes cohérentes.
- **Intégrité référentielle des données réelles** : script d'audit des orphelins sur la base PostgreSQL `keduc` (58 FK vérifiées) → **0 orphelin**.
- **Doublons potentiels** : 0 doublon dans les 6 tables pivots/financières candidates aux contraintes UNIQUE.
- **Indexes réels PostgreSQL** : vérifiés via `Schema::getIndexes()` sur la base réelle. Plusieurs FK **sans index** (PostgreSQL ne crée pas d'index automatique pour les FK) sur des tables interrogées par requêtes réelles.

### 🔴 3 anomalies de relations Eloquent corrigées

1. **`ContratCours::matiere()`** — FK implicite `matiere_id` **absente** de `contrat_cours` (migration `2026_06_04_163934` : `eleve_id`, `type_cours_id`, …). Relation morte (aucun usage dans app/, routes/, views/) → **méthode supprimée** de `app/Models/ContratCours.php`.
2. **`DemandeCours::matiere()`** — FK `matiere_id` **absente** de `demande_cours`. Relation morte → **méthode supprimée** de `app/Models/DemandeCours.php` (la vraie relation `matieres()` via le pivot `demande_cours_matieres` est conservée).
3. **`EvaluationCours::enseignant()`** — FK implicite `enseignant_profil_id` **absente** de `evaluation_cours` (la colonne réelle est `enseignant_id`, FK `constrained('enseignant_profils')`). → **corrigée** : `belongsTo(EnseignantProfil::class, 'enseignant_id')` dans `app/Models/EvaluationCours.php`.

### 🟢 Indexes manquants ajoutés (migration `2026_08_13_210735_add_integrity_indexes_and_unique_constraints`)

Chaque index est justifié par une requête réelle (relation hasMany/belongsTo ou `where()` dans les Services/Controllers) :

| Table | Index ajoutés |
|---|---|
| `factures` | `idx_factures_contrat_cours_id`, `idx_factures_parent_id`, `idx_factures_eleve_id`, `idx_factures_periode_id` (recherches `FacturationService` par contrat/période) |
| `rapport_mensuel_enseignants` | `idx_rm_contrat_cours_id`, `idx_rm_enseignant_id`, `idx_rm_periode_id` (`RapportMensuelService`/`RapportMensuelCalculator` par contrat+enseignant+periode) |
| `paiement_cabinets` | `idx_paiement_cabinet_facture` (`FactureCabinet::paiements()` hasMany) |
| `ligne_facture_cabinets` | `idx_lfc_facture_cabinet` (`FactureCabinet::lignes()` hasMany) |
| `document_bibliotheques` | `idx_doc_biblio_user`, `idx_doc_biblio_type`, `idx_doc_biblio_classe`, `idx_doc_biblio_matiere`, `idx_doc_biblio_periode` (filtres `DocumentBibliothequeService` par type/classe/matière/période/utilisateur) |
| `document_commentaires` | `idx_doc_commentaires_document` |
| `document_signalements` | `idx_doc_signalements_document` |

### 🟢 Contraintes UNIQUE anti-doublon ajoutées (0 doublon existant — vérifié avant application)

| Table | Contrainte |
|---|---|
| `ligne_factures` | `uniq_ligne_facture_affectation` (facture_id, affectation_enseignant_id) — une ligne par affectation par facture |
| `bulletin_paie_lignes` | `uniq_bpl_bulletin_affectation` (bulletin_paie_id, affectation_enseignant_id) |
| `demande_cours_matieres` | `uniq_dcm_demande_matiere` (demande_cours_id, matiere_id) |
| `enseignant_matiere` | `uniq_em_enseignant_matiere` (enseignant_profil_id, matiere_id) |

> `document_tags` possédait déjà `document_tags_document_bibliotheque_id_tag_id_unique`. Les migrations d'index/contraintes sont **réversibles** (rollback testé).

### 🧪 Tests ajoutés (6) — verts sur `keduc_test`

- `tests/Feature/IntegriteDonneesTest.php` :
  - relations mortes `ContratCours::matiere()` / `DemandeCours::matiere()` supprimées (assert `method_exists`),
  - relation `DemandeCours::matieres()` (pivot) conservée,
  - `EvaluationCours::enseignant()` → `getForeignKeyName() === 'enseignant_id'`,
  - contraintes UNIQUE et indexes présents en base (vérification `Schema::getIndexes()`, PostgreSQL uniquement),
  - doublon sur `demande_cours_matieres` **bloqué** par la contrainte (insertion en doublon → `QueryException`).

### 🧪 Vérifications

- `php -l` OK sur les 3 Models modifiés ; migration appliquée, vérifiée (`Schema::getIndexes`) puis **rollback/remigrate** OK sur `keduc_test`.
- **Régression Parties 01/02/03** : verts.
- **Suite complète** : **70 tests, 137 assertions** — seule failure `ExampleTest` (accès `/` sans seed, pré-existante, hors périmètre).
- `phpunit.xml` restauré en sqlite (`:memory:`) après les runs PostgreSQL sur `keduc_test`.

### ⚠️ Limites documentées
- Pas de client `psql` ni d'extension PHP `intl` : la vérification des indexes a été faite via `Schema::getIndexes()` (Laravel) sur la base réelle `keduc` — fiable, aucune modification effectuée « au hasard ».
- `EvaluationCours` reste un module non branché dans l'interface (aucun controller/route) — l'anomalie de FK est corrigée mais le module n'est pas activé.

## 17. Corrections effectuées — Étape 5 (Partie 05 : audit performance)

> État : **faites** et **vérifiées** (7 Feature Tests dédiés verts sur `keduc_test`, suite complète **77 tests / 152 assertions** — seule failure `ExampleTest` pré-existante). Méthode : cartographie (48 Controllers, 38 Services, 179 vues Blade, 0 repository, 9 scopes) puis audit N+1 / requêtes répétées / pagination / agrégations / traitements lourds. Aucune nouvelle dépendance, aucun refactor massif, aucune suppression de contrôle d'accès, `DatabaseSeeder` intact.

### 🔴 Requêtes répétées corrigées

| # | Problème | Localisation | Cause | Impact | Correction | Validation |
|---|---|---|---|---|---|---|
| 1 | Double pagination | `ClasseController::index()` (ligne 21 + 26) | 1er `paginate()` exécuté puis résultat jeté | +1 requête SQL avec COUNT à chaque affichage de la liste des classes | Ligne inutile **supprimée** | `php -l` + suite complète verte |
| 2 | Notifications dupliquées A×A | `DemandeCoursService::create()` + `NotificationDispatcher::courseRequestCreated()` | La boucle sur les admins appelait `courseRequestCreated()` qui re-requêtait et re-notifiait **tous** les admins à chaque itération → A appels × A admins | A² requêtes `User::role('admin')` + A² notifications identiques créées | Un **seul** appel `$notifier->courseRequestCreated()` (qui notifie déjà tous les admins) | Test dédié : 2 admins → 2 notifications exactement |
| 3 | Composer global | `AppServiceProvider::boot()` — `View::composer('*', …)` | Composer appliqué à **toutes** les vues (dont publiques, PDF, mails) alors que seules `panel/partials/navbar.blade.php` utilise `notificationsMenu`/`notificationsUnread` | 2 requêtes notifications (SELECT + COUNT) par rendu de vue, même hors panel | Composer restreint à `panel.*` (le layout `panel.layouts.app` inclut la navbar ; les vues enfant n'utilisent pas ces variables) | Vérification : `grep -rl notificationsMenu/notificationsUnread` → uniquement navbar |

### 🔴 N+1 corrigés (eager loading)

| # | Problème | Localisation | Cause | Impact | Correction | Validation |
|---|---|---|---|---|---|---|
| 4 | N+1 `user` commandes | `LibrairieService::paginateCommandes()` | `paginate(15)` sans `with('user')` alors que la vue admin affiche `$commande->user` (« Compte lié ») | 1 requête users par commande (15/page) | `->with(['user'])` ajouté | Test dédié : `relationLoaded('user')` vrai sur toutes les commandes |
| 5 | N+1 `media` top produits | `LibrairieService::getDashboardStats()` | `topProduits` chargé via `with('produit')` sans media alors que la vue dashboard affiche `$produit->image_url` (`getFirstMediaUrl`) | 1 requête media par produit (max 5) | `->with('produit.media')` | Test dédié : `relationLoaded('media')` vrai |
| 6 | N+1 `media` lignes commande | `LibrairieService::getCommande()` | `lignes.produit` sans `media` ; vues `commandes/show` et `mes-commandes/show` affichent `image_url` | 1 requête media par ligne | `->with(['lignes.produit.media', 'user'])` | `php -l` |
| 7 | N+1 montant payé factures cabinet | `FactureCabinetController::index()` + `FactureCabinet::getMontantPayeAttribute()` | Accessor exécute toujours `paiements()->where('statut','valide')->sum()` ; l'index (15 factures) accède `montant_paye`/`montant_restant` | 2 requêtes SUM par facture (30/page) | `withSum(['paiements as montant_paye' => …], 'montant_paye')` sur l'index ; accessor priorise la collection `paiements` chargée puis l'attribut `withSum` puis la requête (fallback conservé) | Tests dédiés : 0 requête SQL lors des accès (index et show), fallback toujours correct |
| 8 | N+1 élève dans bulletins | `BulletinPaieCalculationService::calculerLignes()` | `with('contratCours.eleve')` sans `eleve.user` ; la boucle accède `eleve?->user?->prenom/nom` | 1 requête users par rapport mensuel | `'contratCours.eleve.user'` ajouté | `php -l` + suite verte |
| 9 | N+1 enseignant dans rapports | `RapportMensuelWebController::index()` | `affectations` chargées avec `matiere` uniquement ; la vue accède `$affectation?->enseignant?->user` | 1-2 requêtes par contrat affiché | `->with(['matiere', 'enseignant.user'])` dans la closure `affectations` | `php -l` + suite verte |
| 10 | N+1 media bibliothèque | `DocumentBibliothequeService` (`paginatePublic`, `paginateForUser`, `paginateAdmin`, `getFavoris`, `getDocumentsRecents/Populaires/MieuxNotes/PlusTelecharges/Similaires`) | Les listes ne chargeaient pas `media` alors que les vues (`index`, `favoris`, `document-card`, publiques) appellent `getFirstMedia('document')` | 1 requête media par document affiché | `'media'` ajouté aux `with([…])` de toutes les listes | Test dédié : `relationLoaded('media')` vrai (paginateForUser) |
| 11 | N+1 compte documents par type | `TypeDocumentController::index()` | `paginate(15)` sans `withCount('documents')` ; la vue utilise `$type->documents_count ?? $type->documents()->count()` | 1 COUNT par type (15/page) | `withCount('documents')` ajouté (le `??` reste en fallback défensif) | `php -l` |
| 12 | N+1 matière cahier de textes | `CahierTexteService::getAffectationsForEleve()` | `with('contrat.eleve.user')` sans `matiere` ; la vue `cahiers-textes/create` affiche `$affectation->matiere->nom` | 1 requête matieres par affectation | `with(['contrat.eleve.user', 'matiere'])` | `php -l` + suite verte |

### 🟡 Requêtes déplacées hors Blade

| # | Problème | Localisation | Cause | Impact | Correction |
|---|---|---|---|---|---|
| 13 | Requête SQL dans Blade | `resources/views/profil/edit-enseignant.blade.php` (ligne ~130) | `$allMatieres = \App\Models\Matiere::where('actif', true)->orderBy('nom')->get();` exécutée à chaque rendu | Logique métier dans la vue, difficile à auditer | Déplacée dans `ProfilController::edit()` (rôle `enseignant` uniquement), passée via `compact` ; la vue se contente de `$allMatieres = $allMatieres ?? []` |

### 🟢 Points audités, documentés sans correction (justifiés)

- **`HomeController::index()`** — 5 `count()` indépendants (enseignants, élèves, users, contrats, familles) sur des tables distinctes, page publique peu volumineuse → acceptable, aucune correction.
- **`TemoignageService::classement()`** — 2 SELECT complets + fusion PHP, mais résultats **cachés 3600 s** (`Cache::remember`) → aucun gain à corriger.
- **`PaiementEnseignantService::generate()`** — 1 `SUM(duree_heures)` par affectation : le nombre d'affectations par contrat est très faible → volumétrie négligeable.
- **`BulletinPaieGenerationService::preview()/generer()`** — 1 requête rapports par enseignant dans `calculerLignes()` : corrigeable uniquement par un refactor transverse risqué (mutations en transaction + génération de numéros) → documenté, à traiter si le nombre d'enseignants devient important.
- **Accessors `BulletinPaie::total_primes/total_retenues/montant_net_final`** — aucun N+1 actif : les vues web utilisent la colonne `montant_net` ; seuls le PDF service (1 bulletin à la fois) les calcule via les ajustements chargés. Accessors conservés tels quels.
- **`User::photo_profil_url`** — 1 requête media par rendu de la navbar publique connectée → impact marginal, non corrigé.
- **`DocumentBibliothequeService::getAuteurStats()` (11 requêtes) / `getAdminStats()` (5 counts)** — dashboards à basse fréquence → conservés.
- **Listes `<select>` (classes/factures/élèves en création)** — pagination **non pertinente** pour des champs de formulaire, volontairement absente.
- **`FactureCabinetController::payer()`** — 1 requête SUM par page détail → `$facture->load('paiements')` ajouté (l'accessor utilise désormais la collection).

### 🟠 Traitements lourds documentés (non déplacés en file d'attente — pas d'infrastructure queue en production)

- **`NotificationDispatcher::actualitePublished()`** — emails synchrones dans une boucle (risque de latence si un destinataire ralentit).
- **PDF synchrones** : 5 services DomPDF (`FacturePdfService`, `CommandePdfService`, `BulletinPaiePdfService`, `RapportMensuelPdfService`, `CahierTextePdfService`) générés au fil de l'eau.
- **Numéros de facture/bulletin via `count()+1`** (`FactureCabinet::genererNumero()`, `BulletinPaieGenerationService::genererNumero()`) — risque de course conditionnelle sous forte concurrence (le `+1` n'est pas atomique). Accepté pour l'échelle actuelle, à revoir si volume élevé.

### 🧪 Tests ajoutés (7) — verts sur `keduc_test`

`tests/Feature/PerformanceOptimizationsTest.php` :
- FactureCabinet : `withSum` sur l'index → **0 requête SQL** lors des accès `montant_paye`/`montant_restant` (via `DB::listen`) ;
- FactureCabinet : accessor utilise la collection `paiements` chargée (0 requête) et conserve le fallback requête ;
- `paginateCommandes()` → toutes les commandes ont `relationLoaded('user')` ;
- `getDashboardStats()` → les top produits ont `relationLoaded('media')` ;
- `paginateForUser()` → les documents ont `relationLoaded('media')` ;
- `DemandeCoursService::create()` → chaque admin reçoit **exactement 1 notification** (anti doublon A×A).

### 🧪 Vérifications

- `php -l` OK sur les 12 fichiers PHP modifiés (2 Controllers Pédagogie, LibrairieService, FactureCabinetController+Model, BulletinPaieCalculationService, RapportMensuelWebController, DocumentBibliothequeService, TypeDocumentController, CahierTexteService, ProfilController, DemandeCoursService, AppServiceProvider).
- **Régression Parties 01-04** : verts (aucun test de sécurité cassé).
- **Suite complète** : **77 tests, 152 assertions** — seule failure `ExampleTest` (accès `/` sans seed, pré-existante, hors périmètre).
- `phpunit.xml` restauré en sqlite (`:memory:`) après les runs PostgreSQL sur `keduc_test` ; fichier `phpunit.keduc_test.xml` temporaire supprimé.

### ⚠️ Incident opérationnel à connaître

Lors de la préparation du run PostgreSQL, la commande `artisan migrate:fresh --seed --env=testing` a été exécutée **sans fichier `.env.testing`** : Laravel a donc utilisé `.env` et a réinitialisé la base de développement **`keduc`** (et non `keduc_test`). **Aucune sauvegarde `.sql` n'était disponible** dans le projet. La base `keduc` contient désormais uniquement les données des seeders (18 users, 6 élèves, 0 commande/contrat). Pour la suite : toujours passer l'environnement de test explicitement (variable `DB_DATABASE=keduc_test` en préfixe de commande) ou vérifier la présence de `.env.testing` avant tout `migrate:fresh`.

---

## 18. Corrections effectuées — Étape 6 (Partie 06 : performance & scalabilité — emails/queue, index SQL, PDF, agrégations)

> État : **faites** et **vérifiées** (6 Feature Tests dédiés verts sur `keduc_test`, suite complète **83 tests / 167 assertions** — seule failure `ExampleTest` pré-existante). Méthode : cartographie de l'infrastructure asynchrone (queue/cache) puis audit parallèle à 4 axes (Controllers/Services N+1, Blade, PDF/emails/queue/cache, index SQL). Aucune nouvelle dépendance, aucun refactor massif, aucun contrôle d'accès supprimé, `DatabaseSeeder` intact. **La migration d'index nécessite que l'utilisateur exécute `php artisan migrate`** (règle absolue : aucune commande impactant la base n'est exécutée par l'agent).

### 🔴 Critiques corrigés

| # | Problème | Localisation | Cause | Impact | Correction | Validation |
|---|---|---|---|---|---|---|
| 1 | Emails d'actualité **synchrones en boucle** | `NotificationDispatcher::actualitePublished()` (boucle `foreach` sur les destinataires) | `Mail::to(...)->send()` pendant la requête HTTP de publication | N envois SMTP synchrones (latence, risque de timeout/500) + le Mailable `ActualitePublishedMail` est déjà `Queueable`/`SerializesModels` mais jamais en file | `->send()` → **`->queue()`** (file `jobs` = `QUEUE_CONNECTION=database`, table `jobs` déjà migrée). Le Mailable est poussé en file et traité par le worker | Test dédié : `Mail::assertQueued(ActualitePublishedMail::class, 1)` + `assertNotSent` |
| 2 | **Fuite de stockage** : un nouveau média PDF ajouté à **chaque** téléchargement | `CahierTextePdfService::buildPdf()` (bloc `$model->addMedia(...)`) | La collection `cahier_texte_pdf` n'est **jamais relue** par l'UI (aucun `getMedia('cahier_texte_pdf')` dans les vues) mais chaque download insère une ligne `media` + un fichier disque | Stockage/BD croissent indéfiniment avec le nombre de téléchargements | Un seul PDF par modèle : le média n'est ajouté que si aucun n'existe déjà pour la collection (requête **fraîche** via `media()` — la relation chargée en mémoire est obsolète après `addMedia`) | Test dédié : 2 × `downloadSingle()` → **1** média seulement |
| 3 | **Crash** de l'export PDF historique | `CahierTextePdfService::downloadHistory()` + `buildPdf()` | `$eleve->addMedia()` appelé sur `Eleve` qui **n'implémente pas `HasMedia`** | Erreur fatale (`addMedia() undefined`) à chaque tentative d'export historique | `downloadHistory()` passe `null` en modèle (aucun attach) + `$eleve->loadMissing('user')` ; `buildPdf()` ne tente l'attach que si le modèle expose `media()` | Test dédié : retourne un `Response` `application/pdf`, aucun média créé |
| 4 | **Index manquants** (FK non indexées sur PostgreSQL) | `notifications(user_id)`, `commandes(user_id)`, `temoignages(user_id)`, `temoignage_commentaires(temoignage_id)`, `document_commentaires(document_bibliotheque_id)`, `document_notes(document_bibliotheque_id)` | Les FK `foreignId()->constrained()` ne créent pas d'index en PostgreSQL | `notifications(user_id)` surtout : composer panel + `NotificationController` (paginate/unreadCount/markAllAsRead) exécutés à **chaque page** du panel | Migration dédiée `2026_08_15_000001_add_performance_indexes.php` (6 index) | Test dédié : 6 index présents via `Schema::getIndexes` |

### 🟡 Moyens corrigés (eager loading)

| # | Problème | Localisation | Cause | Impact | Correction |
|---|---|---|---|---|---|
| 5 | N+1 `media` produits commandes | `LibrairieController::show()`/`pdf()` + `AdminLibrairieController::showCommande()` | `load('lignes.produit')` sans `media` ; les vues `commandes/show` + `mes-commandes/show` affichent `$ligne->produit->image_url` (`getFirstMediaUrl`) | 1 requête `media` par ligne (`loadMissing('media')` de Spatie) | `load('lignes.produit.media')` (3 endroits) |
| 6 | N+1 `media` détail document | `DocumentBibliothequeService::getById()/getBySlug()` + `AdminBibliothequeController::show()` | Les pages de détail chargent commentaires/notes mais pas `media` ; les vues `show`/`admin-show` appellent `getFirstMedia('document')` | 1 requête `media` par page | `'media'` ajouté aux `with([…])`/`load([…])` |

### 🟢 Faibles corrigés

| # | Problème | Localisation | Correction |
|---|---|---|---|
| 7 | Clé de cache `recentes` sans le `$limit` | `ActualiteService::recentes()` | Clé `CACHE_RECENTES . '.' . $limit` (protège contre la collision si plusieurs limites utilisées) |
| 8 | 5 `COUNT(*)` indépendants sur la page d'accueil | `HomeController::index()` | **1 seule requête** `DB::selectOne` à 5 sous-requêtes scalaires (aucune ligne `FROM` → correct même tables vides) |
| 9 | 3 requêtes `TypeCommission::where()->first()` | `FactureCabinetService::calculerPreview()` | **1 requête** `whereIn(['Cours','Inscription','Vente'])` + `keyBy()` (calculs inchangés) |
| 10 | Accès non nul-safe à `$ligne->produit->image_url` | `commandes/show.blade.php` + `mes-commandes/show.blade.php` | `$ligne->produit?->image_url` (Produit est `SoftDeletes` → erreur fatale si produit supprimé) |

### 🟠 Points audités, documentés sans correction (justifiés)

- **N+1 rapports mensuels** (`BulletinPaieGenerationService::preview()/generer()` : ~2N+3 requêtes, 1 SELECT + 1 test `exists`/`count` par enseignant) — corrigeable uniquement par un refactor transverse risqué (logique financière : mutations en transaction + génération de numéros). **Conservé volontairement**, à traiter si le nombre d'enseignants par période devient important.
- **PDF synchrones restants** (`FacturePdfService`, `CommandePdfService`, `BulletinPaiePdfService`, `RapportMensuelPdfService`) — volumes par PDF faibles (1 facture/bulletin), historique cahier corrigé ci-dessus. Basculer en queue n'apporterait rien sans worker en production.
- **`DocumentController::index()`** (1 requête `media` globale + 3 comptages en mémoire) — déjà efficace, aucun correctif.
- **Code mort PDF historique** : `HistoriquePedagogiquePdfController` (méthode `downloadForEleve()` inexistante, **aucune route**) et vue `pdf/cahiers-textes.form` **manquante** (la route `cahiers-textes.pdf.create` → `create()` → vue absente ; le lien est **commenté** dans `cahiers-textes/index.blade.php`). Signalé, non supprimé (hors périmètre de correction, la route `store` restait cassée avant la correction #3).
- **Bug financier signalé (non corrigé, hors périmètre perf)** : `FactureCabinetService::calculerPreview()` ligne 59 — `$montantVentes = round($totalVentes * $tauxVente * 100 / 100 * 100)` = `montant * 100` (surfacturation de la commission « Vente » par 100). À valider/faire corriger par l'utilisateur dans une passe dédiée à la logique métier.
- **Cache des rôles admins** (`NotificationDispatcher`) — jamais invalidé : listes re-requêtées à chaque envoi (actions d'écriture peu fréquentes), aucune correction (invalidation difficile à garantir).
- **`User::photo_profil_url`** (navbar publique) — 1 requête `media` par page connectée : impact marginal, non corrigé (composer restreint à `panel.*`).
- **Notifications créées dans des transactions** (`ContratCoursService::create()`) — potentiellement 2 requêtes/affectation : volumétrie d'écriture minuscule, non corrigé (risque inutile sur une transaction métier sensible).
- **Numéros `count()+1`** — course conditionnelle sous forte concurrence : accepté à l'échelle actuelle (documenté section 17).

### 🧪 Tests ajoutés (6) — verts sur `keduc_test`

`tests/Feature/Partie06OptimisationsTest.php` :
- Publication d'actualité (canal email) → mails **poussés en file** (`assertQueued`, jamais `assertSent`) ;
- 6 index de performance présents en base (`Schema::getIndexes`) ;
- Page d'accueil : les 5 compteurs calculés en **une seule requête** `count(*)` (`DB::listen`) ;
- `mes-commandes.show` : les médias des produits chargés en **une seule requête** (0 requête `media` par ligne) ;
- `downloadSingle()` 2× → **1 seul média** (anti fuite de stockage) ;
- `downloadHistory()` → répond `application/pdf`, aucun média créé (anti crash).

### 🧪 Vérifications

- `php -l` OK sur les 13 fichiers PHP modifiés + la migration.
- **Régression Parties 01-05** : verts (aucun test de sécurité ni de performance cassé).
- **Suite complète** : **83 tests, 167 assertions** — seule failure `ExampleTest` (accès `/` sans seed, pré-existante, hors périmètre).
- `phpunit.xml` restauré en sqlite (`:memory:`) ; fichier `phpunit.keduc_test.xml` temporaire supprimé.

### 🚀 Commandes à exécuter par l'utilisateur

> Règle absolue : aucune commande impactant la base n'est exécutée par l'agent. La migration d'index (et elle seule) doit être appliquée par l'utilisateur :

```bash
/mnt/c/xampp/php/php.exe artisan migrate
```

Cette commande applique uniquement `2026_08_15_000001_add_performance_indexes.php` (6 index non destructifs) sur la base de développement `keduc`. Si un worker est souhaité en production pour le traitement de la file d'emails :

```bash
/mnt/c/xampp/php/php.exe artisan queue:work
```

(Le `QUEUE_CONNECTION=database` étant déjà configuré dans `.env`, les emails d'actualité restent en file tant que le worker ne tourne pas — prévoir un superviseur en production.)

---

## 19. Corrections effectuées — Étape 7 (Partie 07 : UI/UX & SEO)

> État : **faites** et **vérifiées** (6 Feature Tests dédiés verts sur `keduc_test` — 31 assertions ; suite complète **89 tests / 198 assertions**, seule failure `ExampleTest` pré-existante). Méthode : audit parallèle à 3 axes (UI/UX public, SEO public, panel/formulaires), chaque constat re-vérifié par lecture du code réel. Corrections minimales, aucune route/Model/migration modifiée (sauf besoins identifiés), aucune refonte. Détail complet : `RAPPORT_PARTIE_07.md`.

### 🟠 Liens morts `href="#"` — éliminés (0 occurrence restante hors `dropdown-toggle`/`scroll-top`)

- **Remplacés par des routes réelles** : navbar « Cours à domicile » → `demande-cours.create` ; « Voir les produits » → `librairie.produits` ; footer ancres → `url('/').'#…'` hors accueil ; « Mon profil » panel → `profil.edit` ; « Factures » → `finance.factures.index`.
- **Transformés en contenus informatifs** : services footer, sidebars élève/enseignant/parent (Mes cours, Planning, Évaluations, Mes enfants, Progression, Factures, Paiements…), admin/super-admin (Tous les utilisateurs, Paiements enseignants, Paramètres, Audit). Icônes sociales navbar+footer : `<a>` **sans `href`** (placeholder HTML5 valide, style CSS `.social-links a` conservé, aria-label conservé).

### 🟠 UI/UX public

| # | Problème | Correction |
|---|---|---|
| 1 | Sections enseignants/FAQ sans état vide | `@if/@else` + `@forelse/@empty` avec messages français |
| 2 | Bibliothèque sans état vide global, badges non conditionnés | état vide global + badges `@if($typesDocument->count())` + tags/réponses `@forelse/@empty` |
| 3 | Double `<h1>` (logo navbar) sur toutes les pages publiques | logo `h1` → `span.sitename` (CSS `.logo .sitename`) |
| 4 | Librairie : h2 à la place de h1 (4 pages) | h2 → h1 (Nos Produits, Nos Catégories, fiche catégorie, Mon Panier, confirmation) |
| 5 | Login : titre/meta/h1 manquants, erreurs invisibles | title + description + canonical + h1 masqué + `@error`/`is-invalid` + labels `for/id` + `remember` |
| 6 | Alt images génériques | alt réels (connexion, logo, titres de documents/actualités) |

### 🟠 UI/UX panel

| # | Problème | Correction |
|---|---|---|
| 1 | **Dashboard élève vide** (page blanche) | vue créée : h1 + 6 cartes vers routes réelles |
| 2 | Sidebar legacy non référencée (121 l.) + vue `demande-cours/edit` vide orpheline | fichiers supprimés |
| 3 | Fallback de sidebar muet (`@includeIf`) | `$sidebarRole` validé + `@include` sur `sidebars/$role` (repli `default`) |
| 4 | Footer panel : `href=#` non quoté cassait le HTML | spans informatifs (M.ILBOUDO / Magis Plus Center) |
| 5 | `periode->nom` inexistant (bug d'affichage « Période ») | → `->label` (2 vues `rapport-mensuel`) |
| 6 | Backticks Markdown cassant le HTML des contrats | supprimés (index + show) |
| 7 | Carte « Factures » `href="#"` | → `finance.factures.index` |
| 8 | `admin-signalements` : accès non null-safe | `document?->auteur?->prenom/nom` |
| 9 | Table non responsive (`objectifs/show`) | wrapper `.table-responsive` |
| 10 | `type-cours/create` : bouton « Mettre à jour » | → « Créer » |
| 11 | Actions destructrices sans confirmation | confirm sur clôture/suppression de périodes + vider le panier ; `type="button"` sur bouton checkout |

### 🟢 SEO

- **Canonical** par défaut dans le layout public (`url()->current()`) ; doublons `@push` retirés (actualité, témoignage).
- **Meta** : description login + confirmation commande ; keywords vides conservées (documenté).
- **Noindex** : panier librairie + confirmation de commande (données personnelles).
- **JSON-LD** `@graph` (Organization + WebSite) sur la page d'accueil via `json_encode` (3 flags), données issues de `config('keduc.cabinet.nom')`.
- **`robots.txt`** réécrit (Disallow zones privées + directive `Sitemap`) ; **`sitemap.xml`** créé (8 URLs stables, lastmod 15/08/2026). Domaine utilisé : `https://keducbf.com` (dérivé de l'email officiel) — **à adapter si la prod diffère**.

### 🧪 Vérifications

- `php artisan view:cache` : **toutes les vues compilent**.
- Test dédié `tests/Feature/Partie07UiUxSeoTest.php` : **6 tests / 31 assertions — verts** (canonical+JSON-LD+1 seul h1 home, login, 9 URLs publiques, noindex panier, robots/sitemap valides, footer sans `href="#"`).
- **Régression Parties 01-06** : verts. **Suite complète : 89 tests / 198 assertions** — seule failure `ExampleTest` (pré-existante).
- `phpunit.xml` restauré en sqlite (`:memory:`) ; `phpunit.keduc_test.xml` temporaire supprimé.

### 🚀 Commandes à exécuter par l'utilisateur

1. Migration de la Partie 06 (si pas déjà faite) : `/mnt/c/xampp/php/php.exe artisan migrate` (6 index non destructifs).
2. **Adapter le domaine de prod** dans `public/robots.txt` et `public/sitemap.xml` si `https://keducbf.com` ne correspond pas au domaine réel.
3. Recompilation des vues après toute modification : `/mnt/c/xampp/php/php.exe artisan view:cache`.

---



## 20. Validation globale — Étape 8 (Partie 08 : audit final + corrections ciblées)

> État : **faites** et **vérifiées** (3 Feature Tests dédiés verts sur `keduc_test` — 18 assertions ; suite complète **92 tests / 216 assertions**, seule failure `ExampleTest` pré-existante). Méthode : 5 audits parallèles (sécurité, stockage/fichiers, routes/policies, BDD/perf/configs, UI/SEO/docs), chaque constat re-vérifié par lecture du code réel avant correction. Corrections minimales et justifiées ; ce qui n'est pas une anomalie a été **documenté sans correction**. Détail complet : `RAPPORT_PARTIE_08.md`.

### 🔴 Critiques corrigés

| # | Problème | Correction |
|---|---|---|
| B1 | `FactureCabinetService::calculerPreview()` requêtait `contrat_cours.date_debut_contrat`/`date_fin_contrat` (**colonnes inexistantes** → 500 à l'aperçu/génération des factures cabinet) | → colonnes réelles `date_debut`/`date_fin` |
| B2 | Commission « Vente » des factures cabinet multipliée par **×100** (`* 100 / 100 * 100`) → surfacturation massive | → `(int) round($totalVentes * $tauxVente)` |

### 🟡 Routes mortes corrigées

- `routes/auth.php` : resources `parents`, `eleves`, `enseignants` → `->except(['destroy'])` (routes `*.destroy` pointaient vers des méthodes de contrôleur inexistantes ; aucune vue/test ne les référençait).
- `routes/finance.php` : resource `bulletins-paie` → `->only(['index','show'])` (routes `create`/`store` vers méthodes inexistantes).

### 🟠 Stockage corrigé / documenté

- **`app/Models/CahierTexte.php`** : collection média `cahier_texte_pdf` désormais sur disque **`private_media`** (calqué sur `DocumentBibliotheque`) — les PDF de cours ne sont plus exposés en `/storage/...` public.
- **Documentés sans correction** : PDF de dev dans `storage/app/public/{1..10}/` (ne pas supprimer sans vérif BDD media) ; `photo_profil` et documents d'actualité publics par design ; `FacturePdfService` sur disque public à réacheminer en roadmap.

### 🟢 Coordonnées harmonisées (config = source unique)

- Navbar : email + téléphones formatés (`preg_replace`) + lien WhatsApp `wa.me/226…` depuis `config('keduc.cabinet.*')`.
- Footer : placeholder `+226 XX XX XX XX` et email en dur → config.
- Home JSON-LD : email/téléphone depuis config.
- ⚠️ Nota : `.env.testing` récemment créé pointe vers `keduc_test` (isole le risque d'incident du 14/08), mais contient un mot de passe DB — **ne pas committer**.

### 🧪 Tests ajoutés (3) — verts sur `keduc_test`

- `tests/Feature/Partie08ValidationGlobaleTest.php` :
  1. `test_preview_facture_cabinet_sans_erreur_sql_et_commission_vente_correcte` (B1+B2).
  2. `test_routes_mortes_utilisateurs_et_bulletins_retirees`.
  3. `test_coordonnees_publiques_issues_de_la_configuration`.

### 🧪 Vérifications

- `php artisan view:cache` : **toutes les vues compilent** (vues coordonnées modifiées incluses).
- **Régression Parties 01-07** : verts. **Suite complète : 92 tests / 216 assertions** — seule failure `ExampleTest` (pré-existante).
- `phpunit.xml` restauré en sqlite (`:memory:`) ; `phpunit.keduc_test.xml` temporaire supprimé.
- Docs alignées sur le code réel : `docs/conception.md` (produits/commandes/DocumentBibliotheque/témoignages/routes), `AUDIT_PROJET.md` §19, `RAPPORT_PARTIE_06.md` (§27 ×100 → corrigé en P08).

### 🚀 Commandes à exécuter par l'utilisateur

1. Migration des Parties 06/07/08 (si pas déjà faite) : `/mnt/c/xampp/php/php.exe artisan migrate` (index et contraintes non destructifs).
2. Recompilation des vues : `/mnt/c/xampp/php/php.exe artisan view:cache`.
3. Adapter le domaine de prod dans `public/robots.txt` et `public/sitemap.xml` si `https://keducbf.com` diffère du domaine réel.
4. **Ne pas committer `.env.testing`** (contient le mot de passe DB).

---

*Rapport généré à partir de l'audit du code réel (août 2026). Toute correction doit faire l'objet d'une validation manuelle (test HTTP) puis, idéalement, d'un Feature Test.*
