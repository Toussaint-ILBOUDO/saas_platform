import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { CONTENT } from '../../content';
import { FicheCabinet } from '../models';
import { ApiService } from '../services/api.service';

@Component({
  selector: 'app-footer',
  imports: [CommonModule, RouterLink],
  styles: [
    `
      :host {
        display: block;
        background: var(--mpc-bleu);
        color: rgba(255, 255, 255, 0.82);
        position: relative;
        overflow: hidden;
      }
      .pied {
        padding: 4rem 0 0;
        position: relative;
        z-index: 1;
      }
      .pied-titre {
        font-weight: 700;
        color: #fff;
        font-size: 1.05rem;
        margin-bottom: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .pied-titre::before {
        content: '';
        width: 0.4rem;
        height: 1.2rem;
        border-radius: 4px;
        background: var(--mpc-primaire);
      }
      .pied-bloc p {
        line-height: 1.7;
        font-size: 0.94rem;
      }
      .pied-lien {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        color: rgba(255, 255, 255, 0.82);
        text-decoration: none;
        padding: 0.32rem 0;
        font-size: 0.94rem;
        transition: color 0.2s ease, transform 0.2s ease;
      }
      .pied-lien i {
        color: var(--mpc-accent);
        font-size: 0.7rem;
      }
      .pied-lien:hover {
        color: #fff;
        transform: translateX(3px);
      }
      .pied-contact {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 0.8rem;
        font-size: 0.94rem;
      }
      .pied-contact i {
        color: var(--mpc-accent);
        font-size: 1.05rem;
        margin-top: 0.15rem;
      }
      .pied-contact p {
        margin: 0;
        line-height: 1.55;
      }
      .pied-contact a {
        color: rgba(255, 255, 255, 0.82);
        text-decoration: none;
      }
      .pied-contact a:hover {
        color: #fff;
        text-decoration: underline;
      }
      .pied-reseaux {
        display: flex;
        gap: 0.7rem;
        margin-top: 1rem;
      }
      .pied-reseaux a {
        width: 2.3rem;
        height: 2.3rem;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        text-decoration: none;
        transition: all 0.22s ease;
      }
      .pied-reseaux a:hover {
        background: var(--mpc-primaire);
        transform: translateY(-3px);
      }
      .pied-paiement {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
      }
      .badge-paiement {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        font-size: 0.8rem;
        font-weight: 600;
      }
      .pied-barre {
        margin-top: 3rem;
        padding: 1.1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.12);
        font-size: 0.86rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
        justify-content: space-between;
      }
      .pied-barre a {
        color: rgba(255, 255, 255, 0.82);
        text-decoration: none;
      }
      .pied-barre a:hover {
        color: #fff;
        text-decoration: underline;
      }
    `,
  ],
  template: `
    <footer class="pied container">
      <div class="row g-4 g-lg-5">
        <!-- Marque -->
        <div class="col-12 col-lg-4">
          <div class="pied-bloc">
            <a class="d-flex align-items-center gap-2 text-decoration-none mb-3" routerLink="/" style="color: #fff">
              @if (logoUrl()) {
                <img class="logo-img" [src]="logoUrl()" alt="Logo {{ nom }}" style="width:2.4rem;height:2.4rem;border-radius:11px;object-fit:cover;background:#fff" />
              } @else {
                <span class="logo-carre" style="width:2.4rem;height:2.4rem;border-radius:11px;background:var(--mpc-primaire);display:grid;place-items:center;font-weight:800;font-size:1.05rem">{{ initiales }}</span>
              }
              <span class="fw-bold" style="font-size:1.1rem">{{ nom }}</span>
            </a>
            <p>{{ CONTENT.footer.aPropos }}</p>
            <div class="pied-reseaux">
              <a *ngIf="facebook()" [href]="facebook()" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
              <a *ngIf="tiktok()" [href]="tiktok()" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
              <a *ngIf="whatsapp()" [href]="whatsapp()" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
              <a *ngIf="linkedin()" [href]="linkedin()" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
            </div>
          </div>
        </div>

        <!-- Liens rapides -->
        <div class="col-6 col-lg-2">
          <h4 class="pied-titre">{{ CONTENT.footer.navigation }}</h4>
          <div class="d-flex flex-column">
            <a class="pied-lien" *ngFor="let lien of liens" routerLink="{{ lien.route }}" [fragment]="lien.fragment"><i class="bi bi-chevron-right"></i>{{ lien.libelle }}</a>
          </div>
        </div>

        <!-- Services -->
        <div class="col-6 col-lg-3">
          <h4 class="pied-titre">{{ CONTENT.footer.services }}</h4>
          <div class="d-flex flex-column">
            <a class="pied-lien" routerLink="/" fragment="services"><i class="bi bi-chevron-right"></i>{{ CONTENT.footer.serviceItems[0].libelle }}</a>
            <a class="pied-lien" routerLink="/bibliotheque"><i class="bi bi-chevron-right"></i>Bibliothèque</a>
            <a class="pied-lien" routerLink="/boutique"><i class="bi bi-chevron-right"></i>Boutique</a>
            <a class="pied-lien" routerLink="/demande-cours"><i class="bi bi-chevron-right"></i>Demande de cours</a>
            <a class="pied-lien" routerLink="/faq"><i class="bi bi-chevron-right"></i>FAQ</a>
            <a class="pied-lien" routerLink="/actualites"><i class="bi bi-chevron-right"></i>Actualités</a>
          </div>
        </div>

        <!-- Contact -->
        <div class="col-12 col-lg-3">
          <h4 class="pied-titre">Contact</h4>
          <div class="pied-contact" *ngIf="telephone()">
            <i class="bi bi-telephone"></i>
            <p><a [href]="'tel:+' + telephone().replace(/\\D/g, '')">{{ telephone() }}</a></p>
          </div>
          <div class="pied-contact" *ngIf="email()">
            <i class="bi bi-envelope"></i>
            <p><a [href]="'mailto:' + email()">{{ email() }}</a></p>
          </div>
          <div class="pied-contact" *ngIf="adresse()">
            <i class="bi bi-geo-alt"></i>
            <p>{{ adresse() }}</p>
          </div>
          <div class="pied-contact" *ngIf="horaires()">
            <i class="bi bi-clock"></i>
            <p>{{ horaires() }}</p>
          </div>
          <div class="pied-paiement mt-3" *ngIf="afficherPaiements">
            <span class="badge-paiement" *ngIf="paiements().orange_money"><i class="bi bi-phone"></i>Orange Money</span>
            <span class="badge-paiement" *ngIf="paiements().moov_money"><i class="bi bi-phone"></i>Moov Money</span>
            <span class="badge-paiement" *ngIf="paiements().wave"><i class="bi bi-phone"></i>Wave</span>
            <span class="badge-paiement" *ngIf="paiements().cash"><i class="bi bi-cash-coin"></i>Espèces</span>
          </div>
        </div>
      </div>

      <div class="pied-barre">
        <span>© {{ anne }}</span>
        <span class="d-flex gap-3 flex-wrap">
          <a routerLink="/faq">FAQ</a>
          <a routerLink="/contact">Contact</a>
          <a routerLink="/demande-cours">Devenir élève</a>
          <span style="opacity:.6">Propulsé par Magis Plus Center</span>
        </span>
      </div>
    </footer>
  `,
})
export class FooterComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly CONTENT = CONTENT;
  protected nom = CONTENT.cabinet.nom;
  protected initiales = CONTENT.cabinet.sigle;
  protected readonly logoUrl = signal<string | null>(null);
  protected anne = new Date().getFullYear();

  protected readonly telephone = signal('');
  protected readonly email = signal('');
  protected readonly adresse = signal('');
  protected readonly horaires = signal('');
  protected readonly facebook = signal<string | undefined>(undefined);
  protected readonly tiktok = signal<string | undefined>(undefined);
  protected readonly whatsapp = signal<string | undefined>(undefined);
  protected readonly linkedin = signal<string | undefined>(undefined);
  protected readonly paiements = signal<{
    orange_money?: string | null;
    moov_money?: string | null;
    wave?: string | null;
    cash?: boolean;
  }>({});

  protected liens: { libelle: string; route: string; fragment?: string }[] = CONTENT.nav.liens.map(
    (lien) => ({
      libelle: lien.libelle,
      route: lien.cible.startsWith('/#') ? '/' : lien.cible,
      fragment: lien.cible.startsWith('/#') ? lien.cible.slice(2) : undefined,
    })
  );

  protected get afficherPaiements(): boolean {
    return Object.values(this.paiements()).some(Boolean);
  }

  ngOnInit(): void {
    this.api.getCabinetPublic().subscribe((cabinet) => {
      const fiche: FicheCabinet | null = cabinet.donnees?.fiche ?? null;
      this.telephone.set(fiche?.contact?.telephone ?? '');
      this.email.set(fiche?.contact?.email ?? '');
      this.adresse.set(fiche?.contact?.adresse ?? '');
      this.horaires.set(fiche?.contact?.horaires ?? '');
      this.facebook.set(fiche?.reseaux?.facebook || undefined);
      this.tiktok.set(fiche?.reseaux?.tiktok || undefined);
      this.whatsapp.set(fiche?.reseaux?.whatsapp_business || undefined);
      this.linkedin.set(fiche?.reseaux?.linkedin || undefined);
      this.paiements.set({
        orange_money: fiche?.paiements?.orange_money,
        moov_money: fiche?.paiements?.moov_money,
        wave: fiche?.paiements?.wave,
        cash: fiche?.paiements?.cash,
      });
      this.logoUrl.set(cabinet.logo_url ?? null);
    });
  }
}