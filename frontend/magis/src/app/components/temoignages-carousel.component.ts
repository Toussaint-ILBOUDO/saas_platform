import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Temoignage } from '../models';
import { RevealDirective } from '../directives/reveal.directive';

/**
 * Carousel de témoignages : défilement automatique, pause au survol,
 * flèches + points (mobile-first : une carte à la fois).
 */
@Component({
  selector: 'app-temoignages-carousel',
  imports: [CommonModule, RevealDirective],
  styles: [
    `
      :host {
        display: block;
        position: relative;
      }
      .masque {
        overflow: hidden;
        border-radius: 18px;
      }
      .piste {
        display: flex;
        transition: transform 0.55s cubic-bezier(0.22, 0.61, 0.36, 1);
      }
      .diapositive {
        min-width: 100%;
        padding: 0.15rem;
      }
      .carte {
        height: 100%;
        border: 1px solid var(--mpc-bordure);
        border-radius: 18px;
        background: var(--mpc-fond);
        padding: 1.7rem 1.7rem 1.4rem;
        box-shadow: 0 6px 24px rgba(18, 48, 94, 0.06);
        display: flex;
        flex-direction: column;
      }
      .quote {
        width: 2.6rem;
        height: 2.6rem;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1.3rem;
        margin-bottom: 1rem;
      }
      .texte {
        color: var(--mpc-texte);
        font-size: 1.02rem;
        line-height: 1.75;
        flex: 1;
      }
      .etoiles {
        color: var(--mpc-accent);
        letter-spacing: 0.15em;
        margin: 0.9rem 0;
      }
      .auteur {
        display: flex;
        align-items: center;
        gap: 0.8rem;
      }
      .auteur-avatar {
        width: 2.9rem;
        height: 2.9rem;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--mpc-bleu);
        color: #fff;
        font-weight: 700;
        font-size: 0.95rem;
      }
      .auteur-nom {
        font-weight: 700;
        color: var(--mpc-bleu);
        margin: 0;
      }
      .auteur-role {
        font-size: 0.84rem;
        color: var(--mpc-texte-doux);
        margin: 0;
      }
      .controles {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.2rem;
        margin-top: 1.6rem;
      }
      .fleche {
        width: 2.7rem;
        height: 2.7rem;
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        cursor: pointer;
        transition: all 0.22s ease;
      }
      .fleche:hover {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .points {
        display: flex;
        gap: 0.45rem;
      }
      .point {
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 999px;
        border: 0;
        background: var(--mpc-bordure);
        padding: 0;
        cursor: pointer;
        transition: all 0.25s ease;
      }
      .point._actif {
        width: 1.5rem;
        background: var(--mpc-primaire);
      }
    `,
  ],
  template: `
    <div class="masque" appReveal (mouseenter)="pauser(true)" (mouseleave)="pauser(false)">
      <div class="piste" [style.transform]="'translateX(-' + index * 100 + '%)'">
        <div class="diapositive" *ngFor="let temoignage of temoignages">
          <article class="carte">
            <div class="quote"><i class="bi bi-quote"></i></div>
            <p class="texte">{{ temoignage.contenu }}</p>
            <div class="etoiles" *ngIf="temoignage.score > 0">
              <i *ngFor="let e of etoiles(temoignage.score)" class="bi bi-star-fill"></i>
            </div>
            <div class="auteur">
              <span class="auteur-avatar">{{ initiale(temoignage.auteur) }}</span>
              <div>
                <p class="auteur-nom">{{ temoignage.auteur }}</p>
                <p class="auteur-role">{{ temoignage.role_label ?? 'Membre de la communauté' }}</p>
              </div>
            </div>
          </article>
        </div>
      </div>
    </div>

    <div class="controles" *ngIf="temoignages.length > 1">
      <button type="button" class="fleche" (click)="precedent()" aria-label="Témoignage précédent">
        <i class="bi bi-chevron-left"></i>
      </button>
      <div class="points">
        <button type="button" class="point" [class._actif]="index === i"
                *ngFor="let t of temoignages; let i = index"
                (click)="aller(i)" [attr.aria-label]="'Témoignage ' + (i + 1)"></button>
      </div>
      <button type="button" class="fleche" (click)="suivant()" aria-label="Témoignage suivant">
        <i class="bi bi-chevron-right"></i>
      </button>
    </div>
  `,
})
export class TemoignagesCarouselComponent implements OnInit, OnDestroy {
  @Input() temoignages: Temoignage[] = [];

  protected index = 0;
  private timer?: ReturnType<typeof setInterval>;
  private enPause = false;

  ngOnInit(): void {
    if (this.temoignages.length > 1) {
      this.lancer();
    }
  }

  ngOnDestroy(): void {
    this.arreter();
  }

  private lancer(): void {
    this.arreter();
    this.timer = setInterval(() => {
      if (!this.enPause) {
        this.suivant();
      }
    }, 6000);
  }

  private arreter(): void {
    if (this.timer) {
      clearInterval(this.timer);
      this.timer = undefined;
    }
  }

  protected pauser(oui: boolean): void {
    this.enPause = oui;
  }

  protected suivant(): void {
    if (this.temoignages.length === 0) return;
    this.index = (this.index + 1) % this.temoignages.length;
  }

  protected precedent(): void {
    if (this.temoignages.length === 0) return;
    this.index = (this.index - 1 + this.temoignages.length) % this.temoignages.length;
  }

  protected aller(i: number): void {
    this.index = i;
  }

  protected initiale(auteur: string): string {
    return auteur.trim().charAt(0).toUpperCase() || 'M';
  }

  protected etoiles(score: number): unknown[] {
    return Array.from({ length: Math.min(Math.max(Math.round(score / 20), 1), 5) });
  }
}