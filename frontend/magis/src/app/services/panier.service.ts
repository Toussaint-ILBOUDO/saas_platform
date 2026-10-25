import { Injectable, computed, effect, signal } from '@angular/core';
import { LignePanier } from '../models';

const CLE_STOCKAGE = 'mpc.panier.v1';

/**
 * Panier boutique persistant (localStorage).
 *
 * Le compteur expose le nombre de LIGNES DISTINCTES (pas la somme des
 * quantités) — règle UX décidée avec le client. Les prix ne sont JAMAIS
 * pris du navigateur pour la commande : le serveur recalcule tout
 * (`/public/panier/devis`, puis `/public/commandes`).
 */
@Injectable({ providedIn: 'root' })
export class PanierService {
  private readonly lignesSig = signal<LignePanier[]>(this.charger());

  /** Lignes du panier. */
  readonly lignes = this.lignesSig.asReadonly();

  /** Nombre de lignes distinctes (badge header / bouton panier). */
  readonly nombreLignes = computed(() => this.lignesSig().length);

  /** Somme des quantités (affichage « X articles »). */
  readonly nombreArticles = computed(() =>
    this.lignesSig().reduce((somme, ligne) => somme + ligne.quantite, 0)
  );

  /** Sous-total indicatif (le serveur revalide au devis/commande). */
  readonly sousTotal = computed(() =>
    this.lignesSig().reduce((somme, ligne) => somme + ligne.prix_vente * ligne.quantite, 0)
  );

  /** Identifiants et quantités pour le devis / la commande serveur. */
  readonly lignesApi = computed(() =>
    this.lignesSig().map((ligne) => ({ produit_id: ligne.produit_id, quantite: ligne.quantite }))
  );

  constructor() {
    // Persistance automatique à chaque mutation du signal.
    effect(() => {
      const lignes = this.lignesSig();
      try {
        localStorage.setItem(CLE_STOCKAGE, JSON.stringify(lignes));
      } catch {
        /* stockage plein / mode privé : le panier reste en mémoire */
      }
    });
  }

  ajouter(produit: {
    id: number;
    nom: string;
    slug?: string;
    prix_vente?: number;
    image_url?: string | null;
  }): void {
    const prix = produit.prix_vente ?? 0;
    const existante = this.lignesSig().find((l) => l.produit_id === produit.id);

    if (existante) {
      this.lignesSig.update((lignes) =>
        lignes.map((l) =>
          l.produit_id === produit.id ? { ...l, quantite: Math.min(999, l.quantite + 1) } : l
        )
      );
      return;
    }

    this.lignesSig.update((lignes) => [
      ...lignes,
      {
        produit_id: produit.id,
        nom: produit.nom,
        slug: produit.slug,
        prix_vente: prix,
        image_url: produit.image_url ?? null,
        quantite: 1,
      },
    ]);
  }

  changerQuantite(produitId: number, quantite: number): void {
    if (quantite <= 0) {
      this.retirer(produitId);
      return;
    }
    this.lignesSig.update((lignes) =>
      lignes.map((l) => (l.produit_id === produitId ? { ...l, quantite: Math.min(999, quantite) } : l))
    );
  }

  retirer(produitId: number): void {
    this.lignesSig.update((lignes) => lignes.filter((l) => l.produit_id !== produitId));
  }

  vider(): void {
    this.lignesSig.set([]);
  }

  private charger(): LignePanier[] {
    try {
      const brut = localStorage.getItem(CLE_STOCKAGE);
      if (!brut) return [];
      const parsed = JSON.parse(brut) as LignePanier[];
      if (!Array.isArray(parsed)) return [];
      return parsed.filter(
        (l) => l && typeof l.produit_id === 'number' && typeof l.quantite === 'number' && l.quantite > 0
      );
    } catch {
      return [];
    }
  }
}
