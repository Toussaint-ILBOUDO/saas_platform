import { Component, HostListener, computed, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { SwUpdate } from '@angular/service-worker';
import { HeaderComponent } from './components/header.component';
import { FooterComponent } from './components/footer.component';
import { AuthService } from './services/auth.service';
import { HorsLigneService } from './services/hors-ligne.service';

@Component({
  imports: [RouterOutlet, HeaderComponent, FooterComponent],
  selector: 'app-root',
  styleUrl: './app.scss',
  templateUrl: './app.html',
})
export class App {
  protected readonly sansChrome = signal(false);
  protected readonly miseAJourDisponible = signal(false);
  protected haut = false;

  protected readonly auth = inject(AuthService);
  protected readonly file = inject(HorsLigneService);
  private readonly router = inject(Router);
  private readonly swUpdate = inject(SwUpdate);

  /**
   * État réseau affiché en bas de page (T4.5 — trois états : hors ligne,
   * « N en attente », synchronisation).
   */
  protected readonly texteReseau = computed(() => {
    const enAttente = this.file.enAttente();

    if (this.file.enSynchro()) {
      return enAttente > 1 ? `Synchronisation de ${enAttente} saisies…` : 'Synchronisation en cours…';
    }

    if (this.auth.horsLigne()) {
      return enAttente > 0
        ? `Hors ligne — ${enAttente} saisie${enAttente > 1 ? 's' : ''} en attente d’envoi.`
        : 'Hors ligne : lecture seule. Reconnectez-vous pour enregistrer.';
    }

    if (enAttente > 0) return `${enAttente} saisie${enAttente > 1 ? 's' : ''} en attente d’envoi…`;
    return '';
  });

  /** Avertissement : une écriture refusée au retour du réseau (Q-A4). */
  protected readonly texteEchecs = computed(() => {
    const echecs = this.file.echecs();
    if (!echecs.length) return '';

    const reste = echecs.length > 1 ? ` (+${echecs.length - 1} autre${echecs.length > 2 ? 's' : ''})` : '';
    return `${echecs[0].libelle} — ${echecs[0].message}${reste}`;
  });

  /** Bandeaux empilés : sert aussi à décoller le bouton de remontée. */
  protected readonly nbBandeaux = computed(
    () =>
      (this.miseAJourDisponible() ? 1 : 0) +
      (this.file.echecs().length ? 1 : 0) +
      (this.file.synchronise() || this.texteReseau() ? 1 : 0)
  );

  constructor() {
    this.router.events.subscribe((evenement) => {
      if (evenement instanceof NavigationEnd) {
        const url = evenement.urlAfterRedirects;
        this.sansChrome.set(
          url.startsWith('/espace') ||
            url === '/connexion' ||
            url.startsWith('/mot-de-passe-oublie') ||
            url.startsWith('/reinitialiser-mot-de-passe')
        );
      }
    });

    if (this.swUpdate.isEnabled) {
      this.swUpdate.versionUpdates.subscribe((evenement) => {
        if (evenement.type === 'VERSION_READY') {
          this.miseAJourDisponible.set(true);
        }
      });
    }
  }

  protected async appliquerMiseAJour(): Promise<void> {
    try {
      await this.swUpdate.activateUpdate();
    } catch {}
    document.location.reload();
  }

  protected ignorerMiseAJour(): void {
    this.miseAJourDisponible.set(false);
  }

  @HostListener('window:scroll', [])
  protected surScroll(): void {
    this.haut = (window.scrollY ?? 0) > 400;
  }

  protected versHaut(): void {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}