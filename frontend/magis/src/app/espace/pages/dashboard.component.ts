import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { AuthService, libelleRole } from '../../services/auth.service';
import { StatsCabinet } from '../../models';
import { ROADMAP_PAR_ROLE, RUBRIQUES_PAR_ROLE } from '../menu';

const SOUS_TITRE: Record<string, string> = {
  '/espace/finance': 'Caisse, factures et paiements',
  '/espace/rapports': 'Rapports mensuels et objectifs',
  '/espace/utilisateurs': 'Enseignants, élèves, parents, librairie',
  '/espace/actualites': 'Publier et gérer les actualités',
  '/espace/faq': 'Sections et questions fréquentes',
  '/espace/fiche-cabinet': 'Coordonnées, réseaux, zones',
  '/espace/notifications': 'Vos dernières alertes',
  '/espace/profil': 'Informations et mot de passe',
  '/espace/modules/planning': 'Emplois du temps',
  '/espace/modules/cours': 'Cours et séances',
  '/espace/modules/boutique': 'Catalogue et commandes',
  '/espace': 'Aperçu général',
};

interface CarteStat {
  valeur: number;
  label: string;
  icone: string;
  couleur: string;
  tint: string;
}

/** Page d'accueil de l'espace cabinet : salutation, statistiques (admin), accès rapides. */
@Component({
  imports: [RouterLink],
  selector: 'espace-dashboard',
  template: `
    <section class="page-dashboard">
      <header class="bonjour">
        <div class="bonjour-texte">
          <span class="surtitre"><i class="bi bi-stars"></i> Espace {{ roleLibelle(role()) }}</span>
          <h1>Bonjour, {{ prenom() }}</h1>
          <p>Bienvenue sur l'espace du Magis Plus Center. Retrouvez ici vos modules et vos informations.</p>
        </div>
        <a class="boutique" routerLink="/">
          <i class="bi bi-globe2"></i>
          <span>Voir le site</span>
        </a>
      </header>

      @if (role() === 'admin_cabinet') {
        @if (stats()) {
          <div class="stats">
            @for (c of cartesStats(); track c.label) {
              <div class="carte-stat" [style.--couleur]="c.couleur" [style.--teinte]="c.tint">
                <i class="bi {{ c.icone }}"></i>
                <b>{{ c.valeur }}</b>
                <span>{{ c.label }}</span>
              </div>
            }
          </div>
        } @else {
          <div class="info">Chargement des statistiques…</div>
        }
      }

      <h2 class="titre">Accès rapides</h2>
      <div class="grille-acc">
        @for (c of raccourcis(); track c.route) {
          <a class="carte" [routerLink]="c.route">
            <span class="carte-icone"><i class="bi {{ c.icone }}"></i></span>
            <span class="carte-texte">
              <b>{{ c.libelle }}</b>
              <small>{{ c.sousTitre }}</small>
            </span>
          </a>
        }
      </div>

      @if (avenir().length) {
        <h2 class="titre">Bientôt disponible</h2>
        <ul class="liste-avenir">
          @for (m of avenir(); track m) {
            <li><i class="bi bi-hourglass-split"></i><span>{{ m }}</span></li>
          }
        </ul>
      }
    </section>
  `,
  styles: [
    `
      .page-dashboard {
        animation: mpc-monte 0.4s ease both;
      }
      .bonjour {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
      }
      .bonjour-texte h1 {
        margin: 0.15rem 0 0.35rem;
        font-size: clamp(1.5rem, 3vw, 1.9rem);
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .bonjour-texte p {
        margin: 0;
        color: var(--mpc-texte-doux);
        max-width: 620px;
      }
      .surtitre i {
        margin-right: 0.35rem;
      }
      .boutique {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 0.9rem;
        border-radius: 0.8rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-surface);
        color: var(--mpc-texte);
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
        white-space: nowrap;
      }
      .boutique:hover {
        color: var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
      }
      .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.85rem;
        margin-bottom: 1.6rem;
      }
      .carte-stat {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        padding: 1rem 1.1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
      }
      .carte-stat i {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 0.8rem;
        font-size: 1.15rem;
        color: var(--couleur);
        background: var(--teinte);
        margin-bottom: 0.6rem;
      }
      .carte-stat b {
        font-family: var(--mpc-police-titre);
        font-size: 1.5rem;
        line-height: 1.1;
      }
      .carte-stat span {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--mpc-texte-doux);
      }
      .titre {
        margin: 1.6rem 0 0.75rem;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .grille-acc {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 0.85rem;
      }
      .carte {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
        color: var(--mpc-texte);
        text-decoration: none;
        transition: transform 0.15s ease, border-color 0.15s ease;
      }
      .carte:hover {
        transform: translateY(-2px);
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .carte-icone {
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 0.8rem;
        font-size: 1.25rem;
        color: var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
      }
      .carte-texte {
        display: flex;
        flex-direction: column;
        line-height: 1.25;
      }
      .carte-texte b {
        font-size: 0.92rem;
      }
      .carte-texte small {
        color: var(--mpc-texte-doux);
        font-size: 0.76rem;
      }
      .info {
        padding: 0.8rem 1rem;
        border-radius: 0.8rem;
        background: var(--mpc-abandon);
        color: var(--mpc-texte-doux);
        font-size: 0.85rem;
        margin-bottom: 1rem;
      }
      .liste-avenir {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
      }
      .liste-avenir li {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.8rem;
        border-radius: 0.8rem;
        background: var(--mpc-surface-teintee);
        border: 1px dashed var(--mpc-separateur);
        color: var(--mpc-texte-doux);
        font-size: 0.8rem;
        font-weight: 600;
      }
      @media (max-width: 900px) {
        .stats {
          grid-template-columns: repeat(2, 1fr);
        }
      }
      @media (max-width: 520px) {
        .bonjour {
          flex-direction: column;
        }
        .stats {
          grid-template-columns: repeat(2, 1fr);
          gap: 0.6rem;
        }
        .carte-stat {
          padding: 0.8rem 0.85rem;
        }
        .grille-acc {
          grid-template-columns: 1fr;
        }
      }
    `,
  ],
})
export class DashboardComponent implements OnInit {
  private readonly api = inject(ApiService);
  protected readonly auth = inject(AuthService);
  protected readonly roleLibelle = libelleRole;
  protected readonly stats = signal<StatsCabinet | null>(null);

  protected readonly role = computed(() => this.auth.roleActif() || 'admin_cabinet');

  protected readonly prenom = computed(() => {
    const u = this.auth.utilisateur();
    return u?.prenom || u?.nom || 'bienvenue';
  });

  protected readonly cartesStats = computed<CarteStat[]>(() => {
    const s = this.stats();
    if (!s) return [];
    return [
      { valeur: s.nb_eleves ?? 0, label: 'Élèves', icone: 'bi-mortarboard', couleur: 'var(--mpc-primaire)', tint: 'var(--mpc-primaire-tint)' },
      { valeur: s.nb_enseignants ?? 0, label: 'Enseignants', icone: 'bi-person-workspace', couleur: 'var(--mpc-bleu-clair)', tint: 'var(--mpc-bleu-tint)' },
      { valeur: s.nb_familles ?? 0, label: 'Familles', icone: 'bi-house-heart', couleur: 'var(--mpc-succes)', tint: 'var(--mpc-succes-tint)' },
      { valeur: s.nb_contrats ?? 0, label: 'Contrats actifs', icone: 'bi-file-earmark-check', couleur: 'var(--mpc-attention)', tint: 'var(--mpc-attention-tint)' },
    ];
  });

  protected readonly raccourcis = computed(() => {
    const role = this.role() as keyof typeof RUBRIQUES_PAR_ROLE;
    const rubriques = RUBRIQUES_PAR_ROLE[role] ?? RUBRIQUES_PAR_ROLE.admin_cabinet;
    return rubriques
      .flatMap((r) => r.items)
      .filter((i) => i.route !== '/espace')
      .map((i) => ({
        libelle: i.libelle,
        icone: i.icone,
        route: i.route,
        sousTitre: SOUS_TITRE[i.route] ?? 'Module de l’espace',
      }));
  });

  protected readonly avenir = computed(() => {
    const role = this.role() as keyof typeof ROADMAP_PAR_ROLE;
    return ROADMAP_PAR_ROLE[role] ?? ROADMAP_PAR_ROLE.admin_cabinet;
  });

  ngOnInit(): void {
    if (this.role() === 'admin_cabinet') {
      this.api.getStats().subscribe({
        next: (s) => this.stats.set(s),
      });
    }
  }
}