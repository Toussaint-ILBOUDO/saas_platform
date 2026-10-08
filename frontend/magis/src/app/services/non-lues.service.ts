import { Injectable, inject, signal } from '@angular/core';
import { ApiService } from './api.service';

/**
 * Compteur de notifications non lues, partagé entre la cloche de la coquille
 * (`EspaceComponent`) et l'écran « Notifications ».
 *
 * Chaque marquage « lu » (simple clic, ouverture d'une ressource, tout-lire,
 * suppression) décrémente le signal **immédiatement** : c'est ce compteur que
 * l'utilisateur lit en permanence, il ne doit jamais attendre un aller-retour
 * qui n'arrive pas. La coquille recharge aussi le compteur au démarrage, et
 * l'écran resynchronise à chaque chargement de liste.
 */
@Injectable({ providedIn: 'root' })
export class NonLuesService {
  private readonly api = inject(ApiService);

  readonly nonLues = signal(0);

  /** Recompte sur le serveur (source de vérité : `meta.non_lues`). */
  recharger(): void {
    this.api.getNotifications(1, 1).subscribe({
      next: (r) => this.nonLues.set(r.meta?.non_lues ?? 0),
    });
  }

  /** Marquage « lu » optimiste : la cloche descend sans attendre. */
  diminuer(): void {
    this.nonLues.update((v) => Math.max(0, v - 1));
  }
}