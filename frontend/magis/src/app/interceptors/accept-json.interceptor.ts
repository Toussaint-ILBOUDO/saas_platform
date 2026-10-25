import { HttpInterceptorFn } from '@angular/common/http';

/**
 * En-tête `Accept: application/json` sur TOUTES les requêtes API :
 * Laravel renvoie alors les erreurs de validation au format JSON
 * (`erreurs`, `code`) attendu par `messageErreurApi`, au lieu d'une
 * page HTML de debug.
 */
export const acceptJsonInterceptor: HttpInterceptorFn = (req, next) => {
  if (req.url.startsWith('/api')) {
    req = req.clone({ setHeaders: { Accept: 'application/json' } });
  }
  return next(req);
};
