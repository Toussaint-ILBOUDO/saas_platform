import { Component, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth.service';
import { messageErreurApi } from '../../services/messages';
import { CadreAuthComponent } from './cadre-auth.component';
import { STYLES_FORMULAIRE_AUTH } from './formulaire.styles';

@Component({
  imports: [CadreAuthComponent, RouterLink, FormsModule],
  styles: STYLES_FORMULAIRE_AUTH,
  template: `
    <cadre-auth>
      <h1>Connexion</h1>
      <p class="sous-titre">Accédez à l'espace du cabinet.</p>

      @if (erreur()) {
        <div class="alerte" role="alert">
          <i class="bi bi-exclamation-triangle"></i>
          <span>{{ erreur() }}</span>
        </div>
      }

      <form class="form-auth" (ngSubmit)="seConnecter()" novalidate>
        <label class="champ">
          <span>Adresse e-mail</span>
          <span class="controle">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" [(ngModel)]="email" autocomplete="email" placeholder="vous@exemple.bf" required />
          </span>
        </label>

        <label class="champ">
          <span>Mot de passe</span>
          <span class="controle">
            <i class="bi bi-lock"></i>
            <input type="password" name="motDePasse" [(ngModel)]="motDePasse" autocomplete="current-password" placeholder="••••••••" required />
          </span>
        </label>

        <div class="liens">
          <a routerLink="/mot-de-passe-oublie">Mot de passe oublié&nbsp;?</a>
        </div>

        <button type="submit" class="bouton" [disabled]="charge()">
          @if (charge()) {
            <i class="bi bi-arrow-repeat spin"></i>
            Connexion en cours…
          } @else {
            <i class="bi bi-box-arrow-in-right"></i>
            Se connecter
          }
        </button>
      </form>
    </cadre-auth>
  `,
})
export class ConnexionComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected email = '';
  protected motDePasse = '';
  protected readonly charge = signal(false);
  protected readonly erreur = signal('');

  protected async seConnecter(): Promise<void> {
    this.erreur.set('');
    if (!this.email.trim() || !this.motDePasse) {
      this.erreur.set('Renseignez votre e-mail et votre mot de passe.');
      return;
    }
    this.charge.set(true);
    try {
      await this.auth.connexion(this.email.trim(), this.motDePasse);
      const roles = this.auth.utilisateur()?.roles ?? [];
      if (roles.length > 1) {
        await this.router.navigate(['/espace', 'choix-role']);
      } else {
        await this.router.navigate(['/espace']);
      }
    } catch (e) {
      this.erreur.set(messageErreurApi(e));
      this.motDePasse = '';
    } finally {
      this.charge.set(false);
    }
  }
}