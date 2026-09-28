import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { PageHeroComponent } from '../components/page-hero.component';
import { FaqBlockComponent } from '../components/faq-block.component';
import { FaqSection } from '../models';

@Component({
  selector: 'app-faq',
  imports: [CommonModule, RouterLink, PageHeroComponent, FaqBlockComponent],
  template: `
    <app-page-hero [titre]="CONTENT.faq.titre" [sousTitre]="CONTENT.faq.sousTitre"></app-page-hero>
    <section class="py-5">
      <div class="container">
        <app-faq-block [sections]="sections" idFacteur="page-faq"></app-faq-block>
        <div class="text-center mt-4">
          <a class="btn" routerLink="/contact" style="display:inline-flex; gap:.5rem; align-items:center; background: var(--mpc-primaire-tint); color: var(--mpc-primaire); border-radius:999px; padding:.75rem 1.6rem; font-weight:700; text-decoration:none">
            <i class="bi bi-chat-dots"></i>Une autre question ? Contactez-nous
          </a>
        </div>
      </div>
    </section>
  `,
})
export class FaqComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);

  protected readonly CONTENT = CONTENT;
  protected sections: FaqSection[] = [];

  ngOnInit(): void {
    this.seo.definir('Questions fréquentes | Magis Plus Center', CONTENT.faq.sousTitre);
    this.api.getFaq().subscribe((sections) => {
      this.sections = sections;
    });
  }
}