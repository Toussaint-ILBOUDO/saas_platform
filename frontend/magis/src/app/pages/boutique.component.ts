import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { RevealDirective } from '../directives/reveal.directive';
import { PageHeroComponent } from '../components/page-hero.component';
import { Produit } from '../models';

/** Boutique (P5 e-commerce : affichage + commande via WhatsApp jusqu'à l'API dédiée). */
@Component({
  selector: 'app-boutique',
  imports: [CommonModule, FormsModule, RevealDirective, PageHeroComponent],
  styles: [
    `
      .recherche {
        position: relative;
        max-width: 560px;
        margin: 0 auto 2.5rem;
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
        box-shadow: 0 12px 28px rgba(18, 48, 94, 0.10);
      }
      .produit-illustration {
        height: 10rem;
        background: var(--mpc-surface-teintee);
        display: grid;
        place-items: center;
        color: var(--mpc-bleu);
        font-size: 3rem;
        position: relative;
      }
      .produit-illustration img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
      .produit-corps p {
        color: var(--mpc-texte-doux);
        font-size: 0.9rem;
        line-height: 1.6;
        flex: 1;
      }
      .produit-prix {
        color: var(--mpc-primaire);
        font-weight: 800;
        font-size: 1.15rem;
      }
      .commander {
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
      .commander:hover {
        background: var(--mpc-primaire-fonce);
      }
      .panier-compteur {
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
        box-shadow: 0 10px 26px rgba(18, 48, 94, 0.35);
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

        <p class="text-center" style="color: var(--mpc-texte-doux)" *ngIf="produits().length === 0 && charge()">
          <i class="bi bi-bag" style="color:var(--mpc-texte-doux); font-size:2.2rem; display:block; margin-bottom:.5rem"></i>
          {{ CONTENT.boutique.vide }}
        </p>

        <div class="row g-4" *ngIf="produits().length > 0">
          <div class="col-12 col-sm-6 col-lg-4" *ngFor="let produit of produits()" appReveal>
            <article class="produit-carte">
              <div class="produit-illustration">
                <ng-container *ngIf="produit.image_url; else sansImage">
                  <img [src]="produit.image_url" [alt]="produit.nom" loading="lazy" />
                </ng-container>
                <ng-template #sansImage><i class="bi bi-box-seam"></i></ng-template>
                <span class="produit-categorie" *ngIf="produit.categorie">{{ produit.categorie }}</span>
              </div>
              <div class="produit-corps">
                <h2>{{ produit.nom }}</h2>
                <p>{{ produit.description || 'Produit disponible à la boutique.' }}</p>
                <div class="d-flex align-items-center justify-content-between mt-2">
                  <span class="produit-prix">{{ formaterPrix(produit.prix) }}</span>
                  <button type="button" class="commander" (click)="commander(produit)">
                    <i class="bi bi-cart-plus"></i>{{ CONTENT.boutique.commander }}
                  </button>
                </div>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <button type="button" class="panier-compteur" (click)="commanderPanier()" *ngIf="panier.length > 0">
      <i class="bi bi-cart-check-fill"></i>Commander ({{ panier.length }})
    </button>
  `,
})
export class BoutiqueComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);

  protected readonly CONTENT = CONTENT;

  protected readonly produits = signal<Produit[]>([]);
  protected recherche = '';
  protected readonly charge = signal(false);
  protected readonly page = signal(1);
  protected panier: Produit[] = [];
  private timer?: ReturnType<typeof setTimeout>;
  private whatsapp = '';

  ngOnInit(): void {
    this.seo.definir('Boutique scolaire | Magis Plus Center', CONTENT.boutique.sousTitre);
    this.api.getCabinetPublic().subscribe((cabinet) => {
      const fiche = cabinet.donnees?.fiche ?? null;
      const numero = fiche?.contact?.whatsapp || fiche?.contact?.telephone || '';
      const chiffres = numero.replace(/\D/g, '');
      this.whatsapp = chiffres
        ? 'https://wa.me/' + (chiffres.startsWith('226') ? chiffres : '226' + chiffres)
        : '';
    });
    this.api.getProduits(this.page(), 12).subscribe(({ data }) => {
      this.produits.set(data);
      this.charge.set(true);
    });
  }

  protected surRecherche(): void {
    if (this.timer) clearTimeout(this.timer);
    this.timer = setTimeout(() => {
      this.api.getProduits(1, 12, this.recherche).subscribe(({ data }) => {
        this.produits.set(data);
      });
    }, 350);
  }

  protected commander(produit: Produit): void {
    this.panier = [...this.panier, produit];
  }

  protected commanderPanier(): void {
    if (!this.whatsapp) {
      window.location.href = '/demande-cours';
      return;
    }
    const lignes = this.panier.map((p) => `- ${p.nom} (${this.formaterPrix(p.prix)})`).join('%0A');
    const message = `Bonjour Magis Plus Center, %0Aje souhaite commander les articles suivants :%0A${lignes}`;
    window.open(`${this.whatsapp}?text=${message}`, '_blank', 'noopener');
    this.panier = [];
  }

  protected formaterPrix(prix?: number): string {
    if (!prix && prix !== 0) return 'Prix sur demande';
    return prix.toLocaleString('fr-FR') + ' FCFA';
  }
}