# Conception du projet KEduc

> Reference fonctionnelle et architecturale du projet.
> Ce document n'est pas fige. Il evolue avec le projet.

---

## 1. Vision du projet

Plateforme complete de gestion d'un cabinet de soutien scolaire.

### Stack technique (alignee sur le code reel — audit aout 2026)

- Laravel 12 (12.61.0)
- PHP 8.2+
- PostgreSQL
- Sanctum (auth API)
- Spatie Permission (roles + permissions, garde `web`)
- Spatie Media Library (table `media` standard Spatie)
- barryvdh/laravel-dompdf (generation PDF)
- laravel-lang/common
- Vite 7 + Tailwind 4 (dev deps) — les vues utilisent Bootstrap
- Queues : infrastructure configuree (`QUEUE_CONNECTION=database`) — **utilisee depuis la Partie 06** pour l'envoi des emails d'actualite (`NotificationDispatcher` → `Mail::queue()`), traites par un worker (`php artisan queue:work`) en production
- Notifications (table `notifications` + `NotificationDispatcher`)
- Storage Laravel
- Architecture modulaire

### Objectifs

- Application robuste, evolutive, securisee et maintenable.
- Aucune modification architecture sans accord du lead.

---

## 2. Conception des entites

### 2.1 AUTH

#### User

Classe de base pour l'authentification.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| nom | string | |
| prenom | string | |
| telephone_whatsapp | string | |
| telephone_appel | string | |
| email | string | unique, optionnel au debut, index |
| password | string | |
| statut | boolean | actif / inactif, index |
| email_verified_at | datetime | optionnel |
| remember_token | string | |
| deleted_at | timestamp | nullable |

#### Role

| Attribut | Type |
|---|---|
| id | bigint |
| nom | string |
| sigle | varchar |

#### ParentProfil

Lien 1 a 1 avec User.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| user_id | bigint | FK -> Users, index |
| adresse_domicile | string | |
| profession | string | |
| nombre_enfants | integer | |

#### EnseignantProfil

Lien 1 a 1 avec User.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| user_id | bigint | FK -> Users, index |
| numero_orange_money | string | |
| diplome_max | string | |
| lieu_de_service | varchar | |
| domicile | varchar | |
| frais_annuel_regle | boolean | Traquer si l'enseignant a declenche le gain de 5000f |

---

### 2.2 PEDAGOGIE

#### Classe

| Attribut | Type |
|---|---|
| id | bigint |
| nom | string |
| sigle | string |

#### Matiere

| Attribut | Type |
|---|---|
| id | bigint |
| nom | text |
| sigle | varchar(25) |

#### Eleve

Lien 1 a 1 avec User si majeur, ou rattache a un Parent.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| user_id | bigint | optionnel, si l'eleve a son propre compte |
| parent_id | bigint | FK -> Users (role Parent), index |
| classe_id | bigint | FK, index |
| ecole | varchar | |
| date_naissance | date | |
| lieu_naissance | varchar | |
| parent_charge | varchar | |
| etablissement_origine | string | |
| profession_pere | varchar | |
| profession_mere | varchar | |
| regime_etude | varchar | interne ou externe |
| loisirs_sport | varchar | |
| religion_enfant | varchar | |
| maladies_allergies | text | optionnel |
| interdits_familiaux | text | optionnel |
| boisson_preferee | string | optionnel |
| nourriture_preferee | string | optionnel |
| autres_precautions | text | |
| autres_observations | text | |

#### MatieresCompetentes

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| enseignant_id | bigint | index |
| matiere_id | bigint | index |
| created_at | TIMESTAMP | |

#### TypeCours

| Attribut | Type |
|---|---|
| id | bigint |
| libelle | varchar (A domicile, renforcement, en ligne, etc.) |

#### DemandeCours

Pour les visiteurs non connectes.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| nom_parent | string | |
| prenom_parent | varchar | |
| telephone | string | |
| type_cours_id | bigint | FK |
| classe_id | bigint | FK |
| volume_horaire_estime | integer | |
| statut | string | en_attente, traitee, annulee, index |
| created_at | datetime | |

#### DemandeCoursMatiere

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| demande_cours_id | bigint | FK |
| matiere_id | bigint | FK |
| created_at | TIMESTAMP | |

#### ContratCours

L'assignation officielle creee par l'Admin. Lien entre eleve, enseignants et matieres.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| eleve_id | bigint | FK, index |
| type_cours_id | bigint | FK |
| autres_frais_suivi | integer | frais fixes fixes a la commande |
| statut | string | actif, suspendu, termine, index |
| date_debut | date | index |
| date_fin | date | optionnel |

#### AffectationEnseignant

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| contrat_cours_id | bigint | FK, index |
| enseignant_id | bigint | FK, index |
| matiere_id | bigint | FK |
| taux_horaire_enseignant | integer | |
| nombre_heures_prevues | | |
| date_affectation | date | |
| date_fin | date | |
| statut | string | actif, suspendu, termine, index |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

#### PlanningCours

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| affectation_enseignant_id | bigint | FK |
| jour_semaine | string | |
| heure_debut | time | |
| heure_fin | time | |
| is_actif | boolean | |

#### CahierTexte

Rempli a chaque seance.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| affectation_enseignant_id | bigint | FK, index |
| date_seance | date | index |
| heure_debut | time | |
| heure_fin | time | |
| duree_heures | NUMERIC(5,2) | |
| contenu_cours | text | |
| created_at | timestamp | |

#### ObjectifPedagogique

Rempli a la premiere seance.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| periode | VARCHAR | Trimestre 1/2/3, Semestre 1/2, Annuel, index |
| eleve_id | bigint | FK, index |
| moyenne_visee | NUMERIC(6,2) | |
| moyenne_obtenue | NUMERIC(6,2) | NULL |
| materiel_disponible | text | |
| materiel_manquant | text | optionnel, pour alerter l'admin |

#### ObjectifMatiere

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| objectif_pedagogique_id | bigint | FK, index |
| matiere_id | bigint | FK, index |
| moyenne_visee | NUMERIC(6,2) | |
| moyenne_obtenue | NUMERIC(6,2) | |

#### EvaluationCours

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| eleve_id | bigint | FK |
| enseignant_id | bigint | FK |
| auteur_id | bigint | FK -> User |
| note | NUMERIC(2,1) | |
| commentaire | text | |
| anonyme | boolean | default TRUE |
| created_at | TIMESTAMP | |

#### RapportMensuelEnseignant

Genere par le prof en fin de mois.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| contrat_cours_id | bigint | FK, index |
| enseignant_id | bigint | FK -> Users, index |
| periode_id | bigint | FK |
| volume_horaire_cumule | NUMERIC(5,2) | |
| point_notes_matieres | text | |
| point_notes_autres_matieres | text | |
| bilan_activites | text | |
| difficultes_rencontrees | text | |
| solutions_trouvees | text | |
| attentes_parents_eleve | text | |
| attentes_administration | text | |
| appreciation_evolution | text | |
| observations | text | |
| statut | string | soumis, valide, rejete |

---

### 2.3 FINANCE

#### PeriodeComptable

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| label | VARCHAR(20) | "Janvier 2026" |
| date_debut | DATE | 2026-01-01 |
| date_fin | DATE | 2026-01-31 |
| type | VARCHAR(20) | mensuel, trimestriel |
| statut | VARCHAR(20) | ouverte, cloturee |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

#### Facture

Generee par l'admin pour le parent.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| contrat_cours_id | bigint | FK, index |
| parent_id | bigint | FK -> Users, index |
| eleve_id | bigint | FK, index |
| periode_id | bigint | FK |
| numero_facture | | |
| volume_horaire_total | integer | |
| autres_frais | integer | Suivi et responsabilite |
| montant_total | integer | Cout cours + Autres frais |
| statut_paiement | string | en_attente, paye |
| date_paiement | datetime | optionnel |
| mode_paiement | string | Orange Money, Moov Money, Cash |
| created_at | date | |

#### LigneFacture

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| facture_id | bigint | FK, index |
| affectation_enseignant_id | bigint | FK, index |
| nombre_heures | NUMERIC(5,2) | |
| taux_horaire | integer | |
| montant | integer | |

#### PaiementEnseignant

Le salaire verse par l'admin le 25 du mois.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| enseignant_id | bigint | FK -> Users, index |
| periode_id | bigint | FK |
| total_heures_effectuees | float | |
| montant_total | integer | |
| statut | string | en_attente, verse, index |
| transaction_reference | string | optionnel, ID du transfert Mobile Money |
| date_paiement | | |

#### LignePaiementEnseignant

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| paiement_enseignant_id | bigint | index |
| affectation_enseignant_id | bigint | index |
| nombre_heures | NUMERIC(5,2) | |
| montant | INTEGER | |

#### FactureCabinet

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| periode_debut | date | |
| periode_fin | date | |
| total_cours | integer | |
| total_ventes | integer | |
| total_inscriptions | integer | |
| montant_commission | integer | |
| montant_total_du | integer | |
| statut | string | |
| date_facture | date | |
| date_paiement | date | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

#### LigneFactureCabinet

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| facture_cabinet_id | bigint | FK |
| type_commission_id | bigint | FK -> TypeCommission |
| quantite | integer | |
| base_calcul | integer | |
| taux_commission | decimal(5,2) | |
| montant | integer | |
| created_at | timestamp | |

#### PaiementCabinet

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| facture_cabinet_id | bigint | FK |
| montant_paye | integer | |
| mode_paiement | string | |
| reference_paiement | string | nullable |
| date_paiement | date | |
| statut | enum | en_attente, valide, annule |
| created_at | timestamp | |
| updated_at | timestamp | |

#### TypeCommission

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| nom_du_type | varchar | cours, inscription, vente, penalite, remise |

---

### 2.4 BIBLIOTHEQUE

#### DocumentBibliotheque

Le systeme de partage.

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| user_id | bigint | FK -> Users, index (auteur) |
| titre | string | |
| description | text | |
| type_document_id | bigint | FK, index |
| classe_id | bigint | FK, index |
| matiere_id | bigint | FK, index |
| statut | varchar(20) | brouillon, en_attente, publie, refuse |
| is_public | boolean | |
| periode_id | bigint | nullable, FK -> PeriodeDocument |
| created_at | date | |

#### TypeDocument

| Attribut | Type |
|---|---|
| id | bigint |
| nom | text |
| sigle | varchar |

#### FavoriBibliotheque

Table de pivot pour les favoris des eleves/parents.

| Attribut | Type | Details |
|---|---|---|
| user_id | bigint | FK, index |
| document_id | bigint | FK, index |

#### DocumentAccessLog

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| user_id | bigint | |
| document_id | bigint | |
| action | ENUM | view, download |
| created_at | TIMESTAMP | |

---

### 2.5 LIBRAIRIE (E-commerce)

#### Produit

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| categorie_id | bigint | FK -> CategorieProduit, index |
| nom | string | index |
| slug | varchar(255) | unique |
| description | text | nullable |
| prix | decimal(10,2) | |
| frais_livraison | decimal(10,2) | default 0 |
| is_active | boolean | default true, index |
| created_at / updated_at | datetime | |
| deleted_at | timestamp | soft delete |

#### Commande

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| user_id | bigint | optionnel, null si visiteur guest, index |
| nom_client | string | |
| telephone_client | string | |
| adresse_livraison | text | |
| quartier | string | nullable |
| is_livraison | boolean | default false |
| montant_total | decimal(12,2) | |
| frais_livraison | decimal(10,2) | default 0 |
| statut | varchar(30) | en_attente, validee, livree, annulee, index |
| mode_paiement | varchar(50) | nullable |
| reference_transaction | varchar(255) | nullable |
| notes | text | nullable |
| created_at | datetime | index |
| deleted_at | timestamp | soft delete |

#### CommandeLigne

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| commande_id | bigint | FK, index |
| produit_id | bigint | FK, index |
| quantite | integer | |
| prix_unitaire_au_moment_achat | integer | |

---

### 2.6 COMMUNICATION

#### Notification

| Attribut | Type | Details |
|---|---|---|
| id | BIGSERIAL | |
| user_id | BIGINT | index |
| titre | VARCHAR(255) | |
| contenu | TEXT | |
| type | VARCHAR(50) | |
| lu | BOOLEAN | default FALSE, index |
| date_lecture | TIMESTAMP | NULL |
| created_at | TIMESTAMP | index |

#### Actualite

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| user_id | bigint | admin qui publie |
| titre | varchar(255) | |
| slug | varchar(255) | |
| contenu | longText | HTML ou Markdown |
| statut | varchar(20) | brouillon, publie |
| published_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

#### FaqSection

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| title | varchar(255) | |
| slug | varchar(255) | unique |
| order_index | integer | |
| is_active | boolean | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### FaqQuestion

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| faq_section_id | bigint | FK |
| question | varchar(255) | |
| answer | longText | |
| order_index | integer | |
| is_active | boolean | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

### 2.7 TEMOIGNAGES

Module ajoute en aout 2026 (4 tables, 4 enums, policy, services, vues publiques + admin).

#### Temoignage

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| slug | varchar(190) | unique |
| contenu | text | |
| role | varchar(40) | enum : parent, enseignant, eleve, autre |
| anonyme | boolean | |
| statut | varchar(20) | enum : publie, masque |
| published_at | timestamp | nullable |
| is_active | boolean | |
| created_at / updated_at | timestamp | |

#### TemoignageReaction

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| temoignage_id | bigint | FK, index |
| reaction | varchar(30) | enum `TemoignageReactionType` : like, dislike, love, broken_heart, applause, congrats, surprise, thanks |
| ip | varchar(45) | unique(temoignage_id, ip) — dedoublonne les reactions par IP |
| created_at / updated_at | timestamp | |

#### TemoignageCommentaire

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| temoignage_id | bigint | FK, index |
| contenu | text | |
| signale | boolean | |
| created_at / updated_at | timestamp | |

#### TemoignageSignalement

| Attribut | Type | Details |
|---|---|---|
| id | bigint | |
| temoignage_id | bigint | FK, index |
| motif | varchar(100) | |
| description | text | |
| statut | varchar(20) | enum : en_attente, traite, rejete |
| created_at / updated_at | timestamp | |

### 2.8 SYSTEME

#### Media

Table **standard Spatie Media Library** (migration `2026_06_03_220355_create_media_table.php`) :

| Attribut | Type | Details |
|---|---|---|
| id | BIGINT | |
| model_type / model_id | VARCHAR / BIGINT | polymorphisme (mediaable) |
| uuid | UUID | |
| collection_name | VARCHAR(255) | |
| name | VARCHAR(255) | |
| file_name | VARCHAR(255) | |
| mime_type | VARCHAR(100) | |
| disk | VARCHAR(50) | |
| conversions_disk | VARCHAR(50) | |
| size | BIGINT | |
| manipulations | JSON | |
| custom_properties | JSON | |
| generated_conversions | JSON | |
| responsive_images | JSON | |
| order_column | INT | |
| created_at / updated_at | TIMESTAMP | |

> ⚠️ Attention securite : Media Library est configuree par defaut sur le disque `public` et conserve le nom d'origine des fichiers (`usingFileName`) → les documents prives sont adressables par URL directe. Voir `AUDIT_PROJET.md` §4 E3.

#### HistoriqueActivite

| Attribut | Type | Details |
|---|---|---|
| id | BIGINT | |
| utilisateur_id | BIGINT | NULL, index |
| action | VARCHAR(100) | |
| entite | VARCHAR(100) | index |
| entite_id | BIGINT | |
| anciennes_valeurs | JSONB | NULL |
| nouvelles_valeurs | JSONB | NULL |
| adresse_ip | VARCHAR(45) | NULL |
| user_agent | TEXT | NULL |
| created_at | TIMESTAMP | index |

---

## 3. Relations

### Auth

- User N ---- N Role
- User 1 ---- 1 ParentProfil
- User 1 ---- 1 EnseignantProfil
- User 1 ---- 1 Eleve (optionnel)
- User (Parent) 1 ---- N Eleve

### Pedagogie

- Classe 1 ---- N Eleve
- EnseignantProfil N ---- N Matiere (via EnseignantMatiere / MatieresCompetentes)
- TypeCours 1 ---- N DemandeCours
- Classe 1 ---- N DemandeCours
- DemandeCours 1 ---- N DemandeCoursMatiere
- Matiere 1 ---- N DemandeCoursMatiere
- Eleve 1 ---- N ContratCours
- TypeCours 1 ---- N ContratCours
- ContratCours 1 ---- N AffectationEnseignant
- EnseignantProfil 1 ---- N AffectationEnseignant
- Matiere 1 ---- N AffectationEnseignant
- AffectationEnseignant 1 ---- N PlanningCours
- AffectationEnseignant 1 ---- N CahierTexte
- Eleve 1 ---- N ObjectifPedagogique
- ObjectifPedagogique 1 ---- N ObjectifMatiere
- Matiere 1 ---- N ObjectifMatiere
- Eleve 1 ---- N EvaluationCours
- AffectationEnseignant 1 ---- N EvaluationCours
- User 1 ---- N EvaluationCours (auteur)
- AffectationEnseignant 1 ---- N RapportMensuelEnseignant
- EnseignantProfil 1 ---- N RapportMensuelEnseignant
- PeriodeComptable 1 ---- N RapportMensuelEnseignant

### Finance

- ContratCours 1 ---- N Facture
- User (Parent) 1 ---- N Facture
- Eleve 1 ---- N Facture
- PeriodeComptable 1 ---- N Facture
- Facture 1 ---- N LigneFacture
- AffectationEnseignant 1 ---- N LigneFacture
- EnseignantProfil 1 ---- N PaiementEnseignant
- PeriodeComptable 1 ---- N PaiementEnseignant
- PaiementEnseignant 1 ---- N LignePaiementEnseignant
- AffectationEnseignant 1 ---- N LignePaiementEnseignant
- Cabinet 1 ---- N FactureCabinet
- FactureCabinet 1 ---- N LigneFactureCabinet
- FactureCabinet 1 ---- N PaiementCabinet
- TypeCommission 1 ---- N LigneFactureCabinet

### Bibliotheque

- TypeDocument 1 ---- N DocumentBibliotheque
- Classe 1 ---- N DocumentBibliotheque
- Matiere 1 ---- N DocumentBibliotheque
- EnseignantProfil 1 ---- N DocumentBibliotheque
- DocumentBibliotheque 1 ---- N DocumentAccessLog
- User 1 ---- N DocumentAccessLog

### Librairie

- Commande 1 ---- N CommandeLigne
- Produit 1 ---- N CommandeLigne
- User 1 ---- N Commande

### Communication

- User 1 ---- N Notification
- User 1 ---- N Actualite
- FaqSection 1 ---- N FaqQuestion

### Temoignages

- Temoignage 1 ---- N TemoignageReaction
- Temoignage 1 ---- N TemoignageCommentaire
- Temoignage 1 ---- N TemoignageSignalement

### Systeme

- User 1 ---- N Media (uploaded_by)

> `HistoriqueActivite` (audit) figure dans la conception initiale mais **n'est pas implemente** (aucune migration ni modele). Voir `AUDIT_PROJET.md` §3 E10.

---

## 4. Modules

> Aligne sur l'arborescence reelle `app/Modules/` (audit aout 2026). Les modules entre parentheses sont des squelettes vides (aucun fichier).

```
AUTH
  AuthController, DashboardController, ProfilController
  User, Role (via Spatie Permission)

USERS
  ParentController, EleveController, EnseignantController
  ParentProfil, EnseignantProfil, Eleve
  ⚠️ resources sous `auth + role:admin|super-admin` (voir AUDIT_PROJET.md §19)

PEDAGOGIE
  Classe, Matiere, TypeCours, DemandeCours, DemandeCoursMatiere
  ContratCours, AffectationEnseignant, CahierTexte
  ObjectifPedagogique, ObjectifMatiere, EvaluationCours, RapportMensuelEnseignant
  EnseignantMatiere
  (PlanningCours : present dans la conception, modele absent du code)

FINANCE
  PeriodeComptable, Facture, LigneFacture
  PaiementEnseignant, LignePaiementEnseignant
  FactureCabinet, LigneFactureCabinet, PaiementCabinet
  TypeCommission, TypeAjustement
  BulletinPaie, BulletinPaieLigne, BulletinPaieAjustement

BIBLIOTHEQUE
  DocumentBibliotheque, TypeDocument, PeriodeDocument
  Tag, DocumentTag, DocumentNote, DocumentCommentaire
  DocumentSignalement, FavoriBibliotheque, DocumentAccessLog

LIBRAIRIE
  Produit, CategorieProduit
  Commande, LigneCommande

COMMUNICATION
  Actualite, ActualiteReaction
  FaqSection, FaqQuestion

SYSTEME
  Notification

TEMOIGNAGES
  Temoignage, TemoignageReaction, TemoignageCommentaire, TemoignageSignalement

PUBLIC
  HomeController (page d'accueil publique)

(ACADEMIQUE)
(ADMINISTRATION)
(AUDIT)
(MEDIAS)
(SCOLARITE)

Non implementes (conception initiale) : Cabinet, Footer, HistoriqueActivite, PlanningCours.
```

---

## 5. Routes

> Aligne sur les fichiers reels (audit aout 2026). Les fichiers `academique.php`, `scolarite.php`, `systeme.php` annonces initialement **n'existent pas**.

```
routes/
  web.php          # Home publique, connexion, demande-cours, bibliotheque/librairie publiques
  auth.php         # Login/logout, select-role, dashboard, resources Users (⚠️ non protegees)
  pedagogie.php    # Classes, Matieres, TypeCours, Demandes, Contrats, CahiersTextes, Objectifs, Rapports, PDF
  notifications.php# notifications (middleware auth + propriete controlee par Policy/Controller)
  finance.php      # Factures, FactureCabinet, BulletinsPaie, Paiements, Periodes, TypeAjustements
  bibliotheque.php # Public + admin, TypeDocument, PeriodeDocument
  librairie.php    # Produits publics, panier, commandes, confirmation/PDF, admin
  cms.php          # FAQ (sections/questions)
  actualites.php   # Actualites publiques + admin
  temoignages.php  # Temoignages publics + admin
  api.php          # Contrats/CahiersTextes/Factures en JSON (commentes)
  console.php      # commandes artisan
```

Enregistrees dans `bootstrap/app.php` (groupe `web` : web, auth, pedagogie, notifications, finance, bibliotheque, librairie, cms, actualites, temoignages ; groupe `api` : api ; `commands` : console). Alias de middleware : `role`, `permission`, `role_or_permission` (Spatie).

---

## 6. Phases

### PHASE 0 -- FOUNDATION

Infrastructure : Laravel, PostgreSQL, Sanctum, Spatie Permission, Spatie Media Library, Queues, Notifications, Storage, Logs.

### PHASE 1 -- DATABASE

Referentiels : Cabinet, Role, Classe, Matiere, TypeCours, TypeDocument, PeriodeComptable, TypeCommission.
Utilisateurs : User, ParentProfil, EnseignantProfil.
Pedagogie : Eleve, DemandeCours, DemandeCoursMatiere, ContratCours, AffectationEnseignant, PlanningCours, CahierTexte, ObjectifPedagogique, ObjectifMatiere, EvaluationCours, RapportMensuelEnseignant.
Finance : Facture, LigneFacture, PaiementEnseignant, LignePaiementEnseignant, FactureCabinet, LigneFactureCabinet, PaiementCabinet.
Bibliotheque : DocumentBibliotheque, FavoriBibliotheque, DocumentAccessLog.
E-commerce : Produit, Commande, CommandeLigne.
CMS : Actualite, FaqSection, FaqQuestion.
Systeme : Notification, Media, HistoriqueActivite.

### PHASE 2 -- AUTHENTIFICATION

Tables : users, roles, role_user, parent_profils, enseignant_profils.
Fonctionnalites : Connexion, Deconnexion, Mot de passe oublie, Gestion roles, Gestion permissions, Photo profil.

### PHASE 3 -- PARAMETRAGE

Tables : cabinets, classes, matieres, type_cours, type_documents, periodes_comptables, type_commissions.
CRUD complet.

### PHASE 4 -- ELEVES & PARENTS

Tables : eleves, parent_profils.
Fonctions : Creation parent, Creation eleve, Association parent-enfant.

### PHASE 5 -- ENSEIGNANTS

Tables : enseignant_profils, matieres_competentes.
Fonctions : Gestion enseignants, Matieres enseignees, Suivi cotisation annuelle.

### PHASE 6 -- DEMANDES DE COURS

Tables : demande_cours, demande_cours_matieres.
Fonctions : Demande publique, Validation admin, Transformation en contrat.

### PHASE 7 -- CONTRATS

Tables : contrat_cours, affectation_enseignants, planning_cours.
Fonctions : Creation contrat, Affectation prof, Planning, Suspension, Fin contrat.

> A ce stade le business fonctionne deja.

### PHASE 8 -- SUIVI PEDAGOGIQUE

Tables : cahier_textes, objectif_pedagogiques, objectif_matieres, evaluations.
Fonctions : Seances, Objectifs, Evaluations, Historique.

### PHASE 9 -- RAPPORTS

Tables : rapport_mensuel_enseignants.
Fonctions : Soumission rapport, Validation, PDF.

### PHASE 10 -- FACTURATION CLIENT

Tables : factures, ligne_factures.
Fonctions : Calcul heures, Generation facture, PDF, Paiement parent.

### PHASE 11 -- PAIEMENT ENSEIGNANTS

Tables : paiement_enseignants, ligne_paiement_enseignants.
Fonctions : Calcul automatique, Versement, Historique.

### PHASE 12 -- FACTURATION DU CABINET

Tables : facture_cabinets, ligne_facture_cabinets, paiement_cabinets.
Fonctions : Calcul des commissions, Facturation du cabinet, Suivi paiements, Relances.

Exemples de lignes :
- Inscriptions : 15 x 3 000 = 45 000
- Cours actifs : 25 x 2 000 = 50 000
- Ventes librairie : 10 x 10% = 15 000
- **Total : 110 000 FCFA** (facture generee automatiquement)

### PHASE 13 -- BIBLIOTHEQUE

Tables : document_bibliotheques, favori_bibliotheques, document_access_logs.

### PHASE 14 -- LIBRAIRIE

Tables : produits, commandes, commande_lignes.

### PHASE 15 -- CMS

Tables : actualites, faq_sections, faq_questions.

### PHASE 16 -- NOTIFICATIONS

Tables : notifications.
Notifications automatiques : Nouvelle facture, Paiement recu, Nouveau contrat, Nouveau document, Rapport valide.

### PHASE 17 -- MEDIAS

Tables : media (Spatie Media Library).

### PHASE 18 -- AUDIT

Tables : historique_activites.
Tracer : Creation, Modification, Suppression, Connexion, Paiement, Facturation.

### PHASE 19 -- DASHBOARDS

**Admin** : CA mensuel, Cours actifs, Heures realisees, Impayes, Top enseignants, Top matieres.
**Enseignant** : Heures effectuees, Revenus, Paiements.
**Parent** : Progression enfant, Factures, Cours suivis.


## Note informelle — Spécification « Bibliothèque numérique » (ajout postérieur a la conception initiale)

> Cette section a ete ajoutee de maniere informelle apres la conception initiale. Elle decrit la spec fonctionnelle cible de la bibliotheque. L'implementation reelle (migrations `2026_07_22_*`, `2026_07_23_*`, `2026_07_24_*`) est tres proche mais presente quelques ecarts (reportes dans `AUDIT_PROJET.md` §3 E7) : table `periode_documents` dediee, media via Spatie Media Library, etc.


BIBLIOTHÈQUE NUMÉRIQUE
DocumentBibliotheque

C'est la table principale.

Attributs
id : bigint

user_id : bigint
(auteur du document)

titre : varchar(255)

slug : varchar(255) unique

description : text

resume : text nullable

type_document_id : bigint

classe_id : bigint nullable

matiere_id : bigint nullable

periode : varchar(50) nullable
(Trimestre 1, Semestre 1...)

visibilite : enum
(public, prive)

statut : enum
(brouillon,
en_attente,
publie,
archive,
refuse)

thumbnail : string nullable

nombre_pages : integer nullable

taille_fichier : bigint

extension : varchar(20)

mime_type : varchar(100)

nombre_vues : integer default 0

nombre_telechargements : integer default 0

nombre_favoris : integer default 0

nombre_notes : integer default 0

moyenne_notes : decimal(3,2)

is_featured : boolean

published_at : timestamp nullable

created_at

updated_at

deleted_at
TypeDocument
id

nom

sigle

icone

couleur

created_at

updated_at

Exemples :

Livre
Fiche de cours
Sujet
Corrigé
Examen
TD
TP
Devoir
Circulaire
Document administratif
Planning
Calendrier
Rapport
Guide
Autre
Tag
id

nom

slug

created_at

updated_at

Exemples :

BAC

Probatoire

Maths

Révision

Analyse

Algèbre

SVT

Physique

DocumentTag

Pivot

document_id

tag_id
DocumentNote

Notation

id

document_id

user_id

note

commentaire nullable

created_at

updated_at

Un utilisateur ne peut noter un document qu'une seule fois.

DocumentCommentaire
id

document_id

user_id

parent_id nullable

contenu

is_visible

created_at

updated_at

Le parent_id permettra les réponses aux commentaires.

FavoriBibliotheque
user_id

document_id

created_at
DocumentAccessLog
id

document_id

user_id nullable

adresse_ip nullable

user_agent nullable

action

created_at

Action :

view

download

preview

share

Même un visiteur non connecté pourra être comptabilisé grâce à son IP.

DocumentSignalement

Pour signaler un document.

id

document_id

user_id

motif

description

statut

created_at

updated_at

Statut

en_attente

traite

rejete
Relations
User
User

1 ------ N DocumentBibliotheque

1 ------ N DocumentNote

1 ------ N DocumentCommentaire

N ------ N FavoriBibliotheque
DocumentBibliotheque
DocumentBibliotheque

N ------ 1 User

N ------ 1 TypeDocument

N ------ 1 Classe

N ------ 1 Matiere

N ------ N Tag

1 ------ N DocumentCommentaire

1 ------ N DocumentNote

1 ------ N FavoriBibliotheque

1 ------ N DocumentAccessLog

1 ------ N DocumentSignalement

1 ------ N Media
TypeDocument
TypeDocument

1 ------ N DocumentBibliotheque
Classe
Classe

1 ------ N DocumentBibliotheque
Matiere
Matiere

1 ------ N DocumentBibliotheque
Tag
Tag

N ------ N DocumentBibliotheque
Commentaires
DocumentCommentaire

N ------ 1 User

N ------ 1 Document

1 ------ N Réponses
Notes
DocumentNote

N ------ 1 User

N ------ 1 Document
Visibilité

Deux niveaux comme tu l'avais souhaité :

PUBLIC

Accessible à tout le monde
Même sans connexion

--------------------------------

PRIVÉ

Accessible uniquement
aux utilisateurs connectés
Cycle de vie
Création

↓

Brouillon

↓

Soumettre

↓

En attente

↓

Validation Admin

↓

Publié

↓

Consultation

↓

Commentaires

↓

Favoris

↓

Téléchargements

↓

Archivage éventuel
Recherche avancée

L'utilisateur pourra rechercher par :

Mot-clé
Auteur
Classe
Matière
Type de document
Période
Date
Plus récents
Plus consultés
Plus téléchargés
Mieux notés
Public uniquement
Documents privés (utilisateurs connectés uniquement)
Tags
Pages principales
Accueil
Barre de recherche
Documents récents
Documents populaires
Les mieux notés
Les plus téléchargés
Catégories
Matières
Classes
Tags
Détail d'un document
Aperçu en ligne (PDF/image)
Description
Auteur
Date
Nombre de vues
Téléchargements
Moyenne des notes
Commentaires
Documents similaires
Bouton Favori
Bouton Télécharger
Bouton Partager
Tableau de bord de l'auteur

Chaque auteur disposera d'un tableau de bord avec :

Mes documents
Brouillons
En attente de validation
Documents publiés
Documents archivés
Nombre total de vues
Téléchargements
Favoris
Moyenne des notes
Derniers commentaires
