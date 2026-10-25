import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../services/api.service';
import { AuthService } from '../services/auth.service';
import { messageErreurApi } from '../services/messages';
import { PageHeroComponent } from '../components/page-hero.component';
import { TemoignageCarteComponent } from '../components/temoignage-carte.component';
import { RevealDirective } from '../directives/reveal.directive';
import { CONTENT } from '../../content';
import { Temoignage } from '../models';

const PAR_PAGE = 6;
const MAX_CONTENU = 2000;
const MIN_CONTENU = 20;

/**
 * Page publique « Témoignages » : classement paginé (« Voir plus ») et
 * formulaire de partage pour les membres connectés (anonymat possible).
 */
@Component({
  selector: 'page-temoignages',
  imports: [
    CommonModule,
    FormsModule,
    RouterLink,
    PageHeroComponent,
    TemoignageCarteComponent,
    RevealDirective,
  ],
  styles: [
    `
      :host {
        display: block;
      }
      .bandeau {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.8rem;
      }
      .compteur {
        color: var(--mpc-texte-doux);
        font-size: 0.92rem;
        font-weight: 600;
      }
      .compteur b {
        color: var(--mpc-bleu);
        font-size: 1.15rem;
      }
      .btn-temoigner {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        border-radius: 999px;
        padding: 0.75rem 1.5rem;
        background: var(--mpc-primaire);
        color: #fff;
        font: inherit;
        font-weight: 700;
        font-size: 0.92rem;
        cursor: pointer;
        transition: background 0.2s ease;
      }
      .btn-temoigner:hover {
        background: var(--mpc-primaire-fonce);
      }
      .btn-connexion {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid var(--mpc-primaire);
        border-radius: 999px;
        padding: 0.75rem 1.5rem;
        background: transparent;
        color: var(--mpc-primaire);
        font: inherit;
        font-weight: 700;
        font-size: 0.92rem;
        cursor: pointer;
        text-decoration: none;
      }
      .btn-connexion:hover {
        background: var(--mpc-primaire);
        color: #fff;
      }
      .grille {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.4rem;
      }
      @media (min-width: 768px) {
        .grille {
          grid-template-columns: 1fr 1fr;
        }
      }
      .btn-voir-plus {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid var(--mpc-primaire);
        background: transparent;
        color: var(--mpc-primaire);
        border-radius: 999px;
        padding: 0.7rem 1.5rem;
        font: inherit;
        font-weight: 700;
        font-size: 0.92rem;
        cursor: pointer;
        transition: background 0.2s ease, color 0.2s ease;
      }
      .btn-voir-plus:hover:not(:disabled) {
        background: var(--mpc-primaire);
        color: #fff;
      }
      .btn-voir-plus:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .vide {
        text-align: center;
        color: var(--mpc-texte-doux);
        padding: 2.5rem 1rem;
        border: 1px dashed var(--mpc-bordure);
        border-radius: 16px;
      }
      .toast {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 1.2rem;
        padding: 0.75rem 0.9rem;
        border-radius: 0.8rem;
        font-size: 0.88rem;
        font-weight: 600;
      }
      .toast._succes {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .toast._erreur {
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }

      /* ---------- Fenêtre (modal) témoignage ---------- */
      .fenetre {
        position: fixed;
        inset: 0;
        z-index: 80;
        background: rgba(18, 48, 94, 0.45);
        display: grid;
        place-items: center;
        padding: 1rem;
      }
      .panneau {
        width: min(560px, 100%);
        background: var(--mpc-surface, #fff);
        border-radius: 16px;
        padding: 1.6rem;
        box-shadow: 0 20px 50px rgba(18, 48, 94, 0.25);
        max-height: 90vh;
        overflow: auto;
      }
      .panneau h2 {
        margin: 0 0 0.4rem;
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .aide {
        margin: 0 0 1.1rem;
        color: var(--mpc-texte-doux);
        font-size: 0.86rem;
        line-height: 1.5;
      }
      .champ {
        display: grid;
        gap: 0.35rem;
        margin-bottom: 1rem;
      }
      .champ > span {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
      }
      .champ textarea {
        min-height: 150px;
        resize: vertical;
        padding: 0.7rem 0.8rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond, #fff);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.92rem;
        line-height: 1.55;
        outline: none;
      }
      .champ textarea:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .compteur-texte {
        text-align: right;
        font-size: 0.75rem;
        color: var(--mpc-texte-doux);
      }
      .compteur-texte._depasse {
        color: var(--mpc-danger);
        font-weight: 700;
      }
      .champ-erreur {
        margin: 0;
        color: var(--mpc-danger);
        font-size: 0.78rem;
        font-weight: 600;
      }
      .case-anonyme {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.7rem 0.85rem;
        border: 1px solid var(--mpc-bordure);
        border-radius: 0.75rem;
        background: var(--mpc-surface-teintee);
        cursor: pointer;
        user-select: none;
      }
      .case-anonyme input {
        margin-top: 0.2rem;
        accent-color: var(--mpc-primaire);
      }
      .case-anonyme b {
        display: block;
        font-size: 0.86rem;
      }
      .case-anonyme small {
        color: var(--mpc-texte-doux);
        font-size: 0.76rem;
        line-height: 1.4;
      }
      .signe {
        margin: 0.9rem 0 0;
        text-align: center;
        font-size: 0.8rem;
        color: var(--mpc-texte-doux);
      }
      .signe b {
        color: var(--mpc-bleu);
      }
      .actions-fenetre {
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
        margin-top: 1.2rem;
        flex-wrap: wrap;
      }
      .btn-annuler {
        border: 1px solid var(--mpc-bordure);
        background: transparent;
        color: var(--mpc-texte);
        border-radius: 0.75rem;
        padding: 0.65rem 1.1rem;
        font: inherit;
        font-weight: 600;
        cursor: pointer;
      }
      .btn-envoyer {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border: none;
        background: var(--mpc-primaire);
        color: #fff;
        border-radius: 0.75rem;
        padding: 0.65rem 1.2rem;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
      }
      .btn-envoyer:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .confirmation {
        text-align: center;
        padding: 1.5rem 0.5rem;
      }
      .confirmation .icone {
        width: 3.6rem;
        height: 3.6rem;
        margin: 0 auto 0.9rem;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
        font-size: 1.6rem;
      }
      .confirmation h3 {
        margin: 0 0 0.4rem;
        font-size: 1.1rem;
        color: var(--mpc-bleu);
      }
      .confirmation p {
        margin: 0;
        color: var(--mpc-texte-doux);
        font-size: 0.88rem;
        line-height: 1.5;
      }
    `,
  ],
  template: `
    <app-page-hero
      titre="Témoignages"
      sousTitre="Ce que les parents, enseignants et élèves disent de Magis Plus Center."
    ></app-page-hero>

    <section class="py-5">
      <div class="container" style="max-width: 980px">
        <div class="bandeau">
          <p class="compteur">
            @if (total() > 0) {
              <b>{{ total() }}</b> témoignage{{ total() > 1 ? 's' : '' }} de nos familles
            } @else {
              Soyez le premier à partager votre expérience
            }
          </p>
          @if (connecte()) {
            <button class="btn-temoigner" type="button" (click)="ouvrirFenetre()">
              <i class="bi bi-pen"></i> Partager mon expérience
            </button>
          } @else {
            <a class="btn-connexion" routerLink="/connexion">
              <i class="bi bi-box-arrow-in-right"></i> Connectez-vous pour témoigner
            </a>
          }
        </div>

        @if (toast()) {
          <div class="toast" [class._succes]="succes()" [class._erreur]="!succes()">
            <i class="bi" [class.bi-check-circle]="succes()" [class.bi-exclamation-triangle]="!succes()"></i>
            {{ toast() }}
          </div>
        }

        @if (premierChargement() && temoignages().length === 0 && !chargement()) {
          <p class="vide">{{ CONTENT.temoignages.vide }}</p>
        } @else {
          <div class="grille">
            @for (t of temoignages(); track t.id) {
              <div appReveal>
                <app-temoignage-carte [temoignage]="t"></app-temoignage-carte>
              </div>
            }
          </div>

          @if (plusDeTemoignages()) {
            <div class="text-center mt-4">
              <button class="btn-voir-plus" type="button" (click)="voirPlus()" [disabled]="chargement()">
                <i class="bi" [ngClass]="chargement() ? 'bi-hourglass-split' : 'bi-plus-circle'"></i>
                {{ chargement() ? 'Chargement…' : 'Voir plus de témoignages' }}
              </button>
            </div>
          }
        }
      </div>
    </section>

    @if (fenetreOuverte()) {
      <div class="fenetre" (click)="fermerFenetre()">
        <div class="panneau" (click)="$event.stopPropagation()">
          @if (confirmation()) {
            <div class="confirmation">
              <div class="icone"><i class="bi bi-check-circle-fill"></i></div>
              <h3>Merci pour votre témoignage !</h3>
              <p>Il est maintenant visible par toute la communauté du cabinet.</p>
              <div class="actions-fenetre" style="justify-content: center">
                <button class="btn-envoyer" type="button" (click)="fermerFenetre()">Fermer</button>
              </div>
            </div>
          } @else {
            <h2>Partager mon expérience</h2>
            <p class="aide">
              Racontez en quelques mots votre vécu au cabinet Magis Plus Center (enseignants,
              accompagnement, suivi…). Votre témoignage sera publié immédiatement.
            </p>

            <label class="champ">
              <span>Votre témoignage <em>({{ MIN_CONTENU }} caractères minimum)</em></span>
              <textarea
                [(ngModel)]="contenu"
                name="contenu"
                [maxlength]="MAX_CONTENU"
                placeholder="Ex. : Grâce aux enseignants du cabinet, ma fille a retrouvé confiance en elle…"
              ></textarea>
            </label>
            <p class="compteur-texte" [class._depasse]="contenu.length > MAX_CONTENU">
              {{ contenu.length }} / {{ MAX_CONTENU }}
            </p>
            @if (erreurChamp('contenu')) {
              <p class="champ-erreur">{{ erreurChamp('contenu') }}</p>
            }

            <label class="case-anonyme">
              <input type="checkbox" [(ngModel)]="anonyme" name="anonyme" />
              <span>
                <b>Publier anonymement</b>
                <small>Le public verra « Témoignage anonyme » au lieu de votre nom. Décochez pour afficher « Prénom I. ».</small>
              </span>
            </label>
            @if (!anonyme) {
              <p class="signe">Signé : <b>{{ auth.nomComplet() }}</b></p>
            }

            <div class="actions-fenetre">
              <button class="btn-annuler" type="button" (click)="fermerFenetre()">Annuler</button>
              <button class="btn-envoyer" type="button" (click)="envoyer()" [disabled]="envoiEnCours()">
                @if (envoiEnCours()) {
                  <i class="bi bi-arrow-repeat spin"></i> Publication…
                } @else {
                  <i class="bi bi-send"></i> Publier mon témoignage
                }
              </button>
            </div>
          }
        </div>
      </div>
    }
  `,
})
export class TemoignagesComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);
  protected readonly auth = inject(AuthService);

  protected readonly CONTENT = CONTENT;
  protected readonly MIN_CONTENU = MIN_CONTENU;
  protected readonly MAX_CONTENU = MAX_CONTENU;

  protected readonly temoignages = signal<Temoignage[]>([]);
  protected readonly total = signal(0);
  protected readonly pageCourante = signal(1);
  protected readonly dernierePage = signal(1);
  protected readonly chargement = signal(false);
  protected readonly premierChargement = signal(false);

  protected readonly fenetreOuverte = signal(false);
  protected readonly envoiEnCours = signal(false);
  protected readonly confirmation = signal(false);
  protected readonly toast = signal('');
  protected readonly succes = signal(true);
  protected readonly erreursChamps = signal<Record<string, string>>({});

  protected contenu = '';
  protected anonyme = true;

  protected connecte(): boolean {
    return !!this.auth.utilisateur();
  }

  protected plusDeTemoignages(): boolean {
    return this.pageCourante() < this.dernierePage();
  }

  protected erreurChamp(cle: string): string | null {
    return this.erreursChamps()[cle] ?? null;
  }

  ngOnInit(): void {
    this.chargerPage(1);
  }

  protected voirPlus(): void {
    if (this.chargement() || !this.plusDeTemoignages()) return;
    this.chargerPage(this.pageCourante() + 1);
  }

  protected ouvrirFenetre(): void {
    if (!this.connecte()) {
      void this.router.navigateByUrl('/connexion');
      return;
    }
    this.contenu = '';
    this.anonyme = true;
    this.erreursChamps.set({});
    this.confirmation.set(false);
    this.fenetreOuverte.set(true);
  }

  protected fermerFenetre(): void {
    this.fenetreOuverte.set(false);
  }

  protected async envoyer(): Promise<void> {
    this.toast.set('');
    this.erreursChamps.set({});

    const texte = this.contenu.trim();
    if (texte.length < MIN_CONTENU) {
      this.erreursChamps.set({
        contenu: `Votre témoignage doit contenir au moins ${MIN_CONTENU} caractères.`,
      });
      return;
    }

    this.envoiEnCours.set(true);
    try {
      await firstValueFrom(this.api.creerTemoignage(texte, this.anonyme));
      this.confirmation.set(true);
      this.toast.set('Merci ! Votre témoignage est publié.');
      this.succes.set(true);
      await this.auth.rechargerSession();
      this.rechargerListe();
    } catch (e) {
      const erreurs = (e as { error?: { erreurs?: Record<string, string[]> } })?.error?.erreurs;
      if (erreurs?.['contenu']) {
        this.erreursChamps.set({ contenu: erreurs['contenu'][0] });
      }
      this.toast.set(messageErreurApi(e));
      this.succes.set(false);
    } finally {
      this.envoiEnCours.set(false);
    }
  }

  private rechargerListe(): void {
    this.temoignages.set([]);
    this.pageCourante.set(1);
    this.chargerPage(1);
  }

  private chargerPage(page: number): void {
    this.chargement.set(true);
    this.api.getTemoignages(page, PAR_PAGE).subscribe({
      next: ({ data, meta }) => {
        if (page === 1) {
          this.temoignages.set(data);
        } else {
          const connus = new Set(this.temoignages().map((t) => t.id));
          this.temoignages.set([...this.temoignages(), ...data.filter((t) => !connus.has(t.id))]);
        }
        this.pageCourante.set(page);
        if (typeof meta?.total === 'number') this.total.set(meta.total);
        if (typeof meta?.last_page === 'number') this.dernierePage.set(meta.last_page);
        this.premierChargement.set(true);
        this.chargement.set(false);
      },
      error: () => {
        this.premierChargement.set(true);
        this.chargement.set(false);
      },
    });
  }
}
