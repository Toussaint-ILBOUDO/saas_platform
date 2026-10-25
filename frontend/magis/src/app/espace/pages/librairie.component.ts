import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { messageErreurApi } from '../../services/messages';
import {
  CommandeDetail,
  CommandeSynthese,
  DashboardLibrairie,
  MetaPage,
  ParametresLibrairie,
  Produit,
} from '../../models';

type Onglet = 'dashboard' | 'produits' | 'commandes' | 'parametres';

/**
 * Console librairie : dashboard, catalogue, commandes et paramètres (dont la
 * délégation admin→librairie, éditable uniquement par `gestionnaire_librairie`).
 */
@Component({
  selector: 'app-librairie-console',
  imports: [CommonModule, FormsModule, RouterLink],
  styles: [
    `
      .barre {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        flex-wrap: wrap;
        margin-bottom: 1.1rem;
      }
      .barre h1 {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        margin: 0;
      }
      .onglets {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
        margin-bottom: 1.2rem;
      }
      .onglets button {
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        border-radius: 999px;
        padding: 0.45rem 1rem;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
      }
      .onglets button._actif {
        background: var(--mpc-bleu);
        border-color: var(--mpc-bleu);
        color: #fff;
      }
      .kpis {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.8rem;
        margin-bottom: 1.4rem;
      }
      .kpi {
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 14px;
        padding: 0.9rem 1rem;
      }
      .kpi span {
        display: block;
        color: var(--mpc-texte-doux);
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
      }
      .kpi strong {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .carte {
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 14px;
        padding: 1.1rem;
        margin-bottom: 1rem;
      }
      .carte h2 {
        font-size: 1.02rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        margin: 0 0 0.8rem;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
      }
      th,
      td {
        text-align: left;
        padding: 0.5rem 0.35rem;
        border-bottom: 1px solid var(--mpc-bordure);
        vertical-align: middle;
      }
      th {
        color: var(--mpc-texte-doux);
        font-weight: 600;
        font-size: 0.82rem;
      }
      .vignette-produit {
        width: 3.4rem;
        height: 2.6rem;
        background: var(--mpc-surface-teintee);
        border: 1px solid var(--mpc-bordure);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: var(--mpc-texte-doux);
      }
      .vignette-produit img {
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        object-fit: contain;
        padding: 0.15rem;
      }
      .apercu-image {
        margin-top: 0.45rem;
      }
      .apercu-image img {
        width: 84px;
        height: 62px;
        box-sizing: border-box;
        object-fit: contain;
        background: #fff;
        border: 1px solid var(--mpc-bordure);
        border-radius: 8px;
        padding: 3px;
      }
      .statut {
        display: inline-flex;
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
        font-size: 0.78rem;
        font-weight: 700;
      }
      .statut-en_attente {
        background: #fff4e0;
        color: #a86500;
      }
      .statut-confirmee,
      .statut-en_preparation {
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
      }
      .statut-livree {
        background: #e8f6ee;
        color: #1a7f4e;
      }
      .statut-annulee {
        background: #fdecea;
        color: #b3261e;
      }
      .btn {
        border: 1px solid var(--mpc-bordure);
        background: var(--mpc-fond);
        color: var(--mpc-bleu);
        border-radius: 999px;
        padding: 0.4rem 0.85rem;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        gap: 0.3rem;
        align-items: center;
      }
      .btn:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .btn-primaire {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .btn-primaire:hover {
        background: var(--mpc-primaire-fonce);
        color: #fff;
      }
      .btn-danger {
        color: #b3261e;
      }
      label {
        display: block;
        font-size: 0.84rem;
        font-weight: 600;
        color: var(--mpc-bleu);
        margin-bottom: 0.25rem;
      }
      input[type='text'],
      input[type='number'],
      input[type='search'],
      input[type='file'],
      textarea,
      select {
        width: 100%;
        border: 1px solid var(--mpc-bordure);
        border-radius: 10px;
        padding: 0.55rem 0.75rem;
        font-size: 0.9rem;
        background: #fff;
        margin-bottom: 0.8rem;
        outline: none;
      }
      input:focus,
      textarea:focus,
      select:focus {
        border-color: var(--mpc-primaire);
      }
      .grille-form {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 0 1rem;
      }
      .check {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.9rem;
        font-weight: 600;
        color: var(--mpc-bleu);
      }
      .check input {
        width: auto;
        margin: 0;
      }
      .erreur {
        background: #fdecea;
        color: #b3261e;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
      }
      .succes {
        background: #e8f6ee;
        color: #1a7f4e;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        font-weight: 600;
      }
      .vide {
        color: var(--mpc-texte-doux);
        text-align: center;
        padding: 1.5rem;
      }
      .filtres-ligne {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 0.9rem;
      }
      .filtres-ligne input,
      .filtres-ligne select {
        margin-bottom: 0;
        width: auto;
        min-width: 10rem;
      }
      .detail-commande {
        background: var(--mpc-surface-teintee);
        border-radius: 12px;
        padding: 0.9rem;
        margin-top: 0.7rem;
      }
      .actions-ligne {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
      }
      @media (max-width: 767px) {
        table {
          display: block;
          overflow-x: auto;
        }
      }
    `,
  ],
  template: `
    <div class="barre">
      <h1><i class="bi bi-shop"></i> Librairie</h1>
      <a class="btn" routerLink="/boutique" target="_blank">
        <i class="bi bi-eye"></i> Voir la boutique
      </a>
    </div>

    <div class="onglets">
      <button type="button" [class._actif]="onglet() === 'dashboard'" (click)="allerA('dashboard')">Dashboard</button>
      <button type="button" [class._actif]="onglet() === 'produits'" (click)="allerA('produits')">Produits</button>
      <button type="button" [class._actif]="onglet() === 'commandes'" (click)="allerA('commandes')">Commandes</button>
      <button type="button" [class._actif]="onglet() === 'parametres'" (click)="allerA('parametres')">Paramètres</button>
    </div>

    <p class="erreur" *ngIf="erreur()">{{ erreur() }}</p>
    <p class="succes" *ngIf="succes()">{{ succes() }}</p>

    <!-- ======================= DASHBOARD ======================= -->
    <ng-container *ngIf="onglet() === 'dashboard'">
      <div class="kpis" *ngIf="dashboard()">
        <div class="kpi"><span>Produits</span><strong>{{ dashboard()!.produits }}</strong></div>
        <div class="kpi"><span>Publiés</span><strong>{{ dashboard()!.produits_publies }}</strong></div>
        <div class="kpi"><span>Archivés</span><strong>{{ dashboard()!.produits_archives }}</strong></div>
        <div class="kpi"><span>Stock faible</span><strong>{{ dashboard()!.produits_stock_faible }}</strong></div>
        <div class="kpi"><span>Commandes</span><strong>{{ dashboard()!.commandes }}</strong></div>
        <div class="kpi"><span>Commandes du jour</span><strong>{{ dashboard()!.commandes_du_jour }}</strong></div>
        <div class="kpi" *ngIf="dashboard()!.ca_accessible">
          <span>Chiffre d'affaires</span>
          <strong>{{ formaterPrix(dashboard()!.chiffre_affaires) }}</strong>
        </div>
        <div class="kpi" *ngIf="dashboard()!.ca_accessible">
          <span>Frais livraison</span>
          <strong>{{ formaterPrix(dashboard()!.frais_livraison_encaisses) }}</strong>
        </div>
        <div class="kpi" *ngIf="!dashboard()!.ca_accessible">
          <span>Chiffre d'affaires</span>
          <strong style="font-size:.95rem">Masqué (délégation)</strong>
        </div>
      </div>

      <div class="carte" *ngIf="dashboard()">
        <h2>Dernières commandes</h2>
        <p class="vide" *ngIf="!dashboard()!.dernieres_commandes?.length">Aucune commande récente.</p>
        <table *ngIf="dashboard()!.dernieres_commandes?.length">
          <thead>
            <tr><th>Référence</th><th>Client</th><th>Statut</th><th>Total</th></tr>
          </thead>
          <tbody>
            <tr *ngFor="let c of dashboard()!.dernieres_commandes">
              <td>
                <button type="button" class="btn" (click)="ouvrirCommande(c.reference)">{{ c.reference }}</button>
              </td>
              <td>{{ c.nom_client }}</td>
              <td><span class="statut" [ngClass]="'statut-' + c.statut">{{ c.statut }}</span></td>
              <td>{{ formaterPrix(c.montant_total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </ng-container>

    <!-- ======================= PRODUITS ======================= -->
    <ng-container *ngIf="onglet() === 'produits'">
      <div class="filtres-ligne">
        <input type="search" name="recherche" placeholder="Rechercher…" [(ngModel)]="rechercheProduit" (ngModelChange)="chargerProduits()" />
        <button type="button" class="btn btn-primaire" (click)="basculerFormProduit()">
          <i class="bi bi-plus-lg"></i> {{ formProduitOuvert ? 'Fermer' : 'Nouveau produit' }}
        </button>
      </div>

      <div class="carte" *ngIf="formProduitOuvert">
        <h2>{{ produitEditeId ? 'Modifier le produit' : 'Créer un produit' }}</h2>

        <div *ngIf="categories().length === 0" class="succes" style="margin-bottom:1rem">
          <p style="margin:0 0 .6rem">Aucune catégorie pour l'instant. Créez-en une avant d'ajouter un produit :</p>
          <div class="filtres-ligne" style="margin-bottom:0">
            <input type="text" name="nom-cat" placeholder="Nom de la catégorie"
                   [(ngModel)]="nomNouvelleCategorie" style="margin-bottom:0" />
            <button type="button" class="btn btn-primaire" (click)="creerCategorie()"
                    [disabled]="!nomNouvelleCategorie.trim() || envoiCategorie()">
              <i class="bi bi-plus-lg"></i> Créer
            </button>
          </div>
        </div>

        <div class="grille-form">
          <div>
            <label for="pn">Nom *</label>
            <input id="pn" type="text" name="pn" [(ngModel)]="formProduit.nom" />
          </div>
          <div>
            <label for="pc">Catégorie *</label>
            <select id="pc" name="pc" [(ngModel)]="formProduit.categorie_id">
              <option [ngValue]="null" disabled>Sélectionner…</option>
              <option *ngFor="let c of categories()" [ngValue]="c.id">{{ c.nom }}</option>
            </select>
            <button type="button" class="btn" style="margin-top:-0.4rem;margin-bottom:0.6rem"
                    *ngIf="categories().length > 0" (click)="ajouterCategorieRapide()">
              <i class="bi bi-plus-lg"></i> Nouvelle catégorie
            </button>
            <div *ngIf="nouvelleCategorieRapide" class="filtres-ligne" style="margin-bottom:0.6rem">
              <input type="text" name="nom-cat2" placeholder="Nom"
                     [(ngModel)]="nomNouvelleCategorie" style="margin-bottom:0" />
              <button type="button" class="btn btn-primaire" (click)="creerCategorie()"
                      [disabled]="!nomNouvelleCategorie.trim() || envoiCategorie()">
                OK
              </button>
              <button type="button" class="btn" (click)="nouvelleCategorieRapide = false">Annuler</button>
            </div>
          </div>
          <div>
            <label for="pr">Référence</label>
            <input id="pr" type="text" name="pr" [(ngModel)]="formProduit.reference" />
          </div>
          <div>
            <label for="pp">Prix (FCFA) *</label>
            <input id="pp" type="number" name="pp" min="0" [(ngModel)]="formProduit.prix" />
          </div>
          <div>
            <label for="ppp">Prix promo</label>
            <input id="ppp" type="number" name="ppp" min="0" [(ngModel)]="formProduit.prix_promo" />
          </div>
          <div>
            <label for="ps">Stock</label>
            <input id="ps" type="number" name="ps" min="0" [(ngModel)]="formProduit.stock" />
          </div>
          <div>
            <label for="pi">Image</label>
            <input id="pi" type="file" name="pi" accept="image/*" (change)="surFichier($event)" />
            <div class="apercu-image" *ngIf="apercuFichierLocal || (produitEditeId && imageProduitExistante)">
              <img [src]="apercuFichierLocal || imageProduitExistante" alt="Aperçu de l'image" />
            </div>
          </div>
        </div>
        <label for="pd">Description</label>
        <textarea id="pd" name="pd" rows="3" [(ngModel)]="formProduit.description"></textarea>
        <div class="actions-ligne">
          <button type="button" class="btn btn-primaire" (click)="enregistrerProduit()" [disabled]="envoiProduit()">
            <i class="bi bi-save"></i> Enregistrer
          </button>
          <button type="button" class="btn" *ngIf="produitEditeId" (click)="reinitialiserFormProduit()">
            Annuler
          </button>
        </div>
      </div>

      <div class="carte">
        <h2>Catalogue</h2>
        <p class="vide" *ngIf="produits().length === 0">Aucun produit.</p>
        <table *ngIf="produits().length > 0">
          <thead>
            <tr>
              <th>Image</th>
              <th>Nom</th>
              <th>Réf.</th>
              <th>Prix</th>
              <th>Stock</th>
              <th>État</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr *ngFor="let p of produits()">
              <td>
                <div class="vignette-produit">
                  <img *ngIf="p.image_url" [src]="p.image_url" [alt]="p.nom" loading="lazy" />
                  <i *ngIf="!p.image_url" class="bi bi-image"></i>
                </div>
              </td>
              <td>
                <strong>{{ p.nom }}</strong>
                <div style="font-size:.78rem;color:var(--mpc-texte-doux)" *ngIf="p.categorie">{{ p.categorie }}</div>
              </td>
              <td>{{ p.reference || '—' }}</td>
              <td>{{ formaterPrix(p.prix_vente ?? p.prix_promo ?? p.prix) }}</td>
              <td>{{ p.stock ?? '—' }}</td>
              <td>
                <span class="statut" [ngClass]="p.est_archive ? 'statut-annulee' : p.is_active ? 'statut-livree' : 'statut-en_attente'">
                  {{ p.est_archive ? 'Archivé' : p.is_active ? 'Publié' : 'Brouillon' }}
                </span>
              </td>
              <td>
                <div class="actions-ligne">
                  <button type="button" class="btn" (click)="editerProduit(p)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button type="button" class="btn" (click)="basculerProduit(p)">
                    <i class="bi" [class.bi-eye]="!p.is_active" [class.bi-eye-slash]="p.is_active"></i>
                  </button>
                  <button type="button" class="btn btn-danger" (click)="supprimerProduit(p)">
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </ng-container>

    <!-- ======================= COMMANDES ======================= -->
    <ng-container *ngIf="onglet() === 'commandes'">
      <div class="filtres-ligne">
        <select name="statut" [(ngModel)]="filtreStatut" (ngModelChange)="chargerCommandes()">
          <option value="">Tous les statuts</option>
          <option value="en_attente">En attente</option>
          <option value="confirmee">Confirmée</option>
          <option value="en_preparation">En préparation</option>
          <option value="livree">Livrée</option>
          <option value="annulee">Annulée</option>
        </select>
      </div>

      <div class="carte">
        <h2>Commandes</h2>
        <p class="vide" *ngIf="commandes().length === 0">Aucune commande.</p>
        <table *ngIf="commandes().length > 0">
          <thead>
            <tr>
              <th>Référence</th>
              <th>Client</th>
              <th>Statut</th>
              <th>Total</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <ng-container *ngFor="let c of commandes()">
              <tr>
                <td>
                  <button type="button" class="btn" (click)="basculerCommande(c.reference)">{{ c.reference }}</button>
                  <div style="font-size:.78rem;color:var(--mpc-texte-doux)" *ngIf="c.date">{{ c.date }}</div>
                </td>
                <td>
                  {{ c.prenom_client || '' }} {{ c.nom_client }}
                  <div style="font-size:.78rem">{{ c.is_livraison ? 'Livraison' : 'Retrait' }}</div>
                </td>
                <td>
                  <span class="statut" [ngClass]="'statut-' + c.statut">{{ c.statut_libelle || c.statut }}</span>
                  <div *ngIf="c.annulation_demandee" style="font-size:.75rem;color:#a86500">
                    <i class="bi bi-hourglass-split"></i> Annulation demandée
                  </div>
                </td>
                <td>{{ formaterPrix(c.montant_total) }}</td>
                <td>
                  <div class="actions-ligne" *ngIf="detailCommande() && detailCommande()!.reference === c.reference">
                    <a class="btn" [href]="pdfAdmin(c.reference)"><i class="bi bi-file-earmark-pdf"></i></a>
                    <select name="statut-{{ c.reference }}"
                            [(ngModel)]="nouveauStatut"
                            (ngModelChange)="majStatut(c.reference)">
                      <option value="">Changer…</option>
                      <option value="confirmee">Confirmée</option>
                      <option value="en_preparation">En préparation</option>
                      <option value="livree">Livrée</option>
                      <option value="annulee">Annulée</option>
                    </select>
                  </div>
                </td>
              </tr>
              <tr *ngIf="detailCommande() && detailCommande()!.reference === c.reference">
                <td colspan="5">
                  <div class="detail-commande">
                    <table>
                      <thead>
                        <tr><th>Article</th><th>Qté</th><th>Sous-total</th></tr>
                      </thead>
                      <tbody>
                        <tr *ngFor="let l of detailCommande()!.lignes">
                          <td>{{ l.produit }}</td>
                          <td>{{ l.quantite }}</td>
                          <td>{{ formaterPrix(l.sous_total) }}</td>
                        </tr>
                      </tbody>
                    </table>
                    <p style="margin:.6rem 0 .3rem;font-size:.88rem">
                      <strong>Client :</strong> {{ detailCommande()!.prenom_client || '' }}
                      {{ detailCommande()!.nom_client }} · {{ detailCommande()!.telephone_client }}
                    </p>
                    <p style="margin:0 0 .6rem;font-size:.88rem" *ngIf="detailCommande()!.adresse_livraison">
                      <strong>Adresse :</strong> {{ detailCommande()!.adresse_livraison }}
                      <span *ngIf="detailCommande()!.quartier"> · {{ detailCommande()!.quartier }}</span>
                    </p>
                    <label for="note">Note interne</label>
                    <textarea id="note" name="note" rows="2" [(ngModel)]="noteInterne"></textarea>
                    <button type="button" class="btn btn-primaire" (click)="enregistrerNote(c.reference)">
                      <i class="bi bi-save"></i> Enregistrer la note
                    </button>
                  </div>
                </td>
              </tr>
            </ng-container>
          </tbody>
        </table>
      </div>
    </ng-container>

    <!-- ======================= PARAMÈTRES ======================= -->
    <ng-container *ngIf="onglet() === 'parametres'">
      <div class="carte" *ngIf="parametres()">
        <h2>Paramètres boutique</h2>
        <p class="erreur" *ngIf="!estLibraire()">
          <i class="bi bi-lock"></i>
          Seul le gestionnaire de librairie peut modifier ces paramètres.
          Vous pouvez les consulter.
        </p>

        <div class="grille-form">
          <div>
            <label for="sigle">Sigle (référence commandes)</label>
            <input id="sigle" type="text" name="sigle" [(ngModel)]="parametresForm.sigle" [disabled]="!estLibraire()" />
          </div>
          <div>
            <label for="frais">Frais de livraison (FCFA)</label>
            <input id="frais" type="number" name="frais" min="0" [(ngModel)]="parametresForm.frais_livraison" [disabled]="!estLibraire()" />
          </div>
        </div>

        <h2 style="margin-top:1.2rem">Délégation admin → librairie</h2>
        <p style="color:var(--mpc-texte-doux);font-size:.88rem;margin-bottom:.8rem">
          Définit ce que l'administrateur du cabinet peut voir dans cette console.
        </p>
        <label class="check">
          <input type="checkbox" name="dd" [(ngModel)]="parametresForm.admin_peut_voir_dashboard" [disabled]="!estLibraire()" />
          L'admin peut voir le dashboard
        </label>
        <label class="check">
          <input type="checkbox" name="dc" [(ngModel)]="parametresForm.admin_peut_voir_ca" [disabled]="!estLibraire()" />
          L'admin peut voir le chiffre d'affaires
        </label>
        <label class="check">
          <input type="checkbox" name="do" [(ngModel)]="parametresForm.admin_peut_voir_commandes" [disabled]="!estLibraire()" />
          L'admin peut voir les commandes
        </label>

        <button type="button" class="btn btn-primaire" *ngIf="estLibraire()" (click)="enregistrerParametres()">
          <i class="bi bi-save"></i> Enregistrer
        </button>
      </div>
    </ng-container>
  `,
})
export class LibrairieConsoleComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly auth = inject(AuthService);

  protected readonly onglet = signal<Onglet>('dashboard');
  protected readonly dashboard = signal<DashboardLibrairie | null>(null);
  protected readonly produits = signal<Produit[]>([]);
  protected readonly commandes = signal<CommandeSynthese[]>([]);
  protected readonly detailCommande = signal<CommandeDetail | null>(null);
  protected readonly parametres = signal<ParametresLibrairie | null>(null);
  protected readonly categories = signal<{ id: number; nom: string; slug?: string }[]>([]);
  protected readonly erreur = signal<string | null>(null);
  protected readonly succes = signal<string | null>(null);

  protected rechercheProduit = '';
  protected filtreStatut = '';
  protected formProduitOuvert = false;
  protected produitEditeId: number | null = null;
  protected readonly envoiProduit = signal(false);
  protected readonly envoiCategorie = signal(false);
  protected nouvelleCategorieRapide = false;
  protected nomNouvelleCategorie = '';
  protected nouveauStatut = '';
  protected noteInterne = '';
  protected fichier: File | null = null;
  protected imageProduitExistante = '';
  protected apercuFichierLocal = '';

  protected formProduit = {
    nom: '',
    reference: '',
    description: '',
    categorie_id: null as number | null,
    prix: null as number | null,
    prix_promo: null as number | null,
    stock: null as number | null,
  };

  protected parametresForm = {
    sigle: '' as string | null,
    frais_livraison: 0,
    admin_peut_voir_dashboard: true,
    admin_peut_voir_ca: true,
    admin_peut_voir_commandes: true,
  };

  ngOnInit(): void {
    this.chargerDashboard();
  }

  protected allerA(o: Onglet): void {
    this.onglet.set(o);
    this.erreur.set(null);
    if (o === 'dashboard') this.chargerDashboard();
    if (o === 'produits') {
      this.chargerProduits();
      this.chargerCategories();
    }
    if (o === 'commandes') this.chargerCommandes();
    if (o === 'parametres') this.chargerParametres();
  }

  protected estLibraire(): boolean {
    return this.auth.roleActif() === 'gestionnaire_librairie';
  }

  protected chargerDashboard(): void {
    this.erreur.set(null);
    this.api.librairieDashboard().subscribe({
      next: (d) => this.dashboard.set(d),
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected chargerProduits(): void {
    this.api.librairieProduits(1, this.rechercheProduit).subscribe({
      next: ({ data }) => this.produits.set(data),
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected chargerCategories(): void {
    this.api.librairieCategories().subscribe({
      next: (cats) => this.categories.set(cats),
      error: () => this.categories.set([]),
    });
  }

  protected ajouterCategorieRapide(): void {
    this.nouvelleCategorieRapide = true;
    this.nomNouvelleCategorie = '';
  }

  protected creerCategorie(): void {
    const nom = this.nomNouvelleCategorie.trim();
    if (!nom || this.envoiCategorie()) return;
    this.envoiCategorie.set(true);
    this.api.librairieCreerCategorie(nom).subscribe({
      next: (r) => {
        this.envoiCategorie.set(false);
        this.nomNouvelleCategorie = '';
        this.nouvelleCategorieRapide = false;
        this.chargerCategories();
        this.formProduit.categorie_id = r.categorie.id;
        this.succes.set(r.message ?? 'Catégorie créée.');
      },
      error: (e) => {
        this.erreur.set(messageErreurApi(e));
        this.envoiCategorie.set(false);
      },
    });
  }

  protected chargerCommandes(): void {
    this.api.librairieCommandes(1, this.filtreStatut).subscribe({
      next: ({ data }) => {
        this.commandes.set(data);
        this.detailCommande.set(null);
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  // Réagir au changement d'onglet est géré par allerA().

  protected chargerParametres(): void {
    this.api.librairieParametres().subscribe({
      next: (p) => {
        this.parametres.set(p);
        this.parametresForm = {
          sigle: p.sigle,
          frais_livraison: p.frais_livraison,
          admin_peut_voir_dashboard: p.admin_peut_voir_dashboard,
          admin_peut_voir_ca: p.admin_peut_voir_ca,
          admin_peut_voir_commandes: p.admin_peut_voir_commandes,
        };
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected basculerFormProduit(): void {
    this.formProduitOuvert = !this.formProduitOuvert;
    if (this.formProduitOuvert) {
      this.chargerCategories();
    } else {
      this.reinitialiserFormProduit();
    }
  }

  protected reinitialiserFormProduit(): void {
    this.formProduitOuvert = false;
    this.produitEditeId = null;
    this.fichier = null;
    this.imageProduitExistante = '';
    this.revoquerApercuLocal();
    this.nouvelleCategorieRapide = false;
    this.nomNouvelleCategorie = '';
    this.formProduit = {
      nom: '',
      reference: '',
      description: '',
      categorie_id: null,
      prix: null,
      prix_promo: null,
      stock: null,
    };
  }

  protected editerProduit(p: Produit): void {
    this.produitEditeId = p.id;
    this.formProduitOuvert = true;
    this.chargerCategories();
    this.fichier = null;
    this.revoquerApercuLocal();
    this.imageProduitExistante = p.image_url ?? '';
    this.formProduit = {
      nom: p.nom,
      reference: p.reference ?? '',
      description: p.description ?? '',
      categorie_id: p.categorie_id ?? null,
      prix: p.prix ?? p.prix_vente ?? null,
      prix_promo: p.prix_promo ?? null,
      stock: p.stock ?? null,
    };
  }

  protected surFichier(e: Event): void {
    const input = e.target as HTMLInputElement;
    this.fichier = input.files?.[0] ?? null;
    this.revoquerApercuLocal();
    this.apercuFichierLocal = this.fichier ? URL.createObjectURL(this.fichier) : '';
  }

  private revoquerApercuLocal(): void {
    if (this.apercuFichierLocal) {
      URL.revokeObjectURL(this.apercuFichierLocal);
      this.apercuFichierLocal = '';
    }
  }

  protected enregistrerProduit(): void {
    if (!this.formProduit.nom.trim() || this.formProduit.prix === null) {
      this.erreur.set('Nom et prix sont obligatoires.');
      return;
    }
    if (this.formProduit.categorie_id === null || this.formProduit.categorie_id === undefined) {
      this.erreur.set('La catégorie est obligatoire.');
      return;
    }
    const form = new FormData();
    form.append('nom', this.formProduit.nom.trim());
    form.append('categorie_id', String(this.formProduit.categorie_id));
    form.append('reference', this.formProduit.reference?.trim() ?? '');
    form.append('description', this.formProduit.description ?? '');
    form.append('prix', String(this.formProduit.prix));
    if (this.formProduit.prix_promo !== null && this.formProduit.prix_promo !== undefined) {
      form.append('prix_promo', String(this.formProduit.prix_promo));
    }
    if (this.formProduit.stock !== null && this.formProduit.stock !== undefined) {
      form.append('stock', String(this.formProduit.stock));
    }
    if (this.fichier) form.append('image', this.fichier);

    this.envoiProduit.set(true);
    const req =
      this.produitEditeId !== null
        ? this.api.librairieMajProduit(this.produitEditeId, form)
        : this.api.librairieCreerProduit(form);

    req.subscribe({
      next: (r) => {
        this.succes.set(r.message ?? 'Produit enregistré.');
        this.envoiProduit.set(false);
        this.reinitialiserFormProduit();
        this.chargerProduits();
      },
      error: (e) => {
        this.erreur.set(messageErreurApi(e));
        this.envoiProduit.set(false);
      },
    });
  }

  protected basculerProduit(p: Produit): void {
    this.api.librairieBasculerProduit(p.id).subscribe({
      next: () => this.chargerProduits(),
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected supprimerProduit(p: Produit): void {
    if (!confirm(`Supprimer (archiver) « ${p.nom} » ?`)) return;
    this.api.librairieSupprimerProduit(p.id).subscribe({
      next: (r) => {
        this.succes.set(r.message ?? 'Produit archivé.');
        this.chargerProduits();
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected basculerCommande(reference: string): void {
    if (this.detailCommande()?.reference === reference) {
      this.detailCommande.set(null);
      return;
    }
    this.api.librairieCommande(reference).subscribe({
      next: (cmd) => {
        this.detailCommande.set(cmd);
        this.noteInterne = cmd.note_interne ?? '';
        this.nouveauStatut = '';
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected majStatut(reference: string): void {
    if (!this.nouveauStatut) return;
    this.api.librairieMajStatut(reference, this.nouveauStatut).subscribe({
      next: (r) => {
        this.succes.set(r.message ?? 'Statut mis à jour.');
        this.nouveauStatut = '';
        this.chargerCommandes();
      },
      error: (e) => {
        this.erreur.set(messageErreurApi(e));
        this.nouveauStatut = '';
      },
    });
  }

  protected enregistrerNote(reference: string): void {
    this.api.librairieNoteInterne(reference, this.noteInterne.trim() || null).subscribe({
      next: (r) => (this.succes.set(r.message ?? 'Note enregistrée.')),
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected enregistrerParametres(): void {
    this.api.majLibrairieParametres(this.parametresForm).subscribe({
      next: (p) => {
        this.parametres.set(p);
        this.succes.set('Paramètres enregistrés.');
      },
      error: (e) => this.erreur.set(messageErreurApi(e)),
    });
  }

  protected ouvrirCommande(reference: string): void {
    this.allerA('commandes');
    setTimeout(() => this.basculerCommande(reference));
  }

  protected pdfAdmin(reference: string): string {
    return this.api.urlPdfCommandeAdmin(reference);
  }

  protected formaterPrix(prix?: number | null): string {
    if (prix === null || prix === undefined) return '0 FCFA';
    return prix.toLocaleString('fr-FR') + ' FCFA';
  }
}
