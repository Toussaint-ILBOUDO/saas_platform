import { Injectable, inject, signal } from '@angular/core';

export type ThemeEspace = 'clair' | 'sombre';

/**
 * Mode clair / sombre du backoffice. Persisté en localStorage, appliqué sur
 * `<html data-bs-theme="…">` (Bootstrap 5.3 + variables --mpc-*, styles.scss).
 */
@Injectable({ providedIn: 'root' })
export class ThemeService {
  private static readonly CLE = 'mpc-theme';

  private readonly theme = signal<ThemeEspace>(this.lireInitial());

  /** Signal réactif du thème courant. */
  readonly actif = this.theme.asReadonly();

  constructor() {
    this.appliquer();
  }

  basculer(): void {
    this.theme.set(this.theme() === 'clair' ? 'sombre' : 'clair');
    this.appliquer();
  }

  private lireInitial(): ThemeEspace {
    const enregistre = localStorage.getItem(ThemeService.CLE);
    if (enregistre === 'sombre') return 'sombre';
    return 'clair';
  }

  private appliquer(): void {
    const valeur = this.theme();
    localStorage.setItem(ThemeService.CLE, valeur);
    document.documentElement.setAttribute('data-bs-theme', valeur);
  }
}