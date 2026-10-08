# Conception — Chaîne financière et pédagogique (cœur du système)

> Spécification de référence du chantier **P7 finance**. Elle décrit ce qui existe, ce qui est
> corrigé, et la cible à atteindre. Toute implémentation doit être vérifiable contre ce document.
> Décisions associées : D-048 à D-051 (`docs/DECISIONS.md`).

---

## 1. Périmètre

Chaîne couverte, dans l'ordre des dépendances réelles :

1. Référentiels (classes, matières, types de cours, enseignants)
2. **Périodes comptables** (créées et closes par l'admin)
3. **Contrats de cours + affectations** (élève × enseignant × matière, taux horaire)
4. **Planning** de l'enseignant
5. **Objectifs pédagogiques** (remodelés)
6. **Cahier de texte** de l'enseignant (séances, heures)
7. **Rapport mensuel** de l'enseignant (ventilation des heures par matière)
8. **Facture parent**
9. **Bulletin de paie enseignant** (et versement)
10. **Notifications** (base + email + push)

Hors périmètre : facturation de la plateforme vers les cabinets (P8, chaîne `facture_cabinets`
dormante D-002), paie avec cotisations sociales, comptabilité générale, TVA.

---

## 2. État actuel (inventaire 30/09/2026)

### 2.1 Ce qui existe et est réutilisable tel quel

La logique métier KEduc est **complète et correcte dans ses intentions** :

| Domaine | Fichiers |
|---|---|
| Facturation parent | `Modules/Finance/Services/FacturationService.php` (436 l.), `FacturePdfService.php` |
| Paie enseignant | `Modules/Finance/Services/BulletinPaie{Calculation,Generation,Validation,Adjustment,Pdf}Service.php`, `BulletinPaieController`, `Enseignant\BulletinPaieController` |
| Périodes | `Modules/Finance/Services/PeriodeComptableService.php`, `PeriodeComptableControllerWeb` |
| Pédagogie | `Modules/Pedagogie/Services/{CahierTexte,ObjectifPedagogique,RapportMensuelCalculator,RapportMensuel,RapportMensuelPdf,ContratCours,PlanningCours}Service.php` |
| Modèles | 20 modèles dans `app/Models/` (`Facture`, `LigneFacture`, `BulletinPaie*`, `PeriodeComptable`, `CahierTexte`, `RapportMensuelEnseignant`, `ObjectifPedagogique`, `ObjectifMatiere`, `AffectationEnseignant`, `ContratCours`, `PlanningCours`) |
| Notifications | `Modules/Systeme/Services/{NotificationService,NotificationDispatcher}.php` — 17 déclencheurs métier déjà câblés, dont 6 pour le bulletin de paie |
| PDF | 4 services DomPDF + vues `resources/views/pdf/`, `resources/views/pedagogie/rapport-mensuel/` |

### 2.2 Pourquoi ce n'est pas livré

- ~~Aucune route API~~ — **corrigé en T7A.1** : `api/finance/periodes` est livré (CRUD + `close` /
  `reopen`), et `api/pedagogie/*` en T7A.2. Le reste du cycle (factures, bulletins, contrats) reste
  web seulement, lui-même sous l'interrupteur `keduc.web` **désactivé** (D-037) → 404 tant que
  T7A.5 à T7A.9 n'ont pas produit leurs API.
- **Rôles incohérents** : le flux attend `admin` (`hasRole('admin')`, `role:admin`) alors que le
  seeder tenant crée `admin_cabinet`, `enseignant`, `parent`, `eleve`, `gestionnaire_librairie`
  (`TenantDatabaseSeeder.php:31-35`). Dans une base cabinet réelle, **toutes les branches admin de
  ces policies sont fausses** et les routes `role:admin` répondent 403.
- **Aucun test** : ni `FacturationService`, ni `BulletinPaie*`, ni `PeriodeComptable` n'est testé.
  Les tests KEduc existants (`tests/Feature/Legacy/`) sont exclus de la suite (D-026) → la chaîne
  financière n'a **aucune couverture**.
- **Code mort** : 5 contrôleurs orphelins, dont `CahierTexteController` (appelle
  `CahierTexteService::listByAffectation()` — méthode inexistante) et `RapportMensuelController`
  (passe `Auth::id()` = `users.id` à un service qui attend `enseignant_profils.id`).

### 2.3 Défauts métier à corriger (constatés)

| # | Défaut | Emplacement |
|---|---|---|
| 1 | Heures d'un enseignant comptées **autant de fois qu'il a de matières** sur le contrat | `FacturationService` :105/111, :251/258 (`keyBy('enseignant_id')` puis boucle sur les affectations) |
| 2 | Numéros `FAC-YYYYMM-NNNN` / `BP-YYYYMM-NNNN` par `count()+1`, **sans verrou** | `FacturationService:425-435`, `BulletinPaieGenerationService:261-271` |
| 3 | Pas d'index unique `(contrat_cours_id, periode_id)` sur `factures` ; anti-doublon par `exists()` non verrouillé | `FacturationService:203-214` |
| 4 | Aucun gel de période : facture et bulletin générables sur une période **clôturée** | `PeriodeComptableService` (aucun garde) |
| 5 | Aucun garde dans `marquerPaye()` : double paiement possible, le commentaire de paiement écrase le commentaire admin | `FacturationService:396-419` |
| 6 | Aucune fenêtre de dépôt du rapport mensuel : dépôt sur période close, future, ou hors contrat | `RapportMensuelService:26-96`, `StoreRapportMensuelRequest` |
| 7 | Un rapport **rejeté est définitif** : `ensureReportDoesNotExist` refuse toute recréation du triplet | `RapportMensuelService:204-231` |
| 8 | `verifierPrerequis` renvoie « OK » sans affectation active, puis `generer()` échoue | `FacturationService:40-42` vs `:239-244` |
| 9 | Chaîne `paiement_enseignants` concurrente des bulletins (heures du cahier, statut `paye` immédiat) | `PaiementEnseignantService` |
| 10 | Statuts morts : `partiel` jamais écrit ; `statut_paiement_enseignants='paye'` jamais écrit (`tousEnseignantsPayes()` toujours `false`) | `Models/Facture.php:84-92` |
| 11 | Objectifs par `élève × période` (texte libre), sans enseignant ni contrat ; `update()` supprime et recrée les lignes (perte des moyennes obtenues) | `ObjectifPedagogiqueService:223-251` |
| 12 | `valide_admin` (cahier) et `uuid_client` (hors-ligne) jamais écrits | `cahier_textes` |
| 13 | Saisie de séance **passée impossible** (`after_or_equal:today`), date non modifiable | `StoreCahierTexteRequest:25-29` |
| 14 | Notifications : base uniquement (ni email ni push) ; `getUrlAttribute()` pointe vers des routes web mortes | `Notification.php:189-236`, `NotificationDispatcher` |
| 15 | PDF : `isRemoteEnabled => true` (SSRF), chemin serveur renvoyé en JSON, collision de noms de fichiers, vue Blade `pdf/cahiers-textes/form` manquante | `RapportMensuelPdfService`, `CahierTextePdfService` |

> Ce tableau est l'instantané de l'audit du 30/09 : les lignes y restent même une fois corrigées,
> pour garder la trace de ce qui a été trouvé. Suivi des corrections :
> **#1** et #9 → D-048/D-049 ; **#2** → numérotation atomique à verrou (T7A.0) ; **#4** → gel des
> périodes D-051 ; **#5** et #10** → garde-fou de double paiement, `partiel` supprimé (T7A.0) ;
> **#12** → `valide_admin` supprimé, `uuid_client` câblé (T7A.5) ; **#13** → la saisie dans le passé
> est réautorisée, seule la date future est refusée (§6.1bis).

---

## 3. Décisions prises

| Réf | Décision |
|---|---|
| **D-048** | **Une seule chaîne de paie enseignant : les bulletins de paie.** La chaîne applicative `paiement_enseignants` (service, contrôleur, routes) est supprimée et les tables sont **archivées** (`*_archive`) : plus aucune écriture, les versements passés restent lisibles. Le retrait définitif des tables interviendra après exploitation de l'archivage. Le versement devient une action sur le bulletin (`genere → consulte → valide → verse`). Une seule source de vérité des heures : le **rapport mensuel validé**. |
| **D-049** | **Ventilation des heures par matière.** Le rapport mensuel persiste une ligne par affectation (matière) : `rapport_mensuel_enseignant_lignes (rapport_id, affectation_enseignant_id, matiere_id, nombre_seances, nombre_heures)`. La source reste `cahier_textes` sur la période. La facture et le bulletin consomment **ces lignes** : plus aucun double comptage, montants identiques entre les deux, et égalité vérifiable avec une facture manuelle. |
| **D-050** | **Objectifs pédagogiques remodelés** : une ligne par (élève, période comptable, enseignant), FK `periode_id` → `periode_comptables` et `enseignant_id` → `enseignant_profils` ; le champ texte `periode` disparaît ; unicité `(eleve_id, periode_id, enseignant_id)`. La ventilation par matière est conservée, en mise à jour sans destruction. |
| **D-052** | **Fin du cycle de paie.** Le versement se fait hors plateforme : l'enseignant **confirme la réception** (`date_reception`/`recu_par`), ce qui clôt le cycle et notifie l'administration. Un bulletin versé non réceptionné reste **en attente de réception**. La contestation est **motivée par catégorie** (liste fermée de 7 motifs) plus un détail libre. Les notifications de bulletin sont cliquables et redirigent vers l'espace du lecteur. |
| **D-051** | **Cycle de validation au rapport mensuel.** L'enseignant saisit ses séances librement et dépose son rapport ; l'admin **valide** ou **rejette avec motif** ; seules les données validées alimentent facture et bulletin. Dépôt et saisie **uniquement sur une période ouverte** ; une période close **gele** les écritures, toute régularisation passe par l'admin. Un rapport rejeté est **modifiable et re-soumissible**. |

---

## 4. Procédure cible

```
   [admin]  type_cours ─┐
   [admin]  matieres ───┼──► enseignant_matiere ──► contrat_cours ──► affectation_enseignants
   [admin]  classes ────┘                             (taux horaire)        │
                                                                            │
   [admin]  periode_comptables (ouverte) ───────────────────────────────┤
          │                                                                 │
          │   [enseignant]  planning_cours                                 │
          │   [enseignant]  objectifs_pedagogiques (D-050)                 │
          │   [enseignant]  cahier_textes  (heures, séances)               │
          │                              │                                 │
          │                              ▼                                 │
          │   [enseignant]  rapports_mensuels  ──── lignes (D-049) ───────┤
          │                              │ statut soumis                    │
          │                              ▼                                 │
          │   [admin]  validation  ──►  statut validé ──────────┐           │
          │                                    │                │           │
          │                                    ▼                ▼           │
          │   [admin]  factures (parent) ◄──────┘      bulletins_paie ─────┘
          │                  │                                │
          │                  ▼                                ▼
          │   [parent] règlement        [admin] versement     [enseignant] consulter
          │                                                             / valider / contester
          └──────────────── notifications (base + email + push) ───────────────┘
```

**Invariants** (à tester un par un) :

1. Une facture et un bulletin ne se calculent que sur des rapports `valide`.
2. `somme(lignes du rapport) == volume_horaire_cumule`.
3. `somme(lignes de facture) == montant_total − frais_suivi − autres_frais + remise`.
4. `somme(lignes de bulletin) == montant_brut`.
5. Un même `(contrat, période)` ne peut donner qu'une facture ; un même `(enseignant, période)` qu'un bulletin.
6. Une période close n'accepte plus aucune écriture pédagogique.
7. Deux lignes de rapport portant la même affectation ne peuvent coexister (unicité § 5.1).

---

## 5. Modèle de données cible

### 5.1 Nouvelles tables (migrations **tenant**)

**`rapport_mensuel_enseignant_lignes`** (D-049)
| Colonne | Type |
|---|---|
| `id` | PK |
| `rapport_mensuel_enseignant_id` | FK → `rapport_mensuels_enseignants`, cascade, index |
| `affectation_enseignant_id` | FK → `affectation_enseignants`, cascade, index |
| `matiere_id` | FK → `matieres`, cascade |
| `nombre_seances` | integer, défaut 0 |
| `nombre_heures` | decimal(5,2), défaut 0 |
| timestamps | — |
| **unique** | `(rapport_mensuel_enseignant_id, affectation_enseignant_id)` |

Modèle : `App\Modules\Pedagogie\Models\RapportMensuelEnseignantLigne` (relation `belongsTo AffectationEnseignant`).

### 5.2 Tables modifiées

| Table | Modification | Réf |
|---|---|---|
| `objectif_pedagogiques` | **+** `periode_id` (FK, index), **+** `enseignant_id` (FK, index), **−** `periode` (string), **+** unique `(eleve_id, periode_id, enseignant_id)` ; backfill `periode_id` depuis la période dont la date est la plus proche | D-050 |
| `factures` | **+** unique `(contrat_cours_id, periode_id)` | §2.3-3 |
| `factures` | `statut_paiement` : `en_attente` → `payee` ; **`partiel` supprimé** ; garde-fou de double paiement | §2.3-5, -10 |
| `rapport_mensuels_enseignants` | **+** `motif_rejet` (text, nullable), **+** `date_validation` (timestamp, nullable), **+** `valide_par` (FK users, nullable) | D-051 |
| `periodes_comptables` | **+** `cloturee_par` (FK users), **+** `cloturee_at` ; index sur `statut` et `date_debut` | D-051 |
| `cahier_textes` | **−** `valide_admin` (code mort) ; `uuid_client` unique **câblé** (T7A.5 — idempotence hors-ligne D-060, violation de contrainte rattrapée) | §2.3-12, D-060 |

### 5.3 Tables supprimées (D-048)

`paiement_enseignants`, `ligne_paiement_enseignants` — et leurs services, contrôleurs, routes,
politiques, entrées de menu. Le versement est porté par `bulletins_paie`
(`date_paiement`, `mode_paiement`, `reference_paiement`, `statut = 'verse'`).

---

## 6. Règles métier et machines à états

### 6.1 Période comptable
`ouverte` ⇄ `clôturée`.
- Création, modification, clôture, réouverture : `admin_cabinet` uniquement.
- La clôture est **bloquante** : elle vérifie qu'aucune écriture pédagogique ni financière n'est en
  cours sur la période et journalise l'auteur. Elle n'annule rien, elle ferme.
- Écritures refusées sur période close : séance de cahier, objectif, dépôt de rapport, génération
  de facture, génération de bulletin, ajout d'ajustement, versement.

### 6.1bis Séance de cahier de texte (T7A.5)

La saisie est la **seule écriture pédagogique** antérieure au rapport mensuel : c'est elle qui
alimente `RapportMensuelCalculator`, donc la facture et le bulletin. Elle est donc soumise aux mêmes
verrouillages que la période.

- **Périmètre** : l'enseignant saisit sur ses seules affectations actives ; la propriété est
  vérifiée dans le service (`User` explicite), pas seulement dans la policy.
- **Gel** : `GardePeriodeOuverte` refuse saisie, correction et suppression hors période ouverte —
  et une date qui n'appartient à **aucune** période, dont ces heures ne rejoindraient aucun rapport.
- **Passé oui, futur non** : rattraper une séance oubliée est un usage normal ; des heures qui n'ont
  pas eu lieu ne peuvent pas gonfler une facture.
- **Date immuable** : `date_seance` n'est pas modifiable (une date erronée se corrige en supprimant
  et ressaisissant, ce que la période ouverte autorise). `affectation_enseignant_id` est refusée en
  modification : changer de cours ferait porter les heures à une autre ligne de rapport (D-054).
- **Correction le jour même seulement** : passé le jour de la séance, seul l'admin peut régulariser
  via le rapport mensuel.
- **Idempotence** : `uuid_client` unique (D-060). Reprise à `200` (pas `201`), `422` si l'uuid
  appartient à un autre enseignant, violation de contrainte rattrapée en cas de réémissions
  simultanées.
- **Suppression** : autorisée tant que la période est ouverte. Aucune validation individuelle
  (`valide_admin`) n'existe — la validation est portée par le rapport mensuel (D-051), donc
  supprimer une séance validée n'est pas une opération distincte.

### 6.2 Rapport mensuel
```
soumis ──valider──► validé ──────────────────────────► (consommé par facture et bulletin)
   │                    
   └──rejeter(motif)──► rejeté ──(enseignant corrige)──► soumis
```
- Seul l'enseignant affecté au contrat dépose ; pour **chacun** de ses contrats.
- Le dépôt exige une période ouverte, et un `contrat_cours` actif de l'enseignant.
- Le volume n'est **pas** saisi : il est calculé (`RapportMensuelCalculator`) et la ventilation par
  matière est recalculée au dépôt et à chaque correction.
- Un rapport `validé` ou `rejeté` n'est plus modifiable par l'enseignant ; le modifier (admin) le
  repasse en `soumis` et **invalide les factures et bulletins déjà générés** (avertissement explicite).

### 6.3 Facture parent
```
[génération] ──► en_attente ──règlement──► payee
                   (et : payee ──règlement──► refus 422 FACTURE_DEJA_PAYEE)
```
- Génération : `(contrat actif, période ouverte, tous les enseignants actifs ont un rapport validé)`.
- Montants **exclusivement** dérivés des lignes de rapport validé du même contrat et de la même
  période ; `volume_horaire_total` = somme des lignes.
- Frais : `frais_suivi`, `autres_frais`, `remise` (entiers FCFA) ; `remise` plafonnée au sous-total.
- Le règlement enregistre `date_paiement`, `mode_paiement`, `reference_paiement` et **n'écrase pas**
  le commentaire administratif.
- `statut_paiement_enseignants` : supprimé ( meaningless sans la chaîne paiements).

### 6.4 Bulletin de paie
```
genere ──consulter──► consulte ──valider──► valide ──verser──► verse
                          │                    ▲
                          └──contester──► contesté ──corriger──► corrige ──┘ (→ consulte)
```
- Génération : pour chaque enseignant ayant au moins un rapport `validé` sur la période.
- Lignes = lignes de rapport validé, `montant = nombre_heures × taux_horaire_enseignant`.
- `montant_net = montant_brut − frais_suivi + crédits − débits` (ajustements typés).
- **Verrouillage** : plus aucune modification quand `statut = 'verse'`.
- Versement : date, mode, référence ; notifie l'enseignant.

### 6.5 Numérotation atomique
`FAC-YYYYMM-NNNNN` et `BP-YYYYMM-NNNNN`, allocations sous verrou de ligne sur la table
(`lockForUpdate` sur le dernier numéro du mois + `unique` en base, retry sur violation). Plus de
`count()+1`.

---

## 7. API cible (conventions D-040/D-042 : `App\Modules\<M>\Http\Controllers\Api\*ApiController`,
Resources dans `app/Http/Resources/Api/`, pagination `{data, meta}`)

**Admin cabinet** (`auth:web` + `role:admin_cabinet`) :
```
/api/admin/finance/periodes                          GET|POST
/api/admin/finance/periodes/{periode}                 GET|PUT|DELETE
/api/admin/finance/periodes/{periode}/cloturer        POST
/api/admin/finance/periodes/{periode}/reouvrir        POST
```
> **Écart assumé (T7A.1)** : l'API livrée est préfixée `/api/finance/periodes` et non
> `/api/admin/finance/periodes` ; les actions de cycle sont `PATCH .../close` et `PATCH .../reopen`
> plutôt que `POST .../cloturer`. Le préfixe `/admin` du plan initial reservait un jour un espace
> d'administration distinct du métier — l'examen des conventions D-040 montre qu'il est déjà porté par
> `middleware('role:admin_cabinet')`, donc le redoublon serait trompeur. Les verbes d'action sont en
> `PATCH` : ces transitions changent l'état d'une ressource existante, elles ne créent rien.

**Référentiels pédagogiques** (T7A.2, même middleware) :
```
/api/pedagogie/classes                                GET|POST
/api/pedagogie/classes/{classe}                       GET|PUT|DELETE   (409 si utilisée)
/api/pedagogie/matieres                               GET|POST
/api/pedagogie/matieres/{matiere}                     GET|PUT|DELETE   (409 si utilisée)
/api/pedagogie/type-cours                             GET|POST
/api/pedagogie/type-cours/{type_cours}                GET|PUT           (pas de DELETE)
/api/pedagogie/type-cours/{type_cours}/activer         PATCH
/api/pedagogie/type-cours/{type_cours}/desactiver      PATCH
/api/pedagogie/enseignants                            GET|POST
/api/pedagogie/enseignants/{enseignant}               GET|PUT           (jamais de DELETE)
```

**Contrats & affectations** (T7A.3, même middleware — D-054) :
```
/api/pedagogie/contrats                               GET|POST
/api/pedagogie/contrats/{contrat}                     GET|PUT           (jamais de DELETE)
/api/pedagogie/contrats/{contrat}/statut              PATCH             (suspendu | termine)
/api/pedagogie/contrats/{contrat}/affectations        POST
/api/pedagogie/contrats/{contrat}/affectations/{affectation}            PATCH
/api/pedagogie/contrats/{contrat}/affectations/{affectation}/statut    PATCH
```

**« Mes cours »** (T7A.3, `role:enseignant|eleve`) — le filtrage est déduit du profil
du connecté, jamais d'un paramètre de requête : impossible de voir les cours d'un
autre enseignant en forçant `?enseignant_id=`.
```
/api/mes-cours                                         GET
```

**Planning** (T7A.4 — D-055) :
```
/api/pedagogie/planning                                GET|POST
/api/pedagogie/planning/{planningCours}               PATCH|DELETE
```

`GET /api/pedagogie/planning` renvoie trois blocs : `mes_creneaux` (créneaux du
connecté), `creneaux_partages` (créneaux des autres enseignants **du même contrat**)
et `affectations` (ses affectations actives, pour alimenter le formulaire).

`DELETE` est ici **autorisé** — c'est la seule exception à D-054 sur ce module :
`planning_cours` n'est référencée par aucune autre table. Un créneau est une
intention de cours, pas une écriture comptable ; un contrat ou une affectation, si.

Le conflit horaire est vérifié à l'écriture sur les **intervalles**, et non sur
l'égalité des heures de début, des deux côtés :

| Règle | Portée | Message |
|---|---|---|
| L'enseignant est déjà pris | toutes ses affectations | « Vous avez déjà un cours de … auprès d'un **autre élève** » |
| L'élève est déjà en cours | **tous ses contrats** | « Cet élève a déjà cours de … » / « … **avec un autre enseignant** » |

Deux créneaux qui se touchent (`18h–19h` puis `19h–20h`) ne sont pas en conflit :
une journée continue est un cas normal. Le message distingue le cas « même
enseignant » du cas « autre enseignant », sinon il serait faux dans un des deux.

**Consultation** (`role:eleve|parent`, périmètre déduit du profil) :
```
/api/mes-planning                                      GET
```

| Lecteur | Voit |
|---|---|
| Enseignant | ses créneaux + ceux des enseignants du **contrat commun** |
| Parent | le planning de **ses enfants**, même si le compte de l'enfant est inactif |
| Élève | son planning **uniquement si son compte est activé** (`eleves.statut` ET `users.statut`) |

L'activation est relue **à chaque appel** : `AuthService` ne bloque que la
*connexion*, donc sans cette relecture une révocation de compte ne prendrait effet
qu'à la prochaine reconnexion. L'enseignant ne peut écrire que ses propres
créneaux — la propriété est vérifiée dans le contrôleur (403), pas seulement dans
le formulaire.

**Cahier de texte** (T7A.5 — D-060, `role:enseignant` pour l'écriture) :
```
/api/enseignant/cahiers-textes                        GET|POST
/api/enseignant/cahiers-textes/{cahierTexte}          GET|PUT|DELETE
/api/enseignant/cahiers-textes/{cahierTexte}/pdf      GET
/api/enseignant/cahiers-textes/affectations            GET
```

**Consultation par la famille** (`role:eleve|parent`, périmètre déduit du profil) :
```
/api/mes-enfants                                      GET
/api/mes-enfants/{eleve}/cahiers-textes               GET
/api/mes-enfants/{eleve}/cahiers-textes/historique-pdf GET
```

`GET /api/mes-enfants` est le **sélecteur d'enfant** des écrans à portée enfant :
pour un parent il renvoie ses enfants, pour un élève **lui-même** — toujours dans
une liste, afin que le frontend n'ait pas deux formes de réponse selon le rôle.
`eleve.id` y est un `eleves.id` et non un `users.id` (D-059) : la confusion
renvoie 403, mais les identifiants des deux tables se chevauchent souvent, donc
l'erreur ne serait pas toujours visible.

Le statut HTTP porte une information que le corps ne porte pas : `201` à la
création, `200` à la reprise idempotente d'un `uuid_client` déjà connu, `422`
si cet uuid appartient à un autre enseignant. Voir §6.1bis pour les règles
d'écriture et D-060 pour l'idempotence.

### Écrans Angular (P5)

| Écran | Route | Rôles | Fichier |
|---|---|---|---|
| Planning | `/espace/modules/planning` | `enseignant` (édition), `parent`, `eleve` (lecture) | `espace/pages/planning.component.ts` |
| Périodes comptables | `/espace/finance/periodes` | `admin_cabinet` | `espace/pages/periodes.component.ts` |
| Référentiels | `/espace/pedagogie/referentiels` | `admin_cabinet` | `espace/pages/referentiels.component.ts` |
| Contrats & affectations | `/espace/pedagogie/contrats` | `admin_cabinet` | `espace/pages/contrats.component.ts` |
| Mes cours | `/espace/modules/mes-cours` | `enseignant`, `eleve` | `espace/pages/mes-cours.component.ts` |

Points de conception retenus (D-056) :

- **Toutes les routes réelles précèdent les placeholders génériques.** Une route
  `path: 'finance'` matche *par préfixe* : déclarée avant `finance/periodes`,
  elle avale le chemin et l'écran n'est jamais atteint. L'ordre du tableau
  ci-dessus n'est donc pas cosmétique, il est fonctionnel — c'est l'ordre de
  résolution d'Angular, pas une question de priorité.
- **Une grille, trois usages.** Le planning est un seul composant qui lit
  l'enveloppe correspondant au rôle (`mes_creneaux` / `eleves[]` /
  `compte_actif`) : une seule implémentation du calendrier, différenciée par les
  données, pas trois calendriers qui divergeraient.
- **Deux lisibilités dans la même grille** : les créneaux de l'enseignant sont
  cliquables (bordure orange pleine), ceux des collègues sont en pointillés bleus
  et non modifiables. Le tri se fait dans `grouperParJour()`, appelé une fois par
  grille via `@let`.
- **Responsive** : sept colonnes au-dessus de 900 px, liste empilée par jour en
  dessous — des colonnes de jours sont illisibles sur téléphone.
- **Erreurs 422 sous le champ** : `heure_debut` porte le message de conflit
  horaire (D-055), `label` porte l'intitulé dupliqué (D-051).
- **Conséquences énoncées** avant clôture/réouverture d'une période, et édition
  désactivée (avec infobulle) sur une période close.
- **Un état vide doit se calculer sur la même source que la grille.** Le planning
  compte `creneauxAffiches` (propres + partagés) et non les seuls créneaux
  propres : un enseignant qui n'a que des cours en commun — cas réel d'un
  remplaçant — se serait vu afficher un état vide alors que la
  grille affiche des lignes.
- **Le texte doit décrire la réalité du serveur, pas l'intention.** Le bandeau
  « compte élève inactif » affirmait que l'élève pouvait « continuer à se
  connecter » alors que l'écran est précisément masqué pour cette raison ;
  `AuthService` bloque par ailleurs la connexion sur `eleves.statut`.
- **Une union typée, pas une intersection.** `GET /api/mes-planning` renvoie
  `{eleves}` au parent et `{compte_actif, creneaux}` à l'élève : l'intersection
  des deux interfaces laisserait croire que chaque réponse porte les deux clés,
  et la lecture `data.eleves ?? []` masquerait une régression du contrat.
- **« Mes cours » : un écran, deux lectures.** L'enseignant voit ce qu'il
  *donne* (ses contrats, ses élèves, les heures prévues), l'élève ce qu'il
  *suit* (le même contrat, la matière, l'enseignant en face). Même donnée, deux
  questions : d'où un composant unique qui change ses libellés plutôt que deux
  écrans qui divergeraient. Aucun filtre n'est proposé — le périmètre est déjà
  déduit du profil connecté côté serveur, un sélecteur « par enseignant » n'y
  donnerait que l'illusion d'un choix inexistant. Le parent en est exclu.
- **Recherche insensible aux accents et aux jokers.** `App\Support\Recherche`
  applique `LOWER`, replie les caractères accentués via `translate()` et échappe
  `%`/`_`/`\` : chercher « mathematiques » trouve « mathématiques », et un
  utilisateur ne peut plus provoquer un balayage complet en saisissant `%`.

```
/api/admin/contrats                                   GET|POST
/api/admin/contrats/{contrat}                         GET|PUT
/api/admin/finance/factures                           GET
/api/admin/finance/factures/prerequis                 POST   (contrat + période → manquants)
/api/admin/finance/factures/previsualisation          POST
/api/admin/finance/factures                           POST
/api/admin/finance/factures/{facture}                 GET
/api/admin/finance/factures/{facture}/regler          POST
/api/admin/finance/factures/{facture}/pdf             GET
/api/admin/finance/bulletins-paie                     GET
/api/admin/finance/bulletins-paie/previsualisation    POST
/api/admin/finance/bulletins-paie                     POST
/api/admin/finance/bulletins-paie/{bulletin}          GET
/api/admin/finance/bulletins-paie/{bulletin}/ajustements   POST
/api/admin/finance/bulletins-paie/{bulletin}/ajustements/{ajustement}  DELETE
/api/admin/finance/bulletins-paie/{bulletin}/verser   POST
/api/admin/finance/bulletins-paie/{bulletin}/pdf      GET
/api/admin/pedagogie/rapports-mensuels                GET
/api/admin/pedagogie/rapports-mensuels/{rapport}      GET
/api/admin/pedagogie/rapports-mensuels/{rapport}/valider  POST
/api/admin/pedagogie/rapports-mensuels/{rapport}/rejeter   POST
```

**Enseignant** (`auth:web` + rôle `enseignant`) :
```
/api/enseignant/cours                        GET
/api/enseignant/cahiers-textes                GET|POST
/api/enseignant/cahiers-textes/{cahier}      GET|PUT
/api/enseignant/cahiers-textes/eleves/{eleve}/historique-pdf   POST
/api/enseignant/objectifs                    GET|POST
/api/enseignant/objectifs/{objectif}         PUT|DELETE
/api/enseignant/rapports-mensuels             GET|POST
/api/enseignant/rapports-mensuels/previsualisation   POST
/api/enseignant/rapports-mensuels/{rapport}   GET|PUT
/api/enseignant/rapports-mensuels/{rapport}/pdf     GET
/api/enseignant/mes-bulletins                 GET
/api/enseignant/mes-bulletins/{bulletin}      GET
/api/enseignant/mes-bulletins/{bulletin}/consulter  POST
/api/enseignant/mes-bulletins/{bulletin}/valider    POST
/api/enseignant/mes-bulletins/{bulletin}/contester  POST
/api/enseignant/mes-bulletins/{bulletin}/pdf        GET
```

**Parent** (`auth:web` + rôle `parent`) : `/api/mes-factures`, `/api/mes-factures/{facture}`,
`/api/mes-factures/{facture}/pdf`, `/api/mes-enfants/{eleve}/cahiers-textes/historique-pdf`.

---

## 8. Notifications

Les 17 déclencheurs de `NotificationDispatcher` sont conservés et étendus :
`facture`, `paiement_enseignant` (→ `bulletin_paie`/`versement`), `rapport`, `bulletin_paie`.

Corrections : URLs pointant vers les **routes API/Angular** et non vers les routes web ; canal
`mail` en plus de la base pour les événements financiers (facture créée, règlement, bulletin versé,
rapport validé/rejeté) ; push (D-041) dès les clés VAPID disponibles en P6.

---

## 9. PDF

Inchangés dans leur contenu (4 services, vues existantes), corrigés sur : `isRemoteEnabled => false`,
aucun chemin serveur exposé en JSON, noms de fichiers incluant le contrat/période pour éviter les
collisions, en-tête `Content-Disposition` échappé, vue `pdf/cahiers-textes/form` complétée.

---

## 10. Tests

État au 03/10/2026 : **suite verte** (`php artisan test`, base `keduc_test`).

| Niveau | Couverture livrée | Reste |
|---|---|---|
| T7A.1 Finance | `PeriodeComptableApiTest` (8) | facturation, numérotation |
| Invariants §4 | `ReglesMetierFinanceTest` (29) — chevauchement de périodes, objectifs, dates de séance, **facturation** et **cycle de paie** (réception, contestation, notification) | ajustements, versement |
| T7A.2 Référentiels | `ReferentielApiTest` (13) | import CSV enseignants (M1) |
| T7A.3 Contrats | `ContratAffectationApiTest` (16) | — |
| T7A.4 Planning | `PlanningApiTest` (16) | hors-ligne (T4.5) |
| T7A.5 Cahier de texte | `CahierTexteApiTest` (27) — idempotence **y compris en course**, collision d'uuid entre collègues, périmètre et propriété, gel de période, immuabilité de la date et de l'affectation, historique parent/élève, PDF | hors-ligne réel (T4.5) |
| T7A.5 Identité parent | `IdentiteParentTest` (3) — planning, cahier de texte et sélecteur d'enfant, sur deux familles aux identifiants **divergents** (D-059) | — |
| Isolation | `TenantIsolationTest` (4) — chaque test tourne en base tenant `keduc_test_<slug>` | — |
| Conventions | `ApiConventionsTest` (7) — 401/403/404/429/405 + complétude OpenAPI (73 paths) | — |
| Métier à venir | — | `RapportMensuelTest`, `ObjectifPedagogiqueTest` |

Deux règles issues de l'expérience (D-057, D-058), à appliquer aux tests
suivants :

- **Un paramètre que personne ne teste est un paramètre qui ne fonctionne
  pas.** `par_page` / `per_page` divergeait sans qu'aucun test ne le remarque,
  parce que tous les tests s'appuyaient sur la valeur par défaut. Tester une
  valeur **non nulle** (`?per_page=2` → `meta.per_page = 2`), jamais seulement
  l'absence de paramètre.
- **Tester la valeur renvoyée, pas seulement son nombre.** Un état vide doit
  être vérifié sur un cas qui le distingue vraiment d'un cas vide légitime
  (créneaux partagés vs créneaux propres, matière utilisée vs matière orpheline).
- **Un test de concurrence doit fabriquer une vraie transaction indépendante.**
  Simuler la course en écrivant le « concurrent » sur la connexion du service ne
  prouve rien : la transaction du service l'emporte et la ligne disparaît au
  rollback, si bien que le test passe alors que le garde-fou n'a jamais été
  exercé. `test_course_deux_reemissions_simultanees` ouvre une seconde
  connexion PDO en autocommit, seule façon d'obtenir un commit indépendant
  sans threading.
- **Un test doit échouer sur le bug qu'il prétend couvrir.** Les identifiants
  `users.id` et `parent_profils.id` divergeaient déjà sans qu'aucun test ne le
  voie, parce que le fixture n'en créait qu'une seule, où les deux identifiants
  sont forcément égaux. `IdentiteParentTest` en construit **deux** (D-059).

---

## 11. Risques et points d'attention

1. **Migrations à risque** (`paiement_enseignants` archivée, `objectif_pedagogiques` recomposée,
   `valide_admin` retiré) : le propriétaire les exécute lui-même (AGENTS.md) ; un `cabinet:migrer-tous`
   sera nécessaire sur le cabinet de dev.
2. **Backfill des objectifs** : la correspondance texte → période comptable est ambiguë ; les
   objectifs existants sans correspondance seront rattachés à la période contenant leur création,
   les autres sont signalés.
3. **Double emploi avec le web KEduc gelé** : les contrôleurs web de `finance.php`/`pedagogie.php`
   deviennent redondants. Décision : ils sont **gelés** (D-037) et retirés au lot 11, pas supprimés
   silencieusement (convention permanente n° 3).
4. **Notifications en transaction** : elles sont écrites dans la transaction métier ; un rollback les
   annule. Acceptable ici (l'utilisateur veut la notification *si* l'opération a réussi).
5. **VAPID** : le push reste bloqué tant que les clés ne sont pas générables (Windows) — D-041.
