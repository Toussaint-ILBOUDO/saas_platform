/**
 * Navigation du backoffice cabinet (P5.3) par rôle. Les rubriques listent les
 * écrans disponibles ; la "feuille de route" montre les modules à venir
 * (icônes grisées, non navigables tant que l'API n'existe pas).
 */

export type RoleEspace = 'admin_cabinet' | 'enseignant' | 'parent' | 'eleve' | 'gestionnaire_librairie';

export const ROLE_LIBELLE: Record<string, string> = {
  admin_cabinet: 'Administrateur',
  enseignant: 'Enseignant',
  parent: 'Parent',
  eleve: 'Élève',
  gestionnaire_librairie: 'Gestionnaire librairie',
};

export interface ItemEspace {
  libelle: string;
  icone: string;
  route: string;
}

export interface RubriqueEspace {
  libelle?: string;
  items: ItemEspace[];
}

export interface OngletMobile {
  id: string;
  libelle: string;
  icone: string;
  route?: string;
}

export const RUBRIQUES_PAR_ROLE: Record<RoleEspace, RubriqueEspace[]> = {
  admin_cabinet: [
    {
      items: [
        { libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
        { libelle: 'Finance', icone: 'bi-wallet2', route: '/espace/finance' },
        { libelle: 'Rapports', icone: 'bi-file-earmark-bar-graph', route: '/espace/rapports' },
      ],
    },
    {
      libelle: 'La communauté',
      items: [{ libelle: 'Utilisateurs', icone: 'bi-people', route: '/espace/utilisateurs' }],
    },
    {
      libelle: 'Contenus du site',
      items: [
        { libelle: 'Actualités', icone: 'bi-newspaper', route: '/espace/actualites' },
        { libelle: 'FAQ', icone: 'bi-question-circle', route: '/espace/faq' },
        { libelle: 'Fiche du cabinet', icone: 'bi-shop-window', route: '/espace/fiche-cabinet' },
      ],
    },
    {
      libelle: 'Mon espace',
      items: [
        { libelle: 'Notifications', icone: 'bi-bell', route: '/espace/notifications' },
        { libelle: 'Mon profil', icone: 'bi-person-circle', route: '/espace/profil' },
      ],
    },
  ],
  enseignant: [
    {
      items: [
        { libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
        { libelle: 'Mon planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
      ],
    },
    {
      libelle: 'Mon espace',
      items: [
        { libelle: 'Notifications', icone: 'bi-bell', route: '/espace/notifications' },
        { libelle: 'Mon profil', icone: 'bi-person-circle', route: '/espace/profil' },
      ],
    },
  ],
  parent: [
    {
      items: [
        { libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
        { libelle: 'Planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
      ],
    },
    {
      libelle: 'Mon espace',
      items: [
        { libelle: 'Notifications', icone: 'bi-bell', route: '/espace/notifications' },
        { libelle: 'Mon profil', icone: 'bi-person-circle', route: '/espace/profil' },
      ],
    },
  ],
  eleve: [
    {
      items: [
        { libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
        { libelle: 'Mes cours', icone: 'bi-book', route: '/espace/modules/cours' },
      ],
    },
    {
      libelle: 'Mon espace',
      items: [
        { libelle: 'Notifications', icone: 'bi-bell', route: '/espace/notifications' },
        { libelle: 'Mon profil', icone: 'bi-person-circle', route: '/espace/profil' },
      ],
    },
  ],
  gestionnaire_librairie: [
    {
      items: [
        { libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
        { libelle: 'Boutique', icone: 'bi-bag', route: '/espace/modules/boutique' },
      ],
    },
    {
      libelle: 'Mon espace',
      items: [
        { libelle: 'Notifications', icone: 'bi-bell', route: '/espace/notifications' },
        { libelle: 'Mon profil', icone: 'bi-person-circle', route: '/espace/profil' },
      ],
    },
  ],
};

export const ROADMAP_PAR_ROLE: Record<RoleEspace, string[]> = {
  admin_cabinet: ['Pédagogie', 'Planning', 'Bibliothèque', 'Boutique', 'Témoignages'],
  enseignant: ['Mes cours', 'Cahiers de textes', 'Rapports mensuels', 'Objectifs', 'Bulletins de paie', 'Bibliothèque'],
  parent: ['Mes enfants', 'Factures', 'Paiements', 'Bibliothèque'],
  eleve: ['Mon planning', 'Évaluations', 'Documents', 'Bibliothèque'],
  gestionnaire_librairie: ['Catégories', 'Produits', 'Commandes'],
};

export const ONGLETS_MOBILE_PAR_ROLE: Record<RoleEspace, OngletMobile[]> = {
  admin_cabinet: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'finance', libelle: 'Finance', icone: 'bi-wallet2', route: '/espace/finance' },
    { id: 'rapports', libelle: 'Rapports', icone: 'bi-file-earmark-bar-graph', route: '/espace/rapports' },
    { id: 'plus', libelle: 'Plus', icone: 'bi-grid-3x3-gap' },
  ],
  enseignant: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'planning', libelle: 'Planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
    { id: 'notifs', libelle: 'Messages', icone: 'bi-bell', route: '/espace/notifications' },
    { id: 'plus', libelle: 'Plus', icone: 'bi-grid-3x3-gap' },
  ],
  parent: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'planning', libelle: 'Planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
    { id: 'notifs', libelle: 'Messages', icone: 'bi-bell', route: '/espace/notifications' },
    { id: 'plus', libelle: 'Plus', icone: 'bi-grid-3x3-gap' },
  ],
  eleve: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'cours', libelle: 'Cours', icone: 'bi-book', route: '/espace/modules/cours' },
    { id: 'notifs', libelle: 'Messages', icone: 'bi-bell', route: '/espace/notifications' },
    { id: 'plus', libelle: 'Plus', icone: 'bi-grid-3x3-gap' },
  ],
  gestionnaire_librairie: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'boutique', libelle: 'Boutique', icone: 'bi-bag', route: '/espace/modules/boutique' },
    { id: 'notifs', libelle: 'Messages', icone: 'bi-bell', route: '/espace/notifications' },
    { id: 'plus', libelle: 'Plus', icone: 'bi-grid-3x3-gap' },
  ],
};

export interface ModuleDescription {
  titre: string;
  icone: string;
  phrase: string;
}

export const MODULES_DESCRIPTION: Record<string, ModuleDescription> = {
  finance: {
    titre: 'Finance & paie',
    icone: 'bi-wallet2',
    phrase: 'Périodes comptables, factures, bulletins de paie, paiements enseignants et facturation du cabinet.',
  },
  rapports: {
    titre: 'Rapports & objectifs',
    icone: 'bi-file-earmark-bar-graph',
    phrase: 'Rapports mensuels des enseignants et objectifs pédagogiques, avec suivi de validation.',
  },
  planning: {
    titre: 'Planning des cours',
    icone: 'bi-calendar3',
    phrase: 'Emplois du temps et planning par classe, enseignant ou élève.',
  },
  cours: {
    titre: 'Mes cours',
    icone: 'bi-book',
    phrase: 'Le détail des cours, séances et contenus associés.',
  },
  boutique: {
    titre: 'Boutique en ligne',
    icone: 'bi-bag',
    phrase: 'Catégories, produits, commandes et dashboard boutique.',
  },
};

/** Retrouve un libellé de page à partir du chemin courant (topbar). */
export function titreDeRoute(chemin: string): string {
  const segments = chemin.split('/').filter(Boolean);
  const racine = '/' + segments.join('/');
  if (segments[0] === 'espace' && segments[1] === 'actualites' && segments.length > 2) return 'Actualités';
  for (const rubriques of Object.values(RUBRIQUES_PAR_ROLE)) {
    for (const rubrique of rubriques) {
      for (const item of rubrique.items) {
        if (item.route === racine) return item.libelle;
      }
    }
  }
  if (segments[0] === 'espace' && segments[1] === 'modules' && segments[2]) {
    const module = MODULES_DESCRIPTION[segments[2]];
    if (module) return module.titre;
  }
  if (racine === '/espace/actualites') return 'Actualités';
  if (racine === '/espace/utilisateurs') return 'Utilisateurs';
  if (racine === '/espace/fiche-cabinet') return 'Fiche du cabinet';
  if (racine === '/espace/faq') return 'FAQ';
  if (racine === '/espace/notifications') return 'Notifications';
  if (racine === '/espace/profil') return 'Mon profil';
  return 'Espace cabinet';
}