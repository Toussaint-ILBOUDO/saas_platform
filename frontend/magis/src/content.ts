/**
 * CONTENU MODÈLE — Magis Plus Center (D-044).
 * Tous les textes « modèle » de la page publique vivent ici (fichier unique) :
 * corrections en un lieu. Seules les données réellement variables restent en
 * base (fiche cabinet via /api/public/cabinet, actualités, FAQ, documents,
 * produits, enseignants, témoignages, statistiques).
 */
export const CONTENT = {
  cabinet: {
    nom: 'Magis Plus Center',
    sigle: 'MPC',
    slogan: "L'excellence pour tous",
    directeur: '',
  },

  // Palette Magis Plus Center — orange & bleu, sans dégradés.
  theme: {
    primaire: '#e8610c',
    primaireFonce: '#c24f08',
    primaireTint: '#fff4ec',
    bleu: '#12305e',
    bleuClair: '#1d4e89',
    blueTint: '#eef3fb',
    accent: '#f6a15c',
    fond: '#ffffff',
    surfaceTeintee: '#f6f8fc',
    texte: '#2b2b2b',
    texteDoux: '#5a6779',
    bordure: '#e5e9f2',
  },

  seo: {
    titre: 'Magis Plus Center | Cours d\'appui à domicile et en ligne au Burkina Faso',
    description:
      'Magis Plus Center, centre d\'accompagnement scolaire au Burkina Faso : cours d\'appui à domicile et en ligne, soutien scolaire du primaire au supérieur, aide aux devoirs, préparation aux examens et orientation.',
  },

  hero: {
    accroche: 'Bienvenue sur Magis Plus Center',
    sousTitre:
      "L'accompagnement scolaire de référence au Burkina Faso : cours à domicile, cours en ligne et soutien personnalisé du primaire au supérieur.",
    cta_principal: 'Demander un cours d\'appui',
    cta_secondaire: 'Découvrir nos services',
    stats: [
      { icone: 'bi-person-workspace', valeur: '50+', libelle: 'Enseignants qualifiés et vérifiés' },
      { icone: 'bi-people', valeur: 'Familles', libelle: 'accompagnées partout au pays' },
      { icone: 'bi-journal-bookmark-fill', valeur: 'Bibliothèque', libelle: 'de devoirs et d\'exercices' },
      { icone: 'bi-globe2', valeur: 'En ligne', libelle: 'Google Meet, WhatsApp, Teams' },
    ],
  },

  filDarianne: {
    accueil: 'Accueil',
    actualites: 'Actualités',
    bibliotheque: 'Bibliothèque numérique',
    boutique: 'Boutique scolaire',
    faq: 'Questions fréquentes',
    demandeCours: 'Demande de cours',
    contact: 'Contact',
  },

  about: {
    titre: 'À propos de Magis Plus Center',
    texte:
      "Magis Plus Center est un centre éducatif qui relie les parents, les élèves et les enseignants pour un accompagnement scolaire personnalisé, partout au Burkina Faso. Notre mission : donner à chaque apprenant les moyens de réussir, du primaire au supérieur.",
    atouts: [
      { icone: 'bi-person-badge-fill', titre: "Enseignants qualifiés et vérifiés", texte: 'Un réseau d\'enseignants expérimentés, sélectionnés sur leurs compétences académiques et pédagogiques.' },
      { icone: 'bi-journal-bookmark', titre: 'Bibliothèque de devoirs et d\'exercices', texte: 'Accès à des exercices, sujet d\'examens et ressources pédagogiques de qualité.' },
      { icone: 'bi-cart-check', titre: 'Boutique scolaire intégrée', texte: 'Fournitures et équipements pédagogiques disponibles pour accompagner chaque élève.' },
      { icone: 'bi-graph-up-arrow', titre: 'Suivi pédagogique rigoureux', texte: 'Relevés de progression, cahier de texte et compte rendu régulier aux familles.' },
    ],
  },

  services: {
    titre: 'Nos services',
    sousTitre: "Une plateforme complète pour accompagner la réussite scolaire et l'épanouissement des élèves.",
    liste: [
      { icone: 'bi-person-workspace', titre: "Cours d'appui", texte: 'Cours à domicile, en ligne ou en petit groupe avec des enseignants qualifiés, pour tous les niveaux scolaires.', cible: '/demande-cours', bouton: 'Demander un cours', aTelecharger: false },
      { icone: 'bi-shop', titre: 'Boutique scolaire', texte: 'Fournitures scolaires et équipements didactiques : cahiers, livres, stylos et matériels pédagogiques.', cible: '/boutique', bouton: 'Voir les produits', aTelecharger: false },
      { icone: 'bi-journal-bookmark-fill', titre: 'Bibliothèque numérique', texte: 'Devoirs, sujet d\'examens et documents pédagogiques sélectionnés pour chaque niveau.', cible: '/bibliotheque', bouton: 'Accéder', aTelecharger: false },
      { icone: 'bi-mic-fill', titre: 'Conférences éducatives', texte: 'Orientation scolaire et motivation pour faire les bons choix d\'avenir, du primaire au supérieur.', cible: '/#contact', bouton: 'Participer', aTelecharger: false },
      { icone: 'bi-mortarboard-fill', titre: 'Formations', texte: 'Renforcement pédagogique des apprenants et montée en compétences des enseignants.', cible: '/#contact', bouton: 'Découvrir', aTelecharger: false },
      { icone: 'bi-stars', titre: 'Accompagnement sur mesure', texte: 'Suivi personnalisé, méthodologie et solutions adaptées aux objectifs de chaque élève.', cible: '/#contact', bouton: 'En savoir plus', aTelecharger: false },
    ],
  },

  zones: {
    titre: 'Nos zones d\'intervention',
    sousTitre:
      "Magis Plus Center met à votre disposition des enseignants qualifiés pour des cours d'appui à domicile et en ligne dans plusieurs villes et communes du Burkina Faso.",
    blocTitre: 'Une présence dans plusieurs localités du Burkina Faso',
    blocTexte1:
      "Grâce à notre réseau d'enseignants expérimentés, nous accompagnons les élèves, étudiants et familles dans de nombreuses villes et communes.",
    blocTexte2:
      "Que vous cherchiez un professeur particulier, des cours à domicile ou un accompagnement scolaire régulier, Magis Plus Center est à votre service.",
    villesSecondaires: 'Ouagadougou, Komsilga, Pabré, Koubri, Saaba, Tanghin-Dassouri, Ziniaré, Zorgho',
    villesPrincipales: 'Bobo-Dioulasso, Koudougou, Manga, Pouytenga, Tenkodogo, Fada N\'Gourma, Réo, Kokologho, Léo, Yako, Koupéla, Po',
    horsZones: {
      titre: 'Vous ne voyez pas votre ville ?',
      lignes: [
        'Aucun problème.',
        'Nos cours en ligne via Google Meet, Microsoft Teams et WhatsApp permettent d\'accompagner des apprenants partout au Burkina Faso.',
        'Contactez-nous et nous trouverons l\'enseignant adapté à votre besoin.',
      ],
      cta: 'Demander un cours',
      whatsapp: 'WhatsApp',
    },
  },

  appointment: {
    titre: 'Demander un cours d\'appui',
    sousTitre:
      'Remplissez le formulaire et notre équipe vous contactera pour vous proposer un enseignant adapté aux besoins de votre enfant.',
    etapes: [
      { icone: 'bi-pencil-square', titre: '1. Remplissez la demande', texte: 'Informations sur l\'élève et les matières concernées.' },
      { icone: 'bi-telephone-fill', titre: '2. Nous vous contactons', texte: 'Validation des besoins et définition du programme.' },
      { icone: 'bi-person-workspace', titre: '3. Affectation d\'un enseignant', texte: 'Mise en relation rapide avec un enseignant qualifié.' },
    ],
    bouton: 'Faire une demande de cours',
  },

  solutions: {
    titre: 'Nos solutions éducatives',
    sousTitre:
      "Magis Plus Center accompagne les élèves, étudiants et familles partout au Burkina Faso avec des services éducatifs complets : cours d'appui, cours à domicile, cours en ligne, bibliothèque numérique, boutique scolaire et orientation académique.",
    onglets: [
      {
        icone: 'bi-mortarboard-fill',
        nom: "Cours d'appui",
        titre: "Cours d'appui au Burkina Faso",
        intro: 'Un accompagnement scolaire personnalisé pour améliorer les résultats et renforcer la confiance des apprenants.',
        paragraphes: [
          "Magis Plus Center met à disposition des enseignants qualifiés pour des cours d'appui destinés aux élèves du primaire, du collège, du lycée et de l'enseignement supérieur. Nos programmes couvrent les matières scientifiques, littéraires, techniques et professionnelles.",
          "Nous intervenons dans plusieurs villes du Burkina Faso pour aider les élèves à préparer leurs devoirs, examens et concours dans les meilleures conditions.",
        ],
      },
      {
        icone: 'bi-house-door-fill',
        nom: 'Cours à domicile',
        titre: 'Cours à domicile et cours en ligne',
        intro: 'Des enseignants disponibles selon votre emploi du temps.',
        paragraphes: [
          "Nos cours à domicile permettent aux élèves de bénéficier d'un suivi personnalisé directement chez eux, avec des enseignants expérimentés.",
          "Pour les apprenants éloignés ou qui souhaitent plus de flexibilité, Magis Plus Center propose également des cours en ligne via Google Meet, Microsoft Teams et WhatsApp partout au Burkina Faso.",
        ],
      },
      {
        icone: 'bi-book-fill',
        nom: 'Bibliothèque en ligne',
        titre: 'Bibliothèque numérique',
        intro: 'Une base documentaire complète pour apprendre efficacement.',
        paragraphes: [
          "Accédez à des livres numériques, sujet d'examens, annales corrigées, devoirs, exercices pratiques et ressources pédagogiques.",
          "Notre bibliothèque aide élèves, étudiants et enseignants à trouver rapidement les documents nécessaires à leur réussite.",
        ],
      },
      {
        icone: 'bi-shop',
        nom: 'Boutique scolaire',
        titre: 'Boutique scolaire',
        intro: 'Tout le nécessaire pour accompagner la réussite scolaire.',
        paragraphes: [
          'La boutique Magis Plus Center propose fournitures scolaires, manuels, cahiers, stylos, équipements pédagogiques et matériels didactiques.',
          'Parents, élèves et enseignants trouvent rapidement les outils indispensables à l\'apprentissage et à la préparation des examens.',
        ],
      },
      {
        icone: 'bi-mic-fill',
        nom: 'Formations & conférences',
        titre: 'Formations et conférences éducatives',
        intro: 'Développer les compétences pour réussir à l\'école et dans la vie professionnelle.',
        paragraphes: [
          "Magis Plus Center organise régulièrement conférences, ateliers et formations sur la réussite scolaire, les méthodes de travail, l'orientation académique, le leadership et le développement personnel.",
          'Ces activités permettent aux élèves, étudiants, parents et enseignants de mieux préparer leur avenir.',
        ],
      },
    ],
  },

  enseignants: {
    titre: 'Nos enseignants qualifiés',
    sousTitre:
      "Magis Plus Center met à votre disposition des enseignants expérimentés pour les cours d'appui, les cours à domicile et les cours en ligne partout au Burkina Faso. Chacun est sélectionné selon ses compétences académiques, son expérience pédagogique et sa maîtrise des matières enseignées.",
    vide: 'La liste de nos enseignants arrive bientôt.',
    cta_titre: 'Besoin d\'un enseignant qualifié pour votre enfant ?',
    cta_texte: 'Nous vous aidons à trouver rapidement un enseignant adapté au niveau, à la matière et aux objectifs de l\'apprenant.',
  },

  temoignages: {
    titre: 'Témoignages',
    sousTitre:
      "Découvrez ce que les parents, enseignants et élèves disent de Magis Plus Center : des progrès réels, un accompagnement humain et des résultats concrets.",
    vide: 'Les témoignages de nos familles arrivent bientôt. Partagez dès maintenant votre expérience !',
  },

  contact: {
    titre: 'Contactez Magis Plus Center',
    sousTitre:
      "Besoin d'un enseignant qualifié pour votre enfant ? Magis Plus Center vous accompagne partout au Burkina Faso pour les cours d'appui à domicile, les cours en ligne, le soutien scolaire, la préparation aux examens et l'orientation académique.",
    items: [
      { icone: 'bi-telephone', titre: 'Téléphone' },
      { icone: 'bi-envelope', titre: 'Email' },
      { icone: 'bi-geo-alt', titre: 'Zones couvertes' },
      { icone: 'bi-clock', titre: 'Horaires' },
    ],
    carteTitre: 'Trouvez rapidement un enseignant adapté à vos besoins',
    carteTexte:
      "Que vous recherchiez un enseignant pour le primaire, le collège, le lycée ou l'université, Magis Plus Center vous met en relation avec des enseignants qualifiés partout au Burkina Faso.",
    solutions: [
      { icone: 'bi-house-door-fill', titre: 'Cours à domicile', texte: 'Accompagnement personnalisé directement chez vous.' },
      { icone: 'bi-laptop-fill', titre: 'Cours en ligne', texte: 'Google Meet, Teams, WhatsApp et autres plateformes.' },
      { icone: 'bi-journal-bookmark-fill', titre: 'Bibliothèque numérique', texte: 'Livres, exercices, devoirs et sujet d\'examens.' },
      { icone: 'bi-shop', titre: 'Boutique scolaire', texte: 'Fournitures et équipements pédagogiques.' },
    ],
    appeler: 'Appeler maintenant',
    whatsapp: 'WhatsApp',
    envoyerEmail: 'Envoyer un email',
    demandeCours: 'Remplir une demande de cours',
  },

  demandeCours: {
    titre: 'Demande de cours d\'appui',
    sousTitre:
      'Remplissez le formulaire ci-dessous et notre équipe vous contactera pour organiser le meilleur accompagnement.',
    etapesForm: [
      '1. Informations du parent',
      '2. Détails du cours',
      '3. Message complémentaire',
    ],
    success: 'Demande de cours envoyée avec succès. Nous vous contacterons très vite.',
    envoi: 'Envoyer la demande',
  },

  faq: {
    titre: 'Questions fréquentes',
    sousTitre: 'Tout ce qu\'il faut savoir sur nos services, l\'inscription et le déroulement des cours.',
    vide: 'Les réponses à vos questions arrivent bientôt. En attendant, n\'hésitez pas à nous contacter.',
  },

  bibliotheque: {
    titre: 'Bibliothèque numérique',
    sousTitre: 'Devoirs, sujet d\'examens et documents pédagogiques pour accompagner la réussite de chaque élève.',
    vide: 'Aucun document publié pour le moment.',
    telechargements: 'téléchargements',
    vues: 'vues',
  },

  boutique: {
    titre: 'Boutique scolaire',
    sousTitre: 'Fournitures scolaires et équipements pédagogiques, disponibles pour accompagner chaque élève.',
    vide: 'Aucun produit disponible pour le moment.',
    commander: 'Commander',
    fraisLivraison: 'Livraison',
  },

  phraseDefaut: {
    nomCabinet: 'Magis Plus Center',
    sansTelephone: '—',
  },

  footer: {
    aPropos:
      "Magis Plus Center accompagne les élèves du primaire, du collège, du lycée et du supérieur partout au Burkina Faso grâce à un réseau d'enseignants qualifiés, vrais gages de réussite.",
    navigation: 'Navigation',
    navItems: [
      { libelle: 'Accueil', cible: '/' },
      { libelle: 'À propos', cible: '/#about' },
      { libelle: 'Services', cible: '/#services' },
      { libelle: 'Actualités', cible: '/actualites' },
      { libelle: 'Bibliothèque', cible: '/bibliotheque' },
      { libelle: 'Contact', cible: '/contact' },
    ],
    services: 'Nos services',
    serviceItems: [
      { libelle: 'Cours à domicile', cible: '/demande-cours' },
      { libelle: 'Cours en ligne', cible: '/demande-cours' },
      { libelle: 'Bibliothèque numérique', cible: '/bibliotheque' },
      { libelle: 'Boutique scolaire', cible: '/boutique' },
      { libelle: 'Questions fréquentes', cible: '/faq' },
    ],
    atouts: 'Pourquoi Magis Plus Center ?',
    atoutItems: [
      'Enseignants qualifiés et vérifiés',
      'Présent dans tout le Burkina Faso',
      'Suivi pédagogique personnalisé',
      'Comptes rendus réguliers aux familles',
      'Accompagnement du primaire au supérieur',
    ],
    droits: 'Tous droits réservés.',
    conçuPar: 'Conçu et développé par Magis Plus Center',
    paiements: 'Moyens de paiement : Orange Money, Moov Money, Wave, espèces.',
  },

  nav: {
    liens: [
      { libelle: 'Accueil', cible: '/' },
      { libelle: 'À propos', cible: '/#about' },
      { libelle: 'Services', cible: '/#services' },
      { libelle: 'Bibliothèque', cible: '/bibliotheque' },
      { libelle: 'Actualités', cible: '/actualites' },
      { libelle: 'Boutique', cible: '/boutique' },
      { libelle: 'FAQ', cible: '/faq' },
      { libelle: 'Contact', cible: '/#contact' },
    ],
    demande: 'Demander un cours',
  },
} as const;