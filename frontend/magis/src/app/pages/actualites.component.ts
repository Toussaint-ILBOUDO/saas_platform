import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { RevealDirective } from '../directives/reveal.directive';
import { PageHeroComponent } from '../components/page-hero.component';
import { Actualite } from '../models';

@Component({
  selector: 'app-actualites',
  imports: [CommonModule, RouterLink, RevealDirective, PageHeroComponent],
  styles: [
    `
      .article-carte {
        border: 1px solid var(--mpc-bordure);
        border-radius: 16px;
        background: var(--mpc-fond);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: transform 0.22s ease, box-shadow 0.22s ease;
      }
      .article-carte:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(18, 48, 94, 0.12);
      }
      .article-illustration {
        height: 11rem;
        background: var(--mpc-bleu);
        display: grid;
        place-items: center;
        color: var(--mpc-accent);
        font-size: 2.6rem;
        position: relative;
        overflow: hidden;
      }
      .article-illustration img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .article-date {
        position: absolute;
        top: 0.8rem;
        left: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        border-radius: 999px;
        padding: 0.3rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 700;
      }
      .article-corps {
        padding: 1.3rem;
        display: flex;
        flex-direction: column;
        flex: 1;
      }
      .article-corps h2 {
        font-size: 1.12rem;
        font-weight: 700;
        color: var(--mpc-bleu);
      }
      .article-corps p {
        color: var(--mpc-texte-doux);
        font-size: 0.92rem;
        line-height: 1.65;
        flex: 1;
      }
      .article-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        font-size: 0.8rem;
        color: var(--mpc-texte-doux);
      }
      .article-meta i {
        color: var(--mpc-primaire);
      }
      .charger {
        margin-top: 2.2rem;
        text-align: center;
      }
    `,
  ],
  template: `
    <app-page-hero [titre]="'Actualités'"
                   [sousTitre]="'Les dernières nouvelles du centre : cours, évènements, conseils et réussites de nos élèves.'"></app-page-hero>

    <section class="py-5">
      <div class="container">
        <p class="text-center" style="color: var(--mpc-texte-doux)" *ngIf="articles.length === 0 && charge">
          Aucune actualité publiée pour le moment.
        </p>

        <div class="row g-4" *ngIf="articles.length > 0">
          <div class="col-12 col-sm-6 col-lg-4" *ngFor="let article of articles" appReveal>
            <article class="article-carte">
              <div class="article-illustration">
                <ng-container *ngIf="article.image_url; else sansImage">
                  <img [src]="article.image_url" [alt]="article.titre" loading="lazy" />
                </ng-container>
                <ng-template #sansImage><i class="bi bi-newspaper"></i></ng-template>
                <span class="article-date" *ngIf="dateCourte(article)">{{ dateCourte(article) }}</span>
              </div>
              <div class="article-corps">
                <h2 style="flex:0 0 auto">{{ article.titre }}</h2>
                <p>{{ article.resume || 'Découvrez les détails de cette actualité.' }}</p>
                <div class="article-meta">
                  <span *ngIf="article.published_at"><i class="bi bi-clock"></i>{{ dateLongue(article) }}</span>
                  <span *ngIf="article.auteur"><i class="bi bi-person"></i>{{ article.auteur }}</span>
                  <span class="ms-auto"><i class="bi bi-eye"></i>{{ formatCompact(article.nb_vues) }}</span>
                </div>
                <a class="btn mt-3" style="align-self:flex-start; background: var(--mpc-primaire-tint); color: var(--mpc-primaire); border-radius:999px; padding:.55rem 1.2rem; font-weight:700; text-decoration:none" [routerLink]="['/actualites', article.slug]">Lire la suite<i class="bi bi-arrow-right" style="margin-inline-start:.4rem"></i></a>
              </div>
            </article>
          </div>
        </div>

        <div class="charger" *ngIf="aPlus">
          <button type="button" class="btn" style="background: var(--mpc-bleu); color:#fff; border-radius:999px; padding:.8rem 2rem; font-weight:700"
                  (click)="chargerPlus()" [disabled]="chargement">
            <i class="bi bi-arrow-clockwise" *ngIf="chargement"></i>
            {{ chargement ? 'Chargement…' : 'Afficher plus' }}
          </button>
        </div>
      </div>
    </section>
  `,
})
export class ActualitesComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);

  protected readonly CONTENT = CONTENT;

  protected articles: Actualite[] = [];
  protected charge = false;
  protected chargement = false;
  protected page = 1;
  protected dernierePage = 1;

  protected get aPlus(): boolean {
    return !this.chargement && this.page < this.dernierePage;
  }

  ngOnInit(): void {
    this.seo.definir('Actualités | Magis Plus Center', CONTENT.seo.description);
    this.chargerPlus();
  }

  protected chargerPlus(): void {
    this.chargement = true;
    this.api.getActualites(this.page, 9).subscribe(({ data, meta }) => {
      this.articles = [...this.articles, ...data];
      this.page = meta.current_page + 1;
      this.dernierePage = meta.last_page;
      this.charge = true;
      this.chargement = false;
    });
  }

  protected dateLongue(article: Actualite): string {
    if (!article.published_at) return '';
    return new Date(article.published_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  protected dateCourte(article: Actualite): string {
    if (!article.published_at) return '';
    return new Date(article.published_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
  }

  protected formatCompact(valeur?: number | null): string {
    if (!valeur) return '0';
    return valeur >= 1000 ? (valeur / 1000).toFixed(1).replace('.', ',') + 'k' : String(valeur);
  }
}