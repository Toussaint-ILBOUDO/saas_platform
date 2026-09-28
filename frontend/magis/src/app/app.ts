import { Component, HostListener, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { HeaderComponent } from './components/header.component';
import { FooterComponent } from './components/footer.component';

@Component({
  imports: [RouterOutlet, HeaderComponent, FooterComponent],
  selector: 'app-root',
  styleUrl: './app.scss',
  templateUrl: './app.html',
})
export class App {
  protected readonly sansChrome = signal(false);
  protected haut = false;

  private readonly router = inject(Router);

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
  }

  @HostListener('window:scroll', [])
  protected surScroll(): void {
    this.haut = (window.scrollY ?? 0) > 400;
  }

  protected versHaut(): void {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}