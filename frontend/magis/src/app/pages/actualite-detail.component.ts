import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { PageHeroComponent } from '../components/page-hero.component';
import { Actualite } from '../models';

@Component({
  selector: 'app-actualite-detail',
  imports: [CommonModule, RouterLink, PageHeroComponent],
  styles: [
    `
      .article-vignette {
        border-radius: 22px;
        overflow: hidden;
        height: 16rem;
        background: var(--mpc-commune, var(--mpc-bleu));
        display: grid;
        place-items: center;
        color: var(--mpc-accent);
        font-size: 3rem;
      }
      .article-vignette img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.1rem;
        color: var(--mpc-texte-doux);
        font-size: 0.88rem;
      }
      .meta i {
        color: var(--mpc-primaire);
      }
      .contenu ::ng-deep h2,
      .contenu ::ng-deep h3,
      .contenu ::ng-deep h4 {
        font-family: var(--mpc-police-titre);
        color: var(--mpc-bleu);
        margin: 1.4rem 0 0.6rem;
      }
      .contenu ::ng-deep p {
        color: var(--mpc-texte);
        line-height: 1.85;
        margin-bottom: 1rem;
      }
      .contenu ::ng-deep ul,
      .contenu ::ng-deep ol {
        padding-inline-start: 1.3rem;
        line-height: 1.85;
        margin-bottom: 1rem;
      }
      .contenu ::ng-deep img {
        max-width: 100%;
        border-radius: 14px;
      }
      .contenu ::ng-deep a {
        color: var(--mpc-primaire);
      }
      .contenu ::ng-deep blockquote {
        border-inline-start: 4px solid var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
        padding: 1rem 1.2rem;
        border-radius: 0 12px 12px 0;
        color: var(--mpc-bleu);
      }
      .retour {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--mpc-primaire);
        font-weight: 700;
        text-decoration: none;
        margin-bottom: 1.2rem;
      }
      .retour:hover {
        text-decoration: underline;
      }
      .partage a {
        width: 2.4rem;
        height: 2.4rem;
        display: inline-grid;
        place-items: center;
        border-radius: 50%;
        border: 1px solid var(--mpc-bordure);
        color: var(--mpc-bleu);
        text-decoration: none;
        transition: all 0.2s ease;
      }
      .partage a:hover {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
    `,
  ],
  template: `
    <app-page-hero [titre]="article()?.titre ?? 'Actualité'"
                   [sousTitre]="article()?.resume ?? 'Découvrez cette actualité.'"></app-page-hero>

    <section class="py-5">
      <div class="container" style="max-width: 900px">
        <a class="retour" routerLink="/actualites"><i class="bi bi-arrow-left"></i>Retour aux actualités</a>

        <ng-container *ngIf="article()">
          <div class="meta mb-3">
            <span *ngIf="article()!.published_at"><i class="bi bi-calendar3"></i>{{ dateLongue }}</span>
            <span *ngIf="article()!.auteur"><i class="bi bi-person"></i>{{ article()!.auteur }}</span>
            <span class="ms-auto"><i class="bi bi-eye"></i>{{ article()!.nb_vues ?? 0 }} vues</span>
          </div>

          <div class="article-vignette mb-4">
            <ng-container *ngIf="article()!.image_url; else sansImage">
              <img [src]="article()!.image_url" [alt]="article()!.titre" loading="lazy" />
            </ng-container>
            <ng-template #sansImage><i class="bi bi-newspaper"></i></ng-template>
          </div>

          <div class="contenu" [innerHTML]="corpsHtml()"></div>

          <div class="d-flex flex-wrap align-items-center gap-3 mt-5 pt-4" style="border-top:1px solid var(--mpc-bordure)">
            <span style="font-weight:700; color: var(--mpc-bleu)">Partager :</span>
            <div class="partage d-flex gap-2">
              <a [href]="partageUrl('facebook')" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
              <a [href]="partageUrl('whatsapp')" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
              <a [href]="partageUrl('linkedin')" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
            </div>
            <a class="btn ms-auto" routerLink="/demande-cours" style="background: var(--mpc-primaire); color:#fff; border-radius:999px; padding:.6rem 1.4rem; font-weight:700; text-decoration:none; display:inline-flex; gap:.5rem; align-items:center">
              <i class="bi bi-send-fill"></i>Demander un cours
            </a>
          </div>
        </ng-container>
      </div>
    </section>
  `,
})
export class ActualiteDetailComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);
  private readonly route = inject(ActivatedRoute);
  private readonly sanitizer = inject(DomSanitizer);

  protected readonly CONTENT = CONTENT;

  protected readonly article = signal<Actualite | null>(null);
  protected readonly corpsHtml = signal<SafeHtml>('');

  protected get dateLongue(): string {
    if (!this.article()?.published_at) return '';
    return new Date(this.article()!.published_at!).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  ngOnInit(): void {
    const slug = this.route.snapshot.paramMap.get('slug') ?? '';
    this.api.getActualite(slug).subscribe((article) => {
      this.article.set(article);
      this.seo.definir(`${article.titre} | Magis Plus Center`, article.resume ?? CONTENT.seo.description);
      this.corpsHtml.set(this.sanitizer.bypassSecurityTrustHtml(article.contenu ?? ''));
    });
  }

  protected partageUrl(reseau: string): string {
    const url = encodeURIComponent(
      typeof window !== 'undefined' ? window.location.href : ''
    );
    const texte = encodeURIComponent(this.article()?.titre ?? 'Magis Plus Center');
    if (reseau === 'facebook') return `https://www.facebook.com/sharer/sharer.php?u=${url}`;
    if (reseau === 'linkedin') return `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
    return `https://wa.me/?text=${texte}%20%0A${url}`;
  }
}