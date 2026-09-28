import { Component, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { ROLE_LIBELLE } from '../menu';

/** Choix du rôle actif lorsqu'un compte dispose de plusieurs rôles. */
@Component({
  imports: [],
  selector: 'espace-choix-role',
  styles: [
    `
      .page-role {
        max-width: 520px;
        margin: 0 auto;
        padding-top: 1rem;
      }
      .surtitre {
        display: flex;
        justify-content: center;
      }
      .surtitre::before {
        display: none;
      }
      h1 {
        text-align: center;
        margin: 0.25rem 0 0.4rem;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .intro {
        text-align: center;
        margin: 0 0 1.4rem;
        color: var(--mpc-texte-doux);
        font-size: 0.9rem;
      }
      .roles {
        display: grid;
        gap: 0.7rem;
      }
      .role {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding: 1rem 1.1rem;
        border-radius: 1rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-surface);
        box-shadow: var(--mpc-ombre);
        cursor: pointer;
        font: inherit;
        text-align: left;
        color: var(--mpc-texte);
        transition: border-color 0.15s ease, transform 0.15s ease;
      }
      .role:hover {
        border-color: var(--mpc-primaire);
        transform: translateY(-2px);
      }
      .role-icone {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 0.85rem;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1.35rem;
      }
      .role-texte {
        display: flex;
        flex-direction: column;
        line-height: 1.3;
      }
      .role-texte b {
        font-size: 0.98rem;
      }
      .role-texte small {
        color: var(--mpc-texte-doux);
        font-size: 0.8rem;
      }
      .role .fleche {
        margin-left: auto;
        color: var(--mpc-texte-doux);
        font-size: 1.1rem;
      }
      .erreur {
        margin-top: 1rem;
        padding: 0.7rem 0.85rem;
        border-radius: 0.75rem;
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
        font-size: 0.85rem;
        font-weight: 600;
      }
    `,
  ],
  template: `
    <section class="page-role">
      <span class="surtitre"><i class="bi bi-person-gear"></i> Bienvenue</span>
      <h1>Quel rôle souhaitez-vous utiliser&nbsp;?</h1>
      <p class="intro">Votre compte dispose de plusieurs rôles au sein du cabinet.</p>

      <div class="roles">
        @for (role of roles(); track role) {
          <button class="role" type="button" (click)="choisir(role)">
            <span class="role-icone"><i class="bi {{ iconeRole(role) }}"></i></span>
            <span class="role-texte">
              <b>{{ libelle(role) }}</b>
              <small>{{ description(role) }}</small>
            </span>
            <i class="bi bi-chevron-right fleche"></i>
          </button>
        }
      </div>

      @if (message()) {
        <div class="erreur" role="alert">{{ message() }}</div>
      }
    </section>
  `,
})
export class ChoixRoleComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly message = signal('');

  protected readonly roles = computed(() => this.auth.utilisateur()?.roles ?? []);

  protected libelle(role: string): string {
    return ROLE_LIBELLE[role] ?? role;
  }

  protected description(role: string): string {
    const table: Record<string, string> = {
      admin_cabinet: 'Gestion complète du cabinet et du site',
      enseignant: 'Cours, planning, rapports et bibliothèque',
      parent: 'Suivi de vos enfants, factures et paiements',
      eleve: 'Cours, évaluations et documents',
      gestionnaire_librairie: 'Catalogue, stock et boutiques',
    };
    return table[role] ?? 'Rôle de l’espace cabinet';
  }

  protected iconeRole(role: string): string {
    const table: Record<string, string> = {
      admin_cabinet: 'bi-shield-check',
      enseignant: 'bi-person-workspace',
      parent: 'bi-house-heart',
      eleve: 'bi-mortarboard',
      gestionnaire_librairie: 'bi-bag',
    };
    return table[role] ?? 'bi-person';
  }

  protected async choisir(role: string): Promise<void> {
    this.message.set('');
    try {
      await this.auth.choisirRole(role);
      await this.router.navigate(['/espace']);
    } catch {
      this.message.set('Impossible de changer de rôle. Réessayez.');
    }
  }
}