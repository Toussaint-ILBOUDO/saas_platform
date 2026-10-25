import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ApiService } from '../services/api.service';
import { PanierService } from '../services/panier.service';
import { AuthService } from '../services/auth.service';
import { messageErreurApi } from '../services/messages';
import { PageHeroComponent } from '../components/page-hero.component';

/** Page panier : quantités, validation de commande (sans compte ou connecté). */
@Component({
  selector: 'app-panier',
  imports: [CommonModule, FormsModule, RouterLink, PageHeroComponent],
  styles: [
    `
      .panier-vide {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--mpc-texte-doux);
      }
      .panier-vide i {
        font-size: 3rem;
        color: var(--mpc-texte-doux);
        display: block;
        margin-bottom: 1rem;
      }
      .panier-vide a {
        display: inline-flex;
        margin-top: 1.2rem;
        background: var(--mpc-primaire);
        color: #fff;
        border-radius: 999px;
        padding: 0.65rem 1.4rem;
        font-weight: 700;
        text-decoration: none;
      }
      .panier-vide a:hover {
        background: var(--mpc-primaire-fonce);
      }
      .carte {
        border: 1px solid var(--mpc-bordure);
        border-radius: 16px;
        background: var(--mpc-fond);
        padding: 1.2rem;
      }
      .carte h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        margin-bottom: 1rem;
      }
      .carte h2:focus {
        outline: none;
      }
      .ligne {
        display: grid;
        grid-template-columns: 3.5rem 1fr auto;
        gap: 0.9rem;
        align-items: center;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--mpc-bordure);
      }
      .ligne:last-child {
        border-bottom: 0;
      }
      .thumb {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 10px;
        background: var(--mpc-surface-teintee);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--mpc-bleu);
        overflow: hidden;
      }
      .thumb img {
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        object-fit: contain;
        padding: 0.2rem;
      }
      .ligne-info {
        min-width: 0;
      }
      .ligne-info strong {
        display: block;
        color: var(--mpc-bleu);
        font-size: 0.95rem;
      }
      .ligne-info small {
        color: var(--mpc-texte-doux);
      }
      .quantite {
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--mpc-bordure);
        border-radius: 999px;
        overflow: hidden;
        margin-top: 0.35rem;
      }
      .quantite button {
        width: 2rem;
        height: 2rem;
        border: 0;
        background: transparent;
        color: var(--mpc-bleu);
        cursor: pointer;
      }
      .quantite button:hover {
        background: var(--mpc-primaire-tint);
      }
      .quantite span {
        min-width: 1.8rem;
        text-align: center;
        font-weight: 700;
        font-size: 0.9rem;
      }
      .ligne-droite {
        text-align: right;
      }
      .ligne-droite .sous {
        font-weight: 800;
        color: var(--mpc-primaire);
      }
      .retirer {
        border: 0;
        background: transparent;
        color: #b3261e;
        cursor: pointer;
        font-size: 0.82rem;
        font-weight: 600;
        margin-top: 0.25rem;
      }
      .totaux {
        margin-top: 1rem;
        padding-top: 0.8rem;
        border-top: 1px dashed var(--mpc-bordure);
      }
      .totaux div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.4rem;
        font-size: 0.92rem;
        color: var(--mpc-texte);
      }
      .totaux .total {
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--mpc-bleu);
      }
      .notes {
        color: var(--mpc-texte-doux);
        font-size: 0.8rem;
        margin-top: 0.8rem;
      }
      label {
        display: block;
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--mpc-bleu);
        margin-bottom: 0.3rem;
      }
      input[type='text'],
      input[type='tel'],
      textarea,
      select {
        width: 100%;
        border: 1px solid var(--mpc-bordure);
        border-radius: 10px;
        padding: 0.6rem 0.8rem;
        font-size: 0.92rem;
        background: #fff;
        color: var(--mpc-texte);
        outline: none;
        margin-bottom: 0.9rem;
      }
      input:focus,
      textarea:focus,
      select:focus {
        border-color: var(--mpc-primaire);
      }
      .radio-ligne {
        display: flex;
        gap: 1rem;
        margin-bottom: 0.9rem;
        flex-wrap: wrap;
      }
      .radio-ligne label {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0;
        font-weight: 600;
        cursor: pointer;
      }
      .valider {
        width: 100%;
        background: var(--mpc-primaire);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: 0.85rem 1.4rem;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 0.5rem;
      }
      .valider:hover {
        background: var(--mpc-primaire-fonce);
      }
      .valider:disabled {
        opacity: 0.5;
        cursor: default;
      }
      .erreur {
        background: #fdecea;
        color: #b3261e;
        border-radius: 12px;
        padding: 0.8rem 1.2rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
      }
      .succes {
        background: #e8f6ee;
        color: #1a7f4e;
        border-radius: 12px;
        padding: 0.9rem 1.2rem;
        margin-bottom: 1rem;
        font-size: 0.92rem;
        font-weight: 600;
      }
      @media (max-width: 767px) {
        .ligne {
          grid-template-columns: 3rem 1fr;
        }
        .ligne-droite {
          grid-column: 2;
          text-align: left;
        }
      }
    `,
  ],
  template: `
    <app-page-hero titre="Panier" sousTitre="Vérifiez vos articles avant de valider la commande."></app-page-hero>

    <section class="py-5">
      <div class="container" style="max-width: 1100px">
        <div class="panier-vide" *ngIf="panier.lignes().length === 0">
          <i class="bi bi-cart3"></i>
          <p>Votre panier est vide.</p>
          <a routerLink="/boutique">Découvrir la boutique</a>
        </div>

        <div class="row g-4" *ngIf="panier.lignes().length > 0">
          <div class="col-12 col-lg-7">
            <div class="carte">
              <h2>Articles ({{ panier.nombreLignes() }})</h2>
              <div class="ligne" *ngFor="let ligne of panier.lignes()">
                <div class="thumb">
                  <img *ngIf="ligne.image_url; else icone" [src]="ligne.image_url" [alt]="ligne.nom" />
                  <ng-template #icone><i class="bi bi-box-seam"></i></ng-template>
                </div>
                <div class="ligne-info">
                  <strong>{{ ligne.nom }}</strong>
                  <small>{{ formaterPrix(ligne.prix_vente) }} / unité</small>
                  <div class="quantite">
                    <button type="button" (click)="diminuer(ligne.produit_id)" aria-label="Diminuer">−</button>
                    <span>{{ ligne.quantite }}</span>
                    <button type="button" (click)="augmenter(ligne.produit_id)" aria-label="Augmenter">+</button>
                  </div>
                </div>
                <div class="ligne-droite">
                  <div class="sous">{{ formaterPrix(ligne.prix_vente * ligne.quantite) }}</div>
                  <button type="button" class="retirer" (click)="retirer(ligne.produit_id)">
                    <i class="bi bi-trash3"></i> Retirer
                  </button>
                </div>
              </div>
              <div class="totaux">
                <!-- <div><span>Sous-total articles</span><strong>{{ formaterPrix(panier.sousTotal()) }}</strong></div>
                <div><span>Frais de livraison</span><span>{{ formaterPrix(fraisLivraison) }}</span></div> -->
                <div class="total">
                  <span>Total estimé</span>
                  <span>{{ formaterPrix(panier.sousTotal() + fraisLivraison) }}</span>
                </div>
              </div>
              <p class="notes">
                Les prix et frais définitifs sont confirmés par le serveur au moment de la commande.
              </p>
            </div>
          </div>

          <div class="col-12 col-lg-5">
            <div class="carte">
              <h2>Coordonnées</h2>
              <p class="succes" *ngIf="connecte()">
                <i class="bi bi-person-check"></i> Bonjour {{ prenom() || 'vous' }}, vos coordonnées sont préremplies.
              </p>
              <p class="erreur" *ngIf="erreur()">{{ erreur() }}</p>

              <label for="nom">Nom *</label>
              <input id="nom" type="text" name="nom" [(ngModel)]="form.nom_client" required />

              <label for="prenom">Prénom</label>
              <input id="prenom" type="text" name="prenom" [(ngModel)]="form.prenom_client" />

              <label for="tel">Téléphone / WhatsApp *</label>
              <input id="tel" type="tel" name="tel" [(ngModel)]="form.telephone_client" required placeholder="Ex. 70 00 00 00" />

              <div class="radio-ligne">
                <label>
                  <input type="radio" name="mode" [checked]="!form.is_livraison" (change)="form.is_livraison = false" />
                  Retrait boutique
                </label>
                <label>
                  <input type="radio" name="mode" [checked]="form.is_livraison" (change)="form.is_livraison = true" />
                  Livraison
                </label>
              </div>

              <ng-container *ngIf="form.is_livraison">
                <label for="adresse">Adresse de livraison *</label>
                <input id="adresse" type="text" name="adresse" [(ngModel)]="form.adresse_livraison" />

                <label for="quartier">Quartier</label>
                <input id="quartier" type="text" name="quartier" [(ngModel)]="form.quartier" />

                <label for="repere">Point de repère</label>
                <input id="repere" type="text" name="repere" [(ngModel)]="form.point_de_repere" />
              </ng-container>

              <label for="notes">Notes (facultatif)</label>
              <textarea id="notes" name="notes" rows="3" [(ngModel)]="form.notes"></textarea>

              <button type="button" class="valider" (click)="valider()" [disabled]="envoi()">
                <i class="bi bi-bag-check"></i>
                {{ envoi() ? 'Validation…' : 'Valider la commande' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  `,
})
export class PanierComponent {
  protected readonly panier = inject(PanierService);
  private readonly api = inject(ApiService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly erreur = signal<string | null>(null);
  protected readonly envoi = signal(false);
  protected readonly fraisLivraison = 0;

  protected form = {
    nom_client: '',
    prenom_client: '',
    telephone_client: '',
    adresse_livraison: '',
    quartier: '',
    point_de_repere: '',
    notes: '',
    is_livraison: false,
  };

  protected readonly connecte = () => !!this.auth.utilisateur();
  protected readonly prenom = () => this.auth.utilisateur()?.prenom ?? '';

  constructor() {
    const u = this.auth.utilisateur();
    if (u) {
      this.form.nom_client = u.nom ?? '';
      this.form.prenom_client = u.prenom ?? '';
    }
  }

  protected augmenter(id: number): void {
    const ligne = this.panier.lignes().find((l) => l.produit_id === id);
    if (ligne) this.panier.changerQuantite(id, ligne.quantite + 1);
  }

  protected diminuer(id: number): void {
    const ligne = this.panier.lignes().find((l) => l.produit_id === id);
    if (ligne) this.panier.changerQuantite(id, ligne.quantite - 1);
  }

  protected retirer(id: number): void {
    this.panier.retirer(id);
  }

  protected valider(): void {
    if (this.envoi() || this.panier.lignes().length === 0) return;
    this.erreur.set(null);

    if (!this.form.nom_client.trim() || !this.form.telephone_client.trim()) {
      this.erreur.set('Nom et téléphone sont obligatoires.');
      return;
    }
    if (this.form.is_livraison && !this.form.adresse_livraison.trim()) {
      this.erreur.set('Adresse de livraison obligatoire pour une livraison.');
      return;
    }

    const cleIdempotence = this.cleIdempotence();
    this.envoi.set(true);

    this.api
      .passerCommande({
        nom_client: this.form.nom_client.trim(),
        prenom_client: this.form.prenom_client.trim() || undefined,
        telephone_client: this.form.telephone_client.trim(),
        adresse_livraison: this.form.is_livraison ? this.form.adresse_livraison.trim() : undefined,
        quartier: this.form.quartier.trim() || undefined,
        point_de_repere: this.form.point_de_repere.trim() || undefined,
        is_livraison: this.form.is_livraison,
        notes: this.form.notes.trim() || undefined,
        cle_idempotence: cleIdempotence,
        panier: this.panier.lignesApi(),
      })
      .subscribe({
        next: (reponse) => {
          this.panier.vider();
          // Nouvelle clé pour une future commande.
          try {
            localStorage.removeItem('mpc.commande.cle_idempotence');
            localStorage.setItem('mpc.derniere_commande', reponse.commande.reference);
          } catch {
            /* stockage indisponible */
          }
          this.envoi.set(false);
          this.router.navigate(['/commande-confirmee'], {
            queryParams: { code: reponse.commande.reference },
          });
        },
        error: (e) => {
          this.erreur.set(messageErreurApi(e));
          this.envoi.set(false);
        },
      });
  }

  /** Clé d'idempotence locale (réutilisée si l'utilisateur réessaie). */
  private cleIdempotence(): string {
    const cle = 'mpc.commande.cle_idempotence';
    let value = localStorage.getItem(cle);
    if (!value) {
      value = typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : 'x' + Date.now() + Math.random().toString(36).slice(2);
      localStorage.setItem(cle, value);
    }
    return value;
  }

  protected formaterPrix(prix?: number | null): string {
    if (prix === null || prix === undefined) return '0 FCFA';
    return prix.toLocaleString('fr-FR') + ' FCFA';
  }
}
