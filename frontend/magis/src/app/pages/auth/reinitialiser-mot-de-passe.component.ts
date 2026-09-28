import { Component, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import { CadreAuthComponent } from './cadre-auth.component';
import { STYLES_FORMULAIRE_AUTH } from './formulaire.styles';

@Component({
  imports: [CadreAuthComponent, RouterLink, FormsModule],
  styles: STYLES_FORMULAIRE_AUTH,
  template: `
    <cadre-auth>
      <h1>Nouveau mot de passe</h1>
      <p class="sous-titre">Choisissez un mot de passe sécurisé d'au moins 8 caractères.</p>

      @if (erreur()) {
        <div class="alerte" role="alert">
          <i class="bi bi-exclamation-triangle"></i>
          <span>{{ erreur() }}</span>
        </div>
      }

      <form class="form-auth" (ngSubmit)="reinitialiser()" novalidate>
        <label class="champ">
          <span>Nouveau mot de passe</span>
          <span class="controle">
            <i class="bi bi-lock"></i>
            <input type="password" name="motDePasse" [(ngModel)]="motDePasse" autocomplete="new-password" placeholder="••••••••" required />
          </span>
        </label>

        <label class="champ">
          <span>Confirmation</span>
          <span class="controle">
            <i class="bi bi-lock-fill"></i>
            <input type="password" name="confirmation" [(ngModel)]="confirmation" autocomplete="new-password" placeholder="••••••••" required />
          </span>
        </label>

        <button type="submit" class="bouton" [disabled]="charge()">
          @if (charge()) {
            <i class="bi bi-arrow-repeat spin"></i>
            Enregistrement…
          } @else {
            <i class="bi bi-check2"></i>
            Réinitialiser mon mot de passe
          }
        </button>
      </form>

      <p class="secours">Vous changez d'avis&nbsp;? <a routerLink="/connexion">Connexion</a></p>
    </cadre-auth>
  `,
})
export class ReinitialiserMotDePasseComponent {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  protected motDePasse = '';
  protected confirmation = '';
  protected readonly charge = signal(false);
  protected readonly erreur = signal('');

  private readonly email = new URLSearchParams(window.location.search).get('email') ?? '';
  private readonly token = new URLSearchParams(window.location.search).get('token') ?? '';

  protected async reinitialiser(): Promise<void> {
    this.erreur.set('');
    if (this.motDePasse.length < 8) {
      this.erreur.set('Le mot de passe doit contenir au moins 8 caractères.');
      return;
    }
    if (this.motDePasse !== this.confirmation) {
      this.erreur.set('Les deux mots de passe ne correspondent pas.');
      return;
    }
    if (!this.email || !this.token) {
      this.erreur.set('Le lien de réinitialisation est invalide ou expiré.');
      return;
    }
    this.charge.set(true);
    try {
      await this.api.reinitialiserMotDePasse(this.email, this.token, this.motDePasse).toPromise();
      await this.router.navigate(['/connexion']);
    } catch (e) {
      this.erreur.set(messageErreurApi(e));
    } finally {
      this.charge.set(false);
    }
  }
}