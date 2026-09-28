import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { ContenuPublicBackoffice } from '../../models';
import { messageErreurApi, messageSuccesApi } from '../../services/messages';

interface FicheEditable {
  identite: { slogan: string; directeur: string };
  contact: { telephone: string; telephone_2: string; whatsapp: string; email: string; adresse: string; horaires: string };
  paiements: { orange_money: string; moov_money: string; wave: string; cash: boolean };
  zones: { pays: string; devise: string; localites: string };
  reseaux: { facebook: string; tiktok: string; whatsapp_business: string; linkedin: string };
}

const VIDE = (): FicheEditable => ({
  identite: { slogan: '', directeur: '' },
  contact: { telephone: '', telephone_2: '', whatsapp: '', email: '', adresse: '', horaires: '' },
  paiements: { orange_money: '', moov_money: '', wave: '', cash: false },
  zones: { pays: '', devise: '', localites: '' },
  reseaux: { facebook: '', tiktok: '', whatsapp_business: '', linkedin: '' },
});

/** Écran admin « Fiche du cabinet » : édition des données publiées sur le site public. */
@Component({
  imports: [FormsModule],
  selector: 'espace-fiche-cabinet',
  styles: [
    `
      :host {
        display: block;
      }
      .entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.1rem;
      }
      .entete h1 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .entete p {
        margin: 0.2rem 0 0;
        color: var(--mpc-texte-doux);
        font-size: 0.85rem;
      }
      .barre {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        margin-bottom: 1.1rem;
      }
      .reclamer {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--mpc-texte-doux);
      }
      .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1.1rem;
        border: none;
        border-radius: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: background 0.15s ease;
      }
      .btn-save:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-save:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .sections {
        display: grid;
        gap: 1rem;
      }
      .carte {
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        border-radius: 1rem;
        padding: 1.1rem 1.2rem;
      }
      .carte-titre {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin: 0 0 0.95rem;
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .carte-titre i {
        color: var(--mpc-primaire);
        font-size: 1.15rem;
      }
      .grille {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.85rem;
      }
      .champ {
        display: grid;
        gap: 0.3rem;
      }
      .champ > span {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
      }
      .controle {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0 0.75rem;
        height: 44px;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
      }
      .controle:focus-within {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .controle i {
        color: var(--mpc-texte-doux);
      }
      .controle input {
        flex: 1;
        min-width: 0;
        border: none;
        outline: none;
        background: transparent;
        color: var(--mpc-texte);
        font-size: 0.9rem;
      }
      textarea {
        width: 100%;
        min-height: 90px;
        padding: 0.7rem 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.9rem;
        resize: vertical;
      }
      textarea:focus {
        border-color: var(--mpc-primaire);
        outline: none;
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .case {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        height: 44px;
        padding: 0 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--mpc-texte);
        cursor: pointer;
      }
      .case input {
        width: 18px;
        height: 18px;
        accent-color: var(--mpc-primaire);
      }
      .hint {
        font-size: 0.74rem;
        color: var(--mpc-texte-doux);
        margin-top: 0.35rem;
      }
      .toast {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: 0.8rem;
        font-size: 0.86rem;
        font-weight: 600;
      }
      .toast._succes {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .toast._erreur {
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .charge {
        padding: 2rem;
        text-align: center;
        color: var(--mpc-texte-doux);
      }
      @media (max-width: 767px) {
        .entete,
        .barre {
          flex-direction: column;
          align-items: stretch;
        }
        .btn-save {
          justify-content: center;
        }
      }
    `,
  ],
  template: `
    @if (charge()) {
      <div class="charge"><i class="bi bi-arrow-repeat spin"></i> Chargement de la fiche…</div>
    } @else if (erreur()) {
      <div class="toast _erreur"><i class="bi bi-exclamation-triangle"></i>{{ erreur() }}</div>
    } @else {
      <div class="entete">
        <div>
          <h1>Fiche du cabinet</h1>
          <p>Ces informations sont affichées sur le site public (accueil, contact, pied de page).</p>
        </div>
      </div>

      @if (toast()) {
        <div class="toast" [class._succes]="succes()" [class._erreur]="!succes()">
          <i class="bi" [class.bi-check-circle]="succes()" [class.bi-exclamation-triangle]="!succes()"></i>
          {{ toast() }}
        </div>
      }

      <div class="barre">
        <span class="reclamer"><i class="bi bi-check-circle"></i> Modifications publiées immédiatement</span>
        <button type="button" class="btn-save" (click)="enregistrer()" [disabled]="sauvegarde()">
          @if (sauvegarde()) {
            <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
          } @else {
            <i class="bi bi-cloud-arrow-up"></i> Enregistrer
          }
        </button>
      </div>

      <div class="sections">
        <section class="carte">
          <h2 class="carte-titre"><i class="bi bi-building"></i> Identité</h2>
          <div class="grille">
            <label class="champ">
              <span>Slogan</span>
              <span class="controle"><input [(ngModel)]="fiche.identite.slogan" name="slogan" /></span>
            </label>
            <label class="champ">
              <span>Nom du directeur</span>
              <span class="controle"><input [(ngModel)]="fiche.identite.directeur" name="directeur" /></span>
            </label>
          </div>
        </section>

        <section class="carte">
          <h2 class="carte-titre"><i class="bi bi-telephone"></i> Contact</h2>
          <div class="grille">
            <label class="champ">
              <span>Téléphone principal</span>
              <span class="controle"><i class="bi bi-telephone-outbound"></i><input [(ngModel)]="fiche.contact.telephone" name="telephone" placeholder="+226 70 00 00 00" /></span>
            </label>
            <label class="champ">
              <span>Téléphone secondaire</span>
              <span class="controle"><i class="bi bi-telephone"></i><input [(ngModel)]="fiche.contact.telephone_2" name="telephone_2" placeholder="+226 70 00 00 00" /></span>
            </label>
            <label class="champ">
              <span>WhatsApp</span>
              <span class="controle"><i class="bi bi-whatsapp"></i><input [(ngModel)]="fiche.contact.whatsapp" name="whatsapp" placeholder="+226 70 00 00 00" /></span>
            </label>
            <label class="champ">
              <span>Adresse e-mail</span>
              <span class="controle"><i class="bi bi-envelope"></i><input [(ngModel)]="fiche.contact.email" name="email" type="email" /></span>
            </label>
            <label class="champ">
              <span>Adresse physique</span>
              <span class="controle"><i class="bi bi-geo-alt"></i><input [(ngModel)]="fiche.contact.adresse" name="adresse" /></span>
            </label>
            <label class="champ">
              <span>Horaires</span>
              <span class="controle"><i class="bi bi-clock"></i><input [(ngModel)]="fiche.contact.horaires" name="horaires" placeholder="Lun–Sam : 7h30 – 18h" /></span>
            </label>
          </div>
        </section>

        <section class="carte">
          <h2 class="carte-titre"><i class="bi bi-cash-coin"></i> Paiement mobile</h2>
          <div class="grille">
            <label class="champ">
              <span>Orange Money</span>
              <span class="controle"><input [(ngModel)]="fiche.paiements.orange_money" name="om" placeholder="70 00 00 00" /></span>
            </label>
            <label class="champ">
              <span>Moov Money</span>
              <span class="controle"><input [(ngModel)]="fiche.paiements.moov_money" name="moov" placeholder="70 00 00 00" /></span>
            </label>
            <label class="champ">
              <span>Wave</span>
              <span class="controle"><input [(ngModel)]="fiche.paiements.wave" name="wave" placeholder="70 00 00 00" /></span>
            </label>
            <label class="case">
              <input type="checkbox" [(ngModel)]="fiche.paiements.cash" name="cash" />
              Paiement en espèces accepté
            </label>
          </div>
        </section>

        <section class="carte">
          <h2 class="carte-titre"><i class="bi bi-map"></i> Zones & localités</h2>
          <div class="grille">
            <label class="champ">
              <span>Pays</span>
              <span class="controle"><input [(ngModel)]="fiche.zones.pays" name="pays" placeholder="Burkina Faso" /></span>
            </label>
            <label class="champ">
              <span>Devise</span>
              <span class="controle"><input [(ngModel)]="fiche.zones.devise" name="devise" placeholder="FCFA" /></span>
            </label>
          </div>
          <label class="champ" style="margin-top:0.85rem">
            <span>Localités desservies</span>
            <textarea [(ngModel)]="fiche.zones.localites" name="localites" placeholder="Ouagadougou&#10;Bobo-Dioulasso"></textarea>
            <span class="hint">Une localité par ligne.</span>
          </label>
        </section>

        <section class="carte">
          <h2 class="carte-titre"><i class="bi bi-share"></i> Réseaux sociaux</h2>
          <div class="grille">
            <label class="champ">
              <span>Facebook</span>
              <span class="controle"><i class="bi bi-facebook"></i><input [(ngModel)]="fiche.reseaux.facebook" name="facebook" placeholder="https://facebook.com/…" /></span>
            </label>
            <label class="champ">
              <span>TikTok</span>
              <span class="controle"><i class="bi bi-tiktok"></i><input [(ngModel)]="fiche.reseaux.tiktok" name="tiktok" placeholder="https://tiktok.com/@…" /></span>
            </label>
            <label class="champ">
              <span>WhatsApp Business</span>
              <span class="controle"><i class="bi bi-whatsapp"></i><input [(ngModel)]="fiche.reseaux.whatsapp_business" name="wa_business" placeholder="https://wa.me/226…" /></span>
            </label>
            <label class="champ">
              <span>LinkedIn</span>
              <span class="controle"><i class="bi bi-linkedin"></i><input [(ngModel)]="fiche.reseaux.linkedin" name="linkedin" placeholder="https://linkedin.com/…" /></span>
            </label>
          </div>
        </section>
      </div>
    }
  `,
})
export class FicheCabinetComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly charge = signal(true);
  protected readonly sauvegarde = signal(false);
  protected readonly erreur = signal('');
  protected readonly toast = signal('');
  protected readonly succes = signal(true);

  protected fiche: FicheEditable = VIDE();
  private theme: Record<string, unknown> = {};
  private footer: Record<string, unknown> = {};

  ngOnInit(): void {
    this.api.getContenuPublic().subscribe({
      next: (contenu) => {
        this.theme = contenu.theme ?? {};
        this.footer = contenu.footer ?? {};
        this.remplir(contenu.data?.fiche);
        this.charge.set(false);
      },
      error: (e) => {
        this.erreur.set(messageErreurApi(e));
        this.charge.set(false);
      },
    });
  }

  protected async enregistrer(): Promise<void> {
    this.sauvegarde.set(true);
    const payload: ContenuPublicBackoffice = {
      theme: this.theme,
      footer: this.footer,
      data: {
        fiche: {
          identite: { slogan: this.fiche.identite.slogan, directeur: this.fiche.identite.directeur },
          contact: this.fiche.contact,
          paiements: this.fiche.paiements,
          zones: {
            pays: this.fiche.zones.pays,
            devise: this.fiche.zones.devise,
            localites: this.fiche.zones.localites.split('\n').map((l) => l.trim()).filter(Boolean),
          },
          reseaux: this.fiche.reseaux,
        },
      },
    };
    try {
      const reponse = await firstValueFrom(this.api.putContenuPublic(payload));
      this.toast.set(messageSuccesApi(reponse, 'Fiche enregistrée et publiée.'));
      this.succes.set(true);
      if (reponse.data) this.remplir(reponse.data.data?.fiche);
    } catch (e) {
      this.toast.set(messageErreurApi(e));
      this.succes.set(false);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  private remplir(fiche?: unknown): void {
    const f = (fiche ?? {}) as {
      identite?: Partial<FicheEditable['identite']>;
      contact?: Partial<FicheEditable['contact']>;
      paiements?: Partial<FicheEditable['paiements']>;
      zones?: Partial<FicheEditable['zones']>;
      reseaux?: Partial<FicheEditable['reseaux']>;
    };
    this.fiche = VIDE();
    if (f.identite) {
      this.fiche.identite.slogan = f.identite.slogan ?? '';
      this.fiche.identite.directeur = f.identite.directeur ?? '';
    }
    if (f.contact) {
      this.fiche.contact = { ...this.fiche.contact, ...f.contact };
    }
    if (f.paiements) {
      this.fiche.paiements = { ...this.fiche.paiements, ...f.paiements };
    }
    if (f.zones) {
      this.fiche.zones.pays = f.zones.pays ?? '';
      this.fiche.zones.devise = f.zones.devise ?? '';
      this.fiche.zones.localites = Array.isArray(f.zones.localites) ? f.zones.localites.join('\n') : '';
    }
    if (f.reseaux) {
      this.fiche.reseaux = { ...this.fiche.reseaux, ...f.reseaux };
    }
  }
}