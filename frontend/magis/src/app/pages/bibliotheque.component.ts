import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { RevealDirective } from '../directives/reveal.directive';
import { PageHeroComponent } from '../components/page-hero.component';
import { DocumentBibliotheque } from '../models';

@Component({
  selector: 'app-bibliotheque',
  imports: [CommonModule, FormsModule, RevealDirective, PageHeroComponent],
  styles: [
    `
      .recherche {
        position: relative;
        max-width: 560px;
        margin: 0 auto 2.5rem;
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
        box-shadow: 0 12px 28px rgba(18, 48, 94, 0.10);
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
      }
      .doc-meta i {
        color: var(--mpc-primaire);
      }
      .doc-action {
        margin-top: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .telecharger {
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
      .telecharger:hover {
        background: var(--mpc-primaire);
      }
      .note {
        color: var(--mpc-accent);
        font-weight: 700;
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

        <p class="text-center" style="color: var(--mpc-texte-doux)" *ngIf="documents().length === 0 && charge()">
          <i class="bi bi-inbox" style="color:var(--mpc-texte-doux); font-size:2.2rem; display:block; margin-bottom:.5rem"></i>
          {{ CONTENT.bibliotheque.vide }}
        </p>

        <div class="row g-4" *ngIf="documents().length > 0">
          <div class="col-12 col-sm-6 col-lg-4" *ngFor="let document of documents()" appReveal>
            <article class="doc-carte">
              <div class="doc-emblem"><i class="bi bi-file-earmark-text"></i></div>
              <h2>{{ document.titre }}</h2>
              <p>{{ document.resume }}</p>
              <div *ngIf="document.matiere || document.classe">
                <span class="doc-tag" *ngIf="document.matiere">{{ document.matiere }}</span>
                <span class="doc-tag" *ngIf="document.classe">{{ document.classe }}</span>
              </div>
              <div class="doc-meta">
                <span *ngIf="document.nb_telechargements"><i class="bi bi-download"></i>{{ document.nb_telechargements }} {{ CONTENT.bibliotheque.telechargements }}</span>
                <span *ngIf="document.nb_vues"><i class="bi bi-eye"></i>{{ document.nb_vues }} {{ CONTENT.bibliotheque.vues }}</span>
              </div>
              <div class="doc-action">
                <a class="telecharger" *ngIf="fichierTete(document)" [href]="fichierTete(document)!.url" [download]="titreFichier(document)" target="_blank">
                  <i class="bi bi-download"></i>Télécharger
                </a>
                <span class="note" *ngIf="document.note_moyenne"><i class="bi bi-star-fill"></i> {{ formaterNote(document.note_moyenne) }}</span>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>
  `,
})
export class BibliothequeComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);

  protected readonly CONTENT = CONTENT;

  protected readonly documents = signal<DocumentBibliotheque[]>([]);
  protected readonly recherches = signal<DocumentBibliotheque[]>([]);
  protected recherche = '';
  protected readonly charge = signal(false);
  protected readonly page = signal(1);
  private timer?: ReturnType<typeof setTimeout>;

  ngOnInit(): void {
    this.seo.definir('Bibliothèque numérique | Magis Plus Center', CONTENT.bibliotheque.sousTitre);
    this.charger();
  }

  protected charger(): void {
    this.api.getDocuments(this.page(), 12, this.recherche).subscribe(({ data }) => {
      this.documents.set(this.page() === 1 ? data : [...this.documents(), ...data]);
      this.charge.set(true);
    });
  }

  protected surRecherche(): void {
    if (this.timer) clearTimeout(this.timer);
    this.timer = setTimeout(() => {
      this.page.set(1);
      this.charger();
    }, 350);
  }

  protected fichierTete(document: DocumentBibliotheque): { url: string; nom: string; taille: number } | null {
    return document.types && document.types.length > 0 ? document.types[0] : null;
  }

  protected titreFichier(document: DocumentBibliotheque): string {
    const fichier = this.fichierTete(document);
    return fichier?.nom ?? `${document.slug ?? 'document'}`;
  }

  protected formaterNote(note: number): string {
    return note.toFixed(1).replace('.', ',');
  }
}