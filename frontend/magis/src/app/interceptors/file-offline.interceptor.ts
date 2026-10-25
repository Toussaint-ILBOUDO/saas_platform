import { inject } from '@angular/core';
import {
  HttpErrorResponse,
  HttpHeaders,
  HttpInterceptorFn,
  HttpResponse,
} from '@angular/common/http';
import { catchError, from, mergeMap, of, throwError } from 'rxjs';
import { EntreeFile, HorsLigneService } from '../services/hors-ligne.service';

/**
 * File d'écritures hors-ligne (T4.5).
 *
 * Périmètre volontairement étroit (Q9) : **la saisie du cahier de texte
 * enseignant**, qui est la seule écriture autorisée hors-ligne. Le reste de
 * l'API garde son comportement — erreur « Aucune connexion internet » — car
 * l'envoyer en file reviendrait à promettre une synchronisation que personne
 * n'a couverte.
 *
 * Deux entrées dans la même file :
 *  - **réseau absent avant l'envoi** (`navigator.onLine === false`) : on ne
 *    part même pas, la saisie est mise en file d'emblée ;
 *  - **réseau tombé pendant l'envoi** (statut 0) : la requête est morte in
 *    transit — on met en file, quitte à réémettre une écriture que le serveur
 *    a déjà vue : `uuid_client` (D-060) transforme cette réémission en reprise.
 *
 * Les réémissions de la synchro n'utilisent pas HttpClient (elles vont en
 * `fetch`) : elles ne retombent donc jamais ici, et un échec réseau pendant
 * la synchro **conserve** l'entrée au lieu de la remettre en file une seconde
 * fois.
 */

/** Écritures éligibles : saisie du cahier de texte (création, correction, retrait). */
const CHEMIN_FILE = /^\/api\/enseignant\/cahiers-textes(\/|$)/;
const METHODES_FILE = new Set(['POST', 'PUT', 'DELETE']);

function estFileable(requete: { method: string; url: string; body: unknown }): boolean {
  if (!METHODES_FILE.has(requete.method)) return false;
  if (!CHEMIN_FILE.test(requete.url)) return false;
  // Un corps doit être sérialisable ; une suppression n'en a pas.
  if (requete.method === 'DELETE') return true;
  return typeof requete.body === 'object' && requete.body !== null;
}

/** « Séance du 2026-10-14 » / « Séance n° 42 » : ce que dira le bandeau. */
function libelle(requete: { method: string; url: string; body: unknown }): string {
  if (requete.method === 'POST') {
    const corps = requete.body as { date_seance?: unknown } | null;
    return typeof corps?.date_seance === 'string' && corps.date_seance
      ? `Séance du ${corps.date_seance}`
      : 'Nouvelle séance';
  }
  const id = requete.url.match(/cahiers-textes\/(\d+)/)?.[1];
  return id ? `Séance n° ${id}` : 'Séance';
}

export const fileOfflineInterceptor: HttpInterceptorFn = (requete, suite) => {
  if (!estFileable(requete)) return suite(requete);

  const horsLigne = inject(HorsLigneService);

  const mettreEnFile = () => {
    const entree: Omit<EntreeFile, 'sequence'> = {
      url: requete.urlWithParams,
      methode: requete.method as EntreeFile['methode'],
      corps: requete.body ?? null,
      libelle: libelle(requete),
      horodatage: Date.now(),
    };

    return from(horsLigne.mettreEnFile(entree)).pipe(
      // Réponse de **succès** (202) avec l'en-tête que lit `ApiService` :
      // l'écran annonce « mise en file », jamais « enregistrée ».
      mergeMap(() =>
        of(
          new HttpResponse({
            status: 202,
            body: {},
            headers: new HttpHeaders({ 'X-MPC-File': '1' }),
          })
        )
      ),
      // Stockage indisponible (mode privé) : on retente l'envoi, et
      // l'erreur réseau originale remontera telle quelle.
      catchError(() => suite(requete))
    );
  };

  if (typeof navigator !== 'undefined' && !navigator.onLine) return mettreEnFile();

  return suite(requete).pipe(
    catchError((erreur) => {
      if (erreur instanceof HttpErrorResponse && (erreur.status === 0 || erreur.status === null)) {
        return mettreEnFile();
      }
      return throwError(() => erreur);
    })
  );
};
