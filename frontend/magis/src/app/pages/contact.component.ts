import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { RevealDirective } from '../directives/reveal.directive';
import { PageHeroComponent } from '../components/page-hero.component';
import { ContactInfoComponent } from '../components/contact-info.component';
import { FicheCabinet } from '../models';

@Component({
  selector: 'app-contact',
  imports: [CommonModule, RouterLink, RevealDirective, PageHeroComponent, ContactInfoComponent],
  styles: [
    `
      .carte-cta {
        border: 1px solid var(--mpc-bordure);
        border-radius: 16px;
        background: var(--mpc-fond);
        padding: 1.4rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.22s ease, box-shadow 0.22s ease;
      }
      .carte-cta:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(18, 48, 94, 0.10);
      }
      .carte-cta .ic {
        flex-shrink: 0;
        width: 3.1rem;
        height: 3.1rem;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1.3rem;
      }
      .en-tete {
        background: var(--mpc-surface-teintee);
        border: 1px solid var(--mpc-bordure);
        border-radius: 22px;
        padding: 2rem 1.6rem;
      }
    `,
  ],
  template: `
    <app-page-hero [titre]="CONTENT.contact.titre" [sousTitre]="CONTENT.contact.sousTitre"></app-page-hero>

    <section class="py-5">
      <div class="container">
        <div class="row g-4 justify-content-center">
          <!-- Coordonnées dynamiques (fiche cabinet) -->
          <div class="col-12 col-lg-6" appReveal>
            <app-contact-info [fiche]="fiche()"></app-contact-info>
          </div>

          <!-- Actions rapides -->
          <div class="col-12 col-lg-6" appReveal>
            <div class="en-tete">
              <h2 style="font-family:var(--mpc-police-titre); color: var(--mpc-bleu); font-weight:700; font-size:1.35rem; display:flex; gap:.5rem; align-items:center">
                <i class="bi bi-lightning-charge-fill" style="color: var(--mpc-primaire)"></i>Un besoin précis ?
              </h2>
              <p style="color: var(--mpc-texte-doux); line-height:1.7">
                Nous répondons rapidement à toutes vos demandes, du lundi au samedi.
              </p>
              <div class="row g-3 mt-1">
                <div class="col-12 col-sm-6">
                  <a class="carte-cta text-decoration-none" routerLink="/demande-cours">
                    <span class="ic"><i class="bi bi-send-fill"></i></span>
                    <span><strong style="color: var(--mpc-bleu)">Demander un cours</strong><br />
                      <span style="color: var(--mpc-texte-doux); font-size:.86rem">{{ CONTENT.contact.demandeCours }}</span></span>
                  </a>
                </div>
                <div class="col-12 col-sm-6">
                  <a class="carte-cta text-decoration-none" [href]="lienWhatsapp() || '#'" target="_blank" rel="noopener">
                    <span class="ic"><i class="bi bi-whatsapp"></i></span>
                    <span><strong style="color: var(--mpc-bleu)">WhatsApp</strong><br />
                      <span style="color: var(--mpc-texte-doux); font-size:.86rem">{{ CONTENT.contact.whatsapp }}</span></span>
                  </a>
                </div>
                <div class="col-12 col-sm-6">
                  <a class="carte-cta text-decoration-none" [href]="lienTelephone() || '#'">
                    <span class="ic"><i class="bi bi-telephone-fill"></i></span>
                    <span><strong style="color: var(--mpc-bleu)">Appeler</strong><br />
                      <span style="color: var(--mpc-texte-doux); font-size:.86rem">{{ CONTENT.contact.appeler }}</span></span>
                  </a>
                </div>
                <div class="col-12 col-sm-6">
                  <a class="carte-cta text-decoration-none" [href]="lienEmail() || '#'">
                    <span class="ic"><i class="bi bi-envelope-fill"></i></span>
                    <span><strong style="color: var(--mpc-bleu)">Email</strong><br />
                      <span style="color: var(--mpc-texte-doux); font-size:.86rem">{{ CONTENT.contact.envoyerEmail }}</span></span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  `,
})
export class ContactComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);

  protected readonly CONTENT = CONTENT;

  protected readonly fiche = signal<FicheCabinet | null>(null);
  protected readonly lienWhatsapp = signal('');
  protected readonly lienTelephone = signal('');
  protected readonly lienEmail = signal('');

  ngOnInit(): void {
    this.seo.definir('Contact | Magis Plus Center', CONTENT.contact.sousTitre);
    this.api.getCabinetPublic().subscribe((cabinet) => {
      this.fiche.set(cabinet.donnees?.fiche ?? null);
      const contact = this.fiche()?.contact ?? {};
      if (contact.telephone) {
        this.lienTelephone.set('tel:+' + contact.telephone.replace(/\D/g, ''));
        const chiffres = (contact.whatsapp || contact.telephone).replace(/\D/g, '');
        this.lienWhatsapp.set(
          'https://wa.me/' + (chiffres.startsWith('226') ? chiffres : '226' + chiffres)
        );
      }
      if (contact.email) {
        this.lienEmail.set('mailto:' + contact.email);
      }
    });
  }
}