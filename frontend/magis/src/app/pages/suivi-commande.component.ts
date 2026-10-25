import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { messageErreurApi } from '../services/messages';
import { PageHeroComponent } from '../components/page-hero.component';
import { SuiviCommande } from '../models';

/** Suivi PUBLIC d'une commande par code de référence. */
@Component({
  selector: 'app-suivi-commande',
  imports: [CommonModule, FormsModule, PageHeroComponent],
  styles: [
    `
      .recherche {
        max-width: 520px;
        margin: 0 auto 2rem;
        display: flex;
        gap: 0.6rem;
      }
      .recherche input {
        flex: 1;
        border: 1px solid var(--mpc-bordure);
        border-radius: 999px;
        padding: 0.75rem 1.2rem;
        font-size: 0.95rem;
        outline: none;
        background: var(--mpc-fond);
      }
      .recherche input:focus {
        border-color: var(--mpc-primaire);
      }
      .recherche button {
        border: 0;
        border-radius: 999px;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        padding: 0.75rem 1.4rem;
        cursor: pointer;
      }
      .recherche button:hover {
        background: var(--mpc-primaire-fonce);
      }
      .carte {
        max-width: 720px;
        margin: 0 auto;
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 20px;
        padding: 1.8rem;
      }
      .carte h2 {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        margin-bottom: 0.4rem;
      }
      .meta {
        color: var(--mpc-texte-doux);
        font-size: 0.9rem;
        margin-bottom: 1.2rem;
      }
      .statut {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border-radius: 999px;
        padding: 0.35rem 0.9rem;
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 1.2rem;
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
      .etapes {
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem;
      }
      .etapes li {
        display: flex;
        gap: 0.8rem;
        padding: 0.55rem 0;
        align-items: flex-start;
      }
      .etapes .pastille {
        width: 1.6rem;
        height: 1.6rem;
        border-radius: 50%;
        border: 2px solid var(--mpc-bordure);
        display: grid;
        place-items: center;
        font-size: 0.75rem;
        color: transparent;
        flex-shrink: 0;
      }
      .etapes li._fait .pastille {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .etapes li._fait strong {
        color: var(--mpc-bleu);
      }
      .etapes strong {
        display: block;
        color: var(--mpc-texte-doux);
        font-size: 0.92rem;
      }
      .etapes small {
        color: var(--mpc-texte-doux);
      }
      .lignes {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
      }
      .lignes th,
      .lignes td {
        text-align: left;
        padding: 0.5rem 0.3rem;
        border-bottom: 1px solid var(--mpc-bordure);
        font-size: 0.9rem;
      }
      .lignes th {
        color: var(--mpc-texte-doux);
        font-weight: 600;
      }
      .totaux div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.35rem;
        font-size: 0.95rem;
      }
      .totaux .total {
        font-weight: 800;
        color: var(--mpc-bleu);
        font-size: 1.1rem;
      }
      .actions-carte {
        display: flex;
        gap: 0.7rem;
        flex-wrap: wrap;
        margin-top: 1.4rem;
      }
      .btn-carte {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        padding: 0.6rem 1.2rem;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        cursor: pointer;
        border: 0;
      }
      .btn-pdf {
        background: var(--mpc-primaire);
        color: #fff;
      }
      .btn-pdf:hover {
        background: var(--mpc-primaire-fonce);
        color: #fff;
      }
      .btn-annuler {
        background: transparent;
        border: 1.5px solid #b3261e;
        color: #b3261e;
      }
      .btn-annuler:hover {
        background: #b3261e;
        color: #fff;
      }
      .erreur {
        background: #fdecea;
        color: #b3261e;
        border-radius: 12px;
        padding: 0.9rem 1.2rem;
        margin-bottom: 1.2rem;
        text-align: center;
        font-size: 0.9rem;
      }
      .succes {
        background: #e8f6ee;
        color: #1a7f4e;
        border-radius: 12px;
        padding: 0.9rem 1.2rem;
        margin-bottom: 1.2rem;
        text-align: center;
        font-size: 0.9rem;
      }
      .vide {
        text-align: center;
        color: var(--mpc-texte-doux);
        padding: 2rem 1rem;
      }
      .demande-info {
        background: #fff4e0;
        color: #a86500;
        border-radius: 12px;
        padding: 0.7rem 1rem;
        font-size: 0.88rem;
        margin-bottom: 1rem;
      }
    `,
  ],
  template: `
    <app-page-hero titre="Suivi de commande"
      sousTitre="Entrez votre code de commande pour connaître son avancement."></app-page-hero>

    <section class="py-5">
      <div class="container">
        <form class="recherche" (ngSubmit)="rechercher()">
          <input type="text" name="code" [(ngModel)]="code" placeholder="Ex. MPC-20261010-ABC1234"
                 aria-label="Code de commande" />
          <button type="submit" [disabled]="chargement()">Suivre</button>
        </form>

        <p class="erreur" *ngIf="erreur()">{{ erreur() }}</p>
        <p class="succes" *ngIf="message()">{{ message() }}</p>
        <p class="vide" *ngIf="!commande() && !erreur() && !message() && !chargement()">
          <i class="bi bi-search" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
          Saisissez votre code pour afficher le suivi.
        </p>

        <div class="carte" *ngIf="commande()">
          <h2>Commande {{ commande()!.reference }}</h2>
          <p class="meta">
            <i class="bi bi-person"></i> {{ commande()!.prenom_client || '' }} {{ commande()!.nom_client }}
            <span *ngIf="commande()!.quartier"> · <i class="bi bi-geo-alt"></i> {{ commande()!.quartier }}</span>
          </p>
          <span class="statut" [ngClass]="'statut-' + commande()!.statut">
            <i class="bi bi-circle-square"></i> {{ commande()!.statut_libelle }}
          </span>

          <p class="demande-info" *ngIf="commande()!.annulation_demandee">
            <i class="bi bi-hourglass-split"></i>
            Une demande d'annulation est en cours de traitement.
          </p>

          <ul class="etapes" *ngIf="commande()!.etapes?.length">
            <li *ngFor="let etape of commande()!.etapes" [class._fait]="etape.fait">
              <span class="pastille"><i class="bi bi-check-lg"></i></span>
              <span>
                <strong>{{ etape.libelle }}</strong>
                <small *ngIf="etape.date">{{ formaterDate(etape.date) }}</small>
              </span>
            </li>
          </ul>

          <table class="lignes" *ngIf="commande()!.lignes?.length">
            <thead>
              <tr><th>Article</th><th>Qté</th><th>Sous-total</th></tr>
            </thead>
            <tbody>
              <tr *ngFor="let l of commande()!.lignes">
                <td>{{ l.nom }}</td>
                <td>{{ l.quantite }}</td>
                <td>{{ formaterPrix(l.sous_total) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="totaux">
            <div><span>Articles</span><span>{{ formaterPrix(commande()!.montant_total) }}</span></div>
            <div><span>Livraison</span><span>{{ formaterPrix(commande()!.frais_livraison) }}</span></div>
            <div class="total">
              <span>Total</span>
              <span>{{ formaterPrix(commande()!.montant_total + commande()!.frais_livraison) }}</span>
            </div>
          </div>

          <div class="actions-carte">
            <a class="btn-carte btn-pdf" [href]="urlFacture()">
              <i class="bi bi-file-earmark-pdf"></i> Télécharger la facture (PDF)
            </a>
            <button type="button" class="btn-carte btn-annuler"
                    *ngIf="commande()!.annulable && !commande()!.annulation_demandee && !annulationEnCours()"
                    (click)="demanderAnnulation()">
              <i class="bi bi-x-circle"></i> Demander l'annulation
            </button>
          </div>
        </div>
      </div>
    </section>
  `,
})
export class SuiviCommandeComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);
  private readonly route = inject(ActivatedRoute);

  protected readonly commande = signal<SuiviCommande | null>(null);
  protected readonly erreur = signal<string | null>(null);
  protected readonly message = signal<string | null>(null);
  protected readonly chargement = signal(false);
  protected readonly annulationEnCours = signal(false);
  protected code = '';

  ngOnInit(): void {
    this.seo.definir('Suivi de commande | Magis Plus Center', 'Suivez votre commande avec votre code.');
    let code = this.route.snapshot.queryParamMap.get('code')?.trim() ?? '';
    if (!code) {
      try {
        code = localStorage.getItem('mpc.derniere_commande') ?? '';
      } catch {
        code = '';
      }
    }
    if (code) {
      this.code = code;
      this.rechercher();
    }
  }

  protected rechercher(): void {
    const ref = this.code.trim().toUpperCase();
    if (!ref) return;
    this.erreur.set(null);
    this.message.set(null);
    this.chargement.set(true);
    this.api.suivreCommande(ref).subscribe({
      next: (cmd) => {
        this.commande.set(cmd);
        this.chargement.set(false);
        try {
          localStorage.setItem('mpc.derniere_commande', cmd.reference);
        } catch {
          /* stockage indisponible */
        }
      },
      error: (e) => {
        this.commande.set(null);
        this.erreur.set(messageErreurApi(e));
        this.chargement.set(false);
      },
    });
  }

  protected demanderAnnulation(): void {
    const cmd = this.commande();
    if (!cmd || this.annulationEnCours()) return;
    if (!confirm('Demander l\'annulation de cette commande ?')) return;
    this.erreur.set(null);
    this.message.set(null);
    this.annulationEnCours.set(true);
    this.api.annulerCommandePublique(cmd.reference).subscribe({
      next: (reponse) => {
        this.message.set(reponse.message);
        this.annulationEnCours.set(false);
        this.rechercher();
      },
      error: (e) => {
        this.erreur.set(messageErreurApi(e));
        this.annulationEnCours.set(false);
      },
    });
  }

  protected urlFacture(): string {
    return this.api.urlRecapitulatifCommande(this.commande()?.reference ?? this.code.trim().toUpperCase());
  }

  protected formaterPrix(prix?: number | null): string {
    if (prix === null || prix === undefined) return '0 FCFA';
    return prix.toLocaleString('fr-FR') + ' FCFA';
  }

  protected formaterDate(iso?: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }
}
