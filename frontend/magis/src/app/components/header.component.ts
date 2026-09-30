import { Component, ElementRef, HostListener, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavigationEnd, Router, RouterLink } from '@angular/router';
import { CONTENT } from '../../content';
import { FicheCabinet } from '../models';
import { ApiService } from '../services/api.service';

interface LienNav {
  libelle: string;
  route: string;
  fragment?: string;
  activer: boolean;
}

@Component({
  selector: 'app-header',
  imports: [CommonModule, RouterLink],
  styles: [
    `
      :host {
        display: block;
        position: sticky;
        top: 0;
        z-index: 60;
      }
      .barre-top {
        background: var(--mpc-bleu);
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.83rem;
        padding: 0.45rem 0;
      }
      .barre-top a {
        color: #fff;
        text-decoration: none;
      }
      .barre-top a:hover {
        color: var(--mpc-accent);
      }
      .barre-top .sep {
        opacity: 0.35;
        margin: 0 0.6rem;
      }
      .barre-top i {
        color: var(--mpc-accent);
      }
      .entete {
        position: relative;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        transition: box-shadow 0.25s ease;
      }
      .entete._deplie {
        box-shadow: 0 6px 24px rgba(18, 48, 94, 0.10);
      }
      .entete-inner {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.8rem 0;
      }
      .logo {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        text-decoration: none;
        min-width: 0;
      }
      .logo-carre {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 12px;
        background: var(--mpc-primaire);
        color: #fff;
        display: grid;
        place-items: center;
        font-weight: 800;
        font-family: var(--mpc-police-titre);
        font-size: 1.15rem;
        flex-shrink: 0;
      }
      .logo-img {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 12px;
        object-fit: cover;
        background: #fff;
        border: 1px solid var(--mpc-separateur);
        flex-shrink: 0;
      }
      .logo-texte {
        display: flex;
        flex-direction: column;
        line-height: 1.05;
        min-width: 0;
      }
      .logo-nom {
        font-weight: 800;
        color: var(--mpc-bleu);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 44vw;
      }
      .logo-slogan {
        font-size: 0.74rem;
        color: var(--mpc-texte-doux);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 44vw;
      }
      .nav-desktop {
        display: flex;
        align-items: center;
        gap: 1.35rem;
        margin-inline-start: auto;
      }
      .nav-desktop a {
        color: var(--mpc-texte);
        text-decoration: none;
        font-size: 0.94rem;
        font-weight: 600;
        position: relative;
        padding: 0.3rem 0;
        transition: color 0.2s ease;
      }
      .nav-desktop a:hover,
      .nav-desktop a._actif {
        color: var(--mpc-primaire);
      }
      .nav-desktop a::after {
        content: '';
        position: absolute;
        left: 0;
        right: 100%;
        bottom: 0;
        height: 2px;
        border-radius: 2px;
        background: var(--mpc-primaire);
        transition: right 0.25s ease;
      }
      .nav-desktop a:hover::after,
      .nav-desktop a._actif::after {
        right: 0;
      }
      .cta {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.65rem 1.25rem;
        border-radius: 999px;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 6px 16px rgba(232, 97, 12, 0.35);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        white-space: nowrap;
      }
      .cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(232, 97, 12, 0.42);
      }
      .connexion {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.2rem;
        border-radius: 999px;
        border: 1.5px solid var(--mpc-bleu);
        color: var(--mpc-bleu);
        background: transparent;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
      }
      .connexion:hover {
        background: var(--mpc-bleu);
        color: #fff;
      }
      .burger {
        display: none;
        width: 2.6rem;
        height: 2.6rem;
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        border-radius: 10px;
        color: var(--mpc-bleu);
        font-size: 1.15rem;
        margin-inline-start: auto;
        cursor: pointer;
      }
      .voile {
        position: fixed;
        inset: 0;
        background: rgba(18, 48, 94, 0.45);
        z-index: 70;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
      }
      .voile._ouvert {
        opacity: 1;
        pointer-events: auto;
      }
      .tiroir {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: min(78vw, 320px);
        background: var(--mpc-fond);
        z-index: 80;
        display: flex;
        flex-direction: column;
        transform: translateX(100%);
        transition: transform 0.35s cubic-bezier(0.22, 0.61, 0.36, 1);
        box-shadow: -8px 0 30px rgba(18, 48, 94, 0.18);
      }
      .tiroir._ouvert {
        transform: translateX(0);
      }
      .tiroir-entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid var(--mpc-bordure);
      }
      .tiroir-fermer {
        width: 2.3rem;
        height: 2.3rem;
        border: 0;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu);
        border-radius: 10px;
        cursor: pointer;
        font-size: 1.05rem;
      }
      .tiroir-nav {
        display: flex;
        flex-direction: column;
        padding: 0.6rem 1.2rem 1.2rem;
        gap: 0.1rem;
        overflow-y: auto;
      }
      .tiroir-nav a {
        padding: 0.75rem 0.6rem;
        border-radius: 10px;
        color: var(--mpc-texte);
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .tiroir-nav a:hover,
      .tiroir-nav a._actif {
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
      }
      .tiroir-connexion {
        margin: 0.75rem 1.2rem 0;
        justify-content: center;
      }
      .tiroir-cta {
        margin: 0.75rem 1.2rem 1.5rem;
        justify-content: center;
      }
      .reseau-tiroir {
        display: flex;
        gap: 0.6rem;
        padding: 1rem 1.2rem;
        border-top: 1px solid var(--mpc-bordure);
      }
      .reseau-tiroir a {
        width: 2.3rem;
        height: 2.3rem;
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: 1px solid var(--mpc-bordure);
        color: var(--mpc-bleu);
      }
      @media (max-width: 991px) {
        .nav-desktop,
        .cta-desktop,
        .connexion-desktop {
          display: none;
        }
        .burger {
          display: grid;
          place-items: center;
        }
        .barre-top .reseaux-top {
          display: none;
        }
      }
    `,
  ],
  template: `
    <!-- Barre supérieure : coordonnées + réseaux -->
    <div class="barre-top">
      <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center flex-wrap gap-1">
          <a *ngIf="telephone()" [href]="'tel:+' + telephone().replace(/\\D/g, '')" class="d-flex align-items-center gap-1">
            <i class="bi bi-telephone-fill"></i>{{ telephone() }}
          </a>
          <span class="sep" *ngIf="telephone() && email()">|</span>
          <a *ngIf="email()" [href]="'mailto:' + email()" class="d-flex align-items-center gap-1">
            <i class="bi bi-envelope-fill"></i>{{ email() }}
          </a>
        </div>
        <div class="reseaux-top d-flex align-items-center gap-3">
          <a *ngIf="facebook()" [href]="facebook()" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a *ngIf="tiktok()" [href]="tiktok()" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
          <a *ngIf="whatsapp()" [href]="whatsapp()" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
          <a *ngIf="linkedin()" [href]="linkedin()" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
    </div>

    <!-- Navigation collante -->
    <header class="entete" [class._deplie]="deplie()">
      <div class="container entete-inner">
        <a class="logo" routerLink="/">
          @if (logoUrl()) {
            <img class="logo-img" [src]="logoUrl()" alt="Logo {{ nom }}" />
          } @else {
            <span class="logo-carre">{{ initiales }}</span>
          }
          <span class="logo-texte">
            <span class="logo-nom">{{ nom }}</span>
            <span class="logo-slogan">{{ slogan }}</span>
          </span>
        </a>

        <nav class="nav-desktop">
          <a *ngFor="let lien of liens" routerLink="{{ lien.route }}"
             [fragment]="lien.fragment"
             [class._actif]="estActif(lien)">{{ lien.libelle }}</a>
        </nav>
        <a class="connexion connexion-desktop" routerLink="/connexion"><i class="bi bi-box-arrow-in-right"></i>Se connecter</a>
        <a class="cta cta-desktop" routerLink="/demande-cours"><i class="bi bi-send"></i>Demander un cours</a>

        <button type="button" class="burger" (click)="menuOuvert = true" aria-label="Ouvrir le menu">
          <i class="bi bi-list"></i>
        </button>
      </div>
    </header>

    <!-- Tiroir mobile -->
    <div class="voile" [class._ouvert]="menuOuvert" (click)="menuOuvert = false"></div>
    <aside class="tiroir" [class._ouvert]="menuOuvert" aria-hidden="!menuOuvert ? true : null">
      <div class="tiroir-entete">
        <a class="logo" routerLink="/" (click)="menuOuvert = false">
          @if (logoUrl()) {
            <img class="logo-img" [src]="logoUrl()" alt="Logo {{ nom }}" />
          } @else {
            <span class="logo-carre">{{ initiales }}</span>
          }
          <span class="logo-texte"><span class="logo-nom">{{ nom }}</span></span>
        </a>
        <button type="button" class="tiroir-fermer" (click)="menuOuvert = false" aria-label="Fermer le menu">
          <i class="bi bi-x"></i>
        </button>
      </div>
      <nav class="tiroir-nav">
        <a *ngFor="let lien of liens" routerLink="{{ lien.route }}"
           [fragment]="lien.fragment"
           [class._actif]="estActif(lien)"
           (click)="menuOuvert = false">
          {{ lien.libelle }}<i class="bi bi-chevron-right"></i>
        </a>
      </nav>
      <a class="connexion tiroir-connexion" routerLink="/connexion" (click)="menuOuvert = false">
        <i class="bi bi-box-arrow-in-right"></i>Se connecter
      </a>
      <a class="cta tiroir-cta" routerLink="/demande-cours" (click)="menuOuvert = false">
        <i class="bi bi-send"></i>Demander un cours
      </a>
      <div class="reseau-tiroir">
        <a *ngIf="facebook()" [href]="facebook()" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
        <a *ngIf="tiktok()" [href]="tiktok()" target="_blank" rel="noopener"><i class="bi bi-tiktok"></i></a>
        <a *ngIf="whatsapp()" [href]="whatsapp()" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>
        <a *ngIf="linkedin()" [href]="linkedin()" target="_blank" rel="noopener"><i class="bi bi-linkedin"></i></a>
      </div>
    </aside>
  `,
})
export class HeaderComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);
  private readonly element = inject(ElementRef<HTMLElement>);

  protected readonly CONTENT = CONTENT;
  protected menuOuvert = false;
  protected readonly deplie = signal(false);
  protected readonly sectionActive = signal('');
  protected readonly routePage = signal(this.router.url.split('#')[0] ?? '');

  protected nom = CONTENT.cabinet.nom;
  protected initiales = CONTENT.cabinet.sigle;
  protected slogan = CONTENT.cabinet.slogan;
  protected readonly logoUrl = signal<string | null>(null);

  protected readonly telephone = signal('');
  protected readonly email = signal('');
  protected readonly facebook = signal<string | undefined>(undefined);
  protected readonly tiktok = signal<string | undefined>(undefined);
  protected readonly whatsapp = signal<string | undefined>(undefined);
  protected readonly linkedin = signal<string | undefined>(undefined);

  protected liens: LienNav[] = CONTENT.nav.liens.map((lien) => {
    const fragment = lien.cible.startsWith('/#') ? lien.cible.slice(2) : undefined;
    return {
      libelle: lien.libelle,
      route: lien.cible.startsWith('/#') ? '/' : lien.cible,
      fragment,
      activer: !lien.cible.startsWith('/#'),
    };
  });

  ngOnInit(): void {
    this.router.events.subscribe((evenement) => {
      if (evenement instanceof NavigationEnd) {
        this.routePage.set(evenement.url.split('#')[0] ?? '');
        requestAnimationFrame(() => this.mettreAJourSection());
      }
    });
    this.api.getCabinetPublic().subscribe((cabinet) => {
      const fiche: FicheCabinet | null = cabinet.donnees?.fiche ?? null;
      this.telephone.set(fiche?.contact?.telephone ?? '');
      this.email.set(fiche?.contact?.email ?? '');
      this.facebook.set(fiche?.reseaux?.facebook || undefined);
      this.tiktok.set(fiche?.reseaux?.tiktok || undefined);
      this.whatsapp.set(fiche?.reseaux?.whatsapp_business || undefined);
      this.linkedin.set(fiche?.reseaux?.linkedin || undefined);
      this.logoUrl.set(cabinet.logo_url ?? null);
    });
    requestAnimationFrame(() => this.mettreAJourSection());
  }

  protected estActif(lien: LienNav): boolean {
    if (!lien.activer) {
      return this.routePage() === '/' && lien.fragment === this.sectionActive();
    }
    if (lien.route === '/') {
      return this.routePage() === '/' && this.sectionActive() === '';
    }
    return this.routePage() === lien.route;
  }

  @HostListener('window:scroll', [])
  protected surScroll(): void {
    this.deplie.set((window.scrollY ?? 0) > 24);
    this.mettreAJourSection();
  }

  protected mettreAJourSection(): void {
    if (this.routePage() !== '/') {
      this.sectionActive.set('');
      return;
    }
    const sections = this.liens
      .filter((lien) => lien.fragment)
      .map((lien) => document.getElementById(lien.fragment!))
      .filter((el): el is HTMLElement => !!el);
    if (!sections.length) {
      this.sectionActive.set('');
      return;
    }
    const seuil = this.element.nativeElement.offsetHeight + 40;
    let actif = '';
    for (const section of sections) {
      if (section.getBoundingClientRect().top <= seuil) {
        actif = section.id;
      }
    }
    this.sectionActive.set(actif);
  }
}