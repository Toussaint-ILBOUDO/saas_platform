import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

/** En-tête de page interne : bandeau bleu uni (aucun dégradé) + fil d'Ariane. */
@Component({
  selector: 'app-page-hero',
  imports: [CommonModule, RouterLink],
  styles: [
    `
      :host {
        display: block;
        background: var(--mpc-bleu);
        color: #fff;
        padding: 3.4rem 0 3rem;
        position: relative;
        overflow: hidden;
      }
      :host::before {
        content: '';
        position: absolute;
        right: -3.5rem;
        top: -3.5rem;
        width: 14rem;
        height: 14rem;
        border-radius: 50%;
        background: var(--mpc-primaire);
        opacity: 0.12;
      }
      :host::after {
        content: '';
        position: absolute;
        left: -2.5rem;
        bottom: -4.5rem;
        width: 11rem;
        height: 11rem;
        border-radius: 50%;
        background: var(--mpc-primaire);
        opacity: 0.08;
      }
      .gras {
        position: relative;
        z-index: 1;
      }
      .fil {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 0.86rem;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 0.8rem;
      }
      .fil a {
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
      }
      .fil a:hover {
        color: var(--mpc-accent);
      }
      .fil i {
        font-size: 0.7rem;
        color: var(--mpc-accent);
      }
      h1 {
        font-family: var(--mpc-police-titre);
        font-size: clamp(1.7rem, 5vw, 2.6rem);
        font-weight: 800;
        margin: 0 0 0.6rem;
        letter-spacing: -0.01em;
      }
      .sous-titre {
        max-width: 640px;
        color: rgba(255, 255, 255, 0.82);
        font-size: 1.02rem;
        line-height: 1.7;
        margin: 0;
      }
    `,
  ],
  template: `
    <div class="gras container">
      <nav class="fil" aria-label="Fil d'Ariane">
        <a routerLink="/"><i class="bi bi-house"></i>Accueil</a>
        <i class="bi bi-chevron-right"></i>
        <span>{{ titre }}</span>
      </nav>
      <h1>{{ titre }}</h1>
      <p class="sous-titre" *ngIf="sousTitre">{{ sousTitre }}</p>
    </div>
  `,
})
export class PageHeroComponent {
  @Input() titre = '';
  @Input() sousTitre = '';
}