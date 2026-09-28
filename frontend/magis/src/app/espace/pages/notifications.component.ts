import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { MetaPage, NotificationEspace } from '../../models';
import { messageErreurApi } from '../../services/messages';

/** Écran « Notifications » : liste, marquage lu, suppression, tout lire. */
@Component({
  selector: 'espace-notifications',
  imports: [],
  styles: [
    `
      :host {
        display: block;
        max-width: 720px;
        margin: 0 auto;
      }
      .entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.1rem;
      }
      .entete h1 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .btn-lire {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.55rem 0.95rem;
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.8rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
      }
      .btn-lire:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .liste {
        display: grid;
        gap: 0.7rem;
      }
      .notif {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        padding: 1rem 1.1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        cursor: pointer;
        transition: border-color 0.15s ease;
      }
      .notif._nonlue {
        border-color: var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
      }
      .notif:hover {
        border-color: var(--mpc-primaire);
      }
      .notif-icone {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 0.85rem;
        font-size: 1.15rem;
        color: var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
      }
      .notif-corps {
        flex: 1;
        min-width: 0;
      }
      .notif-titre {
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .notif-titre b {
        font-size: 0.9rem;
      }
      .point {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--mpc-primaire);
      }
      .notif-contenu {
        margin: 0.2rem 0 0;
        color: var(--mpc-texte-doux);
        font-size: 0.83rem;
      }
      .notif-date {
        margin-top: 0.35rem;
        font-size: 0.72rem;
        color: var(--mpc-texte-doux);
      }
      .notif-actions {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
      }
      .mini {
        width: 32px;
        height: 32px;
        display: grid;
        place-items: center;
        border: none;
        border-radius: 0.6rem;
        background: transparent;
        color: var(--mpc-texte-doux);
        cursor: pointer;
      }
      .mini:hover {
        color: var(--mpc-danger);
        background: var(--mpc-danger-tint);
      }
      .vide {
        padding: 3rem 1rem;
        text-align: center;
        color: var(--mpc-texte-doux);
        border: 1px dashed var(--mpc-separateur);
        border-radius: 1rem;
      }
      .vide i {
        font-size: 2rem;
        display: block;
        margin-bottom: 0.6rem;
        opacity: 0.5;
      }
      .toast {
        margin-bottom: 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: 0.8rem;
        font-size: 0.86rem;
        font-weight: 600;
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
    `,
  ],
  template: `
    <div class="entete">
      <h1>Notifications</h1>
      @if (nonLues() > 0) {
        <button class="btn-lire" type="button" (click)="toutLire()"><i class="bi bi-check2-all"></i> Tout marquer comme lu</button>
      }
    </div>

    @if (toast()) {
      <div class="toast"><i class="bi bi-exclamation-triangle"></i>{{ toast() }}</div>
    }

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else if (notifications().length === 0) {
      <div class="vide"><i class="bi bi-bell-slash"></i> Aucune notification pour le moment.</div>
    } @else {
      <div class="liste">
        @for (n of notifications(); track n.id) {
          <div class="notif" [class._nonlue]="!n.lu" (click)="marquer(n)">
            <span class="notif-icone"><i class="bi {{ n.icone || 'bi-bell' }}"></i></span>
            <div class="notif-corps">
              <div class="notif-titre">
                <b>{{ n.titre }}</b>
                @if (!n.lu) {
                  <span class="point"></span>
                }
              </div>
              @if (n.contenu) {
                <p class="notif-contenu">{{ n.contenu }}</p>
              }
              <div class="notif-date">{{ dater(n.created_at) }}</div>
            </div>
            <div class="notif-actions">
              <button class="mini" type="button" title="Supprimer" (click)="supprimer($event, n)"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
        }
      </div>
    }
  `,
})
export class NotificationsComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly notifications = signal<NotificationEspace[]>([]);
  protected readonly charge = signal(true);
  protected readonly toast = signal('');

  protected readonly nonLues = computed(() => this.notifications().filter((n) => !n.lu).length);

  ngOnInit(): void {
    this.charger();
  }

  protected async marquer(n: NotificationEspace): Promise<void> {
    if (n.lu) return;
    this.notifications.update((liste) => liste.map((x) => (x.id === n.id ? { ...x, lu: true } : x)));
    try {
      await this.api.marquerNotificationLue(n.id).toPromise();
    } catch {
      this.notifications.update((liste) => liste.map((x) => (x.id === n.id ? { ...x, lu: false } : x)));
    }
  }

  protected async toutLire(): Promise<void> {
    this.notifications.update((liste) => liste.map((x) => ({ ...x, lu: true })));
    try {
      await this.api.lireToutesNotifications().toPromise();
    } catch (e) {
      this.toast.set(messageErreurApi(e));
      this.charger();
    }
  }

  protected async supprimer(evenement: Event, n: NotificationEspace): Promise<void> {
    evenement.stopPropagation();
    try {
      await this.api.supprimerNotification(n.id).toPromise();
      this.notifications.update((liste) => liste.filter((x) => x.id !== n.id));
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  protected dater(iso?: string | null): string {
    if (!iso) return '';
    return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
  }

  private charger(): void {
    this.charge.set(true);
    this.api.getNotifications(1, 50).subscribe({
      next: (r) => {
        this.notifications.set(r.data);
        this.charge.set(false);
      },
      error: (e) => {
        this.toast.set(messageErreurApi(e));
        this.charge.set(false);
      },
    });
  }
}