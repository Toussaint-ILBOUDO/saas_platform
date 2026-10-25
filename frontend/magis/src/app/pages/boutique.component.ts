import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { PanierService } from '../services/panier.service';
import { messageErreurApi } from '../services/messages';
import { RevealDirective } from '../directives/reveal.directive';
import { PageHeroComponent } from '../components/page-hero.component';
import { Produit } from '../models';

/** Catalogue public de la boutique scolaire (filtres, tri, panier). */
@Component({
  selector: 'app-boutique',
  imports: [CommonModule, FormsModule, RouterLink, RevealDirective, PageHeroComponent],
  styles: [
    `
      .recherche {
        position: relative;
        max-width: 560px;
        margin: 0 auto 1.25rem;
      }
      .recherche .bi-search {
        position: absolute;
        left: 1.1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--mpc-primaire);
      }
      .recherche input {
        width: 100%;
        border: 1px solid var(--mpc-bordure);
        border-radius: 999px;
        padding: 0.8rem 1.2rem 0.8rem 2.8rem;
        font-size: 0.95rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        outline: none;
        box-shadow: 0 2px 10px rgba(18, 48, 94, 0.05);
      }
      .recherche input:focus {
        border-color: var(--mpc-primaire);
      }
      .filtres {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: center;
        margin-bottom: 2rem;
      }
      .filtres select {
        border: 1px solid var(--mpc-bordure);
        border-radius: 999px;
        padding: 0.45rem 1rem;
        font-size: 0.86rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        outline: none;
      }
      .filtres select:focus {
        border-color: var(--mpc-primaire);
      }
      .suivi-bouton {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border: 1.5px solid var(--mpc-bleu);
        border-radius: 999px;
        padding: 0.45rem 1rem;
        font-size: 0.86rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        text-decoration: none;
        background: var(--mpc-fond);
      }
      .suivi-bouton:hover {
        background: var(--mpc-bleu);
        color: #fff;
      }
      .produit-carte {
        border: 1px solid var(--mpc-bordure);
        border-radius: 16px;
        background: var(--mpc-fond);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: transform 0.22s ease, box-shadow 0.22s ease;
      }
      .produit-carte:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(18, 48, 94, 0.1);
      }
      .produit-illustration {
        height: 10rem;
        background: var(--mpc-surface-teintee);
        display: grid;
        place-items: center;
        color: var(--mpc-bleu);
        font-size: 3rem;
        position: relative;
        overflow: hidden;
      }
      .produit-illustration img {
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        object-fit: contain;
        padding: 0.6rem;
      }
      .produit-illustration .img-lien {
        position: absolute;
        inset: 0;
        display: block;
        color: var(--mpc-bleu);
        cursor: pointer;
      }
      .produit-categorie {
        position: absolute;
        top: 0.7rem;
        left: 0.7rem;
        background: var(--mpc-bleu);
        color: #fff;
        border-radius: 999px;
        padding: 0.25rem 0.75rem;
        font-size: 0.74rem;
        font-weight: 700;
      }
      .badge-promo {
        position: absolute;
        top: 0.7rem;
        right: 0.7rem;
        background: var(--mpc-accent);
        color: #fff;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.74rem;
        font-weight: 700;
      }
      .produit-corps {
        padding: 1.2rem;
        display: flex;
        flex-direction: column;
        flex: 1;
      }
      .produit-corps h2 {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        line-height: 1.35;
      }
      .produit-corps h2 a {
        color: inherit;
        text-decoration: none;
      }
      .produit-corps p {
        color: var(--mpc-texte-doux);
        font-size: 0.9rem;
        line-height: 1.6;
        flex: 1;
      }
      .prix-bloc {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
      }
      .prix {
        color: var(--mpc-primaire);
        font-weight: 800;
        font-size: 1.15rem;
      }
      .prix-barr {
        color: var(--mpc-texte-doux);
        text-decoration: line-through;
        font-size: 0.88rem;
      }
      .ajouter {
        background: var(--mpc-primaire);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: 0.6rem 1.4rem;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        display: inline-flex;
        gap: 0.45rem;
        align-items: center;
        transition: background 0.2s ease;
      }
      .ajouter:hover {
        background: var(--mpc-primaire-fonce);
      }
      .ajouter:disabled {
        opacity: 0.45;
        cursor: default;
      }
      .detail-lien {
        color: var(--mpc-bleu);
        font-weight: 700;
        font-size: 0.88rem;
        text-decoration: none;
      }
      .detail-lien:hover {
        color: var(--mpc-primaire);
      }
      .stock-alerte {
        color: var(--mpc-accent);
        font-size: 0.8rem;
        font-weight: 600;
      }
      .erreur {
        background: #fdecea;
        color: #b3261e;
        border-radius: 12px;
        padding: 0.8rem 1.2rem;
        margin-bottom: 1.5rem;
        text-align: center;
        font-size: 0.9rem;
      }
      .panier-bouton {
        position: fixed;
        right: 1.2rem;
        bottom: 1.2rem;
        z-index: 50;
        background: var(--mpc-bleu);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: 0.9rem 1.3rem;
        font-weight: 700;
        display: inline-flex;
        gap: 0.55rem;
        align-items: center;
        cursor: pointer;
        text-decoration: none;
        box-shadow: 0 10px 26px rgba(18, 48, 94, 0.35);
      }
      .panier-bouton:hover {
        color: #fff;
        background: var(--mpc-primaire);
      }
      .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2.5rem;
      }
      .pagination button {
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        border-radius: 10px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        cursor: pointer;
      }
      .pagination button:disabled {
        opacity: 0.4;
        cursor: default;
      }
    `,
  ],
  template: `
    <app-page-hero [titre]="CONTENT.boutique.titre" [sousTitre]="CONTENT.boutique.sousTitre"></app-page-hero>

    <section class="py-5">
      <div class="container">
        <div class="recherche" appReveal>
          <i class="bi bi-search"></i>
          <input type="search" name="recherche" placeholder="Rechercher un produit…"
                 [(ngModel)]="recherche" (ngModelChange)="surRecherche()" aria-label="Rechercher un produit" />
        </div>

        <div class="filtres" appReveal>
          <select [(ngModel)]="categorie" (ngModelChange)="recharger()" aria-label="Catégorie">
            <option [ngValue]="null">Toutes les catégories</option>
            <option *ngFor="let c of categories()" [ngValue]="c.id">{{ c.nom }}</option>
          </select>
          <select [(ngModel)]="tri" (ngModelChange)="recharger()" aria-label="Trier">
            <option value="recent">Nouveautés</option>
            <option value="prix_asc">Prix croissant</option>
            <option value="prix_desc">Prix décroissant</option>
          </select>
          <a class="suivi-bouton" routerLink="/suivi-commande">
            <i class="bi bi-truck"></i> Suivre ma demande
          </a>
        </div>

        <p class="erreur" *ngIf="erreur()">{{ erreur() }}</p>

        <p class="text-center" style="color: var(--mpc-texte-doux)" *ngIf="produits().length === 0 && charge()">
          <i class="bi bi-bag" style="color:var(--mpc-texte-doux); font-size:2.2rem; display:block; margin-bottom:.5rem"></i>
          {{ CONTENT.boutique.vide }}
        </p>

        <div class="row g-4" *ngIf="produits().length > 0">
          <div class="col-12 col-sm-6 col-lg-4" *ngFor="let produit of produits()" appReveal>
            <article class="produit-carte">
              <div class="produit-illustration">
                <ng-container *ngIf="produit.image_url; else sansImage">
                  <a class="img-lien" [routerLink]="['/boutique', produit.slug]" [attr.aria-label]="'Voir le détail de ' + produit.nom">
                    <img [src]="produit.image_url" [alt]="produit.nom" loading="lazy" />
                  </a>
                </ng-container>
                <ng-template #sansImage>
                  <a class="img-lien" [routerLink]="['/boutique', produit.slug]" [attr.aria-label]="'Voir le détail de ' + produit.nom">
                    <i class="bi bi-box-seam"></i>
                  </a>
                </ng-template>
                <span class="produit-categorie" *ngIf="produit.categorie">{{ produit.categorie }}</span>
                <span class="badge-promo" *ngIf="produit.en_promotion">Promo</span>
              </div>
              <div class="produit-corps">
                <h2><a [routerLink]="['/boutique', produit.slug]">{{ produit.nom }}</a></h2>
                <p>{{ produit.description || 'Produit disponible à la boutique.' }}</p>
                <span class="stock-alerte" *ngIf="produit.stock !== null && produit.stock !== undefined && produit.stock <= 5 && produit.disponible !== false">
                  Plus que {{ produit.stock }} en stock
                </span>
                <div class="d-flex align-items-center justify-content-between mt-2 gap-2 flex-wrap">
                  <span class="prix-bloc">
                    <span class="prix">{{ formaterPrix(prixAffiche(produit)) }}</span>
                    <span class="prix-barr" *ngIf="produit.en_promotion && prixBarre(produit)">{{ formaterPrix(prixBarre(produit)) }}</span>
                  </span>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2 gap-2 flex-wrap">
                  <a class="detail-lien" [routerLink]="['/boutique', produit.slug]">Détails</a>
                  <button type="button" class="ajouter" (click)="ajouter(produit)" [disabled]="produit.disponible === false">
                    <i class="bi bi-cart-plus"></i>Ajouter
                  </button>
                </div>
              </div>
            </article>
          </div>
        </div>

        <div class="pagination" *ngIf="pagesTotales() > 1">
          <button type="button" (click)="changerPage(page() - 1)" [disabled]="page() <= 1">
            <i class="bi bi-chevron-left"></i> Précédent
          </button>
          <span class="align-self-center" style="color:var(--mpc-texte-doux);font-size:.9rem">
            Page {{ page() }} / {{ pagesTotales() }}
          </span>
          <button type="button" (click)="changerPage(page() + 1)" [disabled]="page() >= pagesTotales()">
            Suivant <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </section>

    <a class="panier-bouton" routerLink="/panier" *ngIf="panier.nombreLignes() > 0">
      <i class="bi bi-cart-check-fill"></i>
      Panier ({{ panier.nombreLignes() }})
    </a>
  `,
})
export class BoutiqueComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);
  private readonly router = inject(Router);
  protected readonly panier = inject(PanierService);

  protected readonly CONTENT = CONTENT;

  protected readonly produits = signal<Produit[]>([]);
  protected readonly categories = signal<{ id: number; nom: string }[]>([]);
  protected readonly charge = signal(false);
  protected readonly erreur = signal<string | null>(null);
  protected readonly page = signal(1);
  protected readonly pagesTotales = signal(1);

  protected recherche = '';
  protected categorie: number | null = null;
  protected tri: 'recent' | 'prix_asc' | 'prix_desc' = 'recent';

  private timer?: ReturnType<typeof setTimeout>;

  ngOnInit(): void {
    this.seo.definir('Boutique scolaire | Magis Plus Center', CONTENT.boutique.sousTitre);
    this.api.getCategoriesProduits().subscribe({
      next: (cats) => this.categories.set(cats),
      error: () => this.categories.set([]),
    });
    this.charger();
  }

  protected charger(): void {
    this.erreur.set(null);
    this.api.getProduits(this.page(), 12, this.recherche, this.tri).subscribe({
      next: ({ data, meta }) => {
        let liste = data;
        if (this.categorie !== null) {
          liste = liste.filter((p) => p.categorie_id === this.categorie);
        }
        this.produits.set(liste);
        this.pagesTotales.set(meta?.last_page ?? 1);
        this.charge.set(true);
      },
      error: (e) => {
        this.erreur.set(messageErreurApi(e));
        this.charge.set(true);
      },
    });
  }

  protected recharger(): void {
    this.page.set(1);
    this.charger();
  }

  protected surRecherche(): void {
    if (this.timer) clearTimeout(this.timer);
    this.timer = setTimeout(() => this.recharger(), 350);
  }

  protected changerPage(p: number): void {
    this.page.set(p);
    this.charger();
  }

  protected ajouter(produit: Produit): void {
    this.panier.ajouter({
      id: produit.id,
      nom: produit.nom,
      slug: produit.slug,
      prix_vente: this.prixAffiche(produit),
      image_url: produit.image_url,
    });
    this.erreur.set(null);
  }

  protected prixAffiche(produit: Produit): number {
    return produit.prix_vente ?? produit.prix_promo ?? produit.prix ?? 0;
  }

  protected prixBarre(produit: Produit): number | null {
    const base = produit.prix ?? null;
    if (base && base > this.prixAffiche(produit)) return base;
    return null;
  }

  protected formaterPrix(prix?: number | null): string {
    if (prix === null || prix === undefined) return 'Prix sur demande';
    return prix.toLocaleString('fr-FR') + ' FCFA';
  }
}
