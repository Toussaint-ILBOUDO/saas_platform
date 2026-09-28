import { Component, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { AuthService, libelleRole } from '../../services/auth.service';
import { messageErreurApi } from '../../services/messages';

/** Écran « Mon profil » : informations du compte, changement de mot de passe, déconnexion. */
@Component({
  imports: [FormsModule],
  selector: 'espace-profil',
  styles: [
    `
      :host {
        display: block;
        max-width: 640px;
      }
      .carte {
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        border-radius: 1rem;
        padding: 1.3rem 1.4rem;
        margin-bottom: 1rem;
      }
      .identite {
        display: flex;
        align-items: center;
        gap: 1rem;
      }
      .avatar {
        flex: 0 0 auto;
        width: 64px;
        height: 64px;
        display: grid;
        place-items: center;
        border-radius: 1.1rem;
        font-weight: 800;
        font-size: 1.3rem;
        color: #fff;
        background: var(--mpc-bleu);
      }
      .identite-texte b {
        display: block;
        font-size: 1.1rem;
        color: var(--mpc-bleu);
      }
      .identite-texte small {
        color: var(--mpc-texte-doux);
        font-size: 0.85rem;
      }
      .roles {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
      }
      .badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.25rem 0.6rem;
        border-radius: 999px;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
      }
      h2 {
        margin: 0 0 1rem;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .form {
        display: grid;
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
      .champ input {
        padding: 0.6rem 0.75rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.9rem;
        outline: none;
      }
      .champ input:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .btn-primaire {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        padding: 0.7rem 1.1rem;
        border: none;
        border-radius: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
      }
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-primaire:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .btn-neutral {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.7rem 1.1rem;
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.8rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
      }
      .btn-neutral._danger:hover {
        border-color: var(--mpc-danger);
        color: var(--mpc-danger);
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
      .ligne {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
      }
    `,
  ],
  template: `
    <section class="carte">
      <div class="identite">
        <span class="avatar">{{ auth.initiales() }}</span>
        <div class="identite-texte">
          <b>{{ auth.nomComplet() }}</b>
          <small>{{ auth.utilisateur()?.email }}</small>
          <div class="roles">
            @for (r of auth.utilisateur()?.roles ?? []; track r) {
              <span class="badge">{{ roleLibelle(r) }}</span>
            }
          </div>
        </div>
      </div>
    </section>

    @if (toast()) {
      <div class="toast" [class._succes]="succes()" [class._erreur]="!succes()">
        <i class="bi" [class.bi-check-circle]="succes()" [class.bi-exclamation-triangle]="!succes()"></i>
        {{ toast() }}
      </div>
    }

    <section class="carte">
      <h2>Changer mon mot de passe</h2>
      <div class="form">
        <label class="champ">
          <span>Mot de passe actuel</span>
          <input type="password" [(ngModel)]="actuel" name="actuel" autocomplete="current-password" />
        </label>
        <label class="champ">
          <span>Nouveau mot de passe <small>(8 caractères minimum)</small></span>
          <input type="password" [(ngModel)]="nouveau" name="nouveau" autocomplete="new-password" />
        </label>
        <label class="champ">
          <span>Confirmation</span>
          <input type="password" [(ngModel)]="confirmation" name="confirmation" autocomplete="new-password" />
        </label>
        <div>
          <button class="btn-primaire" type="button" (click)="changerMotDePasse()" [disabled]="sauvegarde()">
            @if (sauvegarde()) {
              <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
            } @else {
              <i class="bi bi-key"></i> Mettre à jour le mot de passe
            }
          </button>
        </div>
      </div>
    </section>

    <section class="carte">
      <div class="ligne">
        <div>
          <b style="display:block;color:var(--mpc-bleu)">Se déconnecter</b>
          <small style="color:var(--mpc-texte-doux)">Fermer la session sur cet appareil.</small>
        </div>
        <button class="btn-neutral _danger" type="button" (click)="deconnexion()"><i class="bi bi-box-arrow-right"></i> Déconnexion</button>
      </div>
    </section>
  `,
})
export class ProfilComponent {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  protected readonly auth = inject(AuthService);
  protected readonly roleLibelle = libelleRole;

  protected actuel = '';
  protected nouveau = '';
  protected confirmation = '';
  protected readonly sauvegarde = signal(false);
  protected readonly toast = signal('');
  protected readonly succes = signal(true);

  protected async changerMotDePasse(): Promise<void> {
    this.toast.set('');
    if (!this.actuel || this.nouveau.length < 8) {
      this.toast.set('Vérifiez votre mot de passe actuel et la longueur du nouveau (8 caractères minimum).');
      this.succes.set(false);
      return;
    }
    if (this.nouveau !== this.confirmation) {
      this.toast.set('Les deux mots de passe ne correspondent pas.');
      this.succes.set(false);
      return;
    }
    this.sauvegarde.set(true);
    try {
      await this.api.changerMotDePasse(this.actuel, this.nouveau).toPromise();
      this.toast.set('Mot de passe mis à jour avec succès.');
      this.succes.set(true);
      this.actuel = '';
      this.nouveau = '';
      this.confirmation = '';
    } catch (e) {
      this.toast.set(messageErreurApi(e));
      this.succes.set(false);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async deconnexion(): Promise<void> {
    await this.auth.deconnexion();
    await this.router.navigateByUrl('/connexion');
  }
}