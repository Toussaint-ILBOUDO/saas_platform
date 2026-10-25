import { Injectable, signal } from '@angular/core';
import { Subject } from 'rxjs';
import { messageErreurApi } from './messages';

/**
 * Une écriture mise en file (T4.5). La `sequence` est la clef auto-incrémentée
 * du magasin : l'ordre de saisie **est** l'ordre d'envoi, ce qui conserve la
 * chronologie (créer puis corriger une séance hors-ligne reste cohérent).
 */
export interface EntreeFile {
  sequence?: number;
  url: string;
  methode: 'POST' | 'PUT' | 'DELETE';
  corps: unknown;
  /** Libellé affiché dans le bandeau (« Séance du 2026-10-14 »). */
  libelle: string;
  horodatage: number;
}

/** Une écriture refusée au retour du réseau : la saisie est perdue, l'avertissement reste. */
export interface EchecFile {
  libelle: string;
  message: string;
}

const NOM_BASE = 'mpc-file-ecritures';
const MAGASIN = 'file';
/** Reprise automatique après une panne serveur / un réseau qui retombe. */
const DELAI_REPRISE_MS = 30_000;
/** Bandeau « Synchronisé » : visible le temps d'être lu, pas plus. */
const DELAI_FLASH_MS = 6000;

type Issue = 'envoyee' | 'refusee' | 'ignoree' | 'session' | 'reseau';

/**
 * File d'écritures hors-ligne (T4.5) — saisie du cahier de texte (périmètre
 * Q9 : lecture + saisie cahier de texte uniquement).
 *
 * Une écriture refusée par le réseau **n'est pas perdue** : elle part en file
 * IndexedDB et repart, dans l'ordre, au retour de la connexion. L'idempotence
 * est portée par `uuid_client` côté saisie (D-060) : une réémission après une
 * réponse perdue est une reprise (200), jamais un doublon.
 *
 * Politique de statuts à la synchro :
 *  - **2xx** → envoyée, retirée ;
 *  - **401 / 419** (session expirée) → on **conserve** la file (spec : une
 *    session expirée ne détruit pas les saisies) — la reprise viendra du
 *    déclencheur suivant ;
 *  - **404 sur une suppression** → le but est atteint, retirée silencieusement ;
 *  - **autres 4xx** (validation, période close, conflit d'`uuid_client`…) →
 *    refus **définitif** : retirée et **avertissement** (conflit = « dernière
 *    écriture avec avertissement », Q-A4) — laisser une saisie refusée bloquer
 *    la file serait pire que la perdre ;
 *  - **5xx / réseau tombé** → on conserve **et** on retente dans 30 s : une
 *    panne serveur ne doit pas figurer un refus.
 */
@Injectable({ providedIn: 'root' })
export class HorsLigneService {
  /** Écritures en attente d'envoi. */
  private readonly enAttenteSig = signal(0);
  /** Une synchro est en cours (mutex : deux déclencheurs ne se doublent pas). */
  private readonly enSynchroSig = signal(false);
  /** Écritures refusées au retour du réseau, en attente d'être lues. */
  private readonly echecsSig = signal<EchecFile[]>([]);
  /** Bandeau « Synchronisé » (flash quelques secondes). */
  private readonly synchroniseSig = signal(false);

  readonly enAttente = this.enAttenteSig.asReadonly();
  readonly enSynchro = this.enSynchroSig.asReadonly();
  readonly echecs = this.echecsSig.asReadonly();
  readonly synchronise = this.synchroniseSig.asReadonly();

  /** Émis quand la file a été **entièrement** envoyée : l'écran se relit. */
  readonly synchroTerminee = new Subject<void>();

  private base?: Promise<IDBDatabase>;
  private timerReprise: ReturnType<typeof setTimeout> | null = null;
  private timerFlash: ReturnType<typeof setTimeout> | null = null;

  constructor() {
    if (typeof window === 'undefined') return;

    // Le bandeau doit afficher la file héritée d'une session précédente
    // (session expirée → file conservée) dès le premier rendu.
    void this.majCompteur();

    window.addEventListener('online', () => {
      // Délai laissé à `auth.service` pour revalider son cookie : une
      // synchro lancée avec une session morte s'arrêterait sur un 401.
      window.setTimeout(() => void this.synchroniser(), 1500);
    });
    window.addEventListener('offline', () => {
      if (this.timerReprise) clearTimeout(this.timerReprise);
      this.timerReprise = null;
    });

    // Reprise des saisies enregistrées avant la dernière fermeture de l'app.
    window.setTimeout(() => void this.synchroniser(), 2500);
  }

  /* ---------------------------------------------------------------
     Écriture en file
     --------------------------------------------------------------- */

  /**
   * Ajoute une écriture à la file. Rejette si IndexedDB est indisponible
   * (mode privé) — l'intercepteur laissera alors l'erreur réseau originale
   * remonter, ce qui est plus honnête qu'un succès silencieusement perdu.
   */
  async mettreEnFile(entree: Omit<EntreeFile, 'sequence'>): Promise<void> {
    const base = await this.ouvrir();

    await new Promise<void>((resolve, reject) => {
      const transaction = base.transaction(MAGASIN, 'readwrite');
      transaction.objectStore(MAGASIN).add({ ...entree, horodatage: Date.now() });
      transaction.oncomplete = () => resolve();
      transaction.onerror = () => reject(transaction.error ?? new Error('File hors-ligne inaccessible.'));
      transaction.onabort = () => reject(transaction.error ?? new Error('Écriture hors-ligne annulée.'));
    });

    await this.majCompteur();
  }

  /* ---------------------------------------------------------------
     Synchro au retour de connexion
     --------------------------------------------------------------- */

  /** Vide la file dans l'ordre. Idempotent (mutex) et sans effet sans réseau. */
  async synchroniser(): Promise<void> {
    if (this.enSynchroSig()) return;
    if (typeof navigator === 'undefined' || !navigator.onLine) return;

    this.enSynchroSig.set(true);
    if (this.timerReprise) {
      clearTimeout(this.timerReprise);
      this.timerReprise = null;
    }

    let envoyees = 0;
    let sessionMorte = false;

    try {
      // `getAll()` retourne en ordre de clé croissante : l'ordre de saisie.
      const entrees = await this.lireTout();

      for (const entree of entrees) {
        const issue = await this.remettre(entree);
        if (issue === 'envoyee') envoyees++;
        if (issue === 'reseau') break;
        if (issue === 'session') {
          sessionMorte = true;
          break;
        }
      }
    } catch {
      // IndexedDB fermée (purge entre-temps) : rien à défendre ici.
    } finally {
      this.enSynchroSig.set(false);
      await this.majCompteur();
    }

    // Session expirée : la file **se conserve** (spec), le prochain déclencheur
    // reprendra — c'est lui qui dit qu'une session fraîche est de retour.
    if (sessionMorte) return;

    // File encore garnie : réseau tombé en route, ou écriture survenue pendant
    // la synchro — elle ne doit pas attendre un événement qui peut ne pas venir.
    if (this.enAttenteSig() > 0) {
      this.programmerProchainEssai();
      return;
    }

    if (envoyees > 0) this.annoncerSynchro();
  }

  /** Renvoie une écriture. Décide, par statut, si la file continue ou s'arrête. */
  private async remettre(entree: EntreeFile): Promise<Issue> {
    let reponse: globalThis.Response;

    // Sans plafond, une synchro pourrait rester suspendue sur un réseau
    // « présent mais captif » : `enSynchro` bloquerait alors tous les déclencheurs.
    const controleur = new AbortController();
    const minuteur = setTimeout(() => controleur.abort(), 15_000);

    try {
      reponse = await fetch(entree.url, {
        method: entree.methode,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: entree.corps == null ? undefined : JSON.stringify(entree.corps),
        // Session : cookie de même origine, celui-là même qui a posé la saisie.
        credentials: 'same-origin',
        signal: controleur.signal,
      });
    } catch {
      return 'reseau';
    } finally {
      clearTimeout(minuteur);
    }

    if (reponse.ok) {
      await this.retirer(entree);
      return 'envoyee';
    }

    // Session expirée : la file se conserve, le prochain déclencheur reprend.
    if (reponse.status === 401 || reponse.status === 419) return 'session';

    // La séance a déjà disparu ailleurs : supprimer deux fois le même chose….
    if (reponse.status === 404 && entree.methode === 'DELETE') {
      await this.retirer(entree);
      return 'ignoree';
    }

    // Erreur serveur transitoire : on retente, on ne condamne pas la saisie.
    if (reponse.status >= 500) return 'reseau';

    // Refus définitif (4xx) : retirée + avertissement, sans bloquer la suite.
    const message = await this.messageDe(reponse);
    await this.retirer(entree);
    this.echecsSig.update((liste) => [...liste, { libelle: entree.libelle, message }].slice(-5));
    return 'refusee';
  }

  /** Traduit la réponse de refus (format `{message, code, erreurs}`) en phrase. */
  private async messageDe(reponse: globalThis.Response): Promise<string> {
    const corps = await reponse.json().catch(() => null);
    return messageErreurApi({ status: reponse.status, error: corps });
  }

  private annoncerSynchro(): void {
    this.synchroniseSig.set(true);
    this.synchroTerminee.next();
    if (this.timerFlash) clearTimeout(this.timerFlash);
    this.timerFlash = setTimeout(() => this.synchroniseSig.set(false), DELAI_FLASH_MS);
  }

  /** Une synchro interrompue par le réseau ou le serveur sera rejouée. */
  private programmerProchainEssai(): void {
    if (typeof window === 'undefined') return;
    if (typeof navigator !== 'undefined' && !navigator.onLine) return; // l'événement `online` s'en charge
    if (this.timerReprise) return;

    this.timerReprise = setTimeout(() => {
      this.timerReprise = null;
      void this.synchroniser();
    }, DELAI_REPRISE_MS);
  }

  /** Ferme l'avertissement des écritures refusées (l'utilisateur a lu). */
  ignorerEchecs(): void {
    this.echecsSig.set([]);
  }

  /* ---------------------------------------------------------------
     Purge (déconnexion — la session expirée, elle, conserve la file)
     --------------------------------------------------------------- */

  async purger(): Promise<void> {
    // Une synchro en vol recréerait la base une fois supprimée : on la laisse
    // finir (garde-fou : un envoi pendu ne doit pas retenir la déconnexion).
    const depart = Date.now();
    while (this.enSynchroSig() && Date.now() - depart < 3000) {
      await new Promise((fini) => setTimeout(fini, 50));
    }

    if (this.timerReprise) clearTimeout(this.timerReprise);
    if (this.timerFlash) clearTimeout(this.timerFlash);
    this.timerReprise = null;
    this.timerFlash = null;

    this.enAttenteSig.set(0);
    this.echecsSig.set([]);
    this.synchroniseSig.set(false);
    this.base = undefined;

    if (typeof indexedDB === 'undefined') return;
    await new Promise<void>((resolve) => {
      const demande = indexedDB.deleteDatabase(NOM_BASE);
      demande.onsuccess = () => resolve();
      demande.onerror = () => resolve(); // stockage indisponible : rien à défendre
      demande.onblocked = () => resolve();
    });
  }

  /* ---------------------------------------------------------------
     Magasin
     --------------------------------------------------------------- */

  private ouvrir(): Promise<IDBDatabase> {
    if (this.base) return this.base;

    this.base = new Promise<IDBDatabase>((resolve, reject) => {
      if (typeof indexedDB === 'undefined') {
        reject(new Error('Stockage local indisponible.'));
        return;
      }
      const demande = indexedDB.open(NOM_BASE, 1);
      demande.onupgradeneeded = () => {
        if (!demande.result.objectStoreNames.contains(MAGASIN)) {
          demande.result.createObjectStore(MAGASIN, { keyPath: 'sequence', autoIncrement: true });
        }
      };
      demande.onsuccess = () => resolve(demande.result);
      demande.onerror = () => reject(demande.error ?? new Error('File hors-ligne inaccessible.'));
      demande.onblocked = () => reject(new Error('File hors-ligne verrouillée.'));
    });

    // Un échec (mode privé) doit pouvoir être retenté à la saisie suivante.
    this.base.catch(() => {
      this.base = undefined;
    });

    return this.base;
  }

  private async lireTout(): Promise<EntreeFile[]> {
    const base = await this.ouvrir();

    return new Promise<EntreeFile[]>((resolve, reject) => {
      const demande = base.transaction(MAGASIN, 'readonly').objectStore(MAGASIN).getAll();
      demande.onsuccess = () => resolve((demande.result ?? []) as EntreeFile[]);
      demande.onerror = () => reject(demande.error ?? new Error('Lecture de la file impossible.'));
    });
  }

  private async retirer(entree: EntreeFile): Promise<void> {
    if (entree.sequence === undefined) return;
    const base = await this.ouvrir();

    await new Promise<void>((resolve, reject) => {
      const transaction = base.transaction(MAGASIN, 'readwrite');
      transaction.objectStore(MAGASIN).delete(entree.sequence as number);
      transaction.oncomplete = () => resolve();
      transaction.onerror = () => reject(transaction.error ?? new Error('Retrait impossible.'));
    });
  }

  private async majCompteur(): Promise<void> {
    try {
      const base = await this.ouvrir();
      const nombre = await new Promise<number>((resolve, reject) => {
        const demande = base.transaction(MAGASIN, 'readonly').objectStore(MAGASIN).count();
        demande.onsuccess = () => resolve(demande.result);
        demande.onerror = () => reject(demande.error);
      });
      this.enAttenteSig.set(nombre);
    } catch {
      this.enAttenteSig.set(0);
    }
  }
}
