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
  description?: string | null;
  types: { url: string | null; nom: string; taille: number; mime?: string }[];
  fichier?: { nom: string; taille: number; mime: string; extension?: string } | null;
  matiere?: string | null;
  classe?: string | null;
  type_document?: string | null;
  periode?: string | null;
  tags?: string[];
  nb_vues?: number;
  nb_telechargements?: number;
  nb_aimes?: number;
  nb_n_aimes?: number;
  nombre_favoris?: number;
  note_moyenne?: number;
  nb_notes?: number;
  ma_reaction?: 'aime' | 'n_aime_pas' | null;
  is_favori?: boolean;
  created_at?: string | null;
}

/** Référentiels de la bibliothèque publique (filtres). */
export interface ReferentielsBibliothequePublique {
  types: { id: number; nom: string }[];
  matieres: { id: number; nom: string }[];
  classes: { id: number; nom: string }[];
}

export type TriBibliotheque = 'recent' | 'populaire' | 'note' | 'apprecies' | 'favoris';

export interface Produit {
  id: number;
  slug: string;
  nom: string;
  description?: string | null;
  reference?: string | null;
  prix?: number;
  prix_promo?: number | null;
  prix_vente?: number;
  en_promotion?: boolean;
  disponible?: boolean;
  stock?: number | null;
  frais_livraison?: number;
  categorie?: string | null;
  categorie_id?: number | null;
  image_url?: string | null;
  is_active?: boolean;
  est_archive?: boolean;
  similaires?: Produit[];
}

/** Ligne de panier persistée (localStorage). */
export interface LignePanier {
  produit_id: number;
  nom: string;
  slug?: string;
  prix_vente: number;
  image_url?: string | null;
  quantite: number;
}

/** Réponse du devis serveur du panier. */
export interface DevisPanier {
  lignes: {
    produit_id: number;
    nom: string;
    slug: string;
    reference?: string | null;
    image_url?: string | null;
    quantite: number;
    prix_unitaire: number;
    prix_normal?: number;
    en_promotion?: boolean;
    sous_total: number;
    stock_disponible?: number | null;
    disponible: boolean;
  }[];
  montant_total: number;
  frais_livraison: number;
  disponible: boolean;
}

export type StatutCommande =
  | 'en_attente'
  | 'confirmee'
  | 'en_preparation'
  | 'livree'
  | 'annulee';

export interface CommandeSynthese {
  reference: string;
  date?: string | null;
  statut: StatutCommande;
  statut_libelle?: string;
  is_livraison: boolean;
  montant_total: number;
  frais_livraison: number;
  total_articles: number;
  annulable?: boolean;
  annulation_demandee?: boolean;
  nom_client?: string;
  prenom_client?: string | null;
}

export interface CommandeDetail extends CommandeSynthese {
  telephone_client?: string;
  whatsapp?: string | null;
  email_client?: string | null;
  adresse_livraison?: string | null;
  quartier?: string | null;
  point_de_repere?: string | null;
  lien_geolocalisation?: string | null;
  notes_client?: string | null;
  note_interne?: string | null;
  annulation_motif?: string | null;
  lignes: {
    produit?: string | null;
    slug?: string | null;
    reference?: string | null;
    image_url?: string | null;
    quantite: number;
    prix_unitaire: number;
    sous_total: number;
  }[];
  etapes?: { statut: string; libelle: string; date?: string | null; fait: boolean }[];
  historique?: { ancien_statut?: string | null; nouveau_statut?: string; date?: string | null }[];
  pdf?: string;
}

/** Chronologie de suivi public d'une commande. */
export interface SuiviCommande {
  reference: string;
  date?: string | null;
  statut: StatutCommande;
  statut_libelle: string;
  is_livraison: boolean;
  quartier?: string | null;
  nom_client: string;
  prenom_client?: string | null;
  montant_total: number;
  frais_livraison: number;
  etapes: { statut: string; libelle: string; date?: string | null; fait: boolean }[];
  derniere_maj?: string | null;
  annulation_demandee: boolean;
  annulable: boolean;
  lignes: { nom?: string | null; quantite: number; prix_unitaire: number; sous_total: number }[];
}

/** Formulaire de commande (sans compte possible). */
export interface CommandeFormulaire {
  nom_client: string;
  prenom_client?: string;
  telephone_client: string;
  whatsapp?: string;
  adresse_livraison?: string;
  quartier?: string;
  point_de_repere?: string;
  lien_geolocalisation?: string;
  is_livraison: boolean;
  mode_paiement?: string;
  notes?: string;
  cle_idempotence?: string;
  panier: { produit_id: number; quantite: number }[];
}

/** Réponse à la validation d'une commande. */
export interface CommandeCreee {
  message: string;
  commande: {
    reference: string;
    date?: string | null;
    statut: StatutCommande;
    montant_total: number;
    frais_livraison: number;
    is_livraison: boolean;
    lien_suivi: string;
    pdf: string;
  };
}

/** Tableau de bord de la console librairie. */
export interface DashboardLibrairie {
  produits: number;
  produits_publies: number;
  produits_archives: number;
  produits_stock_faible: number;
  categories: number;
  commandes: number;
  commandes_du_jour: number;
  commandes_par_statut: { statut: string; total: number }[];
  ca_accessible: boolean;
  chiffre_affaires: number | null;
  frais_livraison_encaisses: number | null;
  dernieres_commandes: {
    reference: string;
    nom_client: string;
    statut: string;
    montant_total: number;
    is_livraison: boolean;
    total_articles: number;
    date?: string | null;
  }[];
  top_produits: { id: number; nom: string; image_url?: string | null }[];
}

/** Réglages / délégation de la librairie. */
export interface ParametresLibrairie {
  sigle: string | null;
  frais_livraison: number;
  admin_peut_voir_dashboard: boolean;
  admin_peut_voir_ca: boolean;
  admin_peut_voir_commandes: boolean;
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
  telephone_whatsapp?: string | null;
  telephone_appel?: string | null;
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

/** Bloc de profil enseignant (`GET /auth/moi/profils`, rôle actif = enseignant). */
export interface ProfilEnseignant {
  numero_orange_money?: string | null;
  diplome_max?: string | null;
  lieu_de_service?: string | null;
  domicile?: string | null;
  matieres: number[];
  matieres_libelles: string[];
}

/** Bloc de profil parent (`GET /auth/moi/profils`, rôle actif = parent). */
export interface ProfilParent {
  adresse_domicile?: string | null;
  profession?: string | null;
  nombre_enfants: number;
}

/** Enfant rattaché au compte parent (synthèse de la fiche élève). */
export interface EnfantSynthese {
  id: number;
  nom_complet: string;
  classe?: string | null;
  ecole?: string | null;
}

/** Fiche élève éditable (`GET /auth/moi/profils` rôle élève, `PUT /auth/eleves/{id}`). */
export interface FicheEleve {
  id: number;
  classe_id: number | null;
  classe?: string | null;
  ecole?: string | null;
  date_naissance?: string | null;
  lieu_naissance?: string | null;
  parent_charge?: string | null;
  etablissement_origine?: string | null;
  profession_pere?: string | null;
  profession_mere?: string | null;
  regime_etude?: string | null;
  loisirs_sport?: string | null;
  religion_enfant?: string | null;
  maladies_allergies?: string | null;
  interdits_familiaux?: string | null;
  boisson_preferee?: string | null;
  nourriture_preferee?: string | null;
  autres_precautions?: string | null;
  autres_observations?: string | null;
}

/** Réponse de `GET /auth/moi/profils` : le bloc correspond au rôle actif. */
export interface ReponseProfils {
  role: string | null;
  enseignant?: ProfilEnseignant;
  parent?: ProfilParent;
  enfants?: EnfantSynthese[];
  eleve?: FicheEleve | null;
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
  photo_url?: string | null;
  profil: ProfilEnseignantReferentiel | null;
  created_at?: string | null;
  updated_at?: string | null;
}

/**
 * Rapport d'import CSV d'enseignants (M1) : import partiel possible, chaque
 * ligne ignorée ou avertie est référencée par son numéro de ligne fichier
 * (l'en-tête est la ligne 1).
 */
export interface RapportImportEnseignant {
  importees: number;
  ignorees: number;
  erreurs: { ligne: number; message: string; nom?: string; prenom?: string }[];
  avertissements: { ligne: number; message: string }[];
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
  /**
   * Renseigné depuis l'en-tête `X-MPC-File` (T4.5) : la saisie est en file
   * d'attente hors-ligne, elle **n'est pas encore au serveur** — l'écran doit
   * annoncer une mise en file, pas un enregistrement.
   */
  misEnFile?: boolean;
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
  ajustements: FactureAjustement[];
  total_credits: number;
  total_debits: number;
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
 * Ajustement d'une facture : ligne bonus/débit ajoutée par l'administration
 * après génération, qui s'ajoute au montant total.
 */
export interface FactureAjustement {
  id: number;
  facture_id: number;
  type_ajustement_id: number | null;
  type: DirectionAjustement;
  libelle: string;
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
  /**
   * Lignes bonus/débit saisies dans l'aperçu, emportées par la génération
   * (mêmes champs que l'ajustement ajouté après coup sur la facture).
   */
  ajustements?: { type_ajustement_id: number; libelle: string; montant: number }[];
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
  total_credits: number;
  total_debits: number;
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

  enseignant: { id: number; nom: string | null; email: string | null; telephone?: string | null } | null;
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

/* =====================================================================
   Création directe — parent & élève (`/pedagogie/parents`, `/pedagogie/eleves`)
   ===================================================================== */

/** Ligne du référentiel parents de l'admin (ParentResource). */
export interface ParentCabinet {
  id: number;
  nom: string;
  prenom: string;
  email: string | null;
  telephone_whatsapp: string | null;
  telephone_appel: string | null;
  statut: boolean;
  profil?: {
    id: number;
    profession: string | null;
    adresse_domicile: string | null;
    nombre_enfants: number;
  } | null;
  nombre_enfants?: number;
  created_at?: string | null;
  updated_at?: string | null;
}

/** Formulaire de création d'un parent (mot de passe exigé, min 8). */
export interface ParentFormulaire {
  nom: string;
  prenom: string;
  telephone_whatsapp: string | null;
  telephone_appel: string | null;
  email: string | null;
  password: string;
  profession: string | null;
  adresse_domicile: string | null;
  nombre_enfants: number | null;
}

/** Ligne du référentiel élèves de l'admin (EleveResource, wrappé sur `Eleve`). */
export interface EleveCabinet {
  id: number;
  user_id: number;
  nom: string | null;
  prenom: string;
  email: string | null;
  telephone_whatsapp: string | null;
  telephone_appel: string | null;
  statut_compte: boolean;
  ecole: string | null;
  date_naissance: string | null;
  lieu_naissance: string | null;
  classe: { id: number; nom: string; sigle: string } | null;
  parent: { id: number; nom: string; prenom: string; telephone_whatsapp: string | null } | null;
  created_at?: string | null;
}

/** Formulaire de création d'un élève : parent et classe obligatoires. */
export interface EleveFormulaire {
  parent_id: number | null;
  prenom: string;
  nom: string | null;
  classe_id: number | null;
  date_naissance: string | null;
  lieu_naissance: string | null;
  ecole: string | null;
  email: string | null;
  telephone_whatsapp: string | null;
  telephone_appel: string | null;
  password: string | null;
}

/* =====================================================================
   M3 — Scolarité : fiches admin parents & élèves (détail / édition)
   ===================================================================== */

/** Fiche scolaire complète — `EleveDetailResource.fiche`. */
export interface FicheScolaire {
  parent_charge: string | null;
  etablissement_origine: string | null;
  profession_pere: string | null;
  profession_mere: string | null;
  regime_etude: 'interne' | 'externe' | null;
  loisirs_sport: string | null;
  religion_enfant: string | null;
  maladies_allergies: string | null;
  interdits_familiaux: string | null;
  boisson_preferee: string | null;
  nourriture_preferee: string | null;
  autres_precautions: string | null;
  autres_observations: string | null;
}

/** Fiche complète d'un élève côté admin (GET/PUT /pedagogie/eleves/{id}). */
export interface EleveScolarite extends EleveCabinet {
  parent_id: number;
  classe_id: number;
  fiche: FicheScolaire;
}

/** Fiche détaillée d'un parent + ses enfants rattachés. */
export interface ParentScolarite extends ParentCabinet {
  enfants?: EleveCabinet[];
}

/** Payload d'édition admin d'un parent (identité + profil). */
export interface ParentEdition {
  nom: string;
  prenom: string;
  email: string | null;
  telephone_whatsapp: string | null;
  telephone_appel: string | null;
  password?: string;
  adresse_domicile: string | null;
  profession: string | null;
  nombre_enfants: number | null;
}

/**
 * Payload d'édition admin d'un élève : rattachements + identité + fiche.
 * La fiche voyage **à plat** (l'API ne l'attend pas imbriquée) — elle ne
 * l'est que dans la réponse (`EleveDetailResource`).
 */
export interface EleveEdition {
  parent_id: number;
  classe_id: number;
  nom: string | null;
  prenom: string;
  email: string | null;
  telephone_whatsapp: string | null;
  telephone_appel: string | null;
  password?: string;
  ecole: string | null;
  date_naissance: string | null;
  lieu_naissance: string | null;
  parent_charge: string | null;
  etablissement_origine: string | null;
  profession_pere: string | null;
  profession_mere: string | null;
  regime_etude: 'interne' | 'externe' | null;
  loisirs_sport: string | null;
  religion_enfant: string | null;
  maladies_allergies: string | null;
  interdits_familiaux: string | null;
  boisson_preferee: string | null;
  nourriture_preferee: string | null;
  autres_precautions: string | null;
  autres_observations: string | null;
}

/* =====================================================================
   Bibliothèque — espace membre & modération (Angular)
   ===================================================================== */

export type StatutDocumentBibliotheque = 'brouillon' | 'en_attente' | 'publie' | 'refuse' | 'archive';

/** Lien réflexif : la note/signalisation/le signalement rattache le document. */
export interface RefMinimal {
  id: number;
  nom: string;
  prenom: string;
}

export interface CommentaireBibliotheque {
  id: number;
  contenu: string;
  signale: boolean;
  user: RefMinimal | null;
  replies: CommentaireBibliotheque[];
  created_at: string | null;
}

export interface SignalementBibliotheque {
  id: number;
  motif: string;
  motif_libelle?: string;
  description: string | null;
  precisions: string | null;
  statut: 'en_attente' | 'en_cours' | 'traite' | 'rejete';
  statut_libelle?: string;
  note_admin: string | null;
  user: RefMinimal | null;
  /** Trace de l'action de modération effectuée sur ce signalement. */
  traite_at?: string | null;
  traite_par?: RefMinimal | null;
  created_at: string | null;
}

/** Fichier métadonnées — jamais d'URL (disque privé). */
export interface FichierDocument {
  nom: string;
  taille: number;
  mime: string;
  extension: string;
}

/** Document de la bibliothèque dans l'espace membre / modération. */
export interface DocumentBibliothequeEspace {
  id: number;
  slug: string;
  titre: string;
  description: string | null;
  resume: string | null;
  statut: StatutDocumentBibliotheque;
  is_public: boolean;
  nb_vues: number;
  nb_telechargements: number;
  nombre_favoris: number;
  note_moyenne: number;
  nb_notes: number;
  is_favori: boolean;
  ma_note: number | null;
  auteur: RefMinimal | null;
  type_document: { id: number; nom: string; sigle: string } | null;
  classe: { id: number; nom: string; sigle: string } | null;
  matiere: { id: number; nom: string; sigle: string } | null;
  periode: { id: number; nom: string; sigle: string } | null;
  tags: { id: number; nom: string; slug: string }[];
  fichier: FichierDocument | null;
  commentaires: CommentaireBibliotheque[] | null;
  signalements: SignalementBibliotheque[] | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface StatsBibliotheque {
  total: number;
  publies: number;
  brouillons: number;
  en_attente: number;
  refuses: number;
  archives: number;
  total_vues: number;
  total_telechargements: number;
  total_favoris: number;
  note_moyenne: number;
}

/** Dernier commentaire du dashboard (aperçu réduit). */
export interface DernierCommentaire {
  id: number;
  contenu: string;
  created_at: string | null;
  user: RefMinimal | null;
  document: { id: number; titre: string; slug: string } | null;
}

export interface DashboardBibliotheque extends StatsBibliotheque {
  derniers_commentaires: DernierCommentaire[];
}

/** Référentiels du formulaire de document. */
export interface ReferentielsBibliotheque {
  types: { id: number; nom: string; sigle: string }[];
  classes: { id: number; nom: string; sigle: string }[];
  matieres: { id: number; nom: string; sigle: string }[];
  periodes: { id: number; nom: string; sigle: string }[];
}

/** FormData de création/édition — le fichier est envoyé en multipart. */
export interface DocumentBibliothequeFormulaire {
  titre: string;
  description: string | null;
  resume: string | null;
  type_document_id: number | null;
  classe_id: number | null;
  matiere_id: number | null;
  periode_id: number | null;
  tags: string[];
  is_public: boolean;
  /** `brouillon` ou `en_attente` uniquement à la création. */
  statut: 'brouillon' | 'en_attente';
  fichier: File | null;
}

/** Ligne de la file de signalements (modération admin). */
export interface SignalementAdmin {
  id: number;
  motif: string;
  motif_libelle: string;
  description: string | null;
  precisions: string | null;
  statut: 'en_attente' | 'en_cours' | 'traite' | 'rejete';
  statut_libelle: string;
  note_admin: string | null;
  email_contact: string | null;
  anonyme: boolean;
  user: RefMinimal | null;
  traite_par: RefMinimal | null;
  traite_at: string | null;
  document: {
    id: number;
    titre: string;
    slug: string;
    statut: StatutDocumentBibliotheque;
    is_public: boolean;
    auteur: RefMinimal | null;
  } | null;
  created_at: string | null;
}

/** Motifs codifiés de signalement d'un document (API). */
export const MOTIFS_SIGNALEMENT: { valeur: string; libelle: string }[] = [
  { valeur: 'document_incorrect', libelle: 'Document incorrect' },
  { valeur: 'contenu_inapproprie', libelle: 'Contenu inapproprié' },
  { valeur: 'fichier_illisible', libelle: 'Fichier illisible' },
  { valeur: 'doublon', libelle: 'Doublon' },
  { valeur: 'probleme_droits', libelle: 'Problème de droits' },
  { valeur: 'autre', libelle: 'Autre' },
];

/* ============================================================
   T7A.6 — Objectifs pédagogiques (D-050)
   ============================================================ */

/** Ref minimale d'élève renvoyée par l'API objectifs. */
export interface RefEleveObjectif {
  id: number;
  nom?: string | null;
  prenom?: string | null;
  classe?: string | null;
}

/** Période telle que vue par l'écran objectifs (filtre + formulaire). */
export interface PeriodeObjectif {
  id: number;
  label: string;
  date_debut?: string | null;
  date_fin?: string | null;
  statut: StatutPeriode;
  est_ouverte: boolean;
}

export interface MatiereRefObjectif {
  id: number;
  nom: string;
  sigle?: string | null;
}

/** Ligne de ventilation d'un objectif — ses propres moyennes (D-050). */
export interface ObjectifMatiere {
  id: number;
  matiere: MatiereRefObjectif | null;
  moyenne_visee: number | null;
  moyenne_obtenue: number | null;
  commentaire: string | null;
}

export interface ObjectifPedagogique {
  id: number;
  eleve: RefEleveObjectif | null;
  periode: PeriodeObjectif | null;
  enseignant: { id: number; nom?: string | null; prenom?: string | null } | null;
  moyenne_visee: number | null;
  moyenne_obtenue: number | null;
  materiel_disponible: string | null;
  materiel_manquant: string | null;
  commentaire_admin: string | null;
  matieres: ObjectifMatiere[];
  created_at?: string | null;
  updated_at?: string | null;
}

/** Ligne de ventilation du formulaire. */
export interface LigneObjectifMatiere {
  matiere_id: number | null;
  moyenne_visee: number | null;
  moyenne_obtenue: number | null;
  commentaire: string | null;
}

export interface ObjectifFormulaire {
  eleve_id: number | null;
  periode_id: number | null;
  moyenne_visee: number | null;
  moyenne_obtenue: number | null;
  materiel_disponible: string | null;
  materiel_manquant: string | null;
  commentaire_admin: string | null;
  matieres: LigneObjectifMatiere[];
}

/* ============================================================
   Audit — journal d'activité du cabinet (M13)
   ============================================================ */

export interface EntreeAudit {
  id: number;
  action: string;
  description: string | null;
  level: 'info' | 'warning';
  utilisateur?: { id: number | null; nom_complet: string; email: string } | null;
  entite?: { type: string; id: number } | null;
  contexte?: Record<string, unknown> | null;
  ip: string | null;
  user_agent: string | null;
  date: string;
}

export interface FiltresAudit {
  action?: string;
  q?: string;
  du?: string;
  au?: string;
}

/* ============================================================
   Plateforme — contrat commercial et factures (P8)
   ============================================================ */

/** Q-A17 : montant null = non négocié ≠ 0 = gratuit négocié éxplicitement. */
export interface TarifsContratPlateforme {
  frais_creation_contrat: number | null;
  frais_contrat_actif: number | null;
  abonnement_mensuel_enseignant: number | null;
  abonnement_mensuel_parent: number | null;
  abonnement_mensuel_eleve: number | null;
  frais_rapport_mensuel: number | null;
  abonnement_annuel_admin: number | null;
  maintenance_annuelle_site: number | null;
  taux_commission_librairie: number | null;
  taux_commission_autres: number | null;
}

export interface ServiceContratPlateforme {
  libelle: string;
  description: string | null;
  categorie: string;
}

export interface MonContratPlateforme {
  numero: string;
  version: number;
  statut: string;
  date_effet: string | null;
  date_fin_validite: string | null;
  responsable_nom: string | null;
  modalites_paiement: string | null;
  conditions_particulieres: string | null;
  exonerations_codes: string[];
  tarifs: TarifsContratPlateforme;
  /** Catalogue éditable : clé → libellé/description/catégorie. */
  services: Record<string, ServiceContratPlateforme>;
  /** cle catégorie → libellé affichable. */
  categories: Record<string, string>;
  valide_le: string | null;
}

export interface FacturePlateforme {
  id: number;
  numero: string | null;
  periode_debut: string;
  periode_fin: string;
  statut: 'emise' | 'partiellement_payee' | 'payee';
  montant_total: number;
  montant_paye: number;
  emise_le: string | null;
}

export interface PaiementFacturePlateforme {
  montant: number;
  mode: string;
  reference: string | null;
  date: string;
}

export interface DetailFacturePlateforme extends FacturePlateforme {
  conditions_paiement: string | null;
  /** Détaillé facturé : le même tableau que sur le PDF. */
  lignes: Array<{
    libelle: string;
    type: string;
    sens: 'debit' | 'credit';
    quantite: number;
    prix_unitaire: number | null;
    montant: number;
    source: 'auto' | 'manuelle';
    motif: string | null;
  }>;
  paiements: PaiementFacturePlateforme[];
}
