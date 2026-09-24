# INVENTAIRE_KEDUC — Copie du projet KEduc (référence métier)

> Statut : P0 (T0.2/T0.3). Base de référence : `backend/` (copie indépendante de Keduc).
> **Important** : `docs/conception.md` (ancienne conception KEduc) a été **supprimée** par le propriétaire le 24/09/2026 : elle est ignorée définitivement.
> La source de vérité d'architecture est `CONCEPTION.md` (racine). Le présent fichier inventorie le code réel de `backend/`, pour savoir **ce qui se réutilise** et **ce qui s'ignore**.

---

## 1. Versions et dépendances (backend/composer.json)

| Paquet | Version |
|---|---|
| PHP | ^8.2 (local : 8.2.12) |
| laravel/framework | ^12.0 (local : 12.61.0) |
| barryvdh/laravel-dompdf | ^3.1 (PDF) |
| spatie/laravel-permission | ^6.25 (rôles/permissions, config publiée) |
| spatie/laravel-medialibrary | ^11.23 (fichiers, table `media`) |
| laravel/sanctum | ^4.3 (installé, **jetons non utilisés** — API commentée) |
| laravel-lang/common | ^6.8 (lang/fr) |
| laravel/tinker | ^2.10 |
| dev : phpunit ^11.5, laravel/pint ^1.24, pande etc. | |

Vite ^7 + Tailwind ^4 + axios en dev (les vues utilisent en réalité **Bootstrap**, pas Tailwind).

**À installer pour la plateforme (P1/P3/P7)** : `stancl/tenancy` v3, `laravel-notification-channels/webpush`, `dedoc/scramble`.

---

## 2. Migrations et tables (77 migrations → 68 tables)

Dossier `backend/database/migrations/`. Aucune migration tenant/plateforme : tout est dans `migrations/` actuellement.

**Infrastructure / framework** : `users` (nom, prenom, 2 téléphones, email nullable+unique+index, password, statut bool, remember, softDeletes, timestamps), `password_reset_tokens`, `sessions`, `cache`+`cache_locks`, `jobs`+`job_batches`+`failed_jobs`, `personal_access_tokens` (Sanctum), `media` (Spatie, collection_name, disk, uuid, maisons json).

**Rôles Spatie** : `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (guard `web`, teams=False).

**Profils / référentiels** : `parent_profils` (user_id unique), `enseignant_profils` (user_id unique, frais_annuel_regle bool), `classes`, `matieres`, `type_cours`, `type_documents`, `periode_documents` (soft deletes), `tags`.

**Cours / pédagogie** : `eleves` (user_id nullable, parent_id→users, classe_id ; fiche détaillée : école, naissance, régime, allergies, statut bool), `demande_cours` (visiteur : nom/prenom parent, téléphone, volume estimé, statut en_attente), `demande_cours_matieres` (pivot, unique), `contrat_cours` (eleve, type_cours, autres_frais_suivi, statut, dates), `affectation_enseignants` (contrat, enseignant, matiere, taux_horaire_enseignant int, nombre_heures decimal, statut), `planning_cours` (affectation, jour, heure début/fin, unique), `cahier_textes` (affectation, date_seance, heures, duree decimal, contenu, valide_admin), `objectif_pedagogiques`+`objectif_matieres`, `evaluation_cours`.

**Finance** : `periode_comptables`, `factures` (parent : contrat, parent, eleve, periode, numero unique, heures decimal, frais, remise, montant_total int, statut_paiement, mode_paiement, reference, date_limite), `ligne_factures` (unique facture+affectation), `paiement_enseignants` (unique enseignant+contrat+periode), `ligne_paiement_enseignants`, `rapport_mensuel_enseignants`, `bulletins_paie` (numero unique, unique enseignant+periode), `bulletin_paie_lignes` (unique bulletin+affectation), `bulletin_paie_ajustements`, `type_ajustements` (direction credit/debit).

**Facturation « cabinet » (module ANCIEN — à IGNORER pour la plateforme, voir §16)** : `facture_cabinets`, `ligne_facture_cabinets`, `paiement_cabinets`, `type_commissions`. *(Schéma KEduc historique sur lequel le propriétaire a tranché : ne pas reprendre, refonte conception §7.5/7.8 en base centrale.)*

**Bibliothèque** : `document_bibliotheques` (slug unique, is_public, statut, soft deletes, média privé), `document_tags`, `document_notes`, `document_commentaires`, `document_signalements`, `favori_bibliotheques`, `document_access_logs` (enum view/download).

**Librairie** : `categorie_produits`, `produits` (prix/frais_livraison **decimal(10,2)**), `commandes` (reference unique, token, statut avec historique json, whatsapp), `ligne_commandes`.

**Communication / CMS** : `faq_sections` (**colonne `cabinet_id` orpheline sans FK ni index — à supprimer en P1**), `faq_questions`, `actualites` (slug unique, destinataires json, canal_notification, notification_envoyee, soft deletes), `actualite_reactions`, `temoignages` (+ reactions, commentaires, signalements).

**Notifications** : `notifications` (data json, icone).

**Contraintes / index** : migration `2026_08_13_..._add_integrity_indexes_and_unique_constraints` et `2026_08_15_..._add_performance_indexes` (uniques + index FK).

---

## 3. Modèles et relations (54 modèles dans backend/app/Models)

Tous les modèles utilisent le **pluriel snake_case Laravel** sans `$table` explicite (conforme) et `constrained()`/`index()` sur les FK (conforme règle §0.5).

- **User** : traits `HasApiTokens`, `HasRoles`, `Notifiable`, `SoftDeletes`, `InteractsWithMedia`. Relations : parentProfil, enseignantProfil, eleve, enfants (hasMany Eleve), notifications, documents, favoris, notes, etc. **Pas de champ `role`** (rôles via Spatie).
- **Profils** : `ParentProfil` (1-1 user), `EnseignantProfil` (1-1 user, N-N matières via `enseignant_matiere`).
- **Référentiels** : Classe, Matiere, TypeCours, TypeDocument, PeriodeDocument, Tag (slug auto).
- **Cours** : Eleve (user, parent, classe ; hasMany contrats), DemandeCours (+ matieres N-N), ContratCours (hasMany affectations), AffectationEnseignant (hasMany cahiersTextes, lignesFacture, lignesPaiement), PlanningCours (jourLabel/trancheHoraire).
- **Pédagogie** : CahierTexte (HasMedia collection `cahier_texte_pdf`, scopes forEleve/forTeacher/betweenDates), ObjectifPedagogique (+ matières), EvaluationCours.
- **Finance** : PeriodeComptable, Facture (helpers statuts, montantSuivi), LigneFacture, PaiementEnseignant, LignePaiementEnseignant, RapportMensuelEnseignant, BulletinPaie (accesseurs totaux, numero auto), BulletinPaieLigne, BulletinPaieAjustement, TypeAjustement (credit/debit).
- **Facturation cabinet (ancien)** : FactureCabinet (numero `FCAB-YYYYMM-####`, montantPaye/Restant), LigneFactureCabinet (typeCommission), PaiementCabinet (enum statut : en_attente/valide/annule).
- **Bibliothèque** : DocumentBibliotheque (slug auto, médias, scopes, accesseurs), + notes/commentaires/signalements/favoris/accessLogs/tags.
- **Librairie** : CategorieProduit, Produit (slug, médias, scopes), Commande (const STATUTS, reference `CMD-########`, historique), LigneCommande.
- **CMS** : FaqSection (cabinet_id fillable), FaqQuestion, Actualite (pourDestinataires, medias, estGlobale/estInterne), ActualiteReaction, Temoignage (+reactions/commentaires/signalements).
- **Notification** (custom) : data json, accesseurs iconeHtml/couleur/url/actionLabel (routage par type).

**Factory** : `UserFactory` génère `name` alors que le modèle utilise `nom`/`prenom` → **factory à corriger**. Seuls quelques modèles ont `HasFactory`.

---

## 4. Architecture du code (backend/app)

Squelette modulaire `app/Modules/*` :

| Module | Contenu |
|---|---|
| Academique, Administration, Audit, Medias, Scolarite | **dossiers vides** (squelettes : Actions/, Http/, Models/, Services/ vides) |
| Auth | AuthController, DashboardController, ActiveRoleMiddleware (session `active_role`, **non enregistré**), AuthService, LoginRequest |
| Users | EleveController, ParentController, EleveService, ParentService, EnseignantService |
| Pedagogie | Contrôleurs (web + PDF) et services (voir §5/§8) |
| Finance | Contrôleurs (web + PDF) et services (voir §5/§8) |
| Bibliotheque, Librairie, Communication, Temoignages, Systeme | Idem |
| Public | HomeController, Controllers de pages publiques |

Providers : seul `AppServiceProvider` (bindings + `Gate::policy` manuels) dans `bootstrap/providers.php`. Middleware Spatie aliasé dans `bootstrap/app.php` (`role`, `permission`, `role_or_permission`).

---

## 5. Services métier (backend/app/Modules/*/Services — 35 fichiers)

- **Pedagogie** : `ClasseService`, `MatiereService`, `TypeCoursService`, `DemandeCoursService`, `DemandeCoursAdminService`, `ContratCoursService`, `ContratCoursQueryService`, `PlanningCoursService`, `CahierTexteService`, `CahierTextePdfService` (enum PdfCollection), `RapportMensuelService`, `RapportMensuelCalculator`, `RapportMensuelPdfService`, `ObjectifPedagogiqueService`.
- **Finance** : `PeriodeComptableService`, `FacturationService` (génération factures parents, anti-doublon contrat+période, numéro `FAC-YYYYMM-####`, marquerPaye), `PaiementEnseignantService`, `LigneFactureService`, `BulletinPaieCalculationService` (heures depuis rapport cumulé, net = brut − frais_suivi + primes − retenues), `BulletinPaieGenerationService`, `BulletinPaieValidationService`, `BulletinPaieAdjustmentService`, `BulletinPaiePdfService`, `FacturePdfService`, `FactureCabinetService` (**ANCIEN** — commissions : cours 2000 FCFA/actif, inscription 3000, vente 10 % du CA ; calculPreview/creation/paiement/annulation).
- **Users** : `EleveService`, `EnseignantService`, `ParentService`.
- **Systeme** : `NotificationDispatcher` (410 L : notifications DB + email en queue + WhatsApp pour tous les événements), `NotificationService`, `WhatsAppService`.
- **Communication** : `FaqService` (**écrit `cabinet_id`** → à nettoyer), ActualiteService, etc. — **Bibliotheque** : services recherche/upload. — **Librairie** : services produits/commandes/panier. — **Temoignages** : services moderation. — **Auth** : AuthService.

Les dossiers `Actions/` sont **vides** (aucune classe Action).

---

## 6. Policies (backend/app/Policies — 13)

Pattern : `before()` super-admin (+ admin pour CahierTexte/BulletinPaie), permissions Spatie, filtrage propriété (parent/enseignant/élève).
Policies : `Eleve`, `ParentProfil`, `EnseignantProfil`, `Contrat`, `AffectationEnseignant` (via Contrat), `CahierTexte`, `Facture`, `PaiementEnseignant`, `RapportMensuel`, `BulletinPaie`, `DocumentBibliotheque`, `Actualite`, `Faq`, `Temoignage`, `Commande`, `ObjectifPedagogique`, `FactureCabinet` (ancien).
→ **Réutilisables telles quelles** dans l'API (à vérifier au cas par cas).

---

## 7. Form Requests (66 fichiers)

`backend/app/Http/Requests/` + `backend/app/Modules/*/Http/Requests/`. Exemples types : `StoreCahierTexteRequest`, `StoreContratCoursRequest` (affectations imbriquées), `StorePaiementEnseignantRequest` (exists profils/contrats/périodes), `MarquerPayeRequest`, `PayerBulletinRequest` (mode in:especes,orange_money,moov_money,virement,cheque,autre), `StoreAjustementRequest` (closure TypeAjustement actif), `StoreDemandeCoursRequest`, `StoreEleve/Parent/EnseignantRequest`, `GenerateFactureRequest`, `ExportCahierTextePdfRequest`, `FilterTypeCoursRequest`.
→ **Réutilisables telles quelles** côté API (ajout de messages fr déjà en place).

---

## 8. Controllers web, routes et vues

**Enregistrement des routes** : `backend/bootstrap/app.php` charge `web.php, auth.php, pedagogie.php, notifications.php, finance.php, bibliotheque.php, librairie.php, cms.php, actualites.php, temoignages.php` (web) + `api.php` (API, **100 % commentée**) + `console.php`. Health route `/up`.

**Décision validée (Q-B4)** : le web Blade est **gelé** — il reste la référence KEduc, **non servi sur les domaines cabinets** en production ; le backoffice des cabinets sera Angular/API. Le Landlord gagnera un **interrupteur « accès écrans web Keduc »** (T2.10).

- `routes/web.php` : `/` (HomeController), `/demander-un-cours` (create/store).
- `routes/auth.php` : login, logout, sélection de rôle.
- `routes/pedagogie.php` (~356 L) : eleves, parents, enseignants (users/), classes, matieres, type-cours, contrats, affectations, cahiers-textes, rapports, objectifs, evaluations, planning, demandes.
- `routes/finance.php` (~314 L) : periodes, factures-génération, paiements-enseignants, bulletins (admin + enseignant), type-ajustements, factures-cabinet (ancien).
- `routes/bibliotheque.php`, `routes/librairie.php`, `routes/cms.php`, `routes/actualites.php`, `routes/temoignages.php`, `routes/notifications.php`.
- **Controllers morts** (héritage d'une tentative d'API) : `FactureController`, `PeriodeComptableController`, `ContratCoursController`, `CahierTexteController`, `RapportMensuelController`, `HistoriquePedagogiquePdfController` (référencés seulement dans `api.php` commenté).

Sécurité : routes `auth`, `role:`, `permission:`, `can(...)` ; contrôle de propriété par `abort_if/abort_unless` ; **pas de throttle** sur connexion actuellement (à ajouter en API).

---

## 9. Vues Blade (189 fichiers)

Sous `backend/resources/views/` : `panel/` (layouts, sidebars par rôle, dashboards admin/enseignant/parent, notifications), `publicpages/` (layout + ~14 pages + ~11 sections + partials — **Bootstrap**, template acheté), `auth/` (login, select-role), `pedagogie/` (Classes, Matieres, Users/eleves|parents|enseignants, cahiers-textes, contrats, demande-cours, evaluations, mes-cours, objectifs, planning, rapport-mensuel, type-cours, finance/periodes), `finances/` (bulletins-paie, facture-cabinet, facture-parent, mes-bulletins, paiements-enseignants, type-ajustements), `bibliotheque/`, `librairie/`, `communication/` (actualites, faq), `documents/`, `temoignages/`, `pdf/` (voir §10), `emails/` (actualités), `profil/`.

→ **Non converties, non servies sur les cabinets** ; restent la référence fonctionnelle.

---

## 10. Module PDF (barryvdh/laravel-dompdf)

Services + controllers :
- `FacturePdfService` → vue `pdf.facture-parent` (stockage public/factures).
- `RapportMensuelPdfService` → vue `pedagogie.rapport-mensuel.rapport-mensuel` (disque local rapports-mensuels/, stream/download).
- `CahierTextePdfService` → vues `pdf.cahiers-textes.*` (enum `PdfCollection` : seance, historique, layouts/base).
- `BulletinPaiePdfService` → vue `finances.bulletins-paie.pdf`.
- `CommandePdfService` → vue `pdf.librairie-commande`.
- Utilisent `config('keduc.cabinet')` (**config statique → à remplacer par les données tenant**).

---

## 11. Notifications, jobs, mails

- **Aucun dossier `app/Jobs/` ni `app/Notifications/`** : tout passe par `NotificationDispatcher` (Systeme) — envoi **synchrone** DB + `Mail::to()->queue()` + lien WhatsApp (service WhatsApp).
- `app/Mail/ActualitePublishedMail.php` + vue `emails/actualites/publiee.blade.php` (email d'actualité en file d'attente).
- La queue est configurée `QUEUE_CONNECTION=database` (table `jobs` en base courante) — en multi-tenant elle passera en **base centrale** (D13).
- Le canal **webpush** n'existe pas encore (packages + table `push_subscriptions` en P3/P7).

---

## 12. Règles de calcul (à conserver comme référence métier)

- **Facture parent** : prérequis = rapports `soumis|valide` ; lignes = heures effectuées (`ligne_factures.nombre_heures × taux_horaire`) par affectation ; + frais de suivi et autres frais du contrat, remise ; numéro `FAC-YYYYMM-####` ; anti-doublon contrat+période (unique).
- **Paie enseignants** : heures = `rapport_mensuel_enseignants.volume_horaire_cumule` × `affectation_enseignants.taux_horaire_enseignant` ; bulletins : brut − frais_suivi (5 000 par défaut) + primes − retenues = net ; numéro de bulletin unique ; unique enseignant+période.
- **Facturation « cabinet » (ANCIENNE, ignorée pour la plateforme)** : cours actifs 2 000 FCFA, inscriptions 3 000 FCFA, ventes 10 % du CA (`FactureCabinetService`). **Remplacée par conception §7.5/7.8**.

---

## 13. Données initiales (backend/database/seeders — 14)

`DatabaseSeeder` orchestre : `RoleSeeder` (6 rôles guard web : **super-admin, admin, parent, enseignant, eleve, gestionnaire**), `PermissionSeeder` (~42 permissions : eleve.*, enseignant.*, contrat.*, facture.*, paiement.*, rapport.*, bibliotheque.*, librairie.*, faq.*, actualite.*, temoignage.*, dashboard.view), `RolePermissionSeeder`, `ProductionUserSeeder` (superadmin@keduc.bf + admin/5 enseignants/5 parents/6 élèves), `ClasseSeeder` (CP1→3ème), `MatiereSeeder` (7), `TypeCoursSeeder` (4), `TypeAjustementSeeder` (6), `TypeCommissionSeeder` (5), `TypeDocumentSeeder` (9), `PeriodeDocumentSeeder` (7), `LibrairieSeeder` (6 catégories), `FaqSeeder`.
→ En P1 (T1.7) : seeder tenant = 5 rôles (conception) + référentiels par défaut ; classes/matières **vides**.

---

## 14. Tests (backend/tests)

- 20 fichiers Feature + 1 Unit, ~200 méthodes. Executés sur **PostgreSQL `keduc_test`** (sqlite `:memory:` de phpunit.xml ne suffit pas : plusieurs migrations utilisent `dropColumn`/dépendances pgsql).
- Couverture : sécurité accès (Users CRUD, notifications, bibliothèque, PDF, espaces élève/parent/enseignant), finance/paie, fonctionnel récent (actualités internes, planning, référence commande), qualité/integrité/perf.
- **Squelettes** : `ExampleTest` (Unit passe, Feature échoue sans données).
- Après stancl : adapter la stratégie (Q-B3 : `keduc_test` = base centrale ; tenants de test dynamiques `keduc_test_<c1|c2>`).

---

## 15. Config statique et points durs multi-tenant

| Point | Emplacement | Action |
|---|---|---|
| `config('keduc.cabinet')` (nom, slogan, tél, whatsapp, email, orange_money, moov_money, adresse, directeur, logo) | `backend/config/keduc.php` | **Supprimer** le côté statique → stocker dans la base tenant (thème/pied de page/coordonnées). Alertes : PDFs + HomeController + vues publiques |
| `HomeController` | `app/Modules/Public/Controllers/HomeController.php` | SQL **brut** `DB::selectOne` comptant profils/élèves/contrats → à remplacer par des services Eloquent (page publique gérée par Angular) |
| `faq_sections.cabinet_id` | migration + `FaqService` | Colonne **orpheline sans FK** → supprimer (P1) |
| `config/app.php` timezone | `config/app.php` | `UTC` → `Africa/Ouagadougou` (P1) |
| `APP_NAME` | `.env` | `Laravel` → nom de marque (P1) |
| Session / cache / queue | `.env` | sessions `database` → **`file`** (D14) ; cache `database` → **file** (pas de tags, partagé) ; queue `database` en base centrale (D13) |
| `super_admin` / `super-admin` | seeder / rôles tenant | **Nommage à écarter côté tenant** : le super-admin de plateforme vit en base centrale (`super_admins`) |

---

## 16. Écarts avec CONCEPTION.md (sections 7-9) — relevés P0

| § conception | État dans le code | Écart / décision |
|---|---|---|
| §7.1 `cabinets` | Absent | À créer (stancl, id=slug, `getCustomColumns()`) |
| §7.2 `domains` (is_primary, is_active) | Absent | À créer (module Domain custom) |
| §7.3 `parametres_cabinet` | Absent | À créer (tarifs + fonctionnalités json) |
| §7.4 `super_admins` | Absent (rôle `super-admin` spatie à la place) | À créer, guard `landlord`. **Un seul super-admin** (décision) |
| §7.5/7.8 facturation plateforme | Ancien `facture_cabinets`, `ligne_facture_cabinets`, `paiement_cabinets`, `type_commissions` (schéma KEduc) | **Refonte** : tables `factures_cabinet`, `lignes_facture_cabinet`, `paiements_cabinet` en base **centrale** (lignes debit/credit, montant_paye, created_by, etc.). Ancien schéma ignoré/dormant (§ décision Q-B2) |
| §7.6 `journal_plateforme` | Absent | À créer |
| §7.7 tables techniques centrales | `migrations`, `jobs`, ... en migrations racine | Répartir : sessions (file) ; cache (file) ; jobs/failed_jobs centrales |
| §8 tables tenant | 68 tables KEduc | **Réutilisées** telles quelles (isolées par base) ; supprimer `faq_sections.cabinet_id` ; ajouter `uuid_client` unique sur `cahier_textes` ; `theme_cabinet`/`pied_de_page_cabinet`/`inscriptions_facturables` à créer |
| §8.9 thème/coordonnées | `config('keduc.cabinet')` statique | À transformer en données tenant |
| §8.10 `inscriptions_facturables` | Absent | À créer (P8) |
| §9 relations Landlord | Absent | À créer (Cabinet 1-1 ParametresCabinet, 1-N Domain, 1-N Facture...) |
| Règle d'or §0.6 (montants entiers FCFA) | Librairie en `decimal(10,2)` | Écart accepté provisoirement, harmonisation **au M11** (décision Q11) |
| Users (`email` unique) | unique **par base** | Compatible (l'unicité est scoped par base tenant) |

---

## 17. Renvoi vers les documents de décision

- Ambiguïtés et réponses validées : `docs/QUESTIONS.md`
- Journal des décisions : `docs/DECISIONS.md`