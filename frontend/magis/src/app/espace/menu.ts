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
      items: [{ libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' }],
    },
    {
      libelle: 'Pédagogie',
      items: [
        { libelle: 'Référentiels', icone: 'bi-journal-bookmark', route: '/espace/pedagogie/referentiels' },
        { libelle: 'Contrats de cours', icone: 'bi-file-earmark-text', route: '/espace/pedagogie/contrats' },
        { libelle: 'Rapports mensuels', icone: 'bi-file-earmark-bar-graph', route: '/espace/pedagogie/rapports-mensuels' },
        { libelle: 'Modèle de rapport', icone: 'bi-ui-checks', route: '/espace/pedagogie/modele-rapport' },
        { libelle: 'Demandes de cours', icone: 'bi-journal-text', route: '/espace/pedagogie/demandes-cours' },
      ],
    },
    {
      libelle: 'Finance',
      items: [
        { libelle: 'Périodes comptables', icone: 'bi-calendar-range', route: '/espace/finance/periodes' },
        { libelle: 'Factures', icone: 'bi-receipt', route: '/espace/finance/factures' },
        { libelle: 'Bulletins de paie', icone: 'bi-cash-stack', route: '/espace/finance/bulletins-paie' },
      ],
    },
    {
      items: [{ libelle: 'Rapports', icone: 'bi-file-earmark-bar-graph', route: '/espace/rapports' }],
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
        { libelle: 'Mes cours', icone: 'bi-journal-bookmark', route: '/espace/modules/mes-cours' },
        { libelle: 'Mon planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
        { libelle: 'Cahier de texte', icone: 'bi-journal-text', route: '/espace/modules/cahier-de-texte' },
        { libelle: 'Rapports mensuels', icone: 'bi-file-earmark-bar-graph', route: '/espace/modules/rapports-mensuels' },
        { libelle: 'Mes bulletins de paie', icone: 'bi-cash-stack', route: '/espace/modules/mes-bulletins' },
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
        { libelle: 'Cahier de texte', icone: 'bi-journal-text', route: '/espace/modules/cahier-de-texte' },
        { libelle: 'Mes contrats', icone: 'bi-file-earmark-text', route: '/espace/modules/mes-contrats' },
        { libelle: 'Mes factures', icone: 'bi-receipt', route: '/espace/modules/mes-factures' },
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
        { libelle: 'Mes cours', icone: 'bi-journal-bookmark', route: '/espace/modules/mes-cours' },
        { libelle: 'Mon planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
        { libelle: 'Cahier de texte', icone: 'bi-journal-text', route: '/espace/modules/cahier-de-texte' },
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
  admin_cabinet: ['Pédagogie', 'Bibliothèque', 'Boutique', 'Témoignages'],
  enseignant: ['Objectifs', 'Bibliothèque'],
  parent: ['Mes enfants', 'Paiements', 'Bibliothèque'],
  eleve: ['Évaluations', 'Documents', 'Bibliothèque'],
  gestionnaire_librairie: ['Catégories', 'Produits', 'Commandes'],
};

export const ONGLETS_MOBILE_PAR_ROLE: Record<RoleEspace, OngletMobile[]> = {
  admin_cabinet: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'referentiels', libelle: 'Référentiels', icone: 'bi-journal-bookmark', route: '/espace/pedagogie/referentiels' },
    { id: 'contrats', libelle: 'Contrats', icone: 'bi-file-earmark-text', route: '/espace/pedagogie/contrats' },
    { id: 'periodes', libelle: 'Périodes', icone: 'bi-calendar-range', route: '/espace/finance/periodes' },
    // « Finance » reste dans le tiroir : c'est encore un écran « module à
    // venir », il n'a pas sa place dans une barre de 5 entrées à côté de trois
    // écrans livrés. Le tiroir y donne accès, et la barre garde « Plus ».
    { id: 'plus', libelle: 'Plus', icone: 'bi-grid-3x3-gap' },
  ],
  enseignant: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'cours', libelle: 'Mes cours', icone: 'bi-journal-bookmark', route: '/espace/modules/mes-cours' },
    { id: 'planning', libelle: 'Planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
    { id: 'cahier', libelle: 'Cahier', icone: 'bi-journal-text', route: '/espace/modules/cahier-de-texte' },
    { id: 'plus', libelle: 'Plus', icone: 'bi-grid-3x3-gap' },
  ],
  parent: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'planning', libelle: 'Planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
    { id: 'cahier', libelle: 'Cahier', icone: 'bi-journal-text', route: '/espace/modules/cahier-de-texte' },
    { id: 'notifs', libelle: 'Messages', icone: 'bi-bell', route: '/espace/notifications' },
    { id: 'plus', libelle: 'Plus', icone: 'bi-grid-3x3-gap' },
  ],
  eleve: [
    { id: 'accueil', libelle: 'Accueil', icone: 'bi-house-door', route: '/espace' },
    { id: 'cours', libelle: 'Cours', icone: 'bi-journal-bookmark', route: '/espace/modules/mes-cours' },
    { id: 'planning', libelle: 'Planning', icone: 'bi-calendar3', route: '/espace/modules/planning' },
    { id: 'cahier', libelle: 'Cahier', icone: 'bi-journal-text', route: '/espace/modules/cahier-de-texte' },
    { id: 'notifs', libelle: 'Messages', icone: 'bi-bell', route: '/espace/notifications' },
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
    phrase: 'Cours hebdomadaires par élève : créneaux, matières et enseignants.',
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

  // Une fiche ouverte par identifiant (`/mes-contrats/12`, `/contrats/7`)
  // appartient à l'écran de la liste correspondante : sans ce retrait, aucun
  // libellé ne correspond et la barre supérieure afficherait « Espace cabinet ».
  if (segments.length > 2 && /^\d+$/.test(segments[segments.length - 1])) {
    segments.pop();
  }

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
  if (racine === '/espace/pedagogie/referentiels') return 'Référentiels';
  if (racine === '/espace/pedagogie/contrats') return 'Contrats de cours';
  if (racine === '/espace/pedagogie/demandes-cours') return 'Demandes de cours';
  if (racine === '/espace/modules/mes-cours') return 'Mes cours';
  if (racine === '/espace/finance/periodes') return 'Périodes comptables';
  if (racine === '/espace/finance/factures') return 'Factures';
  if (racine === '/espace/finance/bulletins-paie') return 'Bulletins de paie';
  if (racine === '/espace/modules/mes-factures') return 'Mes factures';
  if (racine === '/espace/modules/mes-contrats') return 'Mes contrats';
  if (racine === '/espace/modules/mes-bulletins') return 'Mes bulletins de paie';
  if (racine === '/espace/pedagogie/rapports-mensuels') return 'Rapports mensuels';
  if (racine === '/espace/modules/rapports-mensuels') return 'Rapports mensuels';
  if (racine === '/espace/utilisateurs') return 'Utilisateurs';
  if (racine === '/espace/fiche-cabinet') return 'Fiche du cabinet';
  if (racine === '/espace/faq') return 'FAQ';
  if (racine === '/espace/notifications') return 'Notifications';
  if (racine === '/espace/profil') return 'Mon profil';
  return 'Espace cabinet';
}