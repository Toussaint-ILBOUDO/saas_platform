import { Component, OnInit, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { ActualiteBackoffice, MetaPage } from '../../models';
import { messageErreurApi } from '../../services/messages';

/** Écran admin « Actualités » : liste filtrable, pagination, suppression. */
@Component({
  imports: [RouterLink],
  selector: 'espace-actualites',
  styles: [
    `
      :host {
        display: block;
      }
      .barre {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.9rem;
        margin-bottom: 1.1rem;
        flex-wrap: wrap;
      }
      .barre h1 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .auto {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
      }
      .recherche {
        position: relative;
      }
      .recherche i {
        position: absolute;
        left: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--mpc-texte-doux);
      }
      .recherche input {
        width: 240px;
        height: 44px;
        padding: 0 0.8rem 0 2.4rem;
        border-radius: 0.8rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.88rem;
        outline: none;
      }
      .recherche input:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .filtre {
        height: 44px;
        padding: 0 0.8rem;
        border-radius: 0.8rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.88rem;
        outline: none;
        cursor: pointer;
      }
      .btn-nouvelle {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1rem;
        border: none;
        border-radius: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.88rem;
        text-decoration: none;
        white-space: nowrap;
      }
      .btn-nouvelle:hover {
        background: var(--mpc-primaire-fonce);
        color: #fff;
      }
      .liste {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
      }
      .article {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
      }
      .article-image {
        height: 150px;
        background: var(--mpc-surface-teintee);
        display: grid;
        place-items: center;
        color: var(--mpc-texte-doux);
        font-size: 2.4rem;
        overflow: hidden;
      }
      .article-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .article-corps {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        padding: 0.9rem 1rem 1rem;
      }
      .badges {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
      }
      .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
      }
      .badge._publie {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .badge._brouillon {
        background: var(--mpc-attention-tint);
        color: var(--mpc-attention);
      }
      .badge._ligne {
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
      }
      .badge._horsligne {
        background: var(--mpc-abandon);
        color: var(--mpc-texte-doux);
      }
      .article-titre {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        line-height: 1.35;
      }
      .article-meta {
        color: var(--mpc-texte-doux);
        font-size: 0.76rem;
      }
      .article-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: auto;
        padding-top: 0.6rem;
      }
      .bouton-ico {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.8rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
      }
      .bouton-ico:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .bouton-ico._danger:hover {
        border-color: var(--mpc-danger);
        color: var(--mpc-danger);
      }
      .vide {
        padding: 3rem 1rem;
        text-align: center;
        color: var(--mpc-texte-doux);
        border-radius: 1rem;
        border: 1px dashed var(--mpc-separateur);
      }
      .vide i {
        font-size: 2rem;
        display: block;
        margin-bottom: 0.6rem;
        opacity: 0.5;
      }
      .pagination {
        display: flex;
        justify-content: center;
        gap: 0.4rem;
        margin-top: 1.4rem;
      }
      .page {
        min-width: 40px;
        height: 40px;
        padding: 0 0.6rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.86rem;
        font-weight: 700;
        cursor: pointer;
      }
      .page._courante {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .page:disabled {
        opacity: 0.4;
        cursor: not-allowed;
      }
      .toast {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: 0.8rem;
        font-size: 0.86rem;
        font-weight: 600;
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      @media (max-width: 640px) {
        .recherche input {
          width: 100%;
        }
        .auto,
        .recherche {
          flex: 1;
        }
        .btn-nouvelle {
          flex: 1;
          justify-content: center;
        }
      }
    `,
  ],
  template: `
    <div class="barre">
      <h1>Actualités</h1>
      <a class="btn-nouvelle" routerLink="/espace/actualites/nouvelle"><i class="bi bi-plus-lg"></i> Nouvelle actualité</a>
    </div>

    <div class="auto" style="margin-bottom:1rem">
      <label class="recherche" style="flex:1">
        <i class="bi bi-search"></i>
        <input type="search" #terme [value]="recherche" (keyup.enter)="rechercher(terme.value)" placeholder="Rechercher une actualité…" />
      </label>
      <select class="filtre" [value]="statut()" (change)="changerStatut($event)">
        <option value="">Tous les statuts</option>
        <option value="publie">Publiées</option>
        <option value="brouillon">Brouillons</option>
      </select>
    </div>

    @if (toast()) {
      <div class="toast"><i class="bi bi-exclamation-triangle"></i>{{ toast() }}</div>
    }

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i>Chargement…</div>
    } @else if (actualites().length === 0) {
      <div class="vide">
        <i class="bi bi-newspaper"></i>
        Aucune actualité pour le moment. Pensez à en créer une&nbsp;!
      </div>
    } @else {
      <div class="liste">
        @for (a of actualites(); track a.id) {
          <article class="article">
            <div class="article-image">
              @if (a.image_url) {
                <img [src]="a.image_url" [alt]="a.titre" loading="lazy" />
              } @else {
                <i class="bi bi-newspaper"></i>
              }
            </div>
            <div class="article-corps">
              <div class="badges">
                <span class="badge" [class._publie]="a.est_publiee" [class._brouillon]="!a.est_publiee">
                  {{ a.est_publiee ? 'Publiée' : 'Brouillon' }}
                </span>
                <span class="badge" [class._ligne]="a.is_active" [class._horsligne]="!a.is_active">
                  {{ a.is_active ? 'En ligne' : 'Hors ligne' }}
                </span>
              </div>
              <h2 class="article-titre">{{ a.titre }}</h2>
              <p class="article-meta">
                @if (a.published_at) {
                  Publiée le {{ dater(a.published_at) }}
                } @else {
                  Mise à jour le {{ dater(a.updated_at) }}
                }
                @if (a.auteur) {
                  · {{ a.auteur.prenom }} {{ a.auteur.nom }}
                }
              </p>
              <div class="article-actions">
                <a class="bouton-ico" [routerLink]="['/espace', 'actualites', a.id, 'editer']"><i class="bi bi-pencil"></i> Modifier</a>
                <button class="bouton-ico _danger" type="button" (click)="supprimer(a)"><i class="bi bi-trash"></i> Supprimer</button>
              </div>
            </div>
          </article>
        }
      </div>

      @if (meta() && (meta()?.last_page ?? 1) > 1) {
        <nav class="pagination" aria-label="Pagination des actualités">
          <button class="page" type="button" [disabled]="(meta()?.current_page ?? 1) <= 1" (click)="aller((meta()?.current_page ?? 1) - 1)">
            <i class="bi bi-chevron-left"></i>
          </button>
          @for (p of pages(); track p) {
            <button class="page" [class._courante]="p === (meta()?.current_page ?? 1)" type="button" (click)="aller(p)">{{ p }}</button>
          }
          <button class="page" type="button" [disabled]="(meta()?.current_page ?? 1) >= (meta()?.last_page ?? 1)" (click)="aller((meta()?.current_page ?? 1) + 1)">
            <i class="bi bi-chevron-right"></i>
          </button>
        </nav>
      }
    }
  `,
})
export class ActualitesBackofficeComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly actualites = signal<ActualiteBackoffice[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly charge = signal(true);
  protected readonly toast = signal('');
  protected readonly statut = signal('');
  protected recherche = '';

  protected readonly pages = () => {
    const meta = this.meta();
    if (!meta || meta.last_page <= 1) return [1];
    const courante = meta.current_page;
    const debut = Math.max(1, courante - 2);
    const fin = Math.min(meta.last_page, courante + 2);
    const liste: number[] = [];
    for (let i = debut; i <= fin; i++) liste.push(i);
    return liste;
  };

  ngOnInit(): void {
    this.charger(1);
  }

  protected rechercher(valeur: string): void {
    this.recherche = valeur.trim();
    this.charger(1);
  }

  protected changerStatut(evenement: Event): void {
    this.statut.set((evenement.target as HTMLSelectElement).value);
    this.charger(1);
  }

  protected aller(page: number): void {
    this.charger(page);
  }

  protected async supprimer(a: ActualiteBackoffice): Promise<void> {
    if (!confirm(`Supprimer « ${a.titre} » ? Cette action est définitive.`)) return;
    try {
      await this.api.supprimerActualite(a.id).toPromise();
      const page = Math.max(1, this.meta()?.current_page ?? 1);
      this.charger(page);
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  protected dater(iso?: string | null): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso));
  }

  private charger(page: number): void {
    this.charge.set(true);
    this.api.getActualitesAdmin(page, 12, this.recherche, this.statut()).subscribe({
      next: (r) => {
        this.actualites.set(r.data);
        this.meta.set(r.meta);
        this.charge.set(false);
      },
      error: (e) => {
        this.toast.set(messageErreurApi(e));
        this.charge.set(false);
      },
    });
  }
}