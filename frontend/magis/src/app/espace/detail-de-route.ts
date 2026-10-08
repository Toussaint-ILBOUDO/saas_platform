import { ActivatedRoute } from '@angular/router';
import { firstValueFrom, type Observable } from 'rxjs';

/**
 * Ouvre la fiche ciblée par un identifiant dans l'URL
 * (`/espace/finance/factures/12`, `/espace/modules/mes-contrats/3`, …).
 *
 * C'est la destination des notifications : `Notification::route_angular`
 * renvoie un chemin `…/{id}` pour que le lecteur arrive sur **l'objet** et non
 * sur la liste. Sans ce retrait d'identifiant, chaque écran devrait réécrire
 * la même quinzaine de lignes (lire le paramètre, valider, charger, ouvrir).
 *
 * L'abonnement aux paramètres couvre la réutilisation du composant : cliquer
 * deux notifications de même type de suite ne relance pas `ngOnInit`.
 *
 * Une erreur de chargement est confiée au composant : c'est lui qui sait si
 * il affiche un bandeau, un message ou un toast.
 */
export function ouvrirDetailDepuisRoute<T>(
  route: ActivatedRoute,
  charger: (id: number) => Observable<T>,
  ouvrir: (fiche: T) => void,
  enErreur?: (erreur: unknown) => void
): void {
  route.paramMap.subscribe((params) => {
    const brut = params.get('id');
    if (!brut) return;

    const id = Number(brut);
    if (!Number.isFinite(id) || id <= 0) return;

    firstValueFrom(charger(id))
      .then(ouvrir)
      .catch((erreur) => enErreur?.(erreur));
  });
}
