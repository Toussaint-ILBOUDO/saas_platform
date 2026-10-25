import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { PanierService } from '../services/panier.service';
import { messageErreurApi } from '../services/messages';
import { Produit } from '../models';

/** Fiche produit + panier : quantité, ajout, produits similaires. */
@Component({
  selector: 'app-produit-detail',
  imports: [CommonModule, FormsModule, RouterLink],
  styles: [
    `
      :host {
        display: block;
        min-height: 60vh;
      }
      .retour {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--mpc-bleu);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        margin-bottom: 1.2rem;
      }
      .retour:hover {
        color: var(--mpc-primaire);
      }
      .fiche {
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 2.5rem;
        display: grid;
        grid-template-columns: 1fr;
      }
      /* Fiche en deux colonnes sur écran large : l'image n'écrase plus
         la zone d'achat (prix, quantité, « Ajouter au panier »). */
      @media (min-width: 768px) {
        .fiche {
          grid-template-columns: minmax(0, 44%) minmax(0, 1fr);
        }
      }
      .fiche-illustration {
        position: relative;
        display: grid;
        place-items: center;
        height: 15rem;
        background: var(--mpc-surface-teintee);
        color: var(--mpc-bleu);
        font-size: 4rem;
        overflow: hidden;
      }
      @media (min-width: 768px) {
        .fiche-illustration {
          height: auto;
          min-height: 24rem;
        }
      }
      .fiche-illustration .img-grand {
        position: absolute;
        inset: 0;
        display: block;
        cursor: zoom-in;
      }
      .fiche-illustration img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        object-fit: contain;
        padding: 1.2rem;
      }
      .fiche-corps {
        padding: 1.8rem;
      }
      .fiche-corps h1 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        margin-bottom: 0.6rem;
      }
      .reference {
        font-size: 0.82rem;
        color: var(--mpc-texte-doux);
        margin-bottom: 0.8rem;
      }
      .description {
        color: var(--mpc-texte);
        line-height: 1.7;
        margin-bottom: 1.2rem;
      }
      .prix-bloc {
        display: flex;
        align-items: baseline;
        gap: 0.7rem;
        margin-bottom: 1.2rem;
      }
      .prix {
        color: var(--mpc-primaire);
        font-weight: 800;
        font-size: 1.6rem;
      }
      .prix-barr {
        color: var(--mpc-texte-doux);
        text-decoration: line-through;
      }
      .badge-promo {
        background: var(--mpc-accent);
        color: #fff;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 700;
      }
      .stock {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 1rem;
      }
      .stock-ok {
        color: #1a7f4e;
      }
      .stock-alerte {
        color: var(--mpc-accent);
      }
      .stock-rupture {
        color: #b3261e;
      }
      .ligne-achat {
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        align-items: center;
      }
      .quantite {
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--mpc-bordure);
        border-radius: 999px;
        overflow: hidden;
      }
      .quantite button {
        width: 2.4rem;
        height: 2.4rem;
        border: 0;
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        cursor: pointer;
        font-size: 1.05rem;
      }
      .quantite button:hover {
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
      }
      .quantite span {
        min-width: 2.2rem;
        text-align: center;
        font-weight: 700;
      }
      .ajouter {
        background: var(--mpc-primaire);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: 0.75rem 1.6rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        gap: 0.5rem;
        align-items: center;
      }
      .ajouter:hover {
        background: var(--mpc-primaire-fonce);
      }
      .ajouter:disabled {
        opacity: 0.45;
        cursor: default;
      }
      .message-succes {
        background: #e8f6ee;
        color: #1a7f4e;
        border-radius: 12px;
        padding: 0.7rem 1rem;
        margin-top: 1rem;
        font-weight: 600;
      }
      .erreur {
        background: #fdecea;
        color: #b3261e;
        border-radius: 12px;
        padding: 0.8rem 1.2rem;
        margin-bottom: 1.2rem;
        font-size: 0.9rem;
      }
      .similaires h2 {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        margin-bottom: 1rem;
      }
      .sim-carte {
        border: 1px solid var(--mpc-bordure);
        border-radius: 14px;
        background: var(--mpc-fond);
        padding: 1rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        text-decoration: none;
        color: inherit;
      }
      .sim-carte:hover {
        border-color: var(--mpc-primaire);
        color: inherit;
      }
      .sim-carte h3 {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        margin: 0;
      }
      .sim-prix {
        color: var(--mpc-primaire);
        font-weight: 700;
        font-size: 0.95rem;
      }
    `,
  ],
  template: `
    <section class="py-5">
      <div class="container" style="max-width: 960px">
        <a routerLink="/boutique" class="retour">
          <i class="bi bi-arrow-left"></i> Boutique
        </a>

        <p class="erreur" *ngIf="erreur()">{{ erreur() }}</p>

        <article class="fiche" *ngIf="produit()">
          <div class="fiche-illustration">
            <ng-container *ngIf="produit()!.image_url; else sansImage">
              <a class="img-grand" [href]="produit()!.image_url" target="_blank" rel="noopener"
                 [attr.aria-label]="'Agrandir : ' + produit()!.nom">
                <img [src]="produit()!.image_url" [alt]="produit()!.nom" />
              </a>
            </ng-container>
            <ng-template #sansImage><i class="bi bi-box-seam"></i></ng-template>
          </div>
          <div class="fiche-corps">
            <h1>{{ produit()!.nom }}</h1>
            <p class="reference" *ngIf="produit()!.reference">
              <i class="bi bi-upc-scan"></i> Réf. {{ produit()!.reference }}
            </p>
            <p class="description">{{ produit()!.description || 'Produit disponible à la boutique scolaire.' }}</p>
            <div class="prix-bloc">
              <span class="prix">{{ formaterPrix(prixAffiche(produit()!)) }}</span>
              <span class="prix-barr" *ngIf="produit()!.en_promotion && prixBarre(produit()!)">
                {{ formaterPrix(prixBarre(produit()!)) }}
              </span>
              <span class="badge-promo" *ngIf="produit()!.en_promotion">Promotion</span>
            </div>
            <p class="stock" *ngIf="texteStock()" [ngClass]="classeStock()">{{ texteStock() }}</p>
            <div class="ligne-achat">
              <div class="quantite">
                <button type="button" (click)="diminuer()" aria-label="Diminuer la quantité">−</button>
                <span>{{ quantite }}</span>
                <button type="button" (click)="augmenter()" aria-label="Augmenter la quantité">+</button>
              </div>
              <button type="button" class="ajouter" (click)="ajouter()" [disabled]="!estDisponible()">
                <i class="bi bi-cart-plus"></i> Ajouter au panier
              </button>
            </div>
            <p class="message-succes" *ngIf="ajoute()">
              <i class="bi bi-check-circle"></i> Article ajouté au panier.
              <a routerLink="/panier" style="color:inherit;font-weight:800"> Voir le panier</a>
            </p>
          </div>
        </article>

        <section class="similaires" *ngIf="similaires().length > 0">
          <h2>Produits similaires</h2>
          <div class="row g-3">
            <div class="col-12 col-sm-6 col-lg-3" *ngFor="let sim of similaires()">
              <a class="sim-carte" [routerLink]="['/boutique', sim.slug]">
                <h3>{{ sim.nom }}</h3>
                <span class="sim-prix">{{ formaterPrix(sim.prix_vente ?? sim.prix ?? 0) }}</span>
              </a>
            </div>
          </div>
        </section>
      </div>
    </section>
  `,
})
export class ProduitDetailComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);
  private readonly route = inject(ActivatedRoute);
  private readonly panierService = inject(PanierService);

  protected readonly produit = signal<Produit | null>(null);
  protected readonly erreur = signal<string | null>(null);
  protected readonly ajoute = signal(false);
  protected quantite = 1;
  protected similaires = signal<Produit[]>([]);

  ngOnInit(): void {
    const slug = this.route.snapshot.paramMap.get('slug');
    if (!slug) {
      this.erreur.set('Produit introuvable.');
      return;
    }
    this.api.getProduitPublic(slug).subscribe({
      next: (produit) => {
        this.produit.set(produit);
        this.seo.definir(`${produit.nom} | Boutique`, produit.description ?? '');
        this.similaires.set(produit.similaires ?? []);
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected prixAffiche(p: Produit): number {
    return p.prix_vente ?? p.prix_promo ?? p.prix ?? 0;
  }

  protected prixBarre(p: Produit): number | null {
    const base = p.prix ?? null;
    if (base && base > this.prixAffiche(p)) return base;
    return null;
  }

  protected estDisponible(): boolean {
    const p = this.produit();
    if (!p) return false;
    if (p.disponible === false) return false;
    if (p.stock !== null && p.stock !== undefined && p.stock < this.quantite) return false;
    return true;
  }

  protected texteStock(): string {
    const p = this.produit();
    if (!p || p.disponible === false) return 'Rupture de stock';
    if (p.stock === null || p.stock === undefined) return 'En stock';
    if (p.stock === 0) return 'Rupture de stock';
    if (p.stock <= 5) return `Plus que ${p.stock} en stock`;
    return 'En stock';
  }

  protected classeStock(): string {
    const p = this.produit();
    if (!p || p.disponible === false || p.stock === 0) return 'stock-rupture';
    if (p.stock !== null && p.stock !== undefined && p.stock <= 5) return 'stock-alerte';
    return 'stock-ok';
  }

  protected augmenter(): void {
    const stock = this.produit()?.stock;
    const max = stock !== null && stock !== undefined ? stock : 999;
    this.quantite = Math.min(max, this.quantite + 1);
  }

  protected diminuer(): void {
    this.quantite = Math.max(1, this.quantite - 1);
  }

  protected ajouter(): void {
    const p = this.produit();
    if (!p || !this.estDisponible()) return;
    for (let i = 0; i < this.quantite; i++) {
      this.panierService.ajouter({
        id: p.id,
        nom: p.nom,
        slug: p.slug,
        prix_vente: this.prixAffiche(p),
        image_url: p.image_url,
      });
    }
    this.ajoute.set(true);
    setTimeout(() => this.ajoute.set(false), 3500);
  }

  protected formaterPrix(prix?: number | null): string {
    if (prix === null || prix === undefined) return 'Prix sur demande';
    return prix.toLocaleString('fr-FR') + ' FCFA';
  }
}
