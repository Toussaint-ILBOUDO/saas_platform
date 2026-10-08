import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { NonLuesService } from '../../services/non-lues.service';
import { MetaPage, NotificationEspace } from '../../models';
import { messageErreurApi } from '../../services/messages';

/** Écran « Notifications » : liste filtrée, marquage lu, suppression, tout lire. */
@Component({
  selector: 'espace-notifications',
  imports: [FormsModule],
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
      .entete .compteur {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
        padding: 0.22rem 0.55rem;
        border-radius: 99px;
        margin-left: 0.5rem;
        vertical-align: middle;
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
      .filtres {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        align-items: center;
        margin-bottom: 1rem;
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
      .notif-lien {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.5rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--mpc-primaire);
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
      }
      .notif-lien:hover {
        text-decoration: underline;
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
      <h1>
        Notifications
        @if (nonLues() > 0) {
          <span class="compteur">{{ nonLues() }} non-lue{{ nonLues() > 1 ? 's' : '' }}</span>
        }
      </h1>
      @if (nonLues() > 0) {
        <button class="btn-lire" type="button" (click)="toutLire()"><i class="bi bi-check2-all"></i> Tout marquer comme lu</button>
      }
    </div>

    <div class="filtres">
      <select
        class="form-select form-select-sm w-auto"
        [ngModel]="filtreType()"
        (ngModelChange)="changerType($event)"
      >
        <option value="">Tous les types</option>
        @for (t of TYPES; track t.code) {
          <option [value]="t.code">{{ t.libelle }}</option>
        }
      </select>
      <select class="form-select form-select-sm w-auto" [ngModel]="filtreLu() === null ? '' : filtreLu() ? '1' : '0'" (ngModelChange)="changerLu($event)">
        <option value="">Toutes</option>
        <option value="0">Non lues</option>
        <option value="1">Lues</option>
      </select>
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
              @if (n.route_angular) {
                <button
                  class="notif-lien"
                  type="button"
                  (click)="ouvrir($event, n)"
                >
                  {{ n.action_label || 'Ouvrir' }} <i class="bi bi-arrow-right"></i>
                </button>
              }
            </div>
            <div class="notif-actions">
              <button class="mini" type="button" title="Supprimer" (click)="supprimer($event, n)"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
        }
      </div>

      @if (meta() && meta()!.last_page > 1) {
        <nav class="mt-3">
          <ul class="pagination pagination-sm justify-content-center">
            <li class="page-item" [class.disabled]="page() <= 1">
              <button class="page-link" (click)="changerPage(page() - 1)">Précédent</button>
            </li>
            <li class="page-item disabled">
              <span class="page-link">Page {{ page() }} / {{ meta()!.last_page }}</span>
            </li>
            <li class="page-item" [class.disabled]="page() >= meta()!.last_page">
              <button class="page-link" (click)="changerPage(page() + 1)">Suivant</button>
            </li>
          </ul>
        </nav>
      }
    }
  `,
})
export class NotificationsComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);
  private readonly compteur = inject(NonLuesService);

  /**
   * Les types proposed sont ceux qui ont un écran correspondant. Les autres
   * restent accessibles via « Tous les types » : la liste ne doit jamais
   * cacher une notification parce que son écran n'existe pas encore.
   */
  protected readonly TYPES = [
    { code: 'demande_cours', libelle: 'Demandes de cours' },
    { code: 'rapport', libelle: 'Rapports mensuels' },
    { code: 'bulletin_paie', libelle: 'Bulletins de paie' },
    { code: 'facture', libelle: 'Factures' },
    { code: 'contrat', libelle: 'Contrats' },
    { code: 'temoignage', libelle: 'Témoignages' },
    { code: 'actualite', libelle: 'Actualités' },
  ];

  protected readonly notifications = signal<NotificationEspace[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly page = signal(1);
  protected readonly filtreType = signal('');
  /** `true` = « déjà lue » (l'API filtre `lu=1`), `false` = « non lue ». */
  protected readonly filtreLu = signal<boolean | null>(null);
  protected readonly charge = signal(true);
  protected readonly toast = signal('');

  /**
   * Vient de `meta.non_lues` (total du cabinet), pas d'un recomptage sur la
   * page affichée : filtrer sur « Traitées » ne doit pas faire croire qu'il
   * n'y a plus rien à lire.
   */
  protected readonly nonLues = signal(0);

  protected readonly vide = computed(() => this.notifications().length === 0);

  ngOnInit(): void {
    this.charger();
  }

  protected async marquer(n: NotificationEspace): Promise<void> {
    if (n.lu) return;
    this.notifications.update((liste) => liste.map((x) => (x.id === n.id ? { ...x, lu: true } : x)));
    this.compteur.diminuer();
    this.nonLues.update((v) => Math.max(0, v - 1));
    try {
      await firstValueFrom(this.api.marquerNotificationLue(n.id));
    } catch {
      this.notifications.update((liste) => liste.map((x) => (x.id === n.id ? { ...x, lu: false } : x)));
      this.nonLues.update((v) => v + 1);
      this.compteur.recharger();
      this.toast.set('Impossible de marquer cette notification comme lue.');
    }
  }

  /**
   * Ouvre la ressource dans l'espace.
   *
   * `route_angular` et non `url` : ce dernier est une URL Blade absolue
   * (domaine central, backoffice historique) qui sortirait de l'application.
   */
  protected ouvrir(evenement: Event, n: NotificationEspace): void {
    evenement.stopPropagation();

    if (!n.route_angular) return;

    void this.marquer(n);
    void this.router.navigateByUrl(n.route_angular);
  }

  protected async toutLire(): Promise<void> {
    try {
      await firstValueFrom(this.api.lireToutesNotifications());
      await this.charger();
      this.compteur.nonLues.set(0);
    } catch (e) {
      this.toast.set(messageErreurApi(e));
      this.charger();
    }
  }

  protected async supprimer(evenement: Event, n: NotificationEspace): Promise<void> {
    evenement.stopPropagation();

    const etaitNonLue = !n.lu;

    try {
      await firstValueFrom(this.api.supprimerNotification(n.id));
      this.notifications.update((liste) => liste.filter((x) => x.id !== n.id));
      if (etaitNonLue) {
        this.nonLues.update((v) => Math.max(0, v - 1));
        this.compteur.diminuer();
      }
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  protected changerType(type: string): void {
    this.filtreType.set(type);
    this.page.set(1);
    this.charger();
  }

  protected changerLu(valeur: string): void {
    this.filtreLu.set(valeur === '' ? null : valeur === '1');
    this.page.set(1);
    this.charger();
  }

  protected changerPage(p: number): void {
    if (p < 1) return;
    this.page.set(p);
    this.charger();
  }

  protected dater(iso?: string | null): string {
    if (!iso) return '';
    return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
  }

  private charger(): void {
    this.charge.set(true);

    this.api
      .getNotifications(this.page(), 20, {
        type: this.filtreType() || undefined,
        lu: this.filtreLu() ?? undefined,
      })
      .subscribe({
        next: (r) => {
          this.notifications.set(r.data);
          this.meta.set(r.meta ?? null);
          this.nonLues.set(r.meta?.non_lues ?? 0);
          // La cloche de la coquille partage le même compteur : la lire ici,
          // à chaque chargement, la garde synchronisée même sans décrément
          // local (ex. un « tout lire » fait autre part).
          this.compteur.nonLues.set(r.meta?.non_lues ?? 0);
          this.charge.set(false);
        },
        error: (e) => {
          this.toast.set(messageErreurApi(e));
          this.charge.set(false);
        },
      });
  }
}