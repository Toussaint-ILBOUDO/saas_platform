import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { messageErreurApi } from '../services/messages';
import { DocumentBibliotheque, MOTIFS_SIGNALEMENT } from '../models';

/**
 * Lecteur intégré d'un document public.
 *
 * PDF et images : iframe / img pointant vers `/api/public/documents/{slug}/fichier`
 * (Content-Disposition inline, disque privé). Zoom, pages et plein écran sont
 * ceux du lecteur navigateur — aucune dépendance supplémentaire. Formats non
 * affichables : panneau de téléchargement seul.
 *
 * « Signaler ce document » : formulaire ouvert aux visiteurs anonymes
 * (motif codifié + description + précisions + contact facultatif).
 */
@Component({
  selector: 'app-document-lecteur',
  imports: [CommonModule, RouterLink, FormsModule],
  styles: [
    `
      :host {
        display: block;
        min-height: 60vh;
      }
      .chrome {
        position: sticky;
        top: 4.2rem;
        z-index: 40;
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--mpc-bordure);
        padding: 0.65rem 0;
      }
      .chrome-inner {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        flex-wrap: wrap;
      }
      .retour {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--mpc-bleu);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
      }
      .retour:hover {
        color: var(--mpc-primaire);
      }
      .titre {
        flex: 1;
        min-width: 0;
        font-weight: 700;
        color: var(--mpc-bleu);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .actions {
        display: flex;
        gap: 0.45rem;
        align-items: center;
      }
      .btn-icone {
        width: 2.4rem;
        height: 2.4rem;
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        border-radius: 10px;
        display: grid;
        place-items: center;
        cursor: pointer;
        text-decoration: none;
      }
      .btn-icone:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .btn-icone._signaler {
        color: var(--mpc-danger, #b3261e);
      }
      .btn-icone._signaler:hover {
        border-color: var(--mpc-danger, #b3261e);
      }
      .lecture {
        background: var(--mpc-surface-teintee, #f4f6f9);
        min-height: 65vh;
        display: flex;
        justify-content: center;
        padding: 1rem 0 2rem;
      }
      .cadre {
        width: min(920px, 100%);
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(18, 48, 94, 0.12);
        overflow: hidden;
      }
      .cadre iframe,
      .cadre img {
        display: block;
        width: 100%;
        height: 78vh;
        border: 0;
        object-fit: contain;
        background: #f4f6f9;
      }
      .panneau-telechargement {
        max-width: 480px;
        margin: 4rem auto;
        text-align: center;
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 16px;
        padding: 2.5rem 1.5rem;
      }
      .panneau-telechargement i {
        font-size: 2.8rem;
        color: var(--mpc-primaire);
        display: block;
        margin-bottom: 1rem;
      }
      .btn-telecharger {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--mpc-bleu);
        color: #fff;
        border-radius: 999px;
        padding: 0.7rem 1.6rem;
        font-weight: 700;
        text-decoration: none;
        margin-top: 1.2rem;
      }
      .btn-telecharger:hover {
        background: var(--mpc-primaire);
      }
      .meta {
        max-width: 920px;
        margin: 0 auto 2rem;
        color: var(--mpc-texte-doux);
        font-size: 0.88rem;
        display: flex;
        gap: 1.2rem;
        flex-wrap: wrap;
        align-items: center;
      }
      .meta i {
        color: var(--mpc-primaire);
      }
      .lien-signaler {
        margin-left: auto;
        background: none;
        border: 1px solid var(--mpc-bordure);
        color: var(--mpc-danger, #b3261e);
        border-radius: 999px;
        padding: 0.35rem 0.9rem;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
      }
      .lien-signaler:hover {
        border-color: var(--mpc-danger, #b3261e);
        background: #fdecea;
      }
      .erreur {
        max-width: 520px;
        margin: 3rem auto;
        text-align: center;
        color: #b3261e;
        background: #fdecea;
        border-radius: 12px;
        padding: 1.2rem;
      }
      .resume {
        max-width: 920px;
        margin: 0 auto 3rem;
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 16px;
        padding: 1.3rem 1.5rem;
        color: var(--mpc-texte);
        font-size: 0.95rem;
        line-height: 1.7;
      }
      .resume h2 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        margin-bottom: 0.5rem;
      }
      /* Fenêtre signalement */
      .fenetre {
        position: fixed;
        inset: 0;
        z-index: 80;
        background: rgba(18, 48, 94, 0.45);
        display: grid;
        place-items: center;
        padding: 1rem;
      }
      .panneau-signalement {
        width: min(480px, 100%);
        background: #fff;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 20px 50px rgba(18, 48, 94, 0.25);
        max-height: 90vh;
        overflow: auto;
      }
      .panneau-signalement h2 {
        margin: 0 0 0.3rem;
        font-size: 1.1rem;
        color: var(--mpc-bleu);
      }
      .panneau-signalement .aide {
        margin: 0 0 1rem;
        font-size: 0.85rem;
        color: var(--mpc-texte-doux);
      }
      .champ {
        display: block;
        margin-bottom: 0.85rem;
      }
      .champ span {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        margin-bottom: 0.3rem;
      }
      .champ span em {
        font-style: normal;
        color: var(--mpc-danger, #b3261e);
      }
      .champ select,
      .champ input,
      .champ textarea {
        width: 100%;
        border: 1px solid var(--mpc-bordure);
        border-radius: 10px;
        padding: 0.6rem 0.75rem;
        font: inherit;
        color: var(--mpc-texte);
        background: #fff;
      }
      .champ textarea {
        min-height: 90px;
        resize: vertical;
      }
      .champ select:focus,
      .champ input:focus,
      .champ textarea:focus {
        outline: 2px solid var(--mpc-primaire);
        outline-offset: 1px;
        border-color: var(--mpc-primaire);
      }
      .champ-erreur {
        color: var(--mpc-danger, #b3261e);
        font-size: 0.8rem;
        margin: -0.5rem 0 0.6rem;
      }
      .actions-fenetre {
        display: flex;
        gap: 0.6rem;
        justify-content: flex-end;
        margin-top: 1rem;
      }
      .btn-annuler,
      .btn-envoyer {
        border-radius: 999px;
        padding: 0.55rem 1.2rem;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        border: 1px solid var(--mpc-bordure);
        background: #fff;
        color: var(--mpc-bleu);
      }
      .btn-envoyer {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .btn-envoyer:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .confirmation {
        text-align: center;
        padding: 1rem 0 0.2rem;
      }
      .confirmation i {
        font-size: 2.4rem;
        color: var(--mpc-succes, #15803d);
        display: block;
        margin-bottom: 0.6rem;
      }
      .confirmation p {
        margin: 0;
        color: var(--mpc-texte);
        font-size: 0.95rem;
        line-height: 1.55;
      }
    `,
  ],
  template: `
    <header class="chrome" *ngIf="document()">
      <div class="container chrome-inner">
        <a routerLink="/bibliotheque" class="retour">
          <i class="bi bi-arrow-left"></i> Bibliothèque
        </a>
        <span class="titre">{{ document()!.titre }}</span>
        <div class="actions">
          <a class="btn-icone" [href]="urlTelechargement" [attr.aria-label]="'Télécharger'">
            <i class="bi bi-download"></i>
          </a>
          <button type="button" class="btn-icone" (click)="pleinEcran()" [attr.aria-label]="'Plein écran'">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>
      </div>
    </header>

    <div class="lecture">
      <p class="erreur" *ngIf="erreur()">{{ erreur() }}</p>

      <div class="panneau-telechargement" *ngIf="!erreur() && document() && !peutAfficher()">
        <i class="bi bi-file-earmark-arrow-down"></i>
        <p>Ce document n'est pas prévisualisable en ligne.</p>
        <p style="color:var(--mpc-texte-doux);font-size:.9rem">
          Téléchargez-le pour le consulter avec une application compatible.
        </p>
        <a class="btn-telecharger" [href]="urlTelechargement">
          <i class="bi bi-download"></i> Télécharger le fichier
        </a>
      </div>

      <div class="cadre" *ngIf="!erreur() && document() && peutAfficher()" #cadre>
        <iframe
          *ngIf="estPdf()"
          [src]="urlFichier"
          [title]="document()!.titre"
          type="application/pdf"
        ></iframe>
        <img
          *ngIf="!estPdf()"
          [src]="urlFichier"
          [alt]="document()!.titre"
        />
      </div>
    </div>

    <div class="container" *ngIf="document()">
      <div class="meta">
        <span *ngIf="document()!.type_document">
          <i class="bi bi-tag"></i> {{ document()!.type_document }}
        </span>
        <span *ngIf="document()!.matiere">
          <i class="bi bi-journal"></i> {{ document()!.matiere }}
        </span>
        <span *ngIf="document()!.classe">
          <i class="bi bi-mortarboard"></i> {{ document()!.classe }}
        </span>
        <span *ngIf="document()!.periode">
          <i class="bi bi-calendar3"></i> {{ document()!.periode }}
        </span>
        <span *ngIf="document()!.nb_vues">
          <i class="bi bi-eye"></i> {{ document()!.nb_vues }} vues
        </span>
        <span *ngIf="document()!.nb_telechargements">
          <i class="bi bi-download"></i> {{ document()!.nb_telechargements }} téléchargements
        </span>
        <span *ngIf="document()!.nb_aimes">
          <i class="bi bi-hand-thumbs-up"></i> {{ document()!.nb_aimes }}
        </span>
        <button type="button" class="lien-signaler" (click)="ouvrirSignalement()">
          <i class="bi bi-flag"></i> Signaler ce document
        </button>
      </div>

      <section class="resume" *ngIf="document()!.resume || document()!.description">
        <h2>À propos de ce document</h2>
        <p>{{ document()!.description || document()!.resume }}</p>
      </section>
    </div>

    <!-- Fenêtre signalement -->
    <div class="fenetre" *ngIf="signalementOuvert()" (click)="fermerSignalement()">
      <div class="panneau-signalement" (click)="$event.stopPropagation()">
        <div *ngIf="!confirmation()" class="confirmation-fallback">
          <h2>Signaler ce document</h2>
          <p class="aide">
            Décrivez le problème. Votre signalement sera examiné par un
            administrateur. Un signalement ne retire pas automatiquement le document.
          </p>

          <label class="champ">
            <span>Motif <em>*</em></span>
            <select [(ngModel)]="formulaire.motif" name="motif">
              <option value="">— Choisir un motif —</option>
              <option *ngFor="let m of motifs" [value]="m.valeur">{{ m.libelle }}</option>
            </select>
          </label>
          <p class="champ-erreur" *ngIf="erreurChamp('motif')">{{ erreurChamp('motif') }}</p>

          <label class="champ">
            <span>Description du problème</span>
            <textarea
              [(ngModel)]="formulaire.description"
              name="description"
              maxlength="2000"
              placeholder="Expliquez brièvement ce qui ne va pas…"
            ></textarea>
          </label>
          <p class="champ-erreur" *ngIf="erreurChamp('description')">{{ erreurChamp('description') }}</p>

          <label class="champ">
            <span>Précisions complémentaires <small style="font-weight:400;color:var(--mpc-texte-doux)">(facultatif)</small></span>
            <textarea
              [(ngModel)]="formulaire.precisions"
              name="precisions"
              maxlength="2000"
              placeholder="Page concernée, format attendu, URL…"
            ></textarea>
          </label>
          <p class="champ-erreur" *ngIf="erreurChamp('precisions')">{{ erreurChamp('precisions') }}</p>

          <label class="champ">
            <span>E-mail de contact <small style="font-weight:400;color:var(--mpc-texte-doux)">(facultatif, si anonyme)</small></span>
            <input
              type="email"
              [(ngModel)]="formulaire.email_contact"
              name="email_contact"
              maxlength="190"
              placeholder="vous@exemple.com"
            />
          </label>
          <p class="champ-erreur" *ngIf="erreurChamp('email_contact')">{{ erreurChamp('email_contact') }}</p>

          <p class="champ-erreur" *ngIf="erreurFormulaire()">{{ erreurFormulaire() }}</p>

          <div class="actions-fenetre">
            <button type="button" class="btn-annuler" (click)="fermerSignalement()">Annuler</button>
            <button type="button" class="btn-envoyer" [disabled]="envoiEnCours()" (click)="envoyerSignalement()">
              <span *ngIf="!envoiEnCours()">Envoyer le signalement</span>
              <span *ngIf="envoiEnCours()">Envoi…</span>
            </button>
          </div>
        </div>

        <div *ngIf="confirmation()" class="confirmation">
          <i class="bi bi-check-circle-fill"></i>
          <p>{{ confirmation() }}</p>
          <div class="actions-fenetre" style="justify-content:center">
            <button type="button" class="btn-envoyer" (click)="fermerSignalement()">Fermer</button>
          </div>
        </div>
      </div>
    </div>
  `,
})
export class DocumentLecteurComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);
  private readonly route = inject(ActivatedRoute);
  private readonly sanitizer = inject(DomSanitizer);

  protected readonly document = signal<DocumentBibliotheque | null>(null);
  protected readonly erreur = signal<string | null>(null);

  protected readonly motifs = MOTIFS_SIGNALEMENT;

  protected readonly signalementOuvert = signal(false);
  protected readonly envoiEnCours = signal(false);
  protected readonly confirmation = signal<string | null>(null);
  protected readonly erreursChamps = signal<Record<string, string>>({});
  protected readonly erreurGlobale = signal<string | null>(null);

  protected readonly formulaire = {
    motif: '',
    description: '',
    precisions: '',
    email_contact: '',
  };

  protected urlFichier: SafeResourceUrl = '';
  protected urlTelechargement = '';

  ngOnInit(): void {
    const slug = this.route.snapshot.paramMap.get('slug');
    if (!slug) {
      this.erreur.set('Document introuvable.');
      return;
    }
    this.urlFichier = this.sanitizer.bypassSecurityTrustResourceUrl(this.api.urlFichierDocument(slug));
    this.urlTelechargement = this.api.urlTelechargementDocument(slug);

    this.api.getDocumentPublic(slug).subscribe({
      next: (doc) => {
        this.document.set(doc);
        this.seo.definir(`${doc.titre} | Bibliothèque`, doc.resume ?? '');
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected estPdf(): boolean {
    const ext = this.document()?.fichier?.extension?.toLowerCase() ?? '';
    return ext === 'pdf' || (this.document()?.fichier?.mime ?? '').includes('pdf');
  }

  protected peutAfficher(): boolean {
    const doc = this.document();
    if (!doc) return false;
    const ext = doc.fichier?.extension?.toLowerCase() ?? '';
    const mime = doc.fichier?.mime ?? '';
    if (this.estPdf()) return true;
    return mime.startsWith('image/') || ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(ext);
  }

  protected pleinEcran(): void {
    const iframe = document.querySelector<HTMLIFrameElement>('.cadre iframe, .cadre img') as
      | HTMLIFrameElement
      | HTMLImageElement
      | null;
    const cible = (iframe as HTMLElement & { requestFullscreen?: () => Promise<void> })?.closest?.('.cadre');
    if (cible?.requestFullscreen) {
      void cible.requestFullscreen();
    }
  }

  /* ---------- Signalement ---------- */

  protected ouvrirSignalement(): void {
    this.formulaire.motif = '';
    this.formulaire.description = '';
    this.formulaire.precisions = '';
    this.formulaire.email_contact = '';
    this.erreursChamps.set({});
    this.erreurGlobale.set(null);
    this.confirmation.set(null);
    this.signalementOuvert.set(true);
  }

  protected fermerSignalement(): void {
    this.signalementOuvert.set(false);
    this.confirmation.set(null);
  }

  protected erreurChamp(cle: string): string | null {
    return this.erreursChamps()[cle] ?? null;
  }

  protected erreurFormulaire(): string | null {
    return this.erreurGlobale();
  }

  protected async envoyerSignalement(): Promise<void> {
    const slug = this.document()?.slug;
    if (!slug) return;

    const erreurs: Record<string, string> = {};
    if (!this.formulaire.motif) {
      erreurs['motif'] = 'Le motif est obligatoire.';
    }
    if (this.formulaire.description.length > 2000) {
      erreurs['description'] = 'La description ne doit pas dépasser 2000 caractères.';
    }
    if (this.formulaire.precisions.length > 2000) {
      erreurs['precisions'] = 'Les précisions ne doivent pas dépasser 2000 caractères.';
    }
    if (this.formulaire.email_contact && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.formulaire.email_contact)) {
      erreurs['email_contact'] = 'Adresse e-mail invalide.';
    }
    this.erreursChamps.set(erreurs);
    this.erreurGlobale.set(null);
    if (Object.keys(erreurs).length > 0) return;

    this.envoiEnCours.set(true);
    try {
      const r = await this.api
        .signalerDocumentPublic(slug, {
          motif: this.formulaire.motif,
          description: this.formulaire.description || null,
          precisions: this.formulaire.precisions || null,
          email_contact: this.formulaire.email_contact || null,
        })
        .toPromise();
      this.confirmation.set(r?.message ?? 'Signalement enregistré. Merci.');
    } catch (e) {
      this.erreurGlobale.set(messageErreurApi(e));
    } finally {
      this.envoiEnCours.set(false);
    }
  }
}
