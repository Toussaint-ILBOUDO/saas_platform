import { DecimalPipe } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  CommentaireBibliotheque,
  DashboardBibliotheque,
  DocumentBibliothequeEspace,
  DocumentBibliothequeFormulaire,
  MetaPage,
  ReferentielsBibliotheque,
} from '../../models';

const STATUTS_DOCUMENT: { valeur: string; libelle: string }[] = [
  { valeur: 'brouillon', libelle: 'Brouillon' },
  { valeur: 'en_attente', libelle: 'En attente' },
  { valeur: 'publie', libelle: 'Publié' },
  { valeur: 'refuse', libelle: 'Refusé' },
  { valeur: 'archive', libelle: 'Archivé' },
];

const STATUT_COULEUR: Record<string, string> = {
  brouillon: 'var(--mpc-texte-doux)',
  en_attente: 'var(--mpc-avertissement, #d97706)',
  publie: 'var(--mpc-succes)',
  refuse: 'var(--mpc-danger)',
  archive: 'var(--mpc-texte-doux)',
};

const VIDE_FORMULAIRE = (): DocumentBibliothequeFormulaire & { id: number | null } => ({
  id: null,
  titre: '',
  description: null,
  resume: null,
  type_document_id: null,
  classe_id: null,
  matiere_id: null,
  periode_id: null,
  tags: [],
  is_public: false,
  statut: 'brouillon',
  fichier: null,
});

/**
 * Espace membre « Ma bibliothèque » : dashboard, mes documents, favoris,
 * dépôt/édition d'un document et consultation détaillée avec commentaires.
 *
 * Un seul composant autonome (convention Magis) avec quatre onglets internes :
 * la navigation secondaire se fait par signaux, pas par le routeur — chaque
 * écran est atteignable depuis le menu latéral de l'espace.
 */
@Component({
  imports: [FormsModule, DecimalPipe],
  selector: 'espace-bibliotheque',
  styles: [
    `
      :host {
        display: block;
      }
      .entete {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.1rem;
      }
      .entete h1 {
        margin: 0 0 0.2rem;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .entete p {
        margin: 0;
        color: var(--mpc-texte-doux);
        font-size: 0.86rem;
        max-width: 64ch;
      }
      .btn-primaire {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.62rem 1rem;
        border: none;
        border-radius: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.86rem;
        cursor: pointer;
        white-space: nowrap;
      }
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-primaire:disabled {
        opacity: 0.55;
        cursor: not-allowed;
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
      .btn-neutral:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .onglets {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
        margin-bottom: 1.2rem;
        border-bottom: 1px solid var(--mpc-separateur);
        padding-bottom: 0.6rem;
      }
      .onglet {
        padding: 0.5rem 0.95rem;
        border: none;
        border-radius: 0.75rem;
        background: transparent;
        color: var(--mpc-texte-doux);
        font-weight: 700;
        font-size: 0.86rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
      }
      .onglet._actif {
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
      }
      .stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.8rem;
        margin-bottom: 1.4rem;
      }
      .stat {
        padding: 1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
      }
      .stat .valeur {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .stat .libelle {
        font-size: 0.76rem;
        color: var(--mpc-texte-doux);
        font-weight: 600;
      }
      .barre {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
      }
      .recherche {
        position: relative;
        flex: 1;
        min-width: 190px;
      }
      .recherche i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--mpc-texte-doux);
        font-size: 0.85rem;
        pointer-events: none;
      }
      .recherche input {
        width: 100%;
        height: 42px;
        padding: 0 0.8rem 0 2.2rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.86rem;
        outline: none;
      }
      .recherche input:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .filtre {
        height: 42px;
        padding: 0 0.7rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.86rem;
        outline: none;
      }
      .liste {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 0.9rem;
      }
      .carte {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
      }
      .carte:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(18, 48, 94, 0.08);
      }
      .carte-entete {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        align-items: flex-start;
      }
      .carte h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--mpc-bleu);
        line-height: 1.35;
      }
      .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.22rem 0.55rem;
        border-radius: 999px;
        background: var(--mpc-abandon);
        color: var(--mpc-texte-doux);
        white-space: nowrap;
      }
      .carte-meta {
        display: flex;
        gap: 0.7rem;
        flex-wrap: wrap;
        font-size: 0.76rem;
        color: var(--mpc-texte-doux);
      }
      .carte-meta i {
        margin-right: 0.2rem;
      }
      .carte-actions {
        display: flex;
        gap: 0.4rem;
        margin-top: auto;
        padding-top: 0.5rem;
        border-top: 1px solid var(--mpc-separateur);
      }
      .mini {
        padding: 0.4rem 0.7rem;
        border-radius: 0.6rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.76rem;
        font-weight: 600;
        cursor: pointer;
      }
      .mini:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .mini._danger {
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
      .toast._succes {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
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
        max-width: 640px;
        max-height: 90vh;
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
      .champ input,
      .champ select,
      .champ textarea {
        padding: 0.6rem 0.75rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.88rem;
        outline: none;
      }
      .champ input:focus,
      .champ select:focus,
      .champ textarea:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .champ input[type='file'] {
        padding: 0.5rem;
        background: var(--mpc-fond);
      }
      .aide {
        font-size: 0.74rem;
        color: var(--mpc-texte-doux);
      }
      .actions-form {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 0.4rem;
      }
      .avertissement {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 0.7rem 0.85rem;
        border-radius: 0.75rem;
        background: var(--mpc-avertissement-tint, #fef3c7);
        color: var(--mpc-avertissement, #92400e);
        font-size: 0.82rem;
      }
      .detail-section {
        display: grid;
        gap: 0.5rem;
        padding: 0.9rem 0;
        border-top: 1px solid var(--mpc-separateur);
      }
      .detail-section h3 {
        margin: 0;
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .commentaire {
        padding: 0.65rem 0.8rem;
        border-radius: 0.7rem;
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-separateur);
        font-size: 0.85rem;
      }
      .commentaire .auteur {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--mpc-primaire);
        margin-bottom: 0.2rem;
      }
      .commentaire .reponse {
        margin-top: 0.5rem;
        margin-left: 1.2rem;
      }
      .commentaire-form {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
      }
      .commentaire-form input {
        flex: 1;
        padding: 0.55rem 0.75rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.85rem;
        outline: none;
      }
      .etoiles {
        display: flex;
        gap: 0.2rem;
      }
      .etoile {
        border: none;
        background: none;
        font-size: 1.3rem;
        cursor: pointer;
        color: var(--mpc-separateur);
        padding: 0;
      }
      .etoile._pleine {
        color: #f59e0b;
      }
      @media (max-width: 560px) {
        .grille {
          grid-template-columns: 1fr;
        }
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>Ma bibliothèque</h1>
        <p>Déposez, classez et partagez vos ressources pédagogiques avec le cabinet.</p>
      </div>
      <button class="btn-primaire" type="button" (click)="ouvrirFormulaire()">
        <i class="bi bi-plus-lg"></i> Nouveau document
      </button>
    </div>

    @if (message()) {
      <div class="toast"><i class="bi bi-exclamation-triangle"></i>{{ message() }}</div>
    }
    @if (succes()) {
      <div class="toast _succes"><i class="bi bi-check-circle"></i>{{ succes() }}</div>
    }

    <nav class="onglets">
      <button class="onglet" [class._actif]="onglet() === 'dashboard'" type="button" (click)="basculerOnglet('dashboard')">
        <i class="bi bi-speedometer2"></i> Tableau de bord
      </button>
      <button class="onglet" [class._actif]="onglet() === 'documents'" type="button" (click)="basculerOnglet('documents')">
        <i class="bi bi-folder2-open"></i> Mes documents
      </button>
      <button class="onglet" [class._actif]="onglet() === 'favoris'" type="button" (click)="basculerOnglet('favoris')">
        <i class="bi bi-heart"></i> Favoris
      </button>
    </nav>

    @switch (onglet()) {
      @case ('dashboard') {
        @if (charge()) {
          <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
        } @else if (dashboard(); as d) {
          <div class="stats">
            <div class="stat">
              <div class="valeur">{{ d.total }}</div>
              <div class="libelle">Total documents</div>
            </div>
            <div class="stat">
              <div class="valeur">{{ d.publies }}</div>
              <div class="libelle">Publiés</div>
            </div>
            <div class="stat">
              <div class="valeur">{{ d.en_attente }}</div>
              <div class="libelle">En attente</div>
            </div>
            <div class="stat">
              <div class="valeur">{{ d.total_vues }}</div>
              <div class="libelle">Vues</div>
            </div>
            <div class="stat">
              <div class="valeur">{{ d.total_telechargements }}</div>
              <div class="libelle">Téléchargements</div>
            </div>
            <div class="stat">
              <div class="valeur">{{ d.total_favoris }}</div>
              <div class="libelle">Favoris reçus</div>
            </div>
          </div>

          @if (d.derniers_commentaires.length > 0) {
            <div class="detail-section" style="border-top: none; padding-top: 0">
              <h3>Derniers commentaires</h3>
              @for (c of d.derniers_commentaires; track c.id) {
                <div class="commentaire">
                  <div class="auteur">{{ c.user?.prenom }} {{ c.user?.nom }} — {{ dateCourte(c.created_at) }}</div>
                  <div>{{ c.contenu }}</div>
                  @if (c.document) {
                    <div class="aide" style="margin-top: 0.3rem">
                      Sur :
                      <button class="lien" type="button" (click)="ouvrirDetail(c.document.id)">{{ c.document.titre }}</button>
                    </div>
                  }
                </div>
              }
            </div>
          }
        }
      }

      @case ('documents') {
        <div class="barre">
          <label class="recherche">
            <i class="bi bi-search"></i>
            <input type="search" #terme [value]="recherche()" (keyup.enter)="rechercher(terme.value)" placeholder="Rechercher un document…" />
          </label>
          <select class="filtre" [value]="filtreStatut()" (change)="changerFiltreStatut($event)">
            <option value="">Tous les statuts</option>
            @for (s of statutsDocument; track s.valeur) {
              <option [value]="s.valeur">{{ s.libelle }}</option>
            }
          </select>
        </div>

        @if (charge()) {
          <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
        } @else if (documents().length === 0) {
          <div class="vide"><i class="bi bi-folder2-open"></i> Aucun document. Déposez votre premier support !</div>
        } @else {
          <div class="liste">
            @for (doc of documents(); track doc.id) {
              <article class="carte">
                <div class="carte-entete">
                  <h3>{{ doc.titre }}</h3>
                  <span class="badge" [style.color]="couleurStatut(doc.statut)">{{ libelleStatut(doc.statut) }}</span>
                </div>
                @if (doc.resume) {
                  <p class="aide" style="margin: 0">{{ doc.resume }}</p>
                }
                <div class="carte-meta">
                  @if (doc.type_document) {
                    <span><i class="bi bi-bookmark"></i> {{ doc.type_document.sigle }}</span>
                  }
                  @if (doc.matiere) {
                    <span><i class="bi bi-journal"></i> {{ doc.matiere.nom }}</span>
                  }
                  <span><i class="bi bi-eye"></i> {{ doc.nb_vues }}</span>
                  <span><i class="bi bi-download"></i> {{ doc.nb_telechargements }}</span>
                  @if (doc.note_moyenne > 0) {
                    <span><i class="bi bi-star-fill"></i> {{ doc.note_moyenne | number: '1.1-1' }}</span>
                  }
                  @if (doc.is_public) {
                    <span class="badge" style="background: var(--mpc-succes-tint); color: var(--mpc-succes)">Public</span>
                  } @else {
                    <span class="badge">Privé</span>
                  }
                </div>
                <div class="carte-actions">
                  <button class="mini" type="button" (click)="ouvrirDetail(doc.id)"><i class="bi bi-eye"></i> Voir</button>
                  <button class="mini" type="button" (click)="ouvrirEdition(doc)"><i class="bi bi-pencil"></i> Modifier</button>
                  <button class="mini _danger" type="button" (click)="supprimer(doc)"><i class="bi bi-trash"></i> Supprimer</button>
                </div>
              </article>
            }
          </div>

          @if ((meta()?.last_page ?? 1) > 1) {
            <nav class="pagination">
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
      }

      @case ('favoris') {
        @if (charge()) {
          <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
        } @else if (documents().length === 0) {
          <div class="vide"><i class="bi bi-heart"></i> Aucun favori pour l’instant.</div>
        } @else {
          <div class="liste">
            @for (doc of documents(); track doc.id) {
              <article class="carte">
                <div class="carte-entete">
                  <h3>{{ doc.titre }}</h3>
                  <button class="mini _danger" type="button" (click)="basculerFavori(doc)"><i class="bi bi-heart-fill"></i> Retirer</button>
                </div>
                <div class="carte-meta">
                  @if (doc.auteur) {
                    <span><i class="bi bi-person"></i> {{ doc.auteur.prenom }} {{ doc.auteur.nom }}</span>
                  }
                  @if (doc.matiere) {
                    <span><i class="bi bi-journal"></i> {{ doc.matiere.nom }}</span>
                  }
                </div>
                <div class="carte-actions">
                  <button class="mini" type="button" (click)="ouvrirDetail(doc.id)"><i class="bi bi-eye"></i> Voir</button>
                </div>
              </article>
            }
          </div>
        }
      }
    }

    @if (formulaireOuvert()) {
      <div class="fenetre" (click)="fermerFormulaire()">
        <div class="panneau" (click)="$event.stopPropagation()">
          <div class="panneau-entete">
            <h2>{{ modele().id ? 'Modifier le document' : 'Nouveau document' }}</h2>
            <button class="fermer" type="button" (click)="fermerFormulaire()" aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
          </div>

          <div class="formulaire">
            <label class="champ">
              <span>Titre *</span>
              <input [ngModel]="modele().titre" (ngModelChange)="setChamp('titre', $event)" placeholder="Ex. Exercices de fractions — 6e" />
            </label>

            <label class="champ">
              <span>Résumé</span>
              <input [ngModel]="modele().resume ?? ''" (ngModelChange)="setChamp('resume', $event || null)" placeholder="Une ligne qui donne envie d’ouvrir le document." />
            </label>

            <label class="champ">
              <span>Description</span>
              <textarea rows="3" [ngModel]="modele().description ?? ''" (ngModelChange)="setChamp('description', $event || null)"></textarea>
            </label>

            <div class="grille">
              <label class="champ">
                <span>Type de document</span>
                <select [ngModel]="modele().type_document_id" (ngModelChange)="setChamp('type_document_id', $event)">
                  <option [ngValue]="null">— Choisir —</option>
                  @for (t of referentiels()?.types ?? []; track t.id) {
                    <option [ngValue]="t.id">{{ t.nom }}</option>
                  }
                </select>
              </label>
              <label class="champ">
                <span>Matière</span>
                <select [ngModel]="modele().matiere_id" (ngModelChange)="setChamp('matiere_id', $event)">
                  <option [ngValue]="null">— Choisir —</option>
                  @for (m of referentiels()?.matieres ?? []; track m.id) {
                    <option [ngValue]="m.id">{{ m.nom }}</option>
                  }
                </select>
              </label>
            </div>

            <div class="grille">
              <label class="champ">
                <span>Classe</span>
                <select [ngModel]="modele().classe_id" (ngModelChange)="setChamp('classe_id', $event)">
                  <option [ngValue]="null">— Choisir —</option>
                  @for (c of referentiels()?.classes ?? []; track c.id) {
                    <option [ngValue]="c.id">{{ c.nom }}</option>
                  }
                </select>
              </label>
              <label class="champ">
                <span>Période</span>
                <select [ngModel]="modele().periode_id" (ngModelChange)="setChamp('periode_id', $event)">
                  <option [ngValue]="null">— Choisir —</option>
                  @for (p of referentiels()?.periodes ?? []; track p.id) {
                    <option [ngValue]="p.id">{{ p.nom }}</option>
                  }
                </select>
              </label>
            </div>

            <label class="champ">
              <span>Tags (séparés par des virgules)</span>
              <input [ngModel]="modele().tags.join(', ')" (ngModelChange)="setTags($event)" placeholder="fractions, 6e, exercices" />
            </label>

            <label class="champ">
              <span>Fichier @if (!modele().id) { * }@else { (laisser vide pour conserver) }</span>
              <input type="file" (change)="onFichier($event)" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.txt,.zip" />
              <span class="aide">PDF, images, Office, OpenDocument, texte ou archive — 50 Mo maximum.</span>
            </label>

            <label class="champ">
              <span>Visibilité</span>
              <select [ngModel]="modele().is_public ? '1' : '0'" (ngModelChange)="setChamp('is_public', $event === '1')">
                <option value="0">Privé (visible seulement de vous et des admins)</option>
                <option value="1">Public (visible par tous les membres du cabinet)</option>
              </select>
            </label>

            @if (!modele().id) {
              <label class="champ">
                <span>Statut</span>
                <select [ngModel]="modele().statut" (ngModelChange)="setChamp('statut', $event)">
                  <option value="brouillon">Brouillon (retravailler plus tard)</option>
                  <option value="en_attente">Envoyer en attente de modération</option>
                </select>
              </label>
            }

            <div class="actions-form">
              <button class="btn-neutral" type="button" (click)="fermerFormulaire()">Annuler</button>
              <button class="btn-primaire" type="button" [disabled]="sauvegarde()" (click)="enregistrer()">
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

    @if (detailOuvert() && detail(); as doc) {
      <div class="fenetre" (click)="fermerDetail()">
        <div class="panneau" (click)="$event.stopPropagation()">
          <div class="panneau-entete">
            <h2>{{ doc.titre }}</h2>
            <button class="fermer" type="button" (click)="fermerDetail()" aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
          </div>

          <div class="carte-meta" style="margin-bottom: 0.8rem">
            <span class="badge" [style.color]="couleurStatut(doc.statut)">{{ libelleStatut(doc.statut) }}</span>
            @if (doc.type_document) {
              <span><i class="bi bi-bookmark"></i> {{ doc.type_document.nom }}</span>
            }
            @if (doc.matiere) {
              <span><i class="bi bi-journal"></i> {{ doc.matiere.nom }}</span>
            }
            @if (doc.classe) {
              <span><i class="bi bi-mortarboard"></i> {{ doc.classe.nom }}</span>
            }
            @if (doc.periode) {
              <span><i class="bi bi-calendar3"></i> {{ doc.periode.nom }}</span>
            }
            @if (doc.auteur) {
              <span><i class="bi bi-person"></i> {{ doc.auteur.prenom }} {{ doc.auteur.nom }}</span>
            }
            <span><i class="bi bi-eye"></i> {{ doc.nb_vues }} vues</span>
            <span><i class="bi bi-download"></i> {{ doc.nb_telechargements }} téléchargements</span>
            @if (doc.is_public) {
              <span class="badge" style="background: var(--mpc-succes-tint); color: var(--mpc-succes)">Public</span>
            } @else {
              <span class="badge">Privé</span>
            }
          </div>

          @if (doc.resume) {
            <p style="margin: 0 0 0.8rem; color: var(--mpc-texte)">{{ doc.resume }}</p>
          }
          @if (doc.description) {
            <p style="margin: 0 0 0.8rem; color: var(--mpc-texte-doux); font-size: 0.88rem; white-space: pre-wrap">{{ doc.description }}</p>
          }
          @if (doc.tags.length > 0) {
            <div class="carte-meta" style="margin-bottom: 0.8rem">
              @for (tag of doc.tags; track tag.id) {
                <span class="badge"><i class="bi bi-tag"></i> {{ tag.nom }}</span>
              }
            </div>
          }
          @if (doc.fichier) {
            <a class="btn-neutral" style="display: inline-flex; gap: 0.4rem; text-decoration: none; margin-bottom: 0.8rem" [href]="urlTelechargement(doc.id)" target="_blank">
              <i class="bi bi-download"></i> Télécharger {{ doc.fichier.nom }} ({{ taille(doc.fichier.taille) }})
            </a>
          }

          <div class="detail-section">
            <h3>Note</h3>
            <div class="etoiles">
              @for (e of [1, 2, 3, 4, 5]; track e) {
                <button class="etoile" [class._pleine]="(doc.ma_note ?? 0) >= e" type="button" (click)="noter(doc, e)" aria-label="Note {{ e }}">
                  <i class="bi" [class.bi-star-fill]="(doc.ma_note ?? 0) >= e" [class.bi-star]="(doc.ma_note ?? 0) < e"></i>
                </button>
              }
              @if (doc.nb_notes > 0) {
                <span class="aide" style="align-self: center; margin-left: 0.4rem">{{ doc.note_moyenne | number: '1.1-1' }} / 5 ({{ doc.nb_notes }} avis)</span>
              }
            </div>
            <button class="mini" style="justify-self: start" type="button" (click)="basculerFavori(doc)">
              <i class="bi" [class.bi-heart-fill]="doc.is_favori" [class.bi-heart]="!doc.is_favori"></i>
              {{ doc.is_favori ? 'Dans vos favoris' : 'Ajouter aux favoris' }}
            </button>
          </div>

          <div class="detail-section">
            <h3>Commentaires ({{ totalCommentaires(doc.commentaires ?? []) }})</h3>
            @for (c of doc.commentaires ?? []; track c.id) {
              <div class="commentaire">
                <div class="auteur">{{ c.user?.prenom }} {{ c.user?.nom }} — {{ dateCourte(c.created_at) }}</div>
                <div>{{ c.contenu }}</div>
                @for (r of c.replies; track r.id) {
                  <div class="commentaire reponse">
                    <div class="auteur">{{ r.user?.prenom }} {{ r.user?.nom }} — {{ dateCourte(r.created_at) }}</div>
                    <div>{{ r.contenu }}</div>
                  </div>
                }
              </div>
            }
            <div class="commentaire-form">
              <input type="text" #nouveauCommentaire placeholder="Écrire un commentaire…" (keyup.enter)="commenter(doc, nouveauCommentaire.value); nouveauCommentaire.value = ''" />
              <button class="mini" type="button" (click)="commenter(doc, nouveauCommentaire.value); nouveauCommentaire.value = ''">
                <i class="bi bi-send"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class BibliothequeComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly statutsDocument = STATUTS_DOCUMENT;

  protected readonly onglet = signal<'dashboard' | 'documents' | 'favoris'>('dashboard');
  protected readonly charge = signal(true);
  protected readonly message = signal('');
  protected readonly succes = signal('');
  protected readonly sauvegarde = signal(false);

  protected readonly dashboard = signal<DashboardBibliotheque | null>(null);
  protected readonly documents = signal<DocumentBibliothequeEspace[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly referentiels = signal<ReferentielsBibliotheque | null>(null);
  protected readonly recherche = signal('');
  protected readonly filtreStatut = signal('');

  protected readonly formulaireOuvert = signal(false);
  protected readonly modele = signal<(DocumentBibliothequeFormulaire & { id: number | null })>(VIDE_FORMULAIRE());

  protected readonly detailOuvert = signal(false);
  protected readonly detail = signal<DocumentBibliothequeEspace | null>(null);

  async ngOnInit(): Promise<void> {
    await this.chargerOnglet();
    firstValueFrom(this.api.getReferentielsBibliotheque())
      .then((r) => this.referentiels.set(r))
      .catch(() => {
        /* le formulaire affichera des listes vides */
      });
  }

  protected async basculerOnglet(onglet: 'dashboard' | 'documents' | 'favoris'): Promise<void> {
    this.onglet.set(onglet);
    await this.chargerOnglet();
  }

  private async chargerOnglet(page = 1): Promise<void> {
    this.charge.set(true);
    this.message.set('');
    try {
      if (this.onglet() === 'dashboard') {
        this.dashboard.set(await firstValueFrom(this.api.getDashboardBibliotheque()));
      } else if (this.onglet() === 'documents') {
        const r = await firstValueFrom(
          this.api.getMesDocuments(page, 15, { search: this.recherche(), statut: this.filtreStatut() }),
        );
        this.documents.set(r.data ?? []);
        this.meta.set(r.meta ?? null);
      } else {
        const r = await firstValueFrom(this.api.getFavorisBibliotheque(page, 15));
        this.documents.set(r.data ?? []);
        this.meta.set(r.meta ?? null);
      }
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.charge.set(false);
    }
  }

  protected rechercher(valeur: string): void {
    this.recherche.set(valeur.trim());
    void this.chargerOnglet(1);
  }

  protected changerFiltreStatut(evenement: Event): void {
    this.filtreStatut.set((evenement.target as HTMLSelectElement).value);
    void this.chargerOnglet(1);
  }

  protected aller(page: number): void {
    void this.chargerOnglet(page);
  }

  /* ---------- Formulaire ---------- */

  protected ouvrirFormulaire(): void {
    this.modele.set(VIDE_FORMULAIRE());
    this.message.set('');
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEdition(doc: DocumentBibliothequeEspace): void {
    this.modele.set({
      id: doc.id,
      titre: doc.titre,
      description: doc.description,
      resume: doc.resume,
      type_document_id: doc.type_document?.id ?? null,
      classe_id: doc.classe?.id ?? null,
      matiere_id: doc.matiere?.id ?? null,
      periode_id: doc.periode?.id ?? null,
      tags: doc.tags.map((t) => t.nom),
      is_public: doc.is_public,
      // Le serveur n'accepte `statut` qu'avec `brouillon|en_attente` sur un
      // document existant : un brouillon peut repartir en attente, pas
      // l'inverse. On n'envoie donc jamais `statut` en édition.
      statut: 'brouillon',
      fichier: null,
    });
    this.message.set('');
    this.formulaireOuvert.set(true);
  }

  protected fermerFormulaire(): void {
    this.formulaireOuvert.set(false);
  }

  protected setChamp(champ: keyof (DocumentBibliothequeFormulaire & { id: number | null }), valeur: unknown): void {
    this.modele.set({ ...this.modele(), [champ]: valeur });
  }

  protected setTags(valeur: string): void {
    const tags = valeur
      .split(',')
      .map((t) => t.trim())
      .filter((t) => t.length > 0);
    this.setChamp('tags', tags);
  }

  protected onFichier(evenement: Event): void {
    const input = evenement.target as HTMLInputElement;
    const fichier = input.files?.[0] ?? null;
    this.setChamp('fichier', fichier);
  }

  protected async enregistrer(): Promise<void> {
    this.message.set('');
    this.succes.set('');
    const m = this.modele();
    if (!m.titre.trim()) {
      this.message.set('Le titre est obligatoire.');
      return;
    }
    if (!m.id && !m.fichier) {
      this.message.set('Choisissez un fichier à téléverser.');
      return;
    }

    this.sauvegarde.set(true);
    try {
      const fd = new FormData();
      fd.append('titre', m.titre.trim());
      if (m.resume) fd.append('resume', m.resume);
      if (m.description) fd.append('description', m.description);
      if (m.type_document_id) fd.append('type_document_id', String(m.type_document_id));
      if (m.classe_id) fd.append('classe_id', String(m.classe_id));
      if (m.matiere_id) fd.append('matiere_id', String(m.matiere_id));
      if (m.periode_id) fd.append('periode_id', String(m.periode_id));
      m.tags.forEach((t) => fd.append('tags[]', t));
      fd.append('is_public', m.is_public ? '1' : '0');
      if (!m.id) fd.append('statut', m.statut);
      if (m.fichier) fd.append('fichier', m.fichier);

      if (m.id) {
        await firstValueFrom(this.api.majDocumentBibliotheque(m.id, fd));
        this.succes.set('Document mis à jour.');
      } else {
        await firstValueFrom(this.api.creerDocumentBibliotheque(fd));
        this.succes.set('Document déposé.');
      }
      this.formulaireOuvert.set(false);
      await this.chargerOnglet(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async supprimer(doc: DocumentBibliothequeEspace): Promise<void> {
    if (!confirm(`Supprimer « ${doc.titre} » ? Cette action est définitive.`)) return;
    try {
      await firstValueFrom(this.api.supprimerDocumentBibliotheque(doc.id));
      this.succes.set('Document supprimé.');
      await this.chargerOnglet(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  /* ---------- Détail ---------- */

  protected async ouvrirDetail(id: number): Promise<void> {
    try {
      const doc = await firstValueFrom(this.api.getDocumentBibliotheque(id));
      this.detail.set(doc);
      this.detailOuvert.set(true);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  protected fermerDetail(): void {
    this.detailOuvert.set(false);
    this.detail.set(null);
  }

  protected async basculerFavori(doc: DocumentBibliothequeEspace): Promise<void> {
    try {
      const maj = await firstValueFrom(this.api.basculerFavori(doc.id));
      this.detail.set(maj);
      // Rafraîchir la liste si on est sur l'onglet favoris/documents.
      if (this.onglet() !== 'dashboard') await this.chargerOnglet(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  protected async noter(doc: DocumentBibliothequeEspace, note: number): Promise<void> {
    try {
      const maj = await firstValueFrom(this.api.noterDocument(doc.id, note));
      this.detail.set(maj);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  protected async commenter(doc: DocumentBibliothequeEspace, contenu: string): Promise<void> {
    if (!contenu.trim()) return;
    try {
      await firstValueFrom(this.api.commenterDocument(doc.id, contenu.trim()));
      const maj = await firstValueFrom(this.api.getDocumentBibliotheque(doc.id));
      this.detail.set(maj);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  protected urlTelechargement(id: number): string {
    return this.api.urlTelechargementBibliotheque(id);
  }

  protected totalCommentaires(commentaires: CommentaireBibliotheque[]): number {
    return commentaires.reduce((total, c) => total + 1 + this.totalCommentaires(c.replies), 0);
  }

  protected libelleStatut(statut: string): string {
    return STATUTS_DOCUMENT.find((s) => s.valeur === statut)?.libelle ?? statut;
  }

  protected couleurStatut(statut: string): string {
    return STATUT_COULEUR[statut] ?? 'var(--mpc-texte-doux)';
  }

  protected dateCourte(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  protected taille(octets: number): string {
    if (octets < 1024) return `${octets} o`;
    if (octets < 1024 * 1024) return `${Math.round(octets / 1024)} Ko`;
    return `${(octets / (1024 * 1024)).toFixed(1)} Mo`;
  }
}
