import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { ApiService } from './api.service';
import { SessionUtilisateur } from '../models';

/**
 * Session authentifiée du backoffice cabinet (cookie, guard web — D8/D-038).
 * État exposé en signaux (fiables en mode zoneless) : utilisateur connecté,
 * rôle actif, état "chargement terminé" (pour les gardes).
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);

  private readonly utilisateurSig = signal<SessionUtilisateur | null>(null);
  private readonly pretSig = signal(false);

  /** Utilisateur connecté (null si invité). */
  readonly utilisateur = this.utilisateurSig.asReadonly();

  /** Vrai une fois le premier appel /auth/moi terminé (gardes). */
  readonly pret = this.pretSig.asReadonly();

  /** Rôle actif : `active_role`, sinon premier rôle de l'utilisateur. */
  readonly roleActif = computed(() => {
    const u = this.utilisateurSig();
    return u?.active_role ?? u?.roles?.[0] ?? '';
  });

  /** Nom d'affichage complet. */
  readonly nomComplet = computed(() => {
    const u = this.utilisateurSig();
    if (!u) return '';
    return [u.prenom, u.nom].filter(Boolean).join(' ').trim() || u.email;
  });

  /** Initiales pour l'avatar. */
  readonly initiales = computed(() => {
    const c = this.nomComplet();
    const mots = c.trim().split(/\s+/).slice(0, 2);
    return mots.map((m) => m[0]?.toUpperCase() ?? '').join('');
  });

  /**
   * Recharge la session depuis /api/auth/moi. Attendu par les gardes :
   * résout après le premier appel. Idempotent.
   */
  async initialiser(): Promise<void> {
    if (this.pretSig()) return;
    try {
      const utilisateur = await firstValueFrom(this.api.moi());
      this.utilisateurSig.set(utilisateur.user);
    } catch {
      this.utilisateurSig.set(null);
    } finally {
      this.pretSig.set(true);
    }
  }

  async connexion(email: string, motDePasse: string): Promise<void> {
    const reponse = await firstValueFrom(this.api.connexion(email, motDePasse));
    this.utilisateurSig.set(reponse.user);
    this.pretSig.set(true);
  }

  async choisirRole(role: string): Promise<void> {
    await this.api.roleActif(role).toPromise();
    const u = this.utilisateurSig();
    if (u) {
      this.utilisateurSig.set({ ...u, active_role: role });
    }
  }

  async deconnexion(): Promise<void> {
    try {
      await this.api.deconnexion().toPromise();
    } finally {
      this.utilisateurSig.set(null);
    }
  }
}

/** Raccourci d'affichage d'un rôle technique (ex. `admin_cabinet` → « Administrateur »). */
export function libelleRole(role: string): string {
  const table: Record<string, string> = {
    admin_cabinet: 'Administrateur',
    enseignant: 'Enseignant',
    parent: 'Parent',
    eleve: 'Élève',
    gestionnaire_librairie: 'Gestionnaire librairie',
    super_admin: 'Super administrateur',
  };
  return table[role] ?? role;
}