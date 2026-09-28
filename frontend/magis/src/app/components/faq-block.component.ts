import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FaqSection } from '../models';
import { RevealDirective } from '../directives/reveal.directive';

import { CONTENT } from '../../content';

/** Bloc FAQ : sections + accordéon (une question ouverte à la fois). */
@Component({
  selector: 'app-faq-block',
  imports: [CommonModule, RevealDirective],
  styles: [
    `
      .faq-site {
        max-width: 880px;
        margin: 0 auto;
      }
      .faq-groupe {
        margin-bottom: 2.4rem;
      }
      .faq-groupe-titre {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        font-size: 1.08rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        margin-bottom: 0.25rem;
      }
      .faq-groupe-titre i {
        color: var(--mpc-primaire);
      }
      .faq-groupe-desc {
        color: var(--mpc-texte-doux);
        font-size: 0.95rem;
        margin-bottom: 1.1rem;
      }
      .faq-item {
        border: 1px solid var(--mpc-bordure);
        border-radius: 14px;
        background: var(--mpc-fond);
        overflow: hidden;
        margin-bottom: 0.8rem;
        box-shadow: 0 1px 2px rgba(18, 48, 94, 0.04);
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
      }
      .faq-item._ouvert {
        border-color: var(--mpc-primaire);
        box-shadow: 0 8px 24px rgba(232, 97, 12, 0.12);
      }
      .faq-question {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 1rem 1.15rem;
        background: none;
        border: 0;
        text-align: left;
        cursor: pointer;
        font: inherit;
        color: var(--mpc-texte);
      }
      .faq-num {
        flex-shrink: 0;
        width: 1.8rem;
        height: 1.8rem;
        display: grid;
        place-items: center;
        border-radius: 9px;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 0.82rem;
        font-weight: 700;
      }
      .faq-intitule {
        flex: 1;
        font-weight: 600;
        font-size: 1rem;
      }
      .faq-chevron {
        flex-shrink: 0;
        color: var(--mpc-primaire);
        transition: transform 0.3s ease;
      }
      .faq-item._ouvert .faq-chevron {
        transform: rotate(90deg);
      }
      .faq-reponse {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows 0.35s ease;
      }
      .faq-item._ouvert .faq-reponse {
        grid-template-rows: 1fr;
      }
      .faq-reponse-inner {
        overflow: hidden;
      }
      .faq-reponse p {
        margin: 0 1.15rem 1.1rem 2.65rem;
        color: var(--mpc-texte-doux);
        line-height: 1.7;
        font-size: 0.98rem;
      }
      .faq-vide {
        text-align: center;
        color: var(--mpc-texte-doux);
        padding: 1.5rem 0;
      }
    `,
  ],
  template: `
    <div class="faq-site" appReveal>
      <ng-container *ngFor="let section of sections">
        <div class="faq-groupe">
          <h3 class="faq-groupe-titre">
            <i class="bi bi-tag"></i>{{ section.title }}
          </h3>
          <p class="faq-groupe-desc" *ngIf="section.description">{{ section.description }}</p>

          <div class="faq-item"
               *ngFor="let question of section.questions; let i = index"
               [class._ouvert]="ouverte === cle(section, i)"
               appReveal>
            <button type="button" class="faq-question" (click)="basculer(section, i)"
                    [attr.aria-expanded]="ouverte === cle(section, i)">
              <span class="faq-num">{{ i + 1 }}</span>
              <span class="faq-intitule">{{ question.question }}</span>
              <i class="bi bi-chevron-right faq-chevron"></i>
            </button>
            <div class="faq-reponse">
              <div class="faq-reponse-inner">
                <p>{{ question.answer }}</p>
              </div>
            </div>
          </div>
        </div>
      </ng-container>

      <div class="faq-vide" *ngIf="!sections || sections.length === 0">
        <i class="bi bi-chat-dots display-6" style="color: var(--mpc-texte-doux)"></i>
        <p class="mt-2 mb-1">{{ CONTENT.faq.vide }}</p>
      </div>
    </div>
  `,
})
export class FaqBlockComponent {
  protected readonly CONTENT = CONTENT;

  @Input() sections: FaqSection[] = [];

  @Input() set idFacteur(valeur: string) {
    this.toggleKey = valeur;
  }

  protected toggleKey = 'faq';
  protected ouverte = '';

  protected cle(section: FaqSection, i: number): string {
    return `${this.toggleKey}-${section.id}-${i}`;
  }

  protected basculer(section: FaqSection, i: number): void {
    this.ouverte = this.ouverte === this.cle(section, i) ? '' : this.cle(section, i);
  }
}