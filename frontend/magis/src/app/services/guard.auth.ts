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