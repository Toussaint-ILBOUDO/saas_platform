import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { ApiService } from './api.service';
import { HorsLigneService } from './hors-ligne.service';
import { SessionUtilisateur } from '../models';

/** Cache local du profil (lecture seule hors-ligne, purgé à la déconnexion). */
const CLE_PROFIL = 'mpc.session.profil';
/** Rôle actif choisi : la session serveur (remember) repart à zéro à l'ouverture. */
const CLE_ROLE = 'mpc.session.role';
/** Validité du cache local — cohérente avec le cache 48 h du service worker. */
const DUREE_CACHE_MS = 48 * 60 * 60 * 1000;

/**
 * Session authentifiée du backoffice cabinet (cookie, guard web — D8/D-038).
 *
 * PWA toujours connectée : la connexion pose un cookie « rester connecté »
 * (400 jours) côté serveur ; ici, le profil est mis en cache local pour
 * ouvrir l'app hors-ligne (écritures réservées au réseau — le bandeau le
 * rappelle) et le rôle actif choisi est réactivé après une session recréée
 * par le cookie.
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  /** File d'écritures hors-ligne (T4.5) : vidée à la déconnexion. */
  private readonly file = inject(HorsLigneService);

  private readonly utilisateurSig = signal<SessionUtilisateur | null>(null);
  private readonly pretSig = signal(false);
  /** Vrai si la session affichée vient du cache local (API injoignable). */
  private readonly sessionLocaleSig = signal(false);
  /** État réseau du navigateur (événements `online` / `offline`). */
  private readonly enLigneSig = signal(typeof navigator === 'undefined' ? true : navigator.onLine);

  /** Utilisateur connecté (null si invité). */
  readonly utilisateur = this.utilisateurSig.asReadonly();

  /** Vrai une fois le premier appel /auth/moi terminé (gardes). */
  readonly pret = this.pretSig.asReadonly();

  /** Session servie depuis le cache local : les écritures exigent le réseau. */
  readonly sessionLocale = this.sessionLocaleSig.asReadonly();

  /** Bandeau hors-ligne : réseau coupé ou session en cache. */
  readonly horsLigne = computed(() => !this.enLigneSig() || this.sessionLocaleSig());

  /** Rôle actif : `active_role`, sinon rôle choisi mémorisé, sinon premier rôle. */
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

  constructor() {
    if (typeof window === 'undefined') return;
    window.addEventListener('online', () => {
      this.enLigneSig.set(true);
      // Retour du réseau pendant une session en cache : on revalide le cookie.
      if (this.sessionLocaleSig() && this.pretSig()) {
        void this.rechargerSession();
      }
    });
    window.addEventListener('offline', () => this.enLigneSig.set(false));
  }

  /**
   * Recharge la session depuis /api/auth/moi. Attendu par les gardes :
   * résout après le premier appel. Idempotent. Si le réseau est absent,
   * retombe sur le profil en cache local (hors-ligne).
   */
  async initialiser(): Promise<void> {
    if (this.pretSig()) return;
    try {
      const utilisateur = await firstValueFrom(this.api.moi());
      this.poserSession(utilisateur.user);
    } catch (erreur) {
      this.retablirSessionLocale(erreur);
    } finally {
      this.pretSig.set(true);
    }
  }

  async connexion(email: string, motDePasse: string): Promise<void> {
    const reponse = await firstValueFrom(this.api.connexion(email, motDePasse));
    this.poserSession(reponse.user);
    this.pretSig.set(true);
  }

  /** Recharge la session (après mise à jour du profil côté API). */
  async rechargerSession(): Promise<void> {
    try {
      const reponse = await firstValueFrom(this.api.moi());
      this.poserSession(reponse.user);
    } catch (erreur) {
      this.retablirSessionLocale(erreur);
    }
  }

  async choisirRole(role: string): Promise<void> {
    await this.api.roleActif(role).toPromise();
    const u = this.utilisateurSig();
    if (u) {
      this.utilisateurSig.set({ ...u, active_role: role });
    }
    this.memoriserRole(role);
  }

  async deconnexion(): Promise<void> {
    try {
      await this.api.deconnexion().toPromise();
    } catch {
      /* hors-ligne : la session locale est fermée quand même */
    }
    this.fermerSessionLocale();
    // Données sensibles (finance, pédagogie…) : cache vidé à la déconnexion,
    // file d'écritures locales comprise — une saisie non partie ne doit pas
    // survivre à la session qui l'a produite. (Une session **expirée**, elle,
    // conserve sa file : c'est `retablirSessionLocale` qui la gère, pas ici.)
    this.purgerCachesPwa();
    await this.file.purger();
  }

  /* ---------------------------------------------------------------
     Session locale (cache hors-ligne)
     --------------------------------------------------------------- */

  private poserSession(user: SessionUtilisateur): void {
    const avecRole = this.restaurerRoleMemorise(user);
    this.utilisateurSig.set(avecRole);
    this.sessionLocaleSig.set(false);
    this.enregistrerProfilLocal(avecRole);
    if (avecRole.active_role) this.memoriserRole(avecRole.active_role);

    // Session fraîche (cookie valable) : le bon moment pour vider la file
    // d'écritures — c'est aussi le déclencheur après une session locale
    // rétablie au retour du réseau (T4.5). Mutex côté service.
    void this.file.synchroniser();
  }

  /**
   * Après une session recréée par le cookie « rester connecté », le rôle actif
   * choisi (stocké en session serveur) a été perdu : on le rétablit depuis le
   * stockage local et, si le réseau est là, on le renvoie au serveur.
   */
  private restaurerRoleMemorise(user: SessionUtilisateur): SessionUtilisateur {
    if (user.active_role) return user;

    const memorise = typeof localStorage === 'undefined' ? null : localStorage.getItem(CLE_ROLE);
    if (!memorise || !user.roles?.includes(memorise)) return user;

    if (this.enLigneSig()) {
      this.api.roleActif(memorise).subscribe({ error: () => {} });
    }
    return { ...user, active_role: memorise };
  }

  private memoriserRole(role: string): void {
    try {
      localStorage.setItem(CLE_ROLE, role);
    } catch {
      /* stockage indisponible (mode privé) */
    }
  }

  /**
   * Retombe sur le profil en cache si — et seulement si — l'erreur vient du
   * réseau (session réelle expirée → 401 → retour à l'écran de connexion).
   */
  private retablirSessionLocale(erreur: unknown): void {
    const statut = (erreur as { status?: unknown } | null)?.status;

    if (statut === 401) {
      this.fermerSessionLocale();
      return;
    }

    if (statut === 0 || statut === undefined || !this.enLigneSig()) {
      const profil = this.profilLocal();
      if (profil) {
        this.utilisateurSig.set(this.restaurerRoleMemorise(profil));
        this.sessionLocaleSig.set(true);
        return;
      }
    }

    this.utilisateurSig.set(null);
    this.sessionLocaleSig.set(false);
  }

  private fermerSessionLocale(): void {
    this.utilisateurSig.set(null);
    this.sessionLocaleSig.set(false);
    try {
      localStorage.removeItem(CLE_PROFIL);
    } catch {
      /* stockage indisponible */
    }
  }

  private enregistrerProfilLocal(user: SessionUtilisateur): void {
    try {
      localStorage.setItem(CLE_PROFIL, JSON.stringify({ date: Date.now(), utilisateur: user }));
    } catch {
      /* stockage indisponible (mode privé) */
    }
  }

  private profilLocal(): SessionUtilisateur | null {
    try {
      const brut = localStorage.getItem(CLE_PROFIL);
      if (!brut) return null;
      const donnees = JSON.parse(brut) as { date?: number; utilisateur?: SessionUtilisateur };
      if (!donnees.utilisateur || typeof donnees.date !== 'number') return null;
      if (Date.now() - donnees.date > DUREE_CACHE_MS) {
        localStorage.removeItem(CLE_PROFIL);
        return null;
      }
      return donnees.utilisateur;
    } catch {
      return null;
    }
  }

  /** Vide les caches du service worker (pages et données en cache 48 h). */
  private purgerCachesPwa(): void {
    if (typeof caches === 'undefined') return;
    caches
      .keys()
      .then((cles) => Promise.all(cles.map((cle) => caches.delete(cle))))
      .catch(() => {});
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
