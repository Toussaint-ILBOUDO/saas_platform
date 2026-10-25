# QUESTIONS — Ambiguïtés et décisions du projet Saas_plateforme

> Convention (CONCEPTION.md §0.9) : toute ambiguïté est notée ici avec l'**option par défaut**, appliquée, puis continuée.
> Cette première section regroupe les questions posées au propriétaire lors du cadrage (24/09/2026) et **validées**.

---

## 1. Réponses du propriétaire au cadrage (24/09/2026)

### BLOQUANTES (tranchent P0/P1)

| # | Question | Réponse validée |
|---|---|---|
| **B1** | Organisation du dépôt (Laravel à la racine vs `backend/`) | **(a) Restructurer** : déplacer le code Laravel dans `backend/`, créer `frontend/` frère. **Fait en P0.** |
| **B2** | Facturation plateforme : schéma KEduc (`facture_cabinets` + commissions 2000/3000/10 %) vs conception §7.5/7.8 | **(a) Refonte complète selon conception §7.5/7.8** en base centrale (lignes debit/credit, `montant_paye`, statuts, `inscriptions_facturables`, tarifs via `parametres_cabinet`). L'ancien module KEduc est **ignoré/dormant** (référence historique). |
| **B3** | Stratégie de tests multi-cabinets | **(a) Tout en PostgreSQL** : `keduc_test` = base centrale de test ; bases tenants de test dynamiques (`keduc_test_<c1|c2>`) créées par les tests via le compte `postgres` (stancl `RefreshTenantDatabase`). |
| **B4** | Devenir des vues et routes web KEduc | **(a) Gelées** : référence uniquement, **non servies sur les domaines cabinets**. Le Landlord gagne un interrupteur « accès écrans web Keduc » (activation/ désactivation, voir T2.10 de PLAN.md). |

### NON BLOQUANTES (par thème)

| # | Question | Réponse validée |
|---|---|---|
| Q1 | ID de cabinet | Oui : **id = slug** (`id_generator => null` dans stancl, id fourni manuellement). |
| Q2 | Domaines de dev | `cabinet1.localhost` (tenant) + `admin.localhost` (Landlord). |
| Q3 | Rôles tenant | 5 rôles : `admin_cabinet`, `enseignant`, `parent`, `eleve`, `gestionnaire_librairie` (= `admin`, `gestionnaire` historiques). **Plus de rôle `super-admin` côté tenant** (le super-admin vit uniquement en base centrale). |
| Q4 | Comptes PostgreSQL | Un seul compte applicatif `postgres` (superuser local, `CREATEDB`). Pas d'identifiants par cabinet. |
| Q5 | Style API | Routes en français (`/api/auth/connexion`...), **pas de versioning** `/api`. |
| Q6 | Documentation API | `dedoc/scramble` ajouté en P3. |
| Q7 | Format d'erreur | `{message, code, erreurs}` + code `CABINET_SUSPENDU` (403). |
| Q8 | Stack frontend | Workspace Angular vanilla (pas de Nx), dernière stable, standalone + signals, Tailwind + Angular CDK, `core` en bibliothèque. |
| Q9 | Hors-ligne MVP | Lecture (liste blanche GET) + **saisie cahier de texte** uniquement ; politique de conflit : dernière écriture avec avertissement (H10). |
| Q10 | Thème | Variables CSS chargées à l'exécution via `GET /api/public/cabinet`. |
| Q11 | Décimaux librairie | Écart accepté ; harmonisation en entiers FCFA **au module M11**. |
| Q12 | Hébergeur | Choix à P6 ; scripts génériques (VPS Ubuntu, Nginx, PHP-FPM, PostgreSQL, Supervisor, DNS/SSL wildcard). |
| Q13 | Git & journal | Branche `master`, commits petits en français (Conventional Commits), case cochée + `docs/DECISIONS.md` à chaque tâche, défaut appliqué après consignation ici. |

### Autres consignes du propriétaire
- Feuille de route = **`PLAN.md`** à la racine (et non « feuille-de-route.md »).
- L'ancienne `docs/conception.md` (KEduc) est **supprimée et ignorée**.
- Les configurations de cabinet ne doivent **plus être statiques** (`config('keduc.cabinet')` → base tenant).
- **Un seul super-admin** de plateforme.
- Ambiguïtés : « prendre le meilleur, retirer ce qui n'est pas bon » — conception prime, code comme référence.

---

## 2. Questions ouvertes pour le développement (convention : + par défaut → appliquer)

| # | Sujet | Question (par défaut) |
|---|---|---|
| Q-A1 | Renouvellement annuel facturation plateforme (§7.8) | Périodicité : **annuel** à l'activation puis chaque date anniversaire de contrat/compte (par défaut) — à confirmer avant P8. |
| Q-A2 | Tarif « contrat actif » (H4) | Facturé **une fois** à l'activation du contrat (par défaut) ou mensuel ? |
| Q-A3 | Mot de passe admin | Envoyé **par email** et modifiable (H7, validé) ; alternative « lien d'activation à usage unique » étudiée plus tard. |
| Q-A4 | Conflits hors-ligne (H10) | Dernière écriture avec avertissement (validé en Q9). |
| Q-A5 | Valeurs initiales theme_cabinet | Fournies par le Landlord lors du pipeline de création (données actuelles `config('keduc.cabinet')` comme valeurs de départ). |
| Q-A6 | `push_subscriptions` & webpush | Table tenant + package à installer ; activation au P3/P7. |
| Q-A7 | Domaines personnalisés | Hors MVP (un seul domaine principal par cabinet ; colonnes `is_primary`/`is_active` prévues). |
| Q-A8 | Impersonation | Via stancl `tenancy()->impersonate`, bannière, journalisée (conforme §10.3). |
| Q-A9 | Backup avant suppression cabinet | `pg_dump` obligatoire avant toute suppression définitive (validé) — commande réservée au propriétaire. |
| Q-A10 | Migration de l'historique Keduc | **Hors périmètre** (H12) : KEduc historique reste tel quel ; pas d'import. |
| Q-A11 | `historique_activites` (audit tenant) | Table absente du code Keduc malgré la doc : à créer en P7 (module Audit). |
| Q-A12 | Partiellement payée (statut facture) | Géré par comparaison montant_paye < montant_total (conception §7.5). |
| Q-A13 | Périodes de facturation d'un même cabinet | **Disjointes et croissantes** (par défaut) : une facture commence après la fin de la dernière facture **non annulée** du cabinet — interdit tout chevauchement (protection anti double facturation des abonnements, complément des événements marqués `facturee`). |
| Q-A14 | Abonnements mensuels (enseignant/parent/élève) | Quantité = **nombre de comptes ARGUMENTés actifs au moment du calcul couverts par la période** (bornes : `created_at <= fin(P)` + événements d'activation/suspension si connus ; un compte suspendu à la date du calcul est hors quantité). Une ligne par profil. La reconstitution exacte « actif durant P » pour l'histoire **antérieure au journal** est approximée → flagged « à vérifier » ; le super-admin ajuste/retire. Proratisation journalière : **non** (v1) — ajustement par lignes manuelles. |
| Q-A15 | Vente librairie facturable (commission) | Événement = **commande passée au statut « livrée »** (défaut, assimilée à « vente réalisée ») ; assiette = `montant_total − frais_livraison` ; commission = débit calculé arrondi. Le journal capture `vente_librairie` à la transition. |
| Q-A16 | Correction après émission | Aucune édition d'une facture émise. **Annulation** : facture non payée → statut `annulee` + motif + **événements relâchés** (re-deviennent facturables) + journal ; régularisation par **nouvelle facture**. Autant une facture payée ne s'annule pas (remboursement manuel hors système). |
| Q-A17 | Tarif non renseigné (null) vs gratuit (0) | `null` = tarif **jamais négocié / service non applicable** → aucune ligne générée, contrôle « tarif manquant » dans la préview ; `0` = service **gratuit négocié explicitement** (le service est prévu au contrat, ligne à 0) ; exonération ponctuelle = **ligne manuelle crédit** ou code dans `exonerations`. |
| Q-A18 | Anniversaires (abonnement admin, maintenance site) | Facturés quand la période de facture **couvre l'anniversaire de création du cabinet** (`tenants.created_at`) ; sinon le super-admin les ajoute en ligne manuelle. |
| Q-A19 | Numérotation facture cabinet | `FC-YYYYMM-NNNNN` (défaut), posée **à l'émission** (le brouillon n'en a pas), verrou consultatif PostgreSQL `pg_advisory_xact_lock` par mois — pattern `NumerotationDocuments`; |
| Q-A20 | Historique antérieur au journal | Les activités antérieures au journal (base pré-m13/P8) sont reconstituées par la commande `facturation:rattraper-evenements` (idempotent, clé unique) ; les éléments reconstitués sont marqués « à vérifier » dans la préview. |

> Nouvelle question pendant le dev : l'ajouter ici avec « (par défaut : …) », appliquer le défaut, continuer, puis informer le propriétaire à la prochaine session.