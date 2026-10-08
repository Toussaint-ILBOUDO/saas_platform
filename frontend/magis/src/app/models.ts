/** Modèles TypeScript alignés sur l'API JSON du backend (T3.x / P2). */

export interface FicheIdentite {
  slogan?: string | null;
  directeur?: string | null;
}

export interface FicheContact {
  telephone?: string | null;
  telephone_2?: string | null;
  whatsapp?: string | null;
  email?: string | null;
  adresse?: string | null;
  horaires?: string | null;
}

export interface FichePaiements {
  orange_money?: string | null;
  moov_money?: string | null;
  wave?: string | null;
  cash?: boolean;
}

export interface FicheZones {
  pays?: string | null;
  devise?: string | null;
  localites?: string[];
}

export interface FicheReseaux {
  facebook?: string | null;
  tiktok?: string | null;
  whatsapp_business?: string | null;
  linkedin?: string | null;
}

export interface FicheCabinet {
  identite?: FicheIdentite;
  contact?: FicheContact;
  paiements?: FichePaiements;
  zones?: FicheZones;
  reseaux?: FicheReseaux;
}

/** GET /api/public/cabinet */
export interface CabinetPublic {
  nom: string;
  slug: string;
  theme: Record<string, unknown>;
  pied_de_page: Record<string, unknown>;
  donnees: {
    fiche?: FicheCabinet;
    [cle: string]: unknown;
  };
  logo_url?: string | null;
  fonctionnalites_actives: string[];
}

export interface MetaPage {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface Actualite {
  id: number;
  slug: string;
  titre: string;
  resume?: string | null;
  contenu?: string;
  video_url?: string | null;
  lien_externe?: string | null;
  image_url?: string | null;
  published_at?: string | null;
  nb_vues?: number;
  nb_reactions?: number;
  auteur?: string | null;
}

export interface FaqQuestion {
  id: number;
  question: string;
  answer: string;
}

export interface FaqSection {
  id: number;
  title: string;
  slug: string;
  description?: string | null;
  questions: FaqQuestion[];
}

export interface DocumentBibliotheque {
  id: number;
  slug: string;
  titre: string;
  resume?: string | null;
  types: { url: string; nom: string; taille: number }[];
  matiere?: string | null;
  classe?: string | null;
  nb_vues?: number;
  nb_telechargements?: number;
  note_moyenne?: number;
  created_at?: string | null;
}

export interface Produit {
  id: number;
  slug: string;
  nom: string;
  description?: string | null;
  prix?: number;
  frais_livraison?: number;
  categorie?: string | null;
  image_url?: string | null;
  is_active?: boolean;
}

export interface Temoignage {
  id: number;
  slug: string;
  contenu: string;
  role_label?: string;
  anonyme: boolean;
  auteur: string;
  score: number;
  published_at?: string | null;
}

export interface Enseignant {
  id: number;
  prenom?: string | null;
  nom?: string | null;
  nom_complet: string;
  photo_url?: string | null;
  diplome_max?: string | null;
  lieu_de_service?: string | null;
  matieres: string[];
}

export interface StatsCabinet {
  nb_enseignants: number;
  nb_eleves: number;
  nb_contrats: number;
  nb_familles: number;
}

export interface ReferenceTypeCours {
  id: number;
  libelle: string;
  code?: string | null;
}

export interface ReferenceClasse {
  id: number;
  nom: string;
  sigle?: string | null;
}

export interface ReferenceMatiere {
  id: number;
  nom: string;
  sigle?: string | null;
}

export interface ReferencesPubliques {
  type_cours: ReferenceTypeCours[];
  classes: ReferenceClasse[];
  matieres: ReferenceMatiere[];
}

export interface DemandeCours {
  nom_parent: string;
  prenom_parent: string;
  telephone: string;
  /** Optionnel : le cabinet retombe sur `telephone` pour le contact WhatsApp. */
  telephone_whatsapp?: string;
  type_cours_id?: number;
  classe_id?: number;
  volume_horaire_estime?: number;
  matieres?: number[];
  message?: string;
}

export interface ApiReponse {
  message?: string;
  [cle: string]: unknown;
}

export interface FormatageTelephone {
  national?: string;
  international?: string;
  whatsapp?: string;
}

/** Calcule les formats utiles d'un numéro burkinabè (+226 / 70xxxxxx …). */
export function formaterTelephoneBrut(brut?: string | null): FormatageTelephone {
  if (!brut) return {};
  const digits = (brut as string).replace(/\D/g, '');
  if (digits.length === 9 && !digits.startsWith('226')) {
    return {
      national: '+226 ' + digits.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d)/, '$1 $2 $3 $4 $5'),
      international: '+226' + digits,
      whatsapp: 'https://wa.me/226' + digits,
    };
  }
  const sansIndicatif = digits.startsWith('226') ? digits.slice(3) : digits;
  return {
    national: '+226 ' + sansIndicatif.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d)/, '$1 $2 $3 $4 $5'),
    international: '+226 ' + digits,
    whatsapp: 'https://wa.me/' + (digits.startsWith('226') ? digits : '226' + digits),
  };
}

/* ==================================================================
   Backoffice cabinet (P5.3/P5.4) — types alignés sur /api/admin, /api/auth
   ================================================================== */

export interface SessionUtilisateur {
  id: number;
  nom: string;
  prenom: string;
  email: string;
  statut?: boolean;
  roles: string[];
  active_role?: string | null;
  photo_url?: string | null;
  /**
   * Présent pour un compte élève. `eleve.id` n'est pas `users.id` : c'est cet
   * identifiant qui doit être utilisé pour les URL `/api/mes-enfants/{eleve}/…`.
   */
  eleve?: { id: number } | null;
}

export interface UtilisateurAdmin {
  id: number;
  nom: string;
  prenom: string;
  email: string;
  telephone_whatsapp?: string | null;
  telephone_appel?: string | null;
  statut: boolean;
  roles: string[];
  /**
   * Présent pour les comptes élèves. `eleve.id` est la clé de la table
   * `eleves` : c'est elle qu'attend un contrat, pas `users.id`.
   */
  eleve?: { id: number; classe: { id: number; nom: string; sigle: string } | null } | null;
  created_at?: string | null;
}

export interface ActualiteBackoffice {
  id: number;
  titre: string;
  slug: string;
  resume?: string | null;
  contenu?: string | null;
  video_url?: string | null;
  lien_externe?: string | null;
  image_url?: string | null;
  document_url?: string | null;
  is_active: boolean;
  statut?: string | null;
  est_publiee?: boolean;
  published_at?: string | null;
  nb_vues?: number;
  nb_reactions?: number;
  nb_partages?: number;
  auteur?: { id: number; prenom: string; nom: string; email: string } | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface FaqSectionBackoffice {
  id: number;
  title: string;
  slug: string;
  description?: string | null;
  order_index: number;
  is_active: boolean;
  questions_count: number;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface FaqQuestionBackoffice {
  id: number;
  faq_section_id: number;
  question: string;
  answer: string;
  order_index: number;
  is_active: boolean;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface ContenuPublicBackoffice {
  theme: Record<string, unknown>;
  footer: Record<string, unknown>;
  data: { fiche?: FicheCabinet; [cle: string]: unknown };
}

export interface NotificationEspace {
  id: number;
  titre: string;
  contenu?: string | null;
  type?: string | null;
  icone?: string | null;
  couleur?: string | null;
  action_label?: string | null;
  /** URL absolue Blade — backoffice historique, hors espace Angular. */
  url?: string | null;
  /** Chemin interne de l'espace : c'est celui que l'écran doit router. */
  route_angular?: string | null;
  lu: boolean;
  date_lecture?: string | null;
  created_at?: string | null;
}

/** Compteurs de tête de l'écran « Demandes de cours ». */
export interface StatsDemandesCours {
  total: number;
  en_attente: number;
  traitees: number;
  annulees: number;
  /** Demandes déjà transformées en contrat. */
  avec_contrat: number;
}

/**
 * Demande de cours **en administration** : la ligne du tableau.
 *
 * Distincte de `DemandeCours` (le payload du formulaire public, composed d'ids
 * de référentiels) : ici les libellés sont déjà résolus par l'API.
 */
export interface DemandeCoursAdmin {
  id: number;
  nom_parent: string;
  prenom_parent: string;
  telephone: string;
  telephone_whatsapp?: string | null;
  /**
   * Numéro réellement contactable (WhatsApp saisi, sinon téléphone) et lien
   * `wa.me` correspondant. Toujours renseigné côté serveur : une demande sans
   * numéro exploitable n'est pas representable.
   */
  numero_whatsapp: string;
  lien_whatsapp?: string;
  volume_horaire_estime: number;
  statut: string;
  message?: string | null;
  classe?: { id: number; nom: string; sigle: string } | null;
  type_cours?: { id: number; code: string; libelle: string } | null;
  matieres?: MatiereReferentiel[];
  /** Dossier déjà constitué : présent = action déjà jouée, ne pas la reproposer. */
  parent_cree?: { id: number; nom: string; prenom: string; email?: string | null } | null;
  eleve_cree?: { id: number; user_id?: number | null; classe?: { id: number; nom: string } | null } | null;
  contrat_cree?: { id: number; statut: string; date_debut?: string | null } | null;
  created_at?: string | null;
  updated_at?: string | null;
}
/* ============================================================
   T7A.1 — Périodes comptables (espace admin)
   ============================================================ */

export type TypePeriode = 'mensuel' | 'trimestriel' | 'annuel';
export type StatutPeriode = 'ouverte' | 'cloturee';

export interface PeriodeComptable {
  id: number;
  label: string;
  date_debut: string;
  date_fin: string;
  type: TypePeriode;
  statut: StatutPeriode;
  est_ouverte: boolean;
  est_cloturee: boolean;
  cloturee_par: number | null;
  cloturee_par_nom: string | null;
  cloturee_at: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface CreneauPeriode {
  label: string;
  date_debut: string;
  date_fin: string;
  type: TypePeriode;
}

/* ============================================================
   T7A.4 — Planning des cours (enseignant / parent / élève)
   ============================================================ */

export interface CreneauPlanning {
  id: number;
  /** 1 = lundi … 7 = dimanche (jour de la semaine ISO). */
  jour_semaine: number;
  /** Libellé fourni par l'API (« Lundi » … « Dimanche ») — D-055. */
  jour_label: string;
  /** Format « HH:MM » (le backend tronque la colonne `time` PostgreSQL). */
  heure_debut: string;
  heure_fin: string;
  tranche_horaire: string;
  matiere: { id: number; nom: string; sigle?: string | null } | null;
  enseignant: { id: number; nom?: string | null; prenom?: string | null } | null;
  eleve: { id: number; nom?: string | null; prenom?: string | null } | null;
  contrat_cours_id: number | null;
  affectation_enseignant_id: number;
}

export interface AffectationPlanning {
  id: number;
  matiere: string | null;
  eleve: string;
}

/** `GET /api/pedagogie/planning` — réponse de l'enseignant. */
export interface PlanningEnseignant {
  mes_creneaux: CreneauPlanning[];
  creneaux_partages: CreneauPlanning[];
  affectations: AffectationPlanning[];
}

/** Élève rattaché à un parent dans `GET /api/mes-planning`. */
export interface EnfantPlanning {
  eleve: {
    id: number;
    nom?: string | null;
    prenom?: string | null;
    compte_actif: boolean;
  };
  creneaux: CreneauPlanning[];
}

/** `GET /api/mes-planning` — l'enveloppe dépend du rôle (voir `MesPlanningApiController`). */
export interface MesPlanningParent {
  eleves: EnfantPlanning[];
}

export interface MesPlanningEleve {
  compte_actif: boolean;
  creneaux: CreneauPlanning[];
}

export interface CreneauFormulaire {
  affectation_enseignant_id: number | null;
  jour_semaine: number;
  heure_debut: string;
  heure_fin: string;
}

/* ============================================================
   T7A.2 — Référentiels pédagogiques (espace admin)
   Ces quatre listes conditionnent tout le reste du module : sans
   classe ni matière, aucune affectation ni aucun contrat n'est possible.
   ============================================================ */

export interface ClasseReferentiel {
  id: number;
  nom: string;
  sigle: string;
  nb_eleves?: number;
  nb_demandes_cours?: number;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface CreneauClasse {
  nom: string;
  sigle: string;
}

export interface MatiereReferentiel {
  id: number;
  nom: string;
  sigle: string;
  description: string | null;
  actif: boolean;
  nb_enseignants?: number;
  nb_affectations?: number;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface CreneauMatiere {
  nom: string;
  sigle: string;
  description?: string | null;
}

export interface TypeCoursReferentiel {
  id: number;
  code: string;
  libelle: string;
  description: string | null;
  actif: boolean;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface CreneauTypeCours {
  code: string;
  libelle: string;
  description?: string | null;
}

export interface ProfilEnseignantReferentiel {
  id: number;
  numero_orange_money: string | null;
  diplome_max: string | null;
  lieu_de_service: string | null;
  domicile: string | null;
  frais_annuel_regle: boolean;
  matieres: { id: number; nom: string; sigle: string }[] | null;
}

export interface EnseignantReferentiel {
  id: number;
  nom: string;
  prenom: string;
  email: string | null;
  telephone_whatsapp: string | null;
  telephone_appel: string | null;
  statut: boolean;
  profil: ProfilEnseignantReferentiel | null;
  created_at?: string | null;
  updated_at?: string | null;
}

/**
 * Formulaire enseignant. `password` n'est exigé qu'à la création : l'API
 * l'accepte à la mise à jour mais on ne le propose pas — un admin ne
 * réinitialise pas le mot de passe d'un prof sans son accord.
 */
export interface CreneauEnseignant {
  nom: string;
  prenom: string;
  email?: string | null;
  password?: string | null;
  telephone_whatsapp?: string | null;
  telephone_appel?: string | null;
  numero_orange_money?: string | null;
  diplome_max?: string | null;
  lieu_de_service?: string | null;
  domicile?: string | null;
  frais_annuel_regle?: boolean;
  matieres?: number[];
}

/* ============================================================
   T7A.3 — Contrats de cours & affectations
   ============================================================ */

export type StatutContrat = 'actif' | 'suspendu' | 'termine';
export type StatutAffectation = 'actif' | 'suspendu' | 'termine';

/** Libellés des statuts de contrat — partagés par l'écran admin et le portail parent. */
export const STATUTS_CONTRAT: { valeur: StatutContrat; libelle: string; aide: string }[] = [
  {
    valeur: 'actif',
    libelle: 'Actif',
    aide: 'Les séances et rapports de ce contrat peuvent être saisis.',
  },
  {
    valeur: 'suspendu',
    libelle: 'Suspendu',
    aide: 'Gel temporaire : la facturation s’arrête, le contrat reste en mémoire.',
  },
  {
    valeur: 'termine',
    libelle: 'Terminé',
    aide: 'Définitif. Impossible dès que le contrat a produit une facture.',
  },
];

export interface EleveAffectation {
  id: number;
  nom: string | null;
  prenom: string | null;
  classe_id: number | null;
}

export interface TypeCoursAffectation {
  id: number;
  code: string;
  libelle: string;
}

export interface MatiereAffectation {
  id: number;
  nom: string;
  sigle: string;
}

export interface EnseignantAffectation {
  id: number;
  user_id: number;
  nom: string | null;
  prenom: string | null;
}

export interface Affectation {
  id: number;
  contrat_cours_id: number;
  statut: StatutAffectation;
  date_affectation: string | null;
  date_fin: string | null;
  nombre_heures_prevues: number;
  taux_horaire_enseignant: number;
  montant_prevu: number;
  matiere: MatiereAffectation | null;
  enseignant: EnseignantAffectation | null;
}

export interface ContratCours {
  id: number;
  statut: StatutContrat;
  date_debut: string;
  date_fin: string | null;
  autres_frais_suivi: number;
  notes_admin: string | null;
  eleve: EleveAffectation | null;
  type_cours: TypeCoursAffectation | null;
  affectations: Affectation[];
  nb_affectations?: number;
  created_at?: string | null;
  updated_at?: string | null;
}

/**
 * Ligne de saisie d'une affectation. Le contrat naît avec **au moins une**
 * affectation (invariant serveur) : d'où un tableau, et non deux champs.
 */
export interface AffectationFormulaire {
  enseignant_id: number | null;
  matiere_id: number | null;
  taux_horaire_enseignant: number | null;
  nombre_heures_prevues: number | null;
  date_affectation: string | null;
}

export interface ContratFormulaire {
  eleve_id: number | null;
  type_cours_id: number | null;
  date_debut: string;
  date_fin: string | null;
  autres_frais_suivi: number | null;
  notes_admin: string | null;
  affectations: AffectationFormulaire[];
}

/** Élève proposé par l'API utilisateurs : `eleve.id` ≠ `users.id`. */
export interface EleveChoisi {
  user_id: number;
  nom: string;
  prenom: string;
  email: string | null;
  eleve: { id: number; classe: { id: number; nom: string; sigle: string } | null } | null;
}

/* =====================================================================
   T7A.5 — Cahier de texte
   ===================================================================== */

/**
 * Séance du cahier de texte, présentée avec son contexte (élève, matière,
 * enseignant) : « Mardi 18h, Maths, Mme Traoré » se lit, une ligne de contenu
 * seule ne s'explique pas.
 */
export interface CahierTexte {
  id: number;

  /**
   * Clé de synchronisation hors-ligne. Le client la conserve : c'est elle qui
   * permet de rejouer une saisie dont l'accusé de réception a été perdu sans
   * créer de doublon. Le serveur la rend non modifiable.
   */
  uuid_client: string | null;

  affectation_enseignant_id: number;
  contrat_cours_id: number | null;

  date_seance: string;
  /** Format `HH:MM` — jamais `HH:MM:SS`. */
  heure_debut: string | null;
  heure_fin: string | null;
  duree_heures: number;

  contenu_cours: string;
  objectifs_atteints: string | null;
  observations: string | null;

  matiere: MatiereAffectation | null;
  enseignant: { id: number; nom: string | null; prenom: string | null } | null;
  eleve: { id: number; nom: string | null; prenom: string | null } | null;
}

/** Cours navigable pour la saisie : l'enseignant ne choisit que parmi les siens. */
export interface AffectationCahierTexte {
  id: number;
  matiere: string | null;
  eleve: string;
}

export interface CahierTexteFormulaire {
  affectation_enseignant_id: number | null;
  date_seance: string;
  heure_debut: string;
  heure_fin: string;
  contenu_cours: string;
  objectifs_atteints?: string;
  observations?: string;
  /** Absent en modification : le serveur refuse toute tentative de le changer. */
  uuid_client?: string;
}

/**
 * Payload de correction. Volontairement **plus étroit** que
 * `CahierTexteFormulaire` : le serveur n'accepte ni l'affectation (changer de
 * cours ferait porter les heures à une autre ligne de rapport, cf. D-054) ni un
 * `uuid_client` neuf. `date_seance` figure ici parce qu'elle est acceptée mais
 * vérifiée à l'identique — le client peut ainsi renvoyer sa copie entière.
 * Typer la correction séparément empêche d'envoyer par erreur un champ que le
 * serveur ignorerait silencieusement.
 */
export interface CahierTexteCorrection {
  date_seance: string;
  heure_debut: string;
  heure_fin: string;
  contenu_cours: string;
  objectifs_atteints?: string;
  observations?: string;
}

/**
 * Enveloppe de création. Le statut HTTP porte l'information utile : `201` =
 * créée, `200` = reprise idempotente. Le corps ne contient qu'un `data`
 * (JsonResource), pas de `message` — d'où le champ optionnel.
 */
export interface ReponseCahierTexte {
  data: CahierTexte;
  message?: string;
  /** Renseigné par `ApiService` à partir du statut : `true` si ligne créée. */
  cree?: boolean;
}

/**
 * Enfant proposé par `GET /api/mes-enfants`. Pour un compte élève, la liste ne
 * contient que lui-même : le frontend traite donc parent et élève avec un seul
 * sélecteur. `id` est un `eleves.id`, jamais un `users.id`.
 */
export interface EnfantConsulter {
  id: number;
  nom: string | null;
  prenom: string | null;
  classe: { id: number; nom: string; sigle: string } | null;
  compte_actif: boolean;
}

/* =====================================================================
   T7A.7 — Rapport mensuel enseignant
   ===================================================================== */

/**
 * Ligne de ventilation d'un rapport (D-049). Une ligne par matière ; ce sont
 * EXACTEMENT ces lignes que la facture parent et le bulletin de paie
 * consomment — d'où la présentation du taux et du montant estimé.
 */
export interface RapportMensuelLigne {
  id: number;
  affectation_enseignant_id: number;
  matiere_id: number;
  matiere_nom: string | null;
  nombre_seances: number;
  nombre_heures: number;
  taux_horaire: number;
  montant_estime: number;
}

export type StatutRapport = 'soumis' | 'valide' | 'rejete';

/** Type de champ d'un élément du modèle de rapport. */
export type TypeRapportElement = 'textarea' | 'text';

/** Élément (ou « question ») d'une section du modèle de rapport. */
export interface RapportElement {
  id: number;
  section_id: number;
  libelle: string;
  type: TypeRapportElement;
  obligatoire: boolean;
  aide: string | null;
  ordre: number;
  actif: boolean;
  /** Réponse de l'enseignant — présente sur le détail d'un rapport. */
  reponse?: string | null;
}

/** Section (bloc) du modèle de rapport administré par l'administration. */
export interface RapportSection {
  id: number;
  libelle: string;
  description: string | null;
  ordre: number;
  actif: boolean;
  elements: RapportElement[];
}

/** Aperçu renvoyé par `POST …/rapports-mensuels/apercu`. */
export interface RapportMensuelApercu {
  volume_horaire: number;
  nombre_seances: number;
  bilan: string;
  ventilation: {
    matiere: string | null;
    nombre_seances: number;
    nombre_heures: number;
  }[];
  cahiers: {
    date_seance: string | null;
    matiere: string | null;
    heure_debut: string | null;
    heure_fin: string | null;
    contenu: string | null;
  }[];
}

/**
 * Rapport mensuel enseignant.
 *
 * `actions` est la liste des transitions autorisées pour l'utilisateur
 * connecté (dérivée côté serveur de la policy) : modifier, supprimer,
 * resoumettre, valider, rejeter. Le frontend s'en sert pour afficher
 * exactement les boutons pertinents — pas pour décider seul de la machine à
 * états.
 *
 * `sections` n'est rempli que sur le DÉTAIL d'un rapport : c'est le modèle de
 * rapport administré avec, pour chaque élément, la réponse de l'enseignant.
 * Les listes renvoient un tableau vide.
 */
export interface RapportMensuel {
  id: number;
  contrat_cours_id: number;
  periode_id: number;
  periode: {
    id: number;
    label: string;
    date_debut: string;
    date_fin: string;
    statut: string;
    est_ouverte: boolean;
  } | null;
  enseignant_id: number;
  enseignant: { id: number; nom: string | null } | null;
  eleve: { id: number; nom: string | null; classe: string | null } | null;
  type_cours: { id: number; libelle: string } | null;
  statut: StatutRapport;
  volume_horaire_cumule: number;
  lignes: RapportMensuelLigne[];
  total_seances: number;
  montant_estime: number;
  total_heures_lignes: number;
  /** Bilan automatique du cahier de texte (jamais saisi). */
  bilan_activites: string | null;
  /** Modelé de rapport + réponses — détail uniquement. */
  sections: RapportSection[];
  motif_rejet: string | null;
  date_validation: string | null;
  valide_par: number | null;
  actions: string[];
  created_at: string | null;
  updated_at: string | null;
}

/**
 * Payload de dépôt. Le volume horaire n'est PAS saisissable : il vient du
 * cahier de texte via le serveur. Le client ne fournit que le couple
 * contrat/période et les réponses au modèle de rapport, indexées par
 * `rapport_elements.id`.
 */
export interface RapportMensuelFormulaire {
  contrat_cours_id: number | null;
  periode_id: number | null;
  reponses?: Record<string, string>;
}

/** Rejet motivé — la machine à états exige le motif, le serveur le vérifie. */
export interface RapportMensuelRejet {
  motif_rejet: string;
}

/* =====================================================================
   T7A.8 — Facture parent
   ===================================================================== */

export type StatutPaiement = 'en_attente' | 'payee';

export type ModePaiement =
  | 'especes'
  | 'orange_money'
  | 'moov_money'
  | 'virement'
  | 'cheque'
  | 'autre';

export const MODES_PAIEMENT: { valeur: ModePaiement; libelle: string }[] = [
  { valeur: 'especes', libelle: 'Espèces' },
  { valeur: 'orange_money', libelle: 'Orange Money' },
  { valeur: 'moov_money', libelle: 'Moov Money' },
  { valeur: 'virement', libelle: 'Virement' },
  { valeur: 'cheque', libelle: 'Chèque' },
  { valeur: 'autre', libelle: 'Autre' },
];

/**
 * Facture parent. `actions` porte les transitions autorisées : un parent ne
 * reçoit que `['pdf']` ; un admin reçoit `['pdf', 'payer']` tant que la
 * facture est en attente.
 */
export interface Facture {
  id: number;
  numero_facture: string;
  statut_paiement: StatutPaiement;
  est_payee: boolean;
  est_en_attente: boolean;
  volume_horaire_total: number;
  frais_suivi: number;
  autres_frais: number;
  remise: number;
  montant_total: number;
  commentaire: string | null;
  date_limite_paiement: string | null;
  date_paiement: string | null;
  mode_paiement: ModePaiement | null;
  reference_paiement: string | null;
  periode: { id: number; label: string; date_debut: string; date_fin: string; statut: string; est_ouverte: boolean } | null;
  eleve: { id: number; nom: string | null; classe: string | null } | null;
  parent: { id: number; nom: string | null } | null;
  type_cours: { id: number; libelle: string } | null;
  lignes: LigneFacture[];
  actions: string[];
  created_at: string | null;
  updated_at: string | null;
}

/**
 * Ligne d'une facture : les heures et la matière des RAPPORTS VALIDÉS.
 */
export interface LigneFacture {
  id: number;
  facture_id: number;
  affectation_enseignant_id: number;
  enseignant: string | null;
  matiere: string | null;
  nombre_heures: number;
  taux_horaire: number;
  montant: number;
}

/**
 * Payload de génération : le contrat + la période + les frais éventuels. Le
 * volume horaire et les lignes viennent du serveur (rapports validés, D-051).
 */
export interface FactureFormulaire {
  contrat_cours_id: number | null;
  periode_id: number | null;
  frais_suivi?: number | null;
  autres_frais?: number | null;
  remise?: number | null;
  commentaire?: string;
  date_limite_paiement?: string | null;
}

/** Règlement : acte administratif (jamais auto-réglé par le parent). */
export interface FacturePaiement {
  date_paiement: string;
  mode_paiement: ModePaiement;
  reference_paiement?: string;
}

/** Aperçu renvoyé par `POST /admin/factures/preview` — aucune écriture. */
export interface FactureApercu {
  eleve: string;
  parent: string;
  periode_label: string;
  lignes: {
    affectation_enseignant_id: number;
    matiere: string;
    enseignant: string;
    nombre_heures: number;
    taux_horaire: number;
    montant: number;
  }[];
  volume_horaire_total: number;
  montant_cours: number;
  frais_suivi: number;
  autres_frais: number;
  remise: number;
  montant_total: number;
}

/* =====================================================================
   T7A.9 — Bulletin de paie enseignant
   ===================================================================== */

export type StatutBulletin =
  | 'genere'
  | 'consulte'
  | 'valide'
  | 'conteste'
  | 'corrige'
  | 'verse';

export type DirectionAjustement = 'credit' | 'debit';

/** Types d'ajustement (prime/retenue) proposés à l'administration. */
export interface TypeAjustement {
  id: number;
  libelle: string;
  direction: DirectionAjustement;
  is_active?: boolean;
}

/** Ligne de paie : élève, matière, heures et taux d'origine (édition figée). */
export interface BulletinPaieLigne {
  id: number;
  bulletin_paie_id: number;
  affectation_enseignant_id: number;
  eleve: string | null;
  matiere: string | null;
  nombre_heures: number;
  taux_horaire: number;
  montant: number;
}

export interface BulletinPaieAjustement {
  id: number;
  bulletin_paie_id: number;
  type_ajustement_id: number | null;
  type: 'prime' | 'retenue';
  libelle: string;
  montant: number;
}

/**
 * Bulletin de paie. `actions` porte les transitions autorisées par la machine
 * à états : l'enseignant consulte/valide/conteste/confirme la réception,
 * l'admin corrige/paie/ajuste — rien d'autre, jamais un PATCH libre.
 */
export interface BulletinPaie {
  id: number;
  numero: string;
  statut: StatutBulletin;

  est_genere: boolean;
  est_consulte: boolean;
  est_valide: boolean;
  est_conteste: boolean;
  est_corrige: boolean;
  est_verse: boolean;
  est_recu: boolean;
  en_attente_reception: boolean;

  total_heures: number;
  montant_brut: number;
  frais_suivi: number;
  montant_net: number;
  total_primes?: number;
  total_retenues?: number;
  montant_net_final?: number;

  commentaire_enseignant: string | null;
  motif_contestation: string | null;
  libelle_motif_contestation: string | null;

  date_consultation: string | null;
  date_validation: string | null;
  date_paiement: string | null;
  mode_paiement: ModePaiement | null;
  reference_paiement: string | null;
  date_reception: string | null;
  recu_par: string | null;

  enseignant: { id: number; nom: string | null; email: string | null } | null;
  periode: { id: number; label: string; date_debut: string; date_fin: string; statut: string; est_ouverte: boolean } | null;
  lignes: BulletinPaieLigne[];
  ajustements: BulletinPaieAjustement[];
  actions: string[];
  created_at: string | null;
  updated_at: string | null;
}

/** Ligne d'un enseignant dans l'aperçu de paie (sans écriture). */
export interface BulletinPreviewEnseignant {
  enseignant: { id: number; nom: string | null };
  lignes: {
    affectation_enseignant_id: number;
    contrat_cours_id: number;
    eleve_id: number;
    matiere_id: number;
    eleve_nom: string;
    matiere_nom: string;
    nombre_heures: number;
    taux_horaire: number;
    montant: number;
  }[];
  total_heures: number;
  montant_brut: number;
  frais_suivi: number;
  ajustements: {
    type_ajustement_id: number;
    libelle: string;
    direction: DirectionAjustement;
    montant: number;
  }[];
  total_credits: number;
  total_debits: number;
  montant_net: number;
}

/** Réponse de l'aperçu de paie : lignes par enseignant + totaux + types. */
export interface BulletinApercu {
  data: BulletinPreviewEnseignant[];
  total_enseignants: number;
  total_heures: number;
  total_montant: number;
  types_ajustement: TypeAjustement[];
}

/**
 * Payload de génération : période + frais de suivi et ajustements indexés par
 * enseignant (`[enseignant_id] = { [type_id]: montant }`).
 */
export interface BulletinGenererFormulaire {
  periode_id: number | null;
  frais_suivi?: Record<number, number>;
  ajustements?: Record<number, Record<number, number>>;
}

/** D-052 — motif structuré : catégorie de la liste fermée + détail libre. */
export interface BulletinContestation {
  motif_contestation: string;
  commentaire_enseignant: string;
}

export const MOTIFS_CONTESTATION: { valeur: string; libelle: string }[] = [
  { valeur: 'heures', libelle: 'Heures retenues incorrectes' },
  { valeur: 'taux', libelle: 'Taux horaire incorrect' },
  { valeur: 'seance_manquante', libelle: 'Séance non enregistrée' },
  { valeur: 'seance_en_double', libelle: 'Séance comptée deux fois' },
  { valeur: 'ajustement', libelle: 'Prime ou retenue contestée' },
  { valeur: 'periode', libelle: 'Mauvaise période de rattachement' },
  { valeur: 'autre', libelle: 'Autre motif' },
];

/** Versement : acte administratif (valide → verse). */
export interface BulletinVersement {
  date_paiement: string;
  mode_paiement: ModePaiement;
  reference_paiement?: string;
}
