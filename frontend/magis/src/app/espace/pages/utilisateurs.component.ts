import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { MetaPage, UtilisateurAdmin } from '../../models';
import { messageErreurApi } from '../../services/messages';

const ROLES_DISPONIBLES = ['admin_cabinet', 'enseignant', 'parent', 'eleve', 'gestionnaire_librairie'];

const ROLE_LIBELLE: Record<string, string> = {
  admin_cabinet: 'Admin',
  enseignant: 'Enseignant',
  parent: 'Parent',
  eleve: 'Élève',
  gestionnaire_librairie: 'Librairie',
};

const ROLE_ICONE: Record<string, string> = {
  admin_cabinet: 'bi-shield-check',
  enseignant: 'bi-person-workspace',
  parent: 'bi-house-heart',
  eleve: 'bi-mortarboard',
  gestionnaire_librairie: 'bi-bag',
};

interface ModeleUtilisateur {
  id: number | null;
  nom: string;
  prenom: string;
  email: string;
  telephone_whatsapp: string;
  telephone_appel: string;
  password: string;
  statut: boolean;
  roles: string[];
}

const VIDE = (): ModeleUtilisateur => ({
  id: null,
  nom: '',
  prenom: '',
  email: '',
  telephone_whatsapp: '',
  telephone_appel: '',
  password: '',
  statut: true,
  roles: [],
});

/** Écran admin « Utilisateurs » : liste, recherche, création, édition, activation/suspension. */
@Component({
  imports: [FormsModule],
  selector: 'espace-utilisateurs',
  styles: [
    `
      :host {
        display: block;
      }
      .entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
      }
      .entete h1 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .btn-primaire {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1.05rem;
        border: none;
        border-radius: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
      }
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-primaire:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .filtres {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
      }
      .recherche {
        position: relative;
        flex: 1;
        min-width: 200px;
      }
      .recherche i {
        position: absolute;
        left: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--mpc-texte-doux);
      }
      .recherche input {
        width: 100%;
        height: 44px;
        padding: 0 0.8rem 0 2.4rem;
        border-radius: 0.8rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.88rem;
        outline: none;
      }
      .recherche input:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .filtre {
        height: 44px;
        padding: 0 0.8rem;
        border-radius: 0.8rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.88rem;
        outline: none;
        cursor: pointer;
      }
      .liste {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 0.85rem;
      }
      .carte {
        display: flex;
        gap: 0.8rem;
        align-items: flex-start;
        padding: 0.95rem 1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
      }
      .avatar {
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 0.9rem;
        font-weight: 800;
        font-size: 0.9rem;
        color: var(--mpc-bleu-clair);
        background: var(--mpc-bleu-tint);
      }
      .info {
        flex: 1;
        min-width: 0;
      }
      .info b {
        display: block;
        font-size: 0.92rem;
      }
      .info small {
        color: var(--mpc-texte-doux);
        font-size: 0.78rem;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .badges {
        display: flex;
        gap: 0.3rem;
        flex-wrap: wrap;
        margin-top: 0.45rem;
      }
      .badge {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        background: var(--mpc-abandon);
        color: var(--mpc-texte-doux);
      }
      .badge._actif {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .badge._inactif {
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .actions {
        display: flex;
        gap: 0.3rem;
        margin-top: 0.5rem;
      }
      .mini {
        padding: 0.4rem 0.7rem;
        border-radius: 0.65rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
      }
      .mini:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .mini._suspend {
        border-color: var(--mpc-danger);
        color: var(--mpc-danger);
      }
      .vide {
        padding: 3rem 1rem;
        text-align: center;
        color: var(--mpc-texte-doux);
        border: 1px dashed var(--mpc-separateur);
        border-radius: 1rem;
      }
      .pagination {
        display: flex;
        justify-content: center;
        gap: 0.4rem;
        margin-top: 1.4rem;
      }
      .page {
        min-width: 40px;
        height: 40px;
        padding: 0 0.6rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.86rem;
        font-weight: 700;
        cursor: pointer;
      }
      .page._courante {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .page:disabled {
        opacity: 0.4;
        cursor: not-allowed;
      }
      .toast {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: 0.8rem;
        font-size: 0.86rem;
        font-weight: 600;
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .fenetre {
        position: fixed;
        inset: 0;
        z-index: 90;
        display: grid;
        place-items: center;
        padding: 1rem;
        background: rgba(10, 14, 26, 0.55);
      }
      .panneau {
        width: 100%;
        max-width: 560px;
        max-height: 88vh;
        overflow-y: auto;
        border-radius: 1.1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
        padding: 1.3rem 1.4rem;
      }
      .panneau-entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
      }
      .panneau-entete h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .fermer {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border: none;
        border-radius: 0.7rem;
        background: var(--mpc-abandon);
        color: var(--mpc-texte);
        cursor: pointer;
      }
      .formulaire {
        display: grid;
        gap: 0.8rem;
      }
      .grille {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.8rem;
      }
      .champ {
        display: grid;
        gap: 0.3rem;
      }
      .champ > span {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
      }
      .champ input {
        padding: 0.6rem 0.75rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.88rem;
        outline: none;
      }
      .champ input:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .roles {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
      }
      .role-coche {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
      }
      .role-coche._choisi {
        border-color: var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
      }
      .role-coche input {
        width: 16px;
        height: 16px;
        accent-color: var(--mpc-primaire);
      }
      .attention {
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
      }
      .actions-form {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 0.4rem;
      }
      .btn-neutral {
        padding: 0.55rem 0.95rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
      }
      @media (max-width: 520px) {
        .grille {
          grid-template-columns: 1fr;
        }
      }
    `,
  ],
  template: `
    <div class="entete">
      <h1>Utilisateurs</h1>
      <button class="btn-primaire" type="button" (click)="ouvrirCreation()"><i class="bi bi-person-plus"></i> Nouvel utilisateur</button>
    </div>

    @if (toast()) {
      <div class="toast"><i class="bi bi-exclamation-triangle"></i>{{ toast() }}</div>
    }

    <div class="filtres">
      <label class="recherche">
        <i class="bi bi-search"></i>
        <input type="search" #terme [value]="recherche" (keyup.enter)="rechercher(terme.value)" placeholder="Nom, e-mail, téléphone…" />
      </label>
      <select class="filtre" [value]="roleFiltre()" (change)="changerRole($event)">
        <option value="">Tous les rôles</option>
        @for (r of rolesDisponibles; track r) {
          <option [value]="r">{{ roleLibelle(r) }}</option>
        }
      </select>
      <select class="filtre" [value]="statutFiltre()" (change)="changerStatutFiltre($event)">
        <option value="">Tous les statuts</option>
        <option value="actif">Actifs</option>
        <option value="inactif">Suspendus</option>
      </select>
    </div>

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else if (utilisateurs().length === 0) {
      <div class="vide"><i class="bi bi-people"></i> Aucun utilisateur trouvé.</div>
    } @else {
      <div class="liste">
        @for (u of utilisateurs(); track u.id) {
          <article class="carte">
            <span class="avatar">{{ initiales(u) }}</span>
            <div class="info">
              <b>{{ u.prenom }} {{ u.nom }}</b>
              <small>{{ u.email }}</small>
              <div class="badges">
                <span class="badge" [class._actif]="u.statut" [class._inactif]="!u.statut">
                  {{ u.statut ? 'Actif' : 'Suspendu' }}
                </span>
                @for (r of u.roles; track r) {
                  <span class="badge"><i class="bi {{ iconeRole(r) }}"></i> {{ roleLibelle(r) }}</span>
                }
              </div>
              <div class="actions">
                <button class="mini" type="button" (click)="ouvrirEdition(u)"><i class="bi bi-pencil"></i> Modifier</button>
                @if (u.statut) {
                  <button class="mini _suspend" type="button" (click)="suspendre(u)"><i class="bi bi-pause-circle"></i> Suspendre</button>
                } @else {
                  <button class="mini" type="button" (click)="activer(u)"><i class="bi bi-play-circle"></i> Réactiver</button>
                }
              </div>
            </div>
          </article>
        }
      </div>

      @if ((meta()?.last_page ?? 1) > 1) {
        <nav class="pagination" aria-label="Pagination des utilisateurs">
          <button class="page" type="button" [disabled]="(meta()?.current_page ?? 1) <= 1" (click)="aller((meta()?.current_page ?? 1) - 1)">
            <i class="bi bi-chevron-left"></i>
          </button>
          <span class="page _courante">{{ meta()?.current_page }} / {{ meta()?.last_page }}</span>
          <button class="page" type="button" [disabled]="(meta()?.current_page ?? 1) >= (meta()?.last_page ?? 1)" (click)="aller((meta()?.current_page ?? 1) + 1)">
            <i class="bi bi-chevron-right"></i>
          </button>
        </nav>
      }
    }

    @if (formulaireOuvert()) {
      <div class="fenetre" (click)="fermerFormulaire()">
        <div class="panneau" (click)="$event.stopPropagation()">
          <div class="panneau-entete">
            <h2>{{ modele().id ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' }}</h2>
            <button class="fermer" type="button" (click)="fermerFormulaire()" aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
          </div>

          <div class="formulaire">
            <div class="grille">
              <label class="champ">
                <span>Nom *</span>
                <input [(ngModel)]="modele().nom" name="nom" />
              </label>
              <label class="champ">
                <span>Prénom</span>
                <input [(ngModel)]="modele().prenom" name="prenom" />
              </label>
            </div>
            <label class="champ">
              <span>Adresse e-mail *</span>
              <input type="email" [(ngModel)]="modele().email" name="email" />
            </label>
            <div class="grille">
              <label class="champ">
                <span>Téléphone WhatsApp</span>
                <input type="tel" [(ngModel)]="modele().telephone_whatsapp" name="wa" placeholder="+226…" />
              </label>
              <label class="champ">
                <span>Téléphone (appel)</span>
                <input type="tel" [(ngModel)]="modele().telephone_appel" name="appel" placeholder="+226…" />
              </label>
            </div>
            <label class="champ">
              <span>{{ modele().id ? 'Nouveau mot de passe (laisser vide pour conserver)' : 'Mot de passe provisoire *' }}</span>
              <input type="password" [(ngModel)]="modele().password" name="password" placeholder="8 caractères minimum" />
            </label>
            <label class="champ">
              <span>Rôles</span>
              <span class="roles">
                @for (r of rolesDisponibles; track r) {
                  <span class="role-coche" [class._choisi]="modele().roles.includes(r)">
                    <input type="checkbox" [checked]="modele().roles.includes(r)" (change)="basculerRole(r, $event)" />
                    {{ roleLibelle(r) }}
                  </span>
                }
              </span>
              <span class="attention">Au moins un rôle est requis. « Élève » s’utilise avec le compte de l’élève lui-même.</span>
            </label>
            <label class="role-coche" style="justify-self:start">
              <input type="checkbox" [(ngModel)]="modele().statut" name="statut" />
              Compte actif
            </label>

            <div class="actions-form">
              <button class="btn-neutral" type="button" (click)="fermerFormulaire()">Annuler</button>
              <button class="btn-primaire" type="button" (click)="enregistrer()" [disabled]="sauvegarde()">
                @if (sauvegarde()) {
                  <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
                } @else {
                  <i class="bi bi-check2"></i> Enregistrer
                }
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class UtilisateursComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly rolesDisponibles = ROLES_DISPONIBLES;
  protected readonly utilisateurs = signal<UtilisateurAdmin[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly charge = signal(true);
  protected readonly toast = signal('');
  protected readonly roleFiltre = signal('');
  protected readonly statutFiltre = signal('');
  protected readonly formulaireOuvert = signal(false);
  protected readonly sauvegarde = signal(false);
  protected readonly modele = signal<ModeleUtilisateur>(VIDE());
  protected recherche = '';

  protected readonly roleLibelle = (r: string) => ROLE_LIBELLE[r] ?? r;

  ngOnInit(): void {
    this.charger(1);
  }

  protected initiales(u: UtilisateurAdmin): string {
    return [u.prenom, u.nom].filter(Boolean).map((p) => p[0]?.toUpperCase() ?? '').join('') || '?';
  }

  protected iconeRole(r: string): string {
    return ROLE_ICONE[r] ?? 'bi-person';
  }

  protected rechercher(valeur: string): void {
    this.recherche = valeur.trim();
    this.charger(1);
  }

  protected changerRole(evenement: Event): void {
    this.roleFiltre.set((evenement.target as HTMLSelectElement).value);
    this.charger(1);
  }

  protected changerStatutFiltre(evenement: Event): void {
    this.statutFiltre.set((evenement.target as HTMLSelectElement).value);
    this.charger(1);
  }

  protected aller(page: number): void {
    this.charger(page);
  }

  protected ouvrirCreation(): void {
    this.modele.set(VIDE());
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEdition(u: UtilisateurAdmin): void {
    this.modele.set({
      id: u.id,
      nom: u.nom,
      prenom: u.prenom,
      email: u.email,
      telephone_whatsapp: u.telephone_whatsapp ?? '',
      telephone_appel: u.telephone_appel ?? '',
      password: '',
      statut: u.statut,
      roles: [...u.roles],
    });
    this.formulaireOuvert.set(true);
  }

  protected fermerFormulaire(): void {
    this.formulaireOuvert.set(false);
  }

  protected basculerRole(role: string, evenement: Event): void {
    const coche = (evenement.target as HTMLInputElement).checked;
    const m = this.modele();
    const roles = new Set(m.roles);
    if (coche) roles.add(role);
    else roles.delete(role);
    this.modele.set({ ...m, roles: Array.from(roles) });
  }

  protected async enregistrer(): Promise<void> {
    this.toast.set('');
    const m = this.modele();
    if (!m.nom.trim() || !m.email.trim()) {
      this.toast.set('Le nom et l’adresse e-mail sont obligatoires.');
      return;
    }
    if (m.roles.length === 0) {
      this.toast.set('Choisissez au moins un rôle.');
      return;
    }
    if (!m.id && m.password.length < 8) {
      this.toast.set('Le mot de passe provisoire doit contenir au moins 8 caractères.');
      return;
    }
    this.sauvegarde.set(true);
    const payload: Record<string, string | boolean | string[]> = {
      nom: m.nom.trim(),
      prenom: m.prenom.trim(),
      email: m.email.trim(),
      telephone_whatsapp: m.telephone_whatsapp.trim(),
      telephone_appel: m.telephone_appel.trim(),
      statut: m.statut,
      roles: m.roles,
    };
    if (m.password) payload['password'] = m.password;
    try {
      if (m.id) {
        await this.api.majUtilisateur(m.id, payload).toPromise();
      } else {
        await this.api.creerUtilisateur(payload).toPromise();
      }
      this.formulaireOuvert.set(false);
      this.charger(Math.max(1, this.meta()?.current_page ?? 1));
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async activer(u: UtilisateurAdmin): Promise<void> {
    try {
      await this.api.activerUtilisateur(u.id).toPromise();
      this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  protected async suspendre(u: UtilisateurAdmin): Promise<void> {
    if (!confirm(`Suspendre le compte de ${u.prenom} ${u.nom} ?`)) return;
    try {
      await this.api.suspendreUtilisateur(u.id).toPromise();
      this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  private charger(page: number): void {
    this.charge.set(true);
    const statut = this.statutFiltre() === 'actif' ? true : this.statutFiltre() === 'inactif' ? false : undefined;
    this.api.getUtilisateurs(page, 15, this.recherche, this.roleFiltre(), statut).subscribe({
      next: (r) => {
        this.utilisateurs.set(r.data);
        this.meta.set(r.meta);
        this.charge.set(false);
      },
      error: (e) => {
        this.toast.set(messageErreurApi(e));
        this.charge.set(false);
      },
    });
  }
}