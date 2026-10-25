import { Component, Input, computed, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Temoignage } from '../models';

/**
 * Carte témoignage réutilisable (accueil + page dédiée) : pastille citation,
 * étoiles selon le score, auteur (ou mention « Témoignage anonyme ») et
 * texte tronqué dépliable (« Lire la suite ») hors mode compact.
 */
@Component({
  selector: 'app-temoignage-carte',
  imports: [CommonModule],
  styles: [
    `
      :host {
        display: block;
        height: 100%;
      }
      .carte {
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 18px;
        padding: 1.5rem 1.5rem 1.3rem;
        transition: transform 0.22s ease, box-shadow 0.22s ease;
      }
      .carte:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 32px rgba(18, 48, 94, 0.1);
      }
      .entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
      }
      .quote {
        width: 2.5rem;
        height: 2.5rem;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1.15rem;
        flex-shrink: 0;
      }
      .etoiles {
        display: inline-flex;
        gap: 0.1rem;
        color: #f5a524;
        font-size: 0.85rem;
      }
      .etoiles .vide {
        color: var(--mpc-bordure);
      }
      .texte {
        margin: 0;
        color: var(--mpc-texte);
        font-size: 0.95rem;
        line-height: 1.65;
        white-space: pre-line;
        flex: 1 1 auto;
      }
      .texte._tronque {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      .lien-suite {
        align-self: flex-start;
        border: none;
        background: none;
        padding: 0;
        color: var(--mpc-primaire);
        font: inherit;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
      }
      .lien-suite:hover {
        text-decoration: underline;
      }
      .auteur {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding-top: 0.9rem;
        border-top: 1px solid var(--mpc-bordure);
      }
      .avatar {
        width: 2.4rem;
        height: 2.4rem;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--mpc-bleu);
        color: #fff;
        font-weight: 800;
        font-size: 0.85rem;
        flex-shrink: 0;
      }
      .avatar._anonyme {
        background: var(--mpc-surface-teintee);
        color: var(--mpc-texte-doux);
        border: 1px solid var(--mpc-bordure);
      }
      .auteur-nom {
        margin: 0;
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--mpc-bleu);
      }
      .auteur-role {
        margin: 0;
        font-size: 0.76rem;
        color: var(--mpc-texte-doux);
      }
    `,
  ],
  template: `
    <article class="carte">
      <div class="entete">
        <span class="quote"><i class="bi bi-quote"></i></span>
        <span class="etoiles" [attr.aria-label]="'Score ' + temoignage.score + ' points'">
          @for (e of [1, 2, 3, 4, 5]; track e) {
            <i class="bi" [class.bi-star-fill]="e <= etoiles()" [class.vide]="e > etoiles()" [class.bi-star]="e > etoiles()"></i>
          }
        </span>
      </div>

      <p class="texte" [class._tronque]="tronque()">{{ temoignage.contenu }}</p>
      @if (depliable() && !deplie()) {
        <button class="lien-suite" type="button" (click)="deplie.set(true)">Lire la suite</button>
      }

      <div class="auteur">
        <span class="avatar" [class._anonyme]="temoignage.anonyme">
          @if (temoignage.anonyme) {
            <i class="bi bi-person"></i>
          } @else {
            {{ initiales() }}
          }
        </span>
        <div>
          <p class="auteur-nom">{{ temoignage.auteur }}</p>
          <p class="auteur-role">{{ temoignage.role_label || 'Membre de la communauté' }}</p>
        </div>
      </div>
    </article>
  `,
})
export class TemoignageCarteComponent {
  @Input({ required: true }) temoignage!: Temoignage;
  /** Accueil : extrait court sans dépliage. */
  @Input() compact = false;

  protected readonly deplie = signal(false);

  /** Affiche le lien « Lire la suite » seulement si le texte est long. */
  protected readonly depliable = computed(() => !this.compact && this.temoignage.contenu.length > 200);

  /** Texte tronqué (4 lignes) sauf si déplié ou mode compact forcé. */
  protected readonly tronque = computed(() => !this.deplie());

  /** Étoiles pleines selon le score (0-100 → 1-5, comme le carousel). */
  protected readonly etoiles = computed(() =>
    Math.min(Math.max(Math.round(this.temoignage.score / 20), 1), 5)
  );

  protected readonly initiales = computed(() => {
    const mots = (this.temoignage.auteur || '').trim().split(/\s+/).slice(0, 2);
    return mots.map((m) => m[0]?.toUpperCase() ?? '').join('') || '?';
  });
}
