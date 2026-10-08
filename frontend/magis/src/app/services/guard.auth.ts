import { inject } from '@angular/core';
import { Router, type CanActivateFn } from '@angular/router';
import { AuthService } from './auth.service';

/** Accès à l'espace cabinet : authentifié obligatoire, sinon → connexion. */
export const espaceGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);
  const router = inject(Router);
  await auth.initialiser();
  if (!auth.utilisateur()) return router.parseUrl('/connexion');
  return true;
};

/** Accès réservé à l'administrateur cabinet (ex. gestion utilisateurs). */
export const roleAdminGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);
  const router = inject(Router);
  await auth.initialiser();
  if (!auth.utilisateur()) return router.parseUrl('/connexion');
  if (auth.roleActif() !== 'admin_cabinet') return router.parseUrl('/espace');
  return true;
};

/**
 * Accès réservé à une liste de rôles (écran partagé par plusieurs espaces).
 *
 * L'administrateur n'est volontairement pas inclus : le serveur répond 403 sur
 * ces endpoints (`role:enseignant` / `role:eleve|parent`), donc le laisser
 * entrer produirait un écran vide et une erreur réseau. Le garde renvoie vers
 * l'accueil plutôt que d'afficher une page morte.
 */
export function roleGuard(...rolesAutorises: string[]): CanActivateFn {
  return async () => {
    const auth = inject(AuthService);
    const router = inject(Router);
    await auth.initialiser();
    if (!auth.utilisateur()) return router.parseUrl('/connexion');
    if (!rolesAutorises.includes(auth.roleActif())) return router.parseUrl('/espace');
    return true;
  };
}

/**
 * Périmètre pédagogique : l'enseignant **écrit** (planning, cahier de texte),
 * parent et élève **lisent**. Ce trio n'est pas propre au planning — le cahier
 * de texte s'adresse exactement aux mêmes rôles — d'où un garde nommé par le
 * domaine plutôt que par le premier écran qui l'a utilisé.
 */
export const rolePedagogieGuard = roleGuard('enseignant', 'parent', 'eleve');