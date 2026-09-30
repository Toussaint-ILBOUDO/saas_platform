import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { ApiService } from '../services/api.service';
import { AuthService, libelleRole } from '../services/auth.service';
import { ThemeService } from '../services/theme.service';
import { CONTENT } from '../../content';
import {
  ONGLETS_MOBILE_PAR_ROLE,
  ROADMAP_PAR_ROLE,
  RUBRIQUES_PAR_ROLE,
  titreDeRoute,
} from './menu';

/**
 * Coquille responsive de l'espace cabinet :
 * — desktop (≥1200) : sidebar 264 px, repliable en rail d'icônes ;
 * — tablette (768–1199) : rail d'icônes forcé ;
 * — mobile (<768) : barre d'onglets basse (Accueil / Finance / Rapports / Plus)
 *   + tiroir plein écran pour toutes les rubriques.
 * Menus calculés selon le rôle actif ; mode sombre piloté par ThemeService.
 */
@Component({
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  selector: 'espace',
  styleUrl: './espace.component.scss',
  templateUrl: './espace.component.html',
})
export class EspaceComponent implements OnInit {
  protected readonly auth = inject(AuthService);
  protected readonly themes = inject(ThemeService);
  protected readonly api = inject(ApiService);
  protected readonly router = inject(Router);
  protected readonly roleLibelle = libelleRole;
  protected readonly sigle = CONTENT.cabinet.sigle;
  protected readonly nom = CONTENT.cabinet.nom;
  protected readonly logoUrl = signal<string | null>(null);

  protected readonly replie = signal(false);
  protected readonly tiroirOuvert = signal(false);
  protected readonly profilOuvert = signal(false);
  protected readonly choixRoleOuvert = signal(false);
  protected readonly nonLues = signal(0);
  protected readonly titre = signal('Espace cabinet');

  protected readonly rubriques = computed(() => {
    const role = this.auth.roleActif() as keyof typeof RUBRIQUES_PAR_ROLE;
    return RUBRIQUES_PAR_ROLE[role] ?? RUBRIQUES_PAR_ROLE.admin_cabinet;
  });

  protected readonly roadmap = computed(() => {
    const role = this.auth.roleActif() as keyof typeof ROADMAP_PAR_ROLE;
    return ROADMAP_PAR_ROLE[role] ?? ROADMAP_PAR_ROLE.admin_cabinet;
  });

  protected readonly onglets = computed(() => {
    const role = this.auth.roleActif() as keyof typeof ONGLETS_MOBILE_PAR_ROLE;
    return ONGLETS_MOBILE_PAR_ROLE[role] ?? ONGLETS_MOBILE_PAR_ROLE.admin_cabinet;
  });

  protected readonly aPlusieursRoles = computed(() => (this.auth.utilisateur()?.roles.length ?? 0) > 1);

  ngOnInit(): void {
    this.router.events.subscribe((evenement) => {
      if (evenement instanceof NavigationEnd) {
        this.titre.set(titreDeRoute(evenement.urlAfterRedirects));
      }
    });
    this.api.getNotifications(1, 1).subscribe({
      next: (r) => this.nonLues.set(r.meta.non_lues ?? 0),
    });
    this.api.getCabinetPublic().subscribe((cabinet) => {
      this.logoUrl.set(cabinet.logo_url ?? null);
    });
  }

  protected basculerReplie(): void {
    this.replie.set(!this.replie());
  }

  protected ouvrirTiroir(): void {
    this.tiroirOuvert.set(true);
  }

  protected fermerTiroir(): void {
    this.tiroirOuvert.set(false);
  }

  protected basculerProfil(): void {
    this.profilOuvert.set(!this.profilOuvert());
    this.choixRoleOuvert.set(false);
  }

  protected fermerProfil(): void {
    this.profilOuvert.set(false);
    this.choixRoleOuvert.set(false);
  }

  protected basculerChoixRole(): void {
    this.choixRoleOuvert.set(!this.choixRoleOuvert());
  }

  protected async choisirRole(role: string): Promise<void> {
    await this.auth.choisirRole(role);
    this.fermerProfil();
  }

  protected async deconnexion(): Promise<void> {
    await this.auth.deconnexion();
    await this.router.navigateByUrl('/connexion');
  }
}