import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { RevealDirective } from '../directives/reveal.directive';
import { CounterComponent } from '../components/counter.component';
import { SectionTitleComponent } from '../components/section-title.component';
import { ContactInfoComponent } from '../components/contact-info.component';
import { TemoignagesCarouselComponent } from '../components/temoignages-carousel.component';
import { FaqBlockComponent } from '../components/faq-block.component';
import { SeoService } from '../services/seo.service';
import {
  Enseignant,
  FaqSection,
  FicheCabinet,
  Temoignage,
} from '../models';

interface OngletSolution {
  icone: string;
  nom: string;
  titre: string;
  intro: string;
  paragraphes: readonly string[];
}

@Component({
  selector: 'app-accueil',
  imports: [
    CommonModule,
    RouterLink,
    RevealDirective,
    CounterComponent,
    SectionTitleComponent,
    ContactInfoComponent,
    TemoignagesCarouselComponent,
    FaqBlockComponent,
  ],
  styles: [
    `
      /* ---------- Hero ---------- */
      .hero {
        background: var(--mpc-bleu);
        color: #fff;
        position: relative;
        overflow: hidden;
        padding: 4rem 0 5.5rem;
      }
      .hero::before {
        content: '';
        position: absolute;
        top: -8rem;
        right: -6rem;
        width: 24rem;
        height: 24rem;
        border-radius: 50%;
        background: var(--mpc-primaire);
        opacity: 0.16;
      }
      .hero::after {
        content: '';
        position: absolute;
        bottom: -10rem;
        left: -4rem;
        width: 18rem;
        height: 18rem;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.08);
      }
      .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.10);
        border: 1px solid rgba(255, 255, 255, 0.16);
        color: var(--mpc-accent);
        border-radius: 999px;
        padding: 0.4rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
      }
      .hero h1 {
        font-family: var(--mpc-police-titre);
        font-size: clamp(2.1rem, 6.5vw, 3.4rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.12;
        margin: 1.1rem 0 1.1rem;
      }
      .hero h1 .accent {
        color: var(--mpc-accent);
      }
      .hero p.sous {
        max-width: 620px;
        color: rgba(255, 255, 255, 0.85);
        font-size: clamp(1rem, 2.6vw, 1.13rem);
        line-height: 1.75;
      }
      .hero-ctas {
        display: flex;
        flex-wrap: wrap;
        gap: 0.9rem;
        margin-top: 1.6rem;
      }
      .btn-hero-primaire {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        padding: 0.85rem 1.6rem;
        border-radius: 999px;
        text-decoration: none;
        box-shadow: 0 8px 20px rgba(232, 97, 12, 0.4);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .btn-hero-primaire:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 26px rgba(232, 97, 12, 0.5);
      }
      .btn-hero-second {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        color: #fff;
        font-weight: 600;
        padding: 0.85rem 1.5rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        text-decoration: none;
        transition: background 0.2s ease, border-color 0.2s ease;
      }
      .btn-hero-second:hover {
        background: rgba(255, 255, 255, 0.10);
        border-color: #fff;
      }
      .hero-cadre {
        position: relative;
        z-index: 1;
      }
      .hero-montage {
        position: relative;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.14);
        padding: 1.4rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
      }
      .hero-tuile {
        background: rgba(255, 255, 255, 0.09);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 14px;
        padding: 0.9rem 1rem;
      }
      .hero-tuile .ic {
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: var(--mpc-primaire);
        color: #fff;
        margin-bottom: 0.5rem;
      }
      .hero-tuile .lc {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--mpc-accent);
      }
      .hero-tuile .lv {
        font-size: 0.88rem;
        color: rgba(255, 255, 255, 0.9);
        margin-top: 0.2rem;
        line-height: 1.35;
      }
      .hero-bandeau {
        margin-top: 1rem;
        border-radius: 14px;
        background: var(--mpc-primaire);
        color: #fff;
        padding: 1rem 1.2rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-weight: 700;
      }

      /* ---------- Bandeau confiance ---------- */
      .confiance {
        background: var(--mpc-primaire-tint);
        border-bottom: 1px solid var(--mpc-bordure);
        padding: 1rem 0;
      }
      .confiance-item {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        color: var(--mpc-bleu);
        font-weight: 600;
        font-size: 0.94rem;
      }
      .confiance-item i {
        color: var(--mpc-primaire);
        font-size: 1.15rem;
      }

      /* ---------- À propos ---------- */
      .about-illustration {
        position: relative;
        border-radius: 22px;
        background: var(--mpc-bleu);
        color: #fff;
        padding: 2.4rem 1.8rem;
        min-height: 100%;
      }
      .about-illustration .big-icone {
        width: 4.2rem;
        height: 4.2rem;
        border-radius: 18px;
        display: grid;
        place-items: center;
        background: var(--mpc-primaire);
        font-size: 1.8rem;
        color: #fff;
        margin-bottom: 1.2rem;
      }
      .about-illustration h3 {
        font-family: var(--mpc-police-titre);
        font-weight: 700;
        font-size: 1.5rem;
      }
      .about-illustration p {
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.7;
      }
      .about-puce {
        padding: 1.2rem 1.2rem;
        border: 1px solid var(--mpc-bordure);
        border-radius: 14px;
        background: var(--mpc-fond);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
      }
      .about-puce:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 26px rgba(18, 48, 94, 0.10);
      }
      .about-puce .ic {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1.15rem;
        margin-bottom: 0.7rem;
      }
      .about-puce h4 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        margin-bottom: 0.3rem;
      }
      .about-puce p {
        font-size: 0.9rem;
        color: var(--mpc-texte-doux);
        line-height: 1.6;
        margin: 0;
      }

      /* ---------- Statistiques ---------- */
      .stats {
        background: var(--mpc-bleu);
        color: #fff;
        padding: 3.4rem 0;
      }
      .stats-tuile {
        text-align: center;
      }
      .stats-tuile .ic {
        margin: 0 auto 0.7rem;
        width: 3.2rem;
        height: 3.2rem;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: rgba(255, 255, 255, 0.10);
        color: var(--mpc-accent);
        font-size: 1.35rem;
      }
      .stats-tuile .num {
        font-family: var(--mpc-police-titre);
        font-weight: 800;
        font-size: clamp(1.8rem, 4.5vw, 2.6rem);
        color: var(--mpc-accent);
        line-height: 1.1;
      }
      .stats-tuile .lab {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.92rem;
        margin-top: 0.35rem;
      }

      /* ---------- Services ---------- */
      .service-carte {
        height: 100%;
        border: 1px solid var(--mpc-bordure);
        border-radius: 18px;
        background: var(--mpc-fond);
        padding: 1.6rem;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        display: flex;
        flex-direction: column;
      }
      .service-carte:hover {
        transform: translateY(-5px);
        border-color: var(--mpc-primaire);
        box-shadow: 0 14px 32px rgba(18, 48, 94, 0.12);
      }
      .service-carte .ic {
        width: 3rem;
        height: 3rem;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1.35rem;
        margin-bottom: 1rem;
      }
      .service-carte h3 {
        font-size: 1.08rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        margin-bottom: 0.5rem;
      }
      .service-carte p {
        color: var(--mpc-texte-doux);
        font-size: 0.93rem;
        line-height: 1.65;
        flex: 1;
      }
      .service-carte a {
        color: var(--mpc-primaire);
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.8rem;
      }
      .service-carte a:hover {
        gap: 0.6rem;
      }

      /* ---------- Zones ---------- */
      .zone-carte {
        border: 1px solid var(--mpc-bordure);
        border-radius: 18px;
        background: var(--mpc-fond);
        padding: 1.7rem;
        height: 100%;
      }
      .zone-puce {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu);
        border-radius: 999px;
        padding: 0.42rem 0.9rem;
        font-size: 0.86rem;
        font-weight: 600;
        margin: 0 0.45rem 0.45rem 0;
      }
      .zone-puce i {
        color: var(--mpc-primaire);
      }
      .hors-zones {
        border: 1px dashed var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
        border-radius: 16px;
        padding: 1.4rem;
        margin-top: 1.6rem;
      }

      /* ---------- Solutions ---------- */
      .onglets {
        display: flex;
        gap: 0.55rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        margin-bottom: 1.6rem;
        scrollbar-width: thin;
      }
      .onglet {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        border-radius: 999px;
        padding: 0.6rem 1.1rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.22s ease;
      }
      .onglet i {
        color: var(--mpc-primaire);
      }
      .onglet._actif {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .onglet._actif i {
        color: #fff;
      }
      .solution-panneau {
        border: 1px solid var(--mpc-bordure);
        border-radius: 18px;
        background: var(--mpc-fond);
        padding: 1.8rem;
      }
      .solution-titre {
        font-family: var(--mpc-police-titre);
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        margin-bottom: 0.6rem;
      }
      .solution-intro {
        color: var(--mpc-primaire);
        font-weight: 600;
        font-size: 1.02rem;
        margin-bottom: 0.9rem;
      }
      .solution-panneau p {
        color: var(--mpc-texte);
        line-height: 1.75;
      }

      /* ---------- Enseignants ---------- */
      .enseignant-carte {
        border: 1px solid var(--mpc-bordure);
        border-radius: 18px;
        background: var(--mpc-fond);
        overflow: hidden;
        transition: transform 0.22s ease, box-shadow 0.22s ease;
      }
      .enseignant-carte:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 32px rgba(18, 48, 94, 0.12);
      }
      .enseignant-front {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.3rem 1.3rem 1.1rem;
        background: var(--mpc-surface-teintee);
        border-bottom: 1px solid var(--mpc-bordure);
      }
      .avatar {
        width: 3.6rem;
        height: 3.6rem;
        border-radius: 50%;
        object-fit: cover;
        display: grid;
        place-items: center;
        background: var(--mpc-bleu);
        color: #fff;
        font-weight: 800;
        font-size: 1.2rem;
        flex-shrink: 0;
      }
      .enseignant-nom {
        font-weight: 700;
        color: var(--mpc-bleu);
        margin: 0;
        font-size: 1.05rem;
      }
      .enseignant-role {
        font-size: 0.84rem;
        color: var(--mpc-texte-doux);
      }
      .enseignant-info {
        padding: 1.1rem 1.3rem;
      }
      .enseignant-info .ligne {
        display: flex;
        gap: 0.5rem;
        align-items: flex-start;
        color: var(--mpc-texte-doux);
        font-size: 0.9rem;
        margin-bottom: 0.6rem;
      }
      .enseignant-info .ligne i {
        color: var(--mpc-primaire);
        margin-top: 0.15rem;
      }
      .matiere-puce {
        display: inline-block;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        border-radius: 999px;
        padding: 0.28rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 600;
        margin: 0 0.35rem 0.35rem 0;
      }
      .cta-bande-inner {
        background: var(--mpc-bleu);
        color: #fff;
        border-radius: 22px;
        padding: 2.4rem 2rem;
        position: relative;
        overflow: hidden;
      }
      .cta-bande-inner::before {
        content: '';
        position: absolute;
        top: -3rem;
        right: -3rem;
        width: 11rem;
        height: 11rem;
        border-radius: 50%;
        background: var(--mpc-primaire);
        opacity: 0.18;
      }
      .cta-bande-inner h3 {
        font-family: var(--mpc-police-titre);
        font-weight: 800;
        font-size: clamp(1.4rem, 4vw, 2rem);
      }
      .cta-bande-inner p {
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.7;
      }

      /* ---------- Cadences / étapes ---------- */
      .etape {
        text-align: center;
        padding: 1.2rem;
      }
      .etape .ic {
        margin: 0 auto 0.8rem;
        width: 3.4rem;
        height: 3.4rem;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1.4rem;
      }
      .etape h4 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--mpc-bleu);
      }
      .etape p {
        font-size: 0.9rem;
        color: var(--mpc-texte-doux);
        line-height: 1.6;
        margin: 0;
      }
    `,
  ],
  template: `
    <!-- ============ HERO ============ -->
    <section class="hero">
      <div class="container position-relative" style="z-index: 1">
        <div class="row align-items-center g-4 g-lg-5">
          <div class="col-12 col-lg-7">
            <span class="hero-badge"><i class="bi bi-award-fill"></i>{{ CONTENT.hero.accroche }}</span>
            <h1>Magis Plus Center<br /><span class="accent">L'excellence pour tous</span></h1>
            <p class="sous">{{ CONTENT.hero.sousTitre }}</p>
            <div class="hero-ctas">
              <a class="btn-hero-primaire" routerLink="/demande-cours"><i class="bi bi-send-fill"></i>{{ CONTENT.hero.cta_principal }}</a>
              <a class="btn-hero-second" routerLink="/" fragment="services"><i class="bi bi-grid-1x2-fill"></i>{{ CONTENT.hero.cta_secondaire }}</a>
            </div>
          </div>
          <div class="col-12 col-lg-5 hero-cadre">
            <div class="hero-montage">
              <div class="hero-tuile" *ngFor="let c of montage">
                <div class="ic"><i class="bi {{ c.icone }}"></i></div>
                <div class="lc">{{ c.titre }}</div>
                <div class="lv">{{ c.texte }}</div>
              </div>
            </div>
            <div class="hero-bandeau">
              <i class="bi bi-patch-check-fill" style="font-size:1.4rem"></i>
              <div>
                <div style="font-size:1.02rem">Enseignants qualifiés et vérifiés partout au Burkina Faso</div>
                <div style="font-weight:500;opacity:.9;font-size:.86rem">Primaire · Collège · Lycée · Supérieur</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ BANDEAU CONFIANCE ============ -->
    <section class="confiance">
      <div class="container">
        <div class="row g-3 align-items-center">
          <div class="col-12 col-md-4 confiance-item"><i class="bi bi-person-check-fill"></i>Enseignants qualifiés et vérifiés</div>
          <div class="col-12 col-md-4 confiance-item"><i class="bi bi-geo-alt-fill"></i>Présent dans tout le Burkina Faso</div>
          <div class="col-12 col-md-4 confiance-item"><i class="bi bi-mortarboard-fill"></i>Du primaire au supérieur</div>
        </div>
      </div>
    </section>

    <!-- ============ À PROPOS ============ -->
    <section id="about" class="py-5">
      <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
          <div class="col-12 col-lg-5">
            <div class="about-illustration" appReveal>
              <div class="big-icone"><i class="bi bi-stars"></i></div>
              <h3>Qui sommes-nous ?</h3>
              <p>{{ CONTENT.about.texte }}</p>
            </div>
          </div>
          <div class="col-12 col-lg-7">
            <span class="surtitre">À propos</span>
            <h2 class="titre-section">Magis Plus Center, votre partenaire réussite</h2>
            <p class="texte-section">Un centre éducatif qui met en relation familles, élèves et enseignants pour un accompagnement scolaire personnalisé, du primaire au supérieur, à domicile comme en ligne.</p>
            <div class="row g-3 mt-1">
              <div class="col-12 col-sm-6" *ngFor="let atout of CONTENT.about.atouts" appReveal>
                <div class="about-puce">
                  <div class="ic"><i class="bi {{ atout.icone }}"></i></div>
                  <h4>{{ atout.titre }}</h4>
                  <p>{{ atout.texte }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ STATISTIQUES (API) ============ -->
    <section class="stats" *ngIf="stats">
      <div class="container">
        <div class="row g-4">
          <div class="col-6 col-lg-3 stats-tuile" *ngFor="let stat of stats" appReveal>
            <div class="ic"><i class="bi {{ stat.icone }}"></i></div>
            <div class="num"><app-counter [fin]="stat.valeur" suffixe="+"></app-counter></div>
            <div class="lab">{{ stat.libelle }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ SERVICES ============ -->
    <section id="services" class="py-5" style="background: var(--mpc-surface-teintee)">
      <div class="container">
        <app-section-title surtitre="Nos services" [titre]="CONTENT.services.titre" [sousTitre]="CONTENT.services.sousTitre"></app-section-title>
        <div class="row g-4">
          <div class="col-12 col-sm-6 col-lg-4" *ngFor="let service of CONTENT.services.liste" appReveal>
            <article class="service-carte">
              <div class="ic"><i class="bi {{ service.icone }}"></i></div>
              <h3>{{ service.titre }}</h3>
              <p>{{ service.texte }}</p>
              <a [routerLink]="service.cible"><i class="bi bi-arrow-right"></i>{{ service.bouton }}</a>
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ ZONES ============ -->
    <section id="zones" class="py-5">
      <div class="container">
        <app-section-title surtitre="Où intervenons-nous ?" [titre]="CONTENT.zones.titre" [sousTitre]="CONTENT.zones.sousTitre"></app-section-title>
        <div class="row g-4">
          <div class="col-12 col-lg-6" appReveal>
            <div class="zone-carte">
              <h4 style="color: var(--mpc-bleu); font-weight:700">{{ CONTENT.zones.blocTitre }}</h4>
              <p style="color: var(--mpc-texte-doux); line-height:1.7">{{ CONTENT.zones.blocTexte1 }}</p>
              <div class="mt-3">
                <span class="zone-puce" *ngFor="let ville of villesPrincipales"><i class="bi bi-geo-fill"></i>{{ ville }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-6" appReveal>
            <div class="zone-carte">
              <h4 style="color: var(--mpc-bleu); font-weight:700">Une couverture qui s'étend</h4>
              <p style="color: var(--mpc-texte-doux); line-height:1.7">{{ CONTENT.zones.blocTexte2 }}</p>
              <div class="mt-3">
                <span class="zone-puce" *ngFor="let ville of villesSecondaires"><i class="bi bi-geo-fill"></i>{{ ville }}</span>
              </div>
              <div class="hors-zones">
                <h5 style="color: var(--mpc-bleu); font-weight:700; display:flex; align-items:center; gap:.5rem">
                  <i class="bi bi-globe2" style="color: var(--mpc-primaire)"></i>{{ CONTENT.zones.horsZones.titre }}
                </h5>
                <p style="color: var(--mpc-texte-doux); font-size:.93rem; line-height:1.7; margin-top:.5rem">{{ CONTENT.zones.horsZones.lignes[1] }}</p>
                <div class="d-flex flex-wrap gap-2 mt-2">
                  <a class="btn" style="background: var(--mpc-primaire); color:#fff; border-radius:999px; padding:.6rem 1.3rem; font-weight:700; text-decoration:none" routerLink="/demande-cours"><i class="bi bi-send-fill"></i>{{ CONTENT.zones.horsZones.cta }}</a>
                  <a class="btn" style="border:1px solid var(--mpc-primaire); color: var(--mpc-primaire); border-radius:999px; padding:.6rem 1.3rem; font-weight:700; text-decoration:none" [href]="lienWhatsapp" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i>{{ CONTENT.zones.horsZones.whatsapp }}</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ SOLUTIONS ============ -->
    <section id="solutions" class="py-5" style="background: var(--mpc-surface-teintee)">
      <div class="container">
        <app-section-title surtitre="Comment nous aidons" [titre]="CONTENT.solutions.titre" [sousTitre]="CONTENT.solutions.sousTitre"></app-section-title>
        <div class="onglets" appReveal>
          <button type="button" class="onglet" [class._actif]="indiceSolution === i" *ngFor="let onglet of CONTENT.solutions.onglets; let i = index"
                  (click)="indiceSolution = i">
            <i class="bi {{ onglet.icone }}"></i>{{ onglet.nom }}
          </button>
        </div>
        <div class="solution-panneau" appReveal>
          <h3 class="solution-titre">{{ ongletActif.titre }}</h3>
          <p class="solution-intro">{{ ongletActif.intro }}</p>
          <p *ngFor="let para of ongletActif.paragraphes">{{ para }}</p>
        </div>
      </div>
    </section>

    <!-- ============ ENSEIGNANTS (API) ============ -->
    <section id="enseignants" class="py-5">
      <div class="container">
        <app-section-title surtitre="Notre réseau" [titre]="CONTENT.enseignants.titre" [sousTitre]="CONTENT.enseignants.sousTitre"></app-section-title>

        <div class="row g-4" *ngIf="enseignants.length > 0">
          <div class="col-12 col-sm-6 col-lg-4" *ngFor="let enseignant of enseignants | slice:0:6" appReveal>
            <article class="enseignant-carte">
              <div class="enseignant-front">
                <img class="avatar" *ngIf="enseignant.photo_url" [src]="enseignant.photo_url" alt="{{ enseignant.nom_complet }}" loading="lazy" />
                <span class="avatar" *ngIf="!enseignant.photo_url">{{ initiale(enseignant.nom_complet) }}</span>
                <div>
                  <p class="enseignant-nom">{{ enseignant.nom_complet }}</p>
                  <p class="enseignant-role">Enseignant(e)</p>
                </div>
              </div>
              <div class="enseignant-info">
                <div class="ligne" *ngIf="enseignant.diplome_max"><i class="bi bi-award"></i><span>Diplôme : {{ enseignant.diplome_max }}</span></div>
                <div class="ligne" *ngIf="enseignant.lieu_de_service"><i class="bi bi-geo-alt"></i><span>{{ enseignant.lieu_de_service }}</span></div>
                <div *ngIf="enseignant.matieres.length > 0">
                  <span class="matiere-puce" *ngFor="let matiere of enseignant.matieres">{{ matiere }}</span>
                </div>
              </div>
            </article>
          </div>
        </div>

        <p class="text-center" style="color: var(--mpc-texte-doux)" *ngIf="enseignants.length === 0 && enseignantsCharge">{{ CONTENT.enseignants.vide }}</p>

        <div class="cta-bande mt-5">
          <div class="cta-bande-inner" appReveal>
            <div class="row align-items-center g-3">
              <div class="col-12 col-lg-8">
                <h3>{{ CONTENT.enseignants.cta_titre }}</h3>
                <p style="margin:0">{{ CONTENT.enseignants.cta_texte }}</p>
              </div>
              <div class="col-12 col-lg-4 text-lg-end">
                <a class="btn" style="background: var(--mpc-primaire); color:#fff; border-radius:999px; padding:.85rem 1.6rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:.55rem" routerLink="/demande-cours"><i class="bi bi-person-plus-fill"></i>Choisir mon enseignant</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ TÉMOIGNAGES (API) ============ -->
    <section id="temoignages" class="py-5" style="background: var(--mpc-surface-teintee)">
      <div class="container">
        <app-section-title surtitre="Ils nous font confiance" [titre]="CONTENT.temoignages.titre" [sousTitre]="CONTENT.temoignages.sousTitre"></app-section-title>
        <ng-container *ngIf="temoignagesHaut.length > 0">
          <app-temoignages-carousel [temoignages]="temoignagesHaut"></app-temoignages-carousel>
        </ng-container>
        <p class="text-center mt-3" style="color: var(--mpc-texte-doux)" *ngIf="temoignagesHaut.length === 0">{{ CONTENT.temoignages.vide }}</p>
      </div>
    </section>

    <!-- ============ FAQ (API) ============ -->
    <section id="faq" class="py-5">
      <div class="container">
        <app-section-title surtitre="Besoin d'aide ?" [titre]="CONTENT.faq.titre" [sousTitre]="CONTENT.faq.sousTitre"></app-section-title>
        <app-faq-block [sections]="sectionsFaq" idFacteur="accueil"></app-faq-block>
      </div>
    </section>

    <!-- ============ BANDEAU DÉMARCHE ============ -->
    <section class="pb-5">
      <div class="container">
        <div class="cta-bande-inner" appReveal style="background: var(--mpc-primaire)">
          <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-7">
              <h3>{{ CONTENT.appointment.titre }}</h3>
              <p style="margin:0; color: rgba(255,255,255,.9)">{{ CONTENT.appointment.sousTitre }}</p>
            </div>
            <div class="col-12 col-lg-5 text-lg-end">
              <a class="btn" style="background: var(--mpc-bleu); color:#fff; border-radius:999px; padding:.85rem 1.6rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:.55rem" routerLink="/demande-cours"><i class="bi bi-send-fill"></i>{{ CONTENT.appointment.bouton }}</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ CONTACT ============ -->
    <section id="contact" class="pb-5 pt-1">
      <div class="container">
        <app-section-title surtitre="Contact" [titre]="CONTENT.contact.titre" [sousTitre]="CONTENT.contact.sousTitre"></app-section-title>
        <div class="row g-4 justify-content-center">
          <div class="col-12 col-lg-7" appReveal>
            <app-contact-info [fiche]="fiche"></app-contact-info>
          </div>
          <div class="col-12 col-lg-5" appReveal>
            <div class="zone-carte">
              <h4 style="color: var(--mpc-bleu); font-weight:700; display:flex; align-items:center; gap:.5rem"><i class="bi bi-person-workspace" style="color: var(--mpc-primaire)"></i>{{ CONTENT.contact.carteTitre }}</h4>
              <p style="color: var(--mpc-texte-doux); line-height:1.7">{{ CONTENT.contact.carteTexte }}</p>
              <div class="row g-2 mt-1">
                <div class="col-12 col-sm-6" *ngFor="let solution of CONTENT.contact.solutions">
                  <div class="about-puce" style="padding:1rem">
                    <div class="ic"><i class="bi {{ solution.icone }}"></i></div>
                    <h4 style="font-size:.95rem">{{ solution.titre }}</h4>
                    <p style="font-size:.82rem">{{ solution.texte }}</p>
                  </div>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <a class="btn" style="background: var(--mpc-primaire); color:#fff; border-radius:999px; padding:.65rem 1.3rem; font-weight:700; text-decoration:none" routerLink="/demande-cours"><i class="bi bi-send-fill"></i>{{ CONTENT.contact.demandeCours }}</a>
                <a class="btn" [href]="lienWhatsapp || '#'" target="_blank" rel="noopener" style="border:1px solid var(--mpc-primaire); color: var(--mpc-primaire); border-radius:999px; padding:.65rem 1.3rem; font-weight:700; text-decoration:none"><i class="bi bi-whatsapp"></i>{{ CONTENT.contact.whatsapp }}</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  `,
})
export class AccueilComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);

  protected readonly CONTENT = CONTENT;

  protected fiche: FicheCabinet | null = null;
  protected lienWhatsapp = '#';
  protected enseignants: Enseignant[] = [];
  protected temoignages: Temoignage[] = [];
  protected sectionsFaq: FaqSection[] = [];
  protected stats: { icone: string; valeur: number; libelle: string }[] | null = null;
  protected enseignantsCharge = false;
  protected indiceSolution = 0;

  protected readonly ongletsSolutions: readonly OngletSolution[] = CONTENT.solutions.onglets;

  protected get ongletActif(): OngletSolution {
    return this.ongletsSolutions[this.indiceSolution];
  }

  protected get temoignagesHaut(): Temoignage[] {
    return this.temoignages.slice(0, 6);
  }

  protected get villesPrincipales(): string[] {
    return CONTENT.zones.villesPrincipales.split(', ').map((v) => v.trim());
  }

  protected get villesSecondaires(): string[] {
    return CONTENT.zones.villesSecondaires.split(', ').map((v) => v.trim());
  }

  protected montage = [
    { icone: 'bi-house-door-fill', titre: 'À domicile', texte: 'Cours d\'appui chez vous' },
    { icone: 'bi-laptop-fill', titre: 'En ligne', texte: 'Meet, Teams, WhatsApp' },
    { icone: 'bi-journal-bookmark-fill', titre: 'Bibliothèque', texte: 'Devoirs et annales' },
    { icone: 'bi-shop', titre: 'Boutique', texte: 'Fournitures scolaires' },
  ];

  ngOnInit(): void {
    this.seo.definir(CONTENT.seo.titre, CONTENT.seo.description);

    this.api.getCabinetPublic().subscribe((cabinet) => {
      this.fiche = cabinet.donnees?.fiche ?? null;
      const reseaux = this.fiche?.reseaux ?? {};
      if (this.fiche?.contact?.whatsapp || this.fiche?.contact?.telephone) {
        const chiffres = (this.fiche.contact.whatsapp || this.fiche.contact.telephone || '').replace(/\D/g, '');
        this.lienWhatsapp = 'https://wa.me/' + (chiffres.startsWith('226') ? chiffres : '226' + chiffres);
      } else if (this.fiche?.reseaux?.whatsapp_business) {
        this.lienWhatsapp = this.fiche.reseaux.whatsapp_business;
      }
    });

    this.api.getEnseignants(1).subscribe(({ data }) => {
      this.enseignants = data;
      this.enseignantsCharge = true;
    });

    this.api.getTemoignages(1).subscribe(({ data }) => {
      this.temoignages = data;
    });

    this.api.getFaq().subscribe((sections) => {
      this.sectionsFaq = sections;
    });

    this.api.getStats().subscribe((stats) => {
      this.stats = [
        { icone: 'bi-person-workspace', valeur: stats.nb_enseignants, libelle: 'Enseignants qualifiés' },
        { icone: 'bi-people-fill', valeur: stats.nb_eleves, libelle: 'Élèves accompagnés' },
        { icone: 'bi-card-checklist', valeur: stats.nb_contrats, libelle: 'Contrats de cours' },
        { icone: 'bi-house-heart-fill', valeur: stats.nb_familles, libelle: 'Familles partenaires' },
      ];
    });
  }

  protected initiale(nom: string): string {
    return nom.trim().charAt(0).toUpperCase() || 'E';
  }
}