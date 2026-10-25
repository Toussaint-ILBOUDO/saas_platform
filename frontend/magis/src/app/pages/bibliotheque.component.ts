import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { AuthService } from '../services/auth.service';
import { SeoService } from '../services/seo.service';
import { messageErreurApi } from '../services/messages';
import { RevealDirective } from '../directives/reveal.directive';
import { PageHeroComponent } from '../components/page-hero.component';
import { DocumentBibliotheque, ReferentielsBibliothequePublique } from '../models';

/** Bibliothèque publique : recherche, filtres, tri, pagination, réactions, favoris. */
@Component({
  selector: 'app-bibliotheque',
  imports: [CommonModule, FormsModule, RouterLink, RevealDirective, PageHeroComponent],
  styles: [
    `
      .recherche {
        position: relative;
        max-width: 560px;
        margin: 0 auto 1.25rem;
      }
      .recherche .bi-search {
        position: absolute;
        left: 1.1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--mpc-primaire);
      }
      .recherche input {
        width: 100%;
        border: 1px solid var(--mpc-bordure);
        border-radius: 999px;
        padding: 0.8rem 1.2rem 0.8rem 2.8rem;
        font-size: 0.95rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        outline: none;
        box-shadow: 0 2px 10px rgba(18, 48, 94, 0.05);
      }
      .recherche input:focus {
        border-color: var(--mpc-primaire);
      }
      .filtres {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: center;
        margin-bottom: 2rem;
      }
      .filtres select {
        border: 1px solid var(--mpc-bordure);
        border-radius: 999px;
        padding: 0.45rem 1rem;
        font-size: 0.86rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        outline: none;
      }
      .filtres select:focus {
        border-color: var(--mpc-primaire);
      }
      .doc-carte {
        border: 1px solid var(--mpc-bordure);
        border-radius: 16px;
        background: var(--mpc-fond);
        padding: 1.3rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: transform 0.22s ease, box-shadow 0.22s ease;
      }
      .doc-carte:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(18, 48, 94, 0.1);
      }
      .doc-emblem {
        width: 3rem;
        height: 3rem;
        border-radius: 12px;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        display: grid;
        place-items: center;
        font-size: 1.35rem;
        margin-bottom: 0.9rem;
      }
      .doc-carte h2 {
        font-size: 1.04rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        line-height: 1.35;
      }
      .doc-carte p {
        color: var(--mpc-texte-doux);
        font-size: 0.9rem;
        line-height: 1.65;
        flex: 1;
      }
      .doc-tag {
        display: inline-block;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu);
        border-radius: 999px;
        padding: 0.3rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 600;
        margin: 0 0.3rem 0.3rem 0;
      }
      .doc-meta {
        display: flex;
        gap: 0.9rem;
        font-size: 0.8rem;
        color: var(--mpc-texte-doux);
        flex-wrap: wrap;
      }
      .doc-meta i {
        color: var(--mpc-primaire);
      }
      .doc-actions {
        margin-top: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        flex-wrap: wrap;
      }
      .btn-lire {
        background: var(--mpc-bleu);
        color: #fff;
        border-radius: 999px;
        padding: 0.55rem 1.2rem;
        font-weight: 700;
        font-size: 0.88rem;
        text-decoration: none;
        display: inline-flex;
        gap: 0.45rem;
        align-items: center;
      }
      .btn-lire:hover {
        background: var(--mpc-primaire);
      }
      .reaction-btn {
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-texte-doux);
        border-radius: 999px;
        padding: 0.35rem 0.7rem;
        font-size: 0.8rem;
        cursor: pointer;
        display: inline-flex;
        gap: 0.3rem;
        align-items: center;
        transition: border-color 0.15s ease, color 0.15s ease;
      }
      .reaction-btn:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .reaction-btn._actif-aime {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
      }
      .reaction-btn._actif-nonne {
        border-color: var(--mpc-bleu);
        color: var(--mpc-bleu);
        background: var(--mpc-bleu-tint);
      }
      .favori-btn {
        border: 0;
        background: transparent;
        color: var(--mpc-texte-doux);
        font-size: 1.1rem;
        cursor: pointer;
        padding: 0.2rem;
      }
      .favori-btn._actif {
        color: var(--mpc-accent);
      }
      .note {
        color: var(--mpc-accent);
        font-weight: 700;
        font-size: 0.85rem;
      }
      .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2.5rem;
      }
      .pagination button {
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        border-radius: 10px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        cursor: pointer;
      }
      .pagination button:disabled {
        opacity: 0.4;
        cursor: default;
      }
      .pagination button:not(:disabled):hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .erreur {
        background: #fdecea;
        color: #b3261e;
        border-radius: 12px;
        padding: 0.8rem 1.2rem;
        margin-bottom: 1.5rem;
        text-align: center;
        font-size: 0.9rem;
      }
    `,
  ],
  template: `
    <app-page-hero [titre]="CONTENT.bibliotheque.titre" [sousTitre]="CONTENT.bibliotheque.sousTitre"></app-page-hero>

    <section class="py-5">
      <div class="container">
        <div class="recherche" appReveal>
          <i class="bi bi-search"></i>
          <input type="search" name="recherche" placeholder="Rechercher un document, une matière…"
                 [(ngModel)]="recherche" (ngModelChange)="surRecherche()" aria-label="Rechercher un document" />
        </div>

        <div class="filtres" appReveal>
          <select [(ngModel)]="filtreType" (ngModelChange)="recharger()" aria-label="Type de document">
            <option [ngValue]="null">Tous les types</option>
            <option *ngFor="let t of referentiels().types" [ngValue]="t.id">{{ t.nom }}</option>
          </select>
          <select [(ngModel)]="filtreMatiere" (ngModelChange)="recharger()" aria-label="Matière">
            <option [ngValue]="null">Toutes les matières</option>
            <option *ngFor="let m of referentiels().matieres" [ngValue]="m.id">{{ m.nom }}</option>
          </select>
          <select [(ngModel)]="filtreClasse" (ngModelChange)="recharger()" aria-label="Classe">
            <option [ngValue]="null">Toutes les classes</option>
            <option *ngFor="let c of referentiels().classes" [ngValue]="c.id">{{ c.nom }}</option>
          </select>
          <select [(ngModel)]="tri" (ngModelChange)="recharger()" aria-label="Trier">
            <option value="recent">Plus récents</option>
            <option value="populaire">Plus vus</option>
            <option value="apprecies">Plus appréciés</option>
            <option value="note">Mieux notés</option>
          </select>
        </div>

        <p class="erreur" *ngIf="erreur()">{{ erreur() }}</p>

        <p class="text-center" style="color: var(--mpc-texte-doux)" *ngIf="documents().length === 0 && charge()">
          <i class="bi bi-inbox" style="color:var(--mpc-texte-doux); font-size:2.2rem; display:block; margin-bottom:.5rem"></i>
          {{ CONTENT.bibliotheque.vide }}
        </p>

        <div class="row g-4" *ngIf="documents().length > 0">
          <div class="col-12 col-sm-6 col-lg-4" *ngFor="let document of documents()" appReveal>
            <article class="doc-carte">
              <div class="doc-emblem"><i class="bi bi-file-earmark-text"></i></div>
              <h2>
                <a [routerLink]="['/bibliotheque', document.slug]" style="color:inherit;text-decoration:none">
                  {{ document.titre }}
                </a>
              </h2>
              <p>{{ document.resume }}</p>
              <div *ngIf="document.matiere || document.classe || document.type_document">
                <span class="doc-tag" *ngIf="document.type_document">{{ document.type_document }}</span>
                <span class="doc-tag" *ngIf="document.matiere">{{ document.matiere }}</span>
                <span class="doc-tag" *ngIf="document.classe">{{ document.classe }}</span>
              </div>
              <div class="doc-meta">
                <span *ngIf="document.nb_telechargements"><i class="bi bi-download"></i> {{ document.nb_telechargements }}</span>
                <span *ngIf="document.nb_vues"><i class="bi bi-eye"></i> {{ document.nb_vues }}</span>
                <span class="note" *ngIf="document.note_moyenne"><i class="bi bi-star-fill"></i> {{ formaterNote(document.note_moyenne) }}</span>
              </div>
              <div class="doc-actions">
                <a class="btn-lire" [routerLink]="['/bibliotheque', document.slug]">
                  <i class="bi bi-book"></i>Consulter
                </a>
                <div class="d-flex align-items-center gap-2">
                  <button type="button" class="reaction-btn" [class._actif-aime]="document.ma_reaction === 'aime'"
                          (click)="reagir(document, 'aime')" [disabled]="enCoursReaction() === document.slug"
                          [attr.aria-label]="'J\u2019aime'">
                    <i class="bi bi-hand-thumbs-up"></i>{{ document.nb_aimes ?? 0 }}
                  </button>
                  <button type="button" class="reaction-btn" [class._actif-nonne]="document.ma_reaction === 'n_aime_pas'"
                          (click)="reagir(document, 'n_aime_pas')" [disabled]="enCoursReaction() === document.slug"
                          [attr.aria-label]="'Je n\u2019aime pas'">
                    <i class="bi bi-hand-thumbs-down"></i>{{ document.nb_n_aimes ?? 0 }}
                  </button>
                  <button type="button" class="favori-btn" [class._actif]="document.is_favori"
                          (click)="basculerFavori(document)"
                          [attr.aria-label]="document.is_favori ? 'Retirer des favoris' : 'Ajouter aux favoris'">
                    <i class="bi" [class.bi-heart-fill]="document.is_favori" [class.bi-heart]="!document.is_favori"></i>
                  </button>
                </div>
              </div>
            </article>
          </div>
        </div>

        <div class="pagination" *ngIf="pagesTotales() > 1">
          <button type="button" (click)="changerPage(page() - 1)" [disabled]="page() <= 1">
            <i class="bi bi-chevron-left"></i> Précédent
          </button>
          <span class="align-self-center" style="color:var(--mpc-texte-doux);font-size:.9rem">
            Page {{ page() }} / {{ pagesTotales() }}
          </span>
          <button type="button" (click)="changerPage(page() + 1)" [disabled]="page() >= pagesTotales()">
            Suivant <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </section>
  `,
})
export class BibliothequeComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);
  private readonly auth = inject(AuthService);

  protected readonly CONTENT = CONTENT;

  protected readonly documents = signal<DocumentBibliotheque[]>([]);
  protected readonly referentiels = signal<ReferentielsBibliothequePublique>({
    types: [],
    matieres: [],
    classes: [],
  });
  protected readonly charge = signal(false);
  protected readonly erreur = signal<string | null>(null);
  protected readonly page = signal(1);
  protected readonly pagesTotales = signal(1);
  protected readonly enCoursReaction = signal<string | null>(null);

  protected recherche = '';
  protected filtreType: number | null = null;
  protected filtreMatiere: number | null = null;
  protected filtreClasse: number | null = null;
  protected tri: 'recent' | 'populaire' | 'apprecies' | 'note' = 'recent';

  private timer?: ReturnType<typeof setTimeout>;

  ngOnInit(): void {
    this.seo.definir('Bibliothèque numérique | Magis Plus Center', CONTENT.bibliotheque.sousTitre);
    this.api.getReferentielsBibliothequePublique().subscribe({
      next: (refs) => this.referentiels.set(refs),
      error: () => this.referentiels.set({ types: [], matieres: [], classes: [] }),
    });
    this.charger();
  }

  protected charger(): void {
    this.erreur.set(null);
    this.api
      .getDocuments(this.page(), 12, this.recherche, {
        type_document_id: this.filtreType,
        classe_id: this.filtreClasse,
        matiere_id: this.filtreMatiere,
        sort: this.tri,
      })
      .subscribe({
        next: ({ data, meta }) => {
          this.documents.set(data);
          this.pagesTotales.set(meta?.last_page ?? 1);
          this.charge.set(true);
        },
        error: (e) => {
          this.erreur.set(messageErreurApi(e));
          this.charge.set(true);
        },
      });
  }

  protected recharger(): void {
    this.page.set(1);
    this.charger();
  }

  protected surRecherche(): void {
    if (this.timer) clearTimeout(this.timer);
    this.timer = setTimeout(() => this.recharger(), 350);
  }

  protected changerPage(p: number): void {
    this.page.set(p);
    this.charger();
  }

  protected reagir(document: DocumentBibliotheque, reaction: 'aime' | 'n_aime_pas'): void {
    if (this.enCoursReaction()) return;
    this.enCoursReaction.set(document.slug);
    this.api.reagirDocument(document.slug, reaction).subscribe({
      next: (r) => {
        this.documents.update((docs) =>
          docs.map((d) =>
            d.slug === document.slug
              ? { ...d, ma_reaction: r.reaction, nb_aimes: r.nb_aimes, nb_n_aimes: r.nb_n_aimes }
              : d
          )
        );
        this.enCoursReaction.set(null);
      },
      error: (e) => {
        this.erreur.set(messageErreurApi(e));
        this.enCoursReaction.set(null);
      },
    });
  }

  protected basculerFavori(document: DocumentBibliotheque): void {
    if (!this.auth.utilisateur()) {
      this.erreur.set('Connectez-vous pour gérer vos favoris.');
      return;
    }
    this.api.favoriDocumentPublic(document.slug).subscribe({
      next: (r) => {
        this.documents.update((docs) =>
          docs.map((d) =>
            d.slug === document.slug
              ? { ...d, is_favori: r.is_favori, nombre_favoris: r.nombre_favoris }
              : d
          )
        );
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected formaterNote(note: number): string {
    return note.toFixed(1).replace('.', ',');
  }
}
