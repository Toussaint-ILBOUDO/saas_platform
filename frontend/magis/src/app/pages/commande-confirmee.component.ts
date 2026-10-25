import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { PageHeroComponent } from '../components/page-hero.component';
import { SuiviCommande } from '../models';

/** Page de confirmation après validation d'une commande (référence via query param). */
@Component({
  selector: 'app-commande-confirmee',
  imports: [CommonModule, RouterLink, PageHeroComponent],
  styles: [
    `
      .panneau {
        max-width: 640px;
        margin: 0 auto;
        text-align: center;
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 20px;
        padding: 3rem 1.5rem;
      }
      .icone {
        width: 4.5rem;
        height: 4.5rem;
        margin: 0 auto 1.2rem;
        border-radius: 50%;
        background: #e8f6ee;
        color: #1a7f4e;
        display: grid;
        place-items: center;
        font-size: 2rem;
      }
      h1 {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        margin-bottom: 0.6rem;
      }
      p {
        color: var(--mpc-texte-doux);
        line-height: 1.7;
      }
      .reference-bloc {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin: 1.2rem auto;
        background: var(--mpc-primaire-tint);
        border-radius: 12px;
        padding: 0.7rem 1rem;
        width: fit-content;
      }
      .reference {
        color: var(--mpc-primaire);
        font-weight: 800;
        font-size: 1.15rem;
        letter-spacing: 0.04em;
      }
      .copier {
        border: 0;
        background: transparent;
        color: var(--mpc-primaire);
        cursor: pointer;
        font-size: 0.82rem;
        font-weight: 700;
        padding: 0.2rem 0.5rem;
        border-radius: 8px;
      }
      .copier:hover {
        background: rgba(0, 90, 200, 0.1);
      }
      .total {
        font-weight: 800;
        color: var(--mpc-bleu);
        font-size: 1.05rem;
        margin: 0.6rem 0 0.2rem;
      }
      .actions {
        display: flex;
        gap: 0.8rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 1.5rem;
      }
      .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        padding: 0.7rem 1.4rem;
        font-weight: 700;
        text-decoration: none;
      }
      .btn-primaire {
        background: var(--mpc-primaire);
        color: #fff;
      }
      .btn-primaire:hover {
        background: var(--mpc-primaire-fonce);
        color: #fff;
      }
      .btn-secondaire {
        border: 1.5px solid var(--mpc-bleu);
        color: var(--mpc-bleu);
      }
      .btn-secondaire:hover {
        background: var(--mpc-bleu);
        color: #fff;
      }
      .note {
        margin-top: 1.6rem;
        font-size: 0.85rem;
      }
    `,
  ],
  template: `
    <app-page-hero titre="Commande confirmée" sousTitre="Merci pour votre confiance."></app-page-hero>

    <section class="py-5">
      <div class="container">
        <div class="panneau">
          <div class="icone"><i class="bi bi-check-lg"></i></div>
          <h1>Votre commande a bien été enregistrée</h1>
          <p>
            Conservez votre code de suivi : il vous permet de suivre l'avancement de votre
            commande et de télécharger votre facture à tout moment, sans compte.
          </p>

          <ng-container *ngIf="reference">
            <div class="reference-bloc">
              <span class="reference">{{ reference }}</span>
              <button type="button" class="copier" (click)="copier()">
                <i class="bi" [ngClass]="copie ? 'bi-check-lg' : 'bi-clipboard'"></i>
                {{ copie ? 'Copié !' : 'Copier' }}
              </button>
            </div>
            <p class="total" *ngIf="commande()">
              Total : {{ formaterPrix(commande()!.montant_total + commande()!.frais_livraison) }}
            </p>
            <div class="actions">
              <a class="btn btn-primaire" [href]="urlFacture()">
                <i class="bi bi-file-earmark-pdf"></i> Télécharger la facture (PDF)
              </a>
              <a class="btn btn-secondaire" [routerLink]="['/suivi-commande']" [queryParams]="{ code: reference }">
                <i class="bi bi-truck"></i> Suivre ma commande
              </a>
              <a class="btn btn-secondaire" routerLink="/boutique">
                <i class="bi bi-bag"></i> Retour boutique
              </a>
            </div>
            <p class="note">
              Un doute sur l'avancement ? Utilisez aussi « Suivre ma demande » depuis la boutique
              en saisissant votre code.
            </p>
          </ng-container>

          <p *ngIf="!reference" style="color:#b3261e">
            Aucune référence détectée. Accédez à la page de suivi et saisissez votre code.
          </p>
          <div class="actions" *ngIf="!reference">
            <a class="btn btn-secondaire" routerLink="/suivi-commande">
              <i class="bi bi-truck"></i> Suivre ma commande
            </a>
            <a class="btn btn-secondaire" routerLink="/boutique">
              <i class="bi bi-bag"></i> Retour boutique
            </a>
          </div>
        </div>
      </div>
    </section>
  `,
})
export class CommandeConfirmeeComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly seo = inject(SeoService);
  private readonly api = inject(ApiService);

  protected reference = '';
  protected copie = false;
  protected readonly commande = signal<SuiviCommande | null>(null);

  ngOnInit(): void {
    this.seo.definir('Commande confirmée | Magis Plus Center', 'Votre commande a bien été enregistrée.');
    this.reference = (this.route.snapshot.queryParamMap.get('code') ?? '').trim().toUpperCase();

    if (this.reference) {
      this.api.suivreCommande(this.reference).subscribe({
        next: (cmd) => this.commande.set(cmd),
        error: () => this.commande.set(null),
      });
    }
  }

  protected urlFacture(): string {
    return this.api.urlRecapitulatifCommande(this.reference);
  }

  protected copier(): void {
    if (!this.reference) return;
    const ok = () => {
      this.copie = true;
      setTimeout(() => (this.copie = false), 2000);
    };
    if (navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(this.reference).then(ok);
    }
  }

  protected formaterPrix(prix?: number | null): string {
    if (prix === null || prix === undefined) return '0 FCFA';
    return prix.toLocaleString('fr-FR') + ' FCFA';
  }
}
