import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import { CommandeDetail, CommandeSynthese, MetaPage } from '../../models';

/**
 * « Mes commandes » : liste et détail des commandes passées par le membre
 * connecté (achat sans compte non visible ici — suivi public par code).
 */
@Component({
  selector: 'app-mes-commandes',
  imports: [CommonModule, RouterLink],
  styles: [
    `
      .barre {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        flex-wrap: wrap;
        margin-bottom: 1.2rem;
      }
      .barre h1 {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        margin: 0;
      }
      .carte {
        border: 1px solid var(--mpc-bordure);
        border-radius: 14px;
        background: var(--mpc-fond);
        padding: 1rem 1.1rem;
        margin-bottom: 0.8rem;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.6rem;
        align-items: center;
      }
      .carte strong {
        color: var(--mpc-bleu);
        font-size: 0.98rem;
      }
      .meta {
        color: var(--mpc-texte-doux);
        font-size: 0.85rem;
      }
      .droite {
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        align-items: flex-end;
      }
      .montant {
        font-weight: 800;
        color: var(--mpc-primaire);
      }
      .statut {
        display: inline-flex;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 700;
      }
      .statut-en_attente {
        background: #fff4e0;
        color: #a86500;
      }
      .statut-confirmee,
      .statut-en_preparation {
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
      }
      .statut-livree {
        background: #e8f6ee;
        color: #1a7f4e;
      }
      .statut-annulee {
        background: #fdecea;
        color: #b3261e;
      }
      .lien {
        color: var(--mpc-bleu);
        font-weight: 700;
        text-decoration: none;
        font-size: 0.88rem;
      }
      .lien:hover {
        color: var(--mpc-primaire);
      }
      .detail {
        grid-column: 1 / -1;
        border-top: 1px dashed var(--mpc-bordure);
        padding-top: 0.7rem;
        margin-top: 0.3rem;
      }
      .detail table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
      }
      .detail th,
      .detail td {
        text-align: left;
        padding: 0.35rem 0.3rem;
        border-bottom: 1px solid var(--mpc-bordure);
      }
      .detail th {
        color: var(--mpc-texte-doux);
        font-weight: 600;
      }
      .vide {
        text-align: center;
        color: var(--mpc-texte-doux);
        padding: 3rem 1rem;
      }
      .erreur {
        background: #fdecea;
        color: #b3261e;
        border-radius: 12px;
        padding: 0.8rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
      }
      .actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-top: 0.6rem;
      }
      .btn {
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        border-radius: 999px;
        padding: 0.4rem 0.9rem;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        gap: 0.35rem;
        align-items: center;
      }
      .btn:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .btn-danger {
        color: #b3261e;
      }
      .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.2rem;
      }
      .pagination button {
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        border-radius: 10px;
        padding: 0.45rem 0.9rem;
        font-weight: 600;
        cursor: pointer;
      }
      .pagination button:disabled {
        opacity: 0.4;
      }
      .suivi-etapes {
        list-style: none;
        padding: 0;
        margin: 0.6rem 0 0;
      }
      .suivi-etapes li {
        display: flex;
        gap: 0.6rem;
        padding: 0.35rem 0;
        font-size: 0.88rem;
      }
      .suivi-etapes li._fait {
        color: var(--mpc-bleu);
        font-weight: 600;
      }
      .suivi-etapes li:not(._fait) {
        color: var(--mpc-texte-doux);
      }
    `,
  ],
  template: `
    <div class="barre">
      <h1><i class="bi bi-bag-check"></i> Mes commandes</h1>
      <a class="lien" routerLink="/boutique"><i class="bi bi-bag"></i> Boutique</a>
    </div>

    <p class="erreur" *ngIf="erreur()">{{ erreur() }}</p>

    <p class="vide" *ngIf="commandes().length === 0 && !erreur()">
      <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.4rem"></i>
      Aucune commande pour le moment.
    </p>

    <article class="carte" *ngFor="let c of commandes()">
      <div>
        <strong>{{ c.reference }}</strong>
        <div class="meta">
          <span *ngIf="c.date">{{ c.date }}</span>
          · {{ c.total_articles }} article{{ c.total_articles > 1 ? 's' : '' }}
          · {{ c.is_livraison ? 'Livraison' : 'Retrait' }}
        </div>
        <div class="actions">
          <button type="button" class="btn" (click)="basculerDetail(c.reference)">
            <i class="bi bi-chevron-down"></i>
            {{ detailOuverte() === c.reference ? 'Masquer' : 'Détail' }}
          </button>
          <a class="btn" [href]="pdfUrl(c.reference)">
            <i class="bi bi-file-earmark-pdf"></i> PDF
          </a>
          <button type="button" class="btn btn-danger" *ngIf="c.annulable && !c.annulation_demandee"
                  (click)="annuler(c)">
            <i class="bi bi-x-circle"></i> Annuler
          </button>
        </div>
      </div>
      <div class="droite">
        <span class="montant">{{ formaterPrix(c.montant_total) }}</span>
        <span class="statut" [ngClass]="'statut-' + c.statut">{{ c.statut_libelle || c.statut }}</span>
      </div>

      <div class="detail" *ngIf="detailOuverte() === c.reference && detail()">
        <table *ngIf="detail()!.lignes?.length">
          <thead>
            <tr><th>Article</th><th>Qté</th><th>Sous-total</th></tr>
          </thead>
          <tbody>
            <tr *ngFor="let l of detail()!.lignes">
              <td>{{ l.produit }}</td>
              <td>{{ l.quantite }}</td>
              <td>{{ formaterPrix(l.sous_total) }}</td>
            </tr>
          </tbody>
        </table>
        <ul class="suivi-etapes" *ngIf="detail()!.etapes?.length">
          <li *ngFor="let e of detail()!.etapes" [class._fait]="e.fait">
            <i class="bi" [class.bi-check-circle-fill]="e.fait" [class.bi-circle]="!e.fait"></i>
            {{ e.libelle }}
            <span *ngIf="e.date" style="color:var(--mpc-texte-doux)"> · {{ e.date }}</span>
          </li>
        </ul>
        <p class="meta" *ngIf="detail()!.annulation_demandee" style="color:#a86500;margin-top:.5rem">
          <i class="bi bi-hourglass-split"></i> Demande d'annulation en cours.
        </p>
      </div>
    </article>

    <div class="pagination" *ngIf="pages() > 1">
      <button type="button" (click)="chargerPage(page() - 1)" [disabled]="page() <= 1">Précédent</button>
      <span class="align-self-center" style="font-size:.88rem;color:var(--mpc-texte-doux)">
        {{ page() }} / {{ pages() }}
      </span>
      <button type="button" (click)="chargerPage(page() + 1)" [disabled]="page() >= pages()">Suivant</button>
    </div>
  `,
})
export class MesCommandesComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly commandes = signal<CommandeSynthese[]>([]);
  protected readonly detail = signal<CommandeDetail | null>(null);
  protected readonly erreur = signal<string | null>(null);
  protected readonly page = signal(1);
  protected readonly pages = signal(1);
  protected readonly detailOuverte = signal<string | null>(null);

  ngOnInit(): void {
    this.charger();
  }

  protected charger(): void {
    this.erreur.set(null);
    this.api.mesCommandes(this.page()).subscribe({
      next: ({ data, meta }: { data: CommandeSynthese[]; meta?: MetaPage }) => {
        this.commandes.set(data);
        this.pages.set(meta?.last_page ?? 1);
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected chargerPage(p: number): void {
    this.page.set(p);
    this.charger();
  }

  protected basculerDetail(reference: string): void {
    if (this.detailOuverte() === reference) {
      this.detailOuverte.set(null);
      this.detail.set(null);
      return;
    }
    this.api.maCommande(reference).subscribe({
      next: (cmd) => {
        this.detail.set(cmd);
        this.detailOuverte.set(reference);
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected annuler(c: CommandeSynthese): void {
    if (!confirm(`Annuler la commande ${c.reference} ?`)) return;
    this.api.annulerMaCommande(c.reference).subscribe({
      next: () => {
        this.detailOuverte.set(null);
        this.charger();
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected pdfUrl(reference: string): string {
    return this.api.urlPdfMaCommande(reference);
  }

  protected formaterPrix(prix?: number | null): string {
    if (prix === null || prix === undefined) return '0 FCFA';
    return prix.toLocaleString('fr-FR') + ' FCFA';
  }
}
