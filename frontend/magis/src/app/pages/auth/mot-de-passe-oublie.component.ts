import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { messageErreurApi, messageSuccesApi } from '../../services/messages';
import { CadreAuthComponent } from './cadre-auth.component';
import { STYLES_FORMULAIRE_AUTH } from './formulaire.styles';

@Component({
  imports: [CadreAuthComponent, RouterLink, FormsModule],
  styles: STYLES_FORMULAIRE_AUTH,
  template: `
    <cadre-auth>
      <h1>Mot de passe oublié</h1>
      <p class="sous-titre">
        Indiquez votre adresse e-mail : nous vous envoyons un lien pour réinitialiser votre mot de passe.
      </p>

      @if (erreur()) {
        <div class="alerte" role="alert">
          <i class="bi bi-exclamation-triangle"></i>
          <span>{{ erreur() }}</span>
        </div>
      }
      @if (fait()) {
        <div class="info" role="status">
          <i class="bi bi-check-circle"></i>
          <span>{{ fait() }}</span>
        </div>
      }

      <form class="form-auth" (ngSubmit)="envoyer()" novalidate>
        <label class="champ">
          <span>Adresse e-mail</span>
          <span class="controle">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" [(ngModel)]="email" autocomplete="email" placeholder="vous@exemple.bf" required />
          </span>
        </label>

        <button type="submit" class="bouton" [disabled]="charge()">
          @if (charge()) {
            <i class="bi bi-arrow-repeat spin"></i>
            Envoi en cours…
          } @else {
            <i class="bi bi-send"></i>
            Envoyer le lien
          }
        </button>
      </form>

      <p class="secours">Vous avez retrouvé vos identifiants&nbsp;? <a routerLink="/connexion">Connexion</a></p>
    </cadre-auth>
  `,
})
export class MotDePasseOublieComponent {
  private readonly api = inject(ApiService);

  protected email = '';
  protected readonly charge = signal(false);
  protected readonly erreur = signal('');
  protected readonly fait = signal('');

  protected async envoyer(): Promise<void> {
    this.erreur.set('');
    this.fait.set('');
    if (!this.email.trim()) {
      this.erreur.set('Renseignez votre adresse e-mail.');
      return;
    }
    this.charge.set(true);
    try {
      const reponse = await this.api.motDePasseOublie(this.email.trim()).toPromise();
      this.fait.set(
        messageSuccesApi(reponse, 'Si cette adresse existe dans nos registres, un lien vous a été envoyé.')
      );
      this.email = '';
    } catch (e) {
      const message = messageErreurApi(e);
      if (message === 'Adresse e-mail inconnue.') {
        this.fait.set(messageSuccesApi(null, 'Si cette adresse existe dans nos registres, un lien vous a été envoyé.'));
      } else {
        this.erreur.set(message);
      }
    } finally {
      this.charge.set(false);
    }
  }
}