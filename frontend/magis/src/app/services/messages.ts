/**
 * Traduction des réponses d'erreur de l'API (convention backend : message = code
 * technique en MAJUSCULES, erreurs = détail par champ) en messages lisibles.
 */
const CODES: Record<string, string> = {
  IDENTIFIANTS_INCORRECTS: 'Adresse e-mail ou mot de passe incorrects.',
  COMPTE_ELEVE_INACTIF: 'Ce compte est inactif. Contactez le cabinet.',
  AUCUN_ROLE: 'Aucun rôle n’est associé à ce compte.',
  NON_CONNECTE: 'Vous devez être connecté pour accéder à cette rubrique.',
  ACCES_REFUSE: 'Vous n’avez pas accès à cette rubrique.',
  INTROUVABLE: 'Élément introuvable.',
  VALIDATION_ECHOUEE: 'Vérifiez les champs du formulaire.',
  MOT_DE_PASSE_ACTUEL_INCORRECT: 'Le mot de passe actuel est incorrect.',
  MOT_DE_PASSE_OBLIGATOIRE: 'Le mot de passe est obligatoire.',
  EMAIL_INCONNU: 'Adresse e-mail inconnue.',
  JETON_INVALIDE: 'Lien ou code de réinitialisation invalide ou expiré.',
  ROLE_INVALIDE: 'Rôle invalide ou non autorisé.',
  CABINET_SUSPENDU: 'Ce cabinet est suspendu. Contactez l’administrateur.',
};

export function messageErreurApi(erreur: unknown): string {
  const err = erreur as { status?: unknown; error?: unknown } | undefined;
  const statut = typeof err?.status === 'number' ? err.status : null;
  const erreurs = (err?.error as { erreurs?: unknown } | undefined)?.erreurs as
    | Record<string, string[]>
    | undefined;
  const message = (err?.error as { message?: unknown } | undefined)?.message;

  if (erreurs && typeof erreurs === 'object') {
    const lignes: string[] = [];
    for (const champ of Object.keys(erreurs)) {
      for (const m of erreurs[champ] ?? []) lignes.push(String(m));
    }
    if (lignes.length) return lignes.join(' ');
  }

  if (typeof message === 'string' && message) {
    if (CODES[message]) return CODES[message];
    if (/^[A-Z][A-Z0-9_ ]+$/.test(message)) return 'Une erreur est survenue. Réessayez.';
    return message;
  }

  if (statut === null || statut === 0) {
    return 'Serveur injoignable. Vérifiez que le backend est démarré.';
  }
  if (statut === 502 || statut === 503 || statut === 504) {
    return 'Serveur temporairement indisponible. Réessayez dans un instant.';
  }
  if (statut >= 500) {
    return 'Erreur serveur. Consultez backend/storage/logs/laravel.log.';
  }
  return 'Une erreur est survenue. Réessayez.';
}

export function messageSuccesApi(reponse: unknown, defaut: string): string {
  const r = reponse as { message?: unknown };
  const message = r?.message;
  if (typeof message === 'string' && message && !/^[A-Z][A-Z0-9_ ]+$/.test(message)) return message;
  return defaut;
}