import { Component, OnInit, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { CONTENT } from '../../../content';

/** Carte d'authentification : logo, contenu projeté, retour au site public. */
@Component({
  imports: [RouterLink],
  selector: 'cadre-auth',
  styles: [
    `
      .page-auth {
        min-height: 100dvh;
        display: grid;
        place-items: center;
        padding: 1.5rem 1rem;
        background: var(--mpc-fond);
        position: relative;
      }
      .page-auth::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: var(--mpc-primaire);
      }
      .carte {
        width: 100%;
        max-width: 400px;
        display: flex;
        flex-direction: column;
        gap: 1.4rem;
        padding: 2.2rem 1.9rem;
        border-radius: 1.25rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
      }
      .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        justify-content: center;
      }
      .logo-img {
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 13px;
        object-fit: cover;
        background: #fff;
        border: 1px solid var(--mpc-separateur);
        flex-shrink: 0;
      }
      .logo-carre {
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 13px;
        background: var(--mpc-primaire);
        color: #fff;
        display: grid;
        place-items: center;
        font-weight: 800;
        font-family: var(--mpc-police-titre);
        font-size: 0.95rem;
        flex-shrink: 0;
      }
      .logo-texte {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
      }
      .logo-nom {
        font-weight: 800;
        color: var(--mpc-texte);
        font-family: var(--mpc-police-titre);
        font-size: 1.05rem;
      }
      .logo-slogan {
        color: var(--mpc-texte-doux);
        font-size: 0.76rem;
      }
      .retour-site {
        margin: 0;
        text-align: center;
      }
      .retour-site a {
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--mpc-texte-doux);
      }
      .retour-site a:hover {
        color: var(--mpc-primaire);
      }
      .retour-site i {
        margin-right: 0.3rem;
      }
    `,
  ],
  template: `
    <div class="page-auth">
      <div class="carte">
        <a class="logo" routerLink="/">
          @if (logoUrl()) {
            <img class="logo-img" [src]="logoUrl()" alt="Logo {{ nom }}" />
          } @else {
            <span class="logo-carre">{{ sigle }}</span>
          }
          <span class="logo-texte">
            <span class="logo-nom">{{ nom }}</span>
            <span class="logo-slogan">{{ slogan }}</span>
          </span>
        </a>
        <ng-content />
      </div>
      <p class="retour-site">
        <a routerLink="/"><i class="bi bi-arrow-left"></i>Retour au site public</a>
      </p>
    </div>
  `,
})
export class CadreAuthComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected nom = CONTENT.cabinet.nom;
  protected sigle = CONTENT.cabinet.sigle;
  protected slogan = CONTENT.cabinet.slogan;
  protected readonly logoUrl = signal<string | null>(null);

  ngOnInit(): void {
    this.api.getCabinetPublic().subscribe((cabinet) => {
      this.logoUrl.set(cabinet.logo_url ?? null);
    });
  }
}