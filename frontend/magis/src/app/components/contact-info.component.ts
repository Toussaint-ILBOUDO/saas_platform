import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FicheCabinet, formaterTelephoneBrut } from '../models';
import { RevealDirective } from '../directives/reveal.directive';
import { CONTENT } from '../../content';

/**
 * Coordonnées + paiements + zones + réseaux tirés de la fiche cabinet (D-044).
 * Boutons d'action : appeler, WhatsApp, email, demande de cours.
 */
@Component({
  selector: 'app-contact-info',
  imports: [CommonModule, RevealDirective],
  styles: [
    `
      .info-item {
        display: flex;
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        border: 1px solid var(--mpc-bordure);
        border-radius: 14px;
        background: var(--mpc-fond);
      }
      .info-icone {
        flex-shrink: 0;
        width: 2.9rem;
        height: 2.9rem;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1.2rem;
      }
      .info-texte h3 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        margin: 0 0 0.3rem;
      }
      .info-texte p {
        margin: 0;
        color: var(--mpc-texte-doux);
        line-height: 1.6;
        font-size: 0.95rem;
        white-space: pre-line;
      }
      .info-texte a {
        color: var(--mpc-primaire);
        text-decoration: none;
        font-weight: 600;
      }
      .info-texte a:hover {
        text-decoration: underline;
      }
      .badge-paiement {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu);
        font-size: 0.82rem;
        font-weight: 600;
      }
      .reseaux a {
        display: inline-grid;
        place-items: center;
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 50%;
        border: 1px solid var(--mpc-bordure);
        color: var(--mpc-bleu);
        transition: all 0.22s ease;
      }
      .reseaux a:hover {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
        transform: translateY(-2px);
      }
    `,
  ],
  template: `
    <div class="d-flex flex-column gap-3">
      <!-- Téléphone(s) -->
      <div class="info-item" appReveal>
        <div class="info-icone"><i class="bi bi-telephone"></i></div>
        <div class="info-texte">
          <h3>{{ CONTENT.contact.items[0].titre }}</h3>
          <p>
            <a *ngIf="telPrincipal" [href]="lienTel(telPrincipal)">{{ formater(telPrincipal) }}</a>
            <ng-container *ngIf="!telPrincipal">—</ng-container>
            <ng-container *ngIf="telSecondaire"> · {{ formater(telSecondaire) }}</ng-container>
          </p>
        </div>
      </div>

      <!-- Email -->
      <div class="info-item" appReveal>
        <div class="info-icone"><i class="bi bi-envelope"></i></div>
        <div class="info-texte">
          <h3>{{ CONTENT.contact.items[1].titre }}</h3>
          <p><a *ngIf="email" [href]="'mailto:' + email">{{ email }}</a><ng-container *ngIf="!email">—</ng-container></p>
        </div>
      </div>

      <!-- Adresse -->
      <div class="info-item" appReveal>
        <div class="info-icone"><i class="bi bi-geo-alt"></i></div>
        <div class="info-texte">
          <h3>{{ CONTENT.contact.items[2].titre }}</h3>
          <p>{{ adresse || '—' }}</p>
        </div>
      </div>

      <!-- Horaires -->
      <div class="info-item" appReveal>
        <div class="info-icone"><i class="bi bi-clock"></i></div>
        <div class="info-texte">
          <h3>{{ CONTENT.contact.items[3].titre }}</h3>
          <p>{{ horaires || '—' }}</p>
        </div>
      </div>

      <!-- Paiements -->
      <div class="info-item" appReveal>
        <div class="info-icone"><i class="bi bi-wallet2"></i></div>
        <div class="info-texte">
          <h3>Moyens de paiement</h3>
          <p class="d-flex flex-wrap gap-2 mt-1">
            <span class="badge-paiement" *ngIf="paiementOrange"><i class="bi bi-phone"></i>{{ paisementLabel('Orange Money', paiementOrange) }}</span>
            <span class="badge-paiement" *ngIf="paiementMoov"><i class="bi bi-phone"></i>{{ paisementLabel('Moov Money', paiementMoov) }}</span>
            <span class="badge-paiement" *ngIf="paiementWave"><i class="bi bi-phone"></i>{{ paisementLabel('Wave', paiementWave) }}</span>
            <span class="badge-paiement" *ngIf="cashAccepte"><i class="bi bi-cash-coin"></i>Espèces</span>
            <span class="badge-paiement" *ngIf="!afficherPaiements"><i class="bi bi-credit-card"></i>À définir</span>
          </p>
        </div>
      </div>

      <!-- Rés réseau sociaux -->
      <div class="info-item" appReveal>
        <div class="info-icone"><i class="bi bi-share"></i></div>
        <div class="info-texte">
          <h3>Nous suivre</h3>
          <div class="reseaux d-flex gap-2 mt-1">
            <a *ngIf="reseaux.facebook" [href]="reseaux.facebook" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
            <a *ngIf="reseaux.tiktok" [href]="reseaux.tiktok" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
            <a *ngIf="lienWhatsapp" [href]="lienWhatsapp" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
            <a *ngIf="reseaux.linkedin" [href]="reseaux.linkedin" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
            <ng-container *ngIf="!afficherReseaux"><span class="text-muted small">—</span></ng-container>
          </div>
        </div>
      </div>
    </div>
  `,
})
export class ContactInfoComponent {
  protected readonly CONTENT = CONTENT;

  @Input() fiche: FicheCabinet | null = null;

  protected get telPrincipal(): string | null {
    return this.fiche?.contact?.telephone ?? null;
  }
  protected get telSecondaire(): string | null {
    return this.fiche?.contact?.telephone_2 ?? null;
  }
  protected get email(): string | null {
    return this.fiche?.contact?.email ?? null;
  }
  protected get adresse(): string | null {
    return this.fiche?.contact?.adresse ?? null;
  }
  protected get horaires(): string | null {
    return this.fiche?.contact?.horaires ?? null;
  }
  protected get paiementOrange(): string | null {
    return this.fiche?.paiements?.orange_money ?? null;
  }
  protected get paiementMoov(): string | null {
    return this.fiche?.paiements?.moov_money ?? null;
  }
  protected get paiementWave(): string | null {
    return this.fiche?.paiements?.wave ?? null;
  }
  protected get cashAccepte(): boolean {
    return !!this.fiche?.paiements?.cash;
  }
  protected get afficherPaiements(): boolean {
    return !!(this.paiementOrange || this.paiementMoov || this.paiementWave || this.cashAccepte);
  }
  protected get reseaux() {
    return this.fiche?.reseaux ?? {};
  }
  protected get lienWhatsapp(): string | null {
    const scrute = this.fiche?.contact?.whatsapp || this.fiche?.contact?.telephone || null;
    if (!scrute) return this.fiche?.reseaux?.whatsapp_business ?? null;
    const format = formaterTelephoneBrut(scrute);
    return format.whatsapp ?? this.fiche?.reseaux?.whatsapp_business ?? null;
  }
  protected get afficherReseaux(): boolean {
    const reseaux = this.reseaux as Record<string, unknown>;
    return Object.values(reseaux).some((v) => !!v) || !!this.lienWhatsapp;
  }

  protected formater(numero: string): string {
    return formaterTelephoneBrut(numero).national ?? numero;
  }
  protected lienTel(numero: string): string {
    return 'tel:+' + numero.replace(/\D/g, '');
  }
  protected paisementLabel(nom: string, numero: string): string {
    return `${nom} · ${this.formater(numero)}`;
  }
}