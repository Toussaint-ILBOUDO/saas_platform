import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RevealDirective } from '../directives/reveal.directive';

/** En-tête de section réutilisable : surtitre + titre + sous-titre. */
@Component({
  selector: 'app-section-title',
  styles: [
    `
      .section-titre {
        max-width: 820px;
        margin: 0 auto 3rem;
        text-align: center;
      }
      .section-titre .surtitre {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--mpc-primaire);
        margin-bottom: 0.75rem;
      }
      .section-titre .surtitre::before,
      .section-titre .surtitre::after {
        content: '';
        width: 1.75rem;
        height: 2px;
        background: var(--mpc-primaire);
        opacity: 0.45;
        border-radius: 2px;
      }
      .section-titre h2 {
        font-family: var(--mpc-police-titre);
        font-size: clamp(1.6rem, 4.2vw, 2.35rem);
        font-weight: 700;
        color: var(--mpc-bleu);
        margin: 0 0 0.9rem;
        letter-spacing: -0.01em;
      }
      .section-titre p {
        color: var(--mpc-texte-doux);
        font-size: clamp(0.98rem, 2.4vw, 1.08rem);
        line-height: 1.7;
        margin: 0;
      }
    `,
  ],
  template: `
    <div class="section-titre" appReveal>
      <span class="surtitre">{{ surtitre }}</span>
      <h2>{{ titre }}</h2>
      <p *ngIf="sousTitre">{{ sousTitre }}</p>
    </div>
  `,
  imports: [CommonModule, RevealDirective],
})
export class SectionTitleComponent {
  @Input() surtitre = '';
  @Input() titre = '';
  @Input() sousTitre = '';
}