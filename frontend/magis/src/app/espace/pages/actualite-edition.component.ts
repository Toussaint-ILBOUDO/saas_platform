import { Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';

/** Écran admin « Actualités » : création / modification (multipart, fichiers). */
@Component({
  imports: [FormsModule, RouterLink],
  selector: 'espace-actualite-edition',
  styles: [
    `
      :host {
        display: block;
        max-width: 860px;
      }
      .entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.1rem;
        flex-wrap: wrap;
      }
      .entete h1 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .retour {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--mpc-texte-doux);
        font-size: 0.84rem;
        font-weight: 600;
        text-decoration: none;
      }
      .retour:hover {
        color: var(--mpc-primaire);
      }
      .carte {
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        border-radius: 1rem;
        padding: 1.2rem;
        margin-bottom: 1rem;
      }
      .grille {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
      .controle input,
      .controle select {
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
        min-height: 150px;
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
      .fichiers {
        display: grid;
        gap: 0.9rem;
      }
      .debut {
        border-radius: 0.75rem;
        border: 1px dashed var(--mpc-separateur);
        background: var(--mpc-fond);
      }
      .apercu {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        background: var(--mpc-abandon);
        font-size: 0.8rem;
        color: var(--mpc-texte);
      }
      .apercu i {
        color: var(--mpc-primaire);
        font-size: 1.1rem;
      }
      .apercu img {
        width: 70px;
        height: 48px;
        object-fit: cover;
        border-radius: 0.5rem;
      }
      .pied {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.8rem;
        flex-wrap: wrap;
        margin-bottom: 0.5rem;
      }
      .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1.1rem;
        border-radius: 0.8rem;
        font-weight: 700;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
      }
      .btn-primaire {
        background: var(--mpc-primaire);
        color: #fff;
      }
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-primaire:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .btn-neutral {
        background: var(--mpc-abandon);
        color: var(--mpc-texte);
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
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .spinner {
        color: var(--mpc-texte-doux);
        padding: 2rem;
        text-align: center;
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <a class="retour" routerLink="/espace/actualites"><i class="bi bi-arrow-left"></i> Retour aux actualités</a>
        <h1 style="margin-top:0.4rem">{{ id() ? 'Modifier l’actualité' : 'Nouvelle actualité' }}</h1>
      </div>
    </div>

    @if (toast()) {
      <div class="toast"><i class="bi bi-exclamation-triangle"></i>{{ toast() }}</div>
    }

    @if (charge()) {
      <div class="spinner"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else {
      <section class="carte">
        <div class="grille">
          <label class="champ">
            <span>Titre *</span>
            <span class="controle"><input [(ngModel)]="titre" name="titre" required /></span>
          </label>
          <label class="champ">
            <span>Slug (laissez vide pour l’auto-génération)</span>
            <span class="controle"><input [(ngModel)]="slug" name="slug" placeholder="ex. rentree-2026" /></span>
          </label>
        </div>

        <label class="champ" style="margin-top:0.85rem">
          <span>Résumé</span>
          <textarea [(ngModel)]="resume" name="resume" style="min-height:70px" placeholder="2 à 3 phrases accrocheuses"></textarea>
        </label>

        <label class="champ" style="margin-top:0.85rem">
          <span>Contenu <small>(texte long, sauts de ligne autorisés)</small></span>
          <textarea [(ngModel)]="contenu" name="contenu"></textarea>
        </label>
      </section>

      <section class="carte">
        <div class="grille">
          <label class="champ">
            <span>Vidéo (URL YouTube)</span>
            <span class="controle"><i class="bi bi-play-btn"></i><input [(ngModel)]="videoUrl" name="videoUrl" placeholder="https://youtube.com/…" /></span>
          </label>
          <label class="champ">
            <span>Lien externe</span>
            <span class="controle"><i class="bi bi-link-45deg"></i><input [(ngModel)]="lienExterne" name="lienExterne" placeholder="https://…" /></span>
          </label>
          <label class="champ">
            <span>Statut</span>
            <span class="controle">
              <i class="bi bi-toggle-on"></i>
              <select [(ngModel)]="statut" name="statut">
                <option value="publie">Publiée</option>
                <option value="brouillon">Brouillon</option>
              </select>
            </span>
          </label>
          <label class="case">
            <input type="checkbox" [(ngModel)]="isActive" name="isActive" />
            Visible sur le site
          </label>
        </div>
      </section>

      <section class="carte">
        <h2 style="margin:0 0 0.9rem;font-size:0.95rem;font-weight:800;color:var(--mpc-bleu)">Fichiers</h2>
        <div class="fichiers">
          <label class="champ">
            <span>Image principale <small>(JPG/PNG/WebP)</small></span>
            <input class="debut" type="file" name="imagePrincipale" accept="image/*" (change)="imageChoisie($event)" />
          </label>
          @if (maj() && imageUrlExistante) {
            <div class="apercu">
              <img [src]="imageUrlExistante" alt="" />
              <span>Image actuellement publiée. Choisissez un fichier pour la remplacer.</span>
            </div>
          }
          @if (imageFichier) {
            <div class="apercu"><i class="bi bi-image"></i><span>{{ imageFichier.name }}</span></div>
          }

          <label class="champ">
            <span>Galerie <small>(plusieurs images optionnelles)</small></span>
            <input class="debut" type="file" name="galerie" accept="image/*" multiple (change)="galerieChoisie($event)" />
          </label>
          @if (galerieFichiers().length) {
            <div class="apercu"><i class="bi bi-collection"></i><span>{{ galerieFichiers().length }} image(s) sélectionnée(s)</span></div>
          }

          <label class="champ">
            <span>Document joint <small>(PDF — brochures, règlement intérieur…)</small></span>
            <input class="debut" type="file" name="document" accept="application/pdf" (change)="documentChoisi($event)" />
          </label>
          @if (documentFichier) {
            <div class="apercu"><i class="bi bi-file-earmark-pdf"></i><span>{{ documentFichier.name }}</span></div>
          }
        </div>
      </section>

      <div class="pied">
        <a class="btn btn-neutral" routerLink="/espace/actualites">Annuler</a>
        <button class="btn btn-primaire" type="button" (click)="enregistrer()" [disabled]="sauvegarde()">
          @if (sauvegarde()) {
            <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
          } @else {
            <i class="bi bi-check2"></i> {{ id() ? 'Enregistrer les modifications' : 'Créer l’actualité' }}
          }
        </button>
      </div>
    }
  `,
})
export class ActualiteEditionComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly id = signal<number | null>(null);
  protected readonly charge = signal(true);
  protected readonly sauvegarde = signal(false);
  protected readonly toast = signal('');
  protected readonly galerieFichiers = signal<File[]>([]);

  protected titre = '';
  protected slug = '';
  protected resume = '';
  protected contenu = '';
  protected videoUrl = '';
  protected lienExterne = '';
  protected statut = 'publie';
  protected isActive = true;
  protected imageUrlExistante = '';
  protected imageFichier: File | null = null;
  protected documentFichier: File | null = null;

  protected readonly maj = this.id.asReadonly();

  ngOnInit(): void {
    const param = this.route.snapshot.paramMap.get('id');
    if (!param) {
      this.charge.set(false);
      return;
    }
    this.id.set(Number(param));
    this.api.getActualiteAdmin(this.id()!).subscribe({
      next: (a) => {
        this.titre = a.titre ?? '';
        this.slug = a.slug ?? '';
        this.resume = a.resume ?? '';
        this.contenu = a.contenu ?? '';
        this.videoUrl = a.video_url ?? '';
        this.lienExterne = a.lien_externe ?? '';
        this.statut = a.est_publiee ? 'publie' : 'brouillon';
        this.isActive = a.is_active ?? true;
        this.imageUrlExistante = a.image_url ?? '';
        this.charge.set(false);
      },
      error: (e) => {
        this.toast.set(messageErreurApi(e));
        this.charge.set(false);
      },
    });
  }

  protected imageChoisie(evenement: Event): void {
    this.imageFichier = this.premierFichier(evenement);
  }

  protected galerieChoisie(evenement: Event): void {
    const cible = evenement.target as HTMLInputElement;
    this.galerieFichiers.set(Array.from(cible.files ?? []));
  }

  protected documentChoisi(evenement: Event): void {
    this.documentFichier = this.premierFichier(evenement);
  }

  protected async enregistrer(): Promise<void> {
    this.toast.set('');
    if (!this.titre.trim()) {
      this.toast.set('Le titre est obligatoire.');
      return;
    }
    this.sauvegarde.set(true);
    const form = new FormData();
    form.set('titre', this.titre.trim());
    if (this.slug.trim()) form.set('slug', this.slug.trim());
    if (this.resume.trim()) form.set('resume', this.resume.trim());
    if (this.contenu.trim()) form.set('contenu', this.contenu);
    if (this.videoUrl.trim()) form.set('video_url', this.videoUrl.trim());
    if (this.lienExterne.trim()) form.set('lien_externe', this.lienExterne.trim());
    form.set('statut', this.statut);
    form.set('is_active', this.isActive ? '1' : '0');
    if (this.imageFichier) form.append('image_principale', this.imageFichier);
    for (const f of this.galerieFichiers()) form.append('galerie[]', f);
    if (this.documentFichier) form.append('document', this.documentFichier);

    try {
      if (this.id()) {
        await this.api.majActualite(this.id()!, form).toPromise();
      } else {
        await this.api.creerActualite(form).toPromise();
      }
      await this.router.navigate(['/espace', 'actualites']);
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    } finally {
      this.sauvegarde.set(false);
    }
  }

  private premierFichier(evenement: Event): File | null {
    const cible = evenement.target as HTMLInputElement;
    return cible.files?.[0] ?? null;
  }
}