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
  url?: string | null;
  lu: boolean;
  created_at?: string | null;
}