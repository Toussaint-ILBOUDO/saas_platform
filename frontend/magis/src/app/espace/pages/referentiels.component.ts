import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  ClasseReferentiel,
  CreneauClasse,
  CreneauEnseignant,
  CreneauMatiere,
  CreneauTypeCours,
  EnseignantReferentiel,
  MatiereReferentiel,
  MetaPage,
  TypeCoursReferentiel,
} from '../../models';

type Onglet = 'classes' | 'matieres' | 'types' | 'enseignants';

interface DescriptionOnglet {
  id: Onglet;
  libelle: string;
  icone: string;
  phrase: string;
}

/**
 * Les quatre listes ne sont pas quatre écrans : ce sont les quatre réponses à
 * la même question — « qu'est-ce que le cabinet sait déjà ? ». Elles sont donc
 * sur un seul écran à onglets. L'utilisateur qui crée un enseignant a besoin de
 * la liste des matières **dans le même écran** que la fiche qu'il remplit.
 */
const ONGLETS: DescriptionOnglet[] = [
  {
    id: 'classes',
    libelle: 'Classes',
    icone: 'bi-people',
    phrase:
      'Les niveaux de cours du cabinet. Une classe porte ses élèves : elle ne peut plus être supprimée dès qu’un élève y est rattaché.',
  },
  {
    id: 'matieres',
    libelle: 'Matières',
    icone: 'bi-journal-bookmark',
    phrase:
      'Les matières enseignées. Une matière utilisée par une affectation se désactive plutôt que de se supprimer, pour ne pas casser l’historique de facturation.',
  },
  {
    id: 'types',
    libelle: 'Types de cours',
    icone: 'bi-tags',
    phrase:
      'La nature commerciale d’un contrat — domicile, groupe, examen. Les contrats existants en référence un type : on le désactive, on ne le supprime pas.',
  },
  {
    id: 'enseignants',
    libelle: 'Enseignants',
    icone: 'bi-person-badge',
    phrase:
      'Les comptes des enseignants et leurs matières. Un compte ne se supprime jamais : il porte les rapports, les bulletins et les contrats.',
  },
];

const MODELE_CLASSE: CreneauClasse = { nom: '', sigle: '' };
const MODELE_MATIERE: CreneauMatiere = { nom: '', sigle: '', description: null };
const MODELE_TYPE: CreneauTypeCours = { code: '', libelle: '', description: null };

@Component({
  imports: [FormsModule],
  selector: 'espace-referentiels',
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

      /* ---------- Onglets ---------- */
      .onglets {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        padding: 0.3rem;
        border-radius: 0.95rem;
        background: var(--mpc-surface-teintee);
        border: 1px solid var(--mpc-separateur);
        margin-bottom: 1rem;
      }
      .onglet {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.9rem;
        border: none;
        border-radius: 0.75rem;
        background: transparent;
        color: var(--mpc-texte-doux);
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        white-space: nowrap;
      }
      .onglet:hover {
        color: var(--mpc-bleu);
      }
      .onglet._actif {
        background: var(--mpc-surface);
        color: var(--mpc-primaire);
        box-shadow: 0 1px 3px rgba(10, 14, 26, 0.08);
      }
      .onglet .compte {
        min-width: 20px;
        height: 20px;
        padding: 0 0.35rem;
        display: inline-grid;
        place-items: center;
        border-radius: 999px;
        background: var(--mpc-abandon);
        color: var(--mpc-texte-doux);
        font-size: 0.68rem;
        font-weight: 800;
      }
      .onglet._actif .compte {
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
      }

      /* ---------- Barre d'outils ---------- */
      .barre {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 0.9rem;
      }
      .recherche {
        position: relative;
        flex: 1;
        min-width: 200px;
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
        padding: 0.6rem 0.75rem 0.6rem 2.15rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-surface);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.86rem;
        outline: none;
      }
      .recherche input:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .filtre {
        padding: 0.58rem 0.7rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-surface);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.83rem;
        font-weight: 600;
        outline: none;
      }
      .filtre:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
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
      .btn-primaire:hover {
        background: var(--mpc-primaire-fonce);
      }

      .phrase {
        margin: 0 0 0.9rem;
        padding: 0.8rem 0.95rem;
        border-radius: 0.9rem;
        background: var(--mpc-info-tint);
        color: var(--mpc-bleu);
        font-size: 0.83rem;
        line-height: 1.5;
      }
      .phrase i {
        margin-right: 0.35rem;
      }

      /* ---------- Liste ---------- */
      .liste {
        display: grid;
        gap: 0.7rem;
      }
      .ligne {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        flex-wrap: wrap;
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
      }
      .ligne._inactive {
        background: var(--mpc-fond);
        border-style: dashed;
      }
      .pastille {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 0.85rem;
        font-weight: 800;
        font-size: 0.7rem;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
      }
      .pastille._eleve {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        font-size: 1rem;
        text-transform: none;
        background: var(--mpc-commune);
        color: #fff;
      }
      .detail {
        flex: 1;
        min-width: 190px;
      }
      .detail b {
        display: block;
        font-size: 0.95rem;
        color: var(--mpc-bleu);
      }
      .detail small {
        display: block;
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
      }
      .detail .note {
        display: block;
        font-size: 0.76rem;
        color: var(--mpc-texte-doux);
        margin-top: 0.15rem;
      }
      .badges {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
      }
      .badge {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: var(--mpc-abandon);
        color: var(--mpc-texte-doux);
        white-space: nowrap;
      }
      .badge._actif {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .badge._inactif {
        background: var(--mpc-attention-tint);
        color: var(--mpc-attention);
      }
      .badge._info {
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
      }
      .matieres {
        display: flex;
        gap: 0.3rem;
        flex-wrap: wrap;
        margin-top: 0.35rem;
      }
      .actions {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
      }
      .mini {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.42rem 0.7rem;
        border-radius: 0.65rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
      }
      .mini:hover:not(:disabled) {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .mini:disabled {
        opacity: 0.45;
        cursor: not-allowed;
      }
      .mini._danger:hover:not(:disabled) {
        background: var(--mpc-danger);
        border-color: var(--mpc-danger);
        color: #fff;
      }

      /* ---------- États ---------- */
      .alerte {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.8rem 0.95rem;
        border-radius: 0.9rem;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .succes {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.8rem 0.95rem;
        border-radius: 0.9rem;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .vide {
        padding: 2.6rem 1rem;
        text-align: center;
        color: var(--mpc-texte-doux);
        border: 1px dashed var(--mpc-separateur);
        border-radius: 1rem;
        background: var(--mpc-surface);
      }
      .vide i {
        font-size: 1.5rem;
        display: block;
        margin-bottom: 0.5rem;
        opacity: 0.55;
      }

      .pagination {
        display: flex;
        justify-content: center;
        gap: 0.4rem;
        margin-top: 1.3rem;
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

      /* ---------- Modale ---------- */
      .fenetre {
        position: fixed;
        inset: 0;
        z-index: 90;
        display: grid;
        place-items: center;
        padding: 1rem;
        background: rgba(10, 14, 26, 0.55);
        overflow-y: auto;
      }
      .panneau {
        width: 100%;
        max-width: 620px;
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
        gap: 0.85rem;
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
      .champ textarea:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .champ._erreur input {
        border-color: var(--mpc-danger);
      }
      .erreur {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--mpc-danger);
      }
      .aide {
        font-size: 0.75rem;
        color: var(--mpc-texte-doux);
      }
      .grille {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
      }
      .case {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.7rem 0.8rem;
        border-radius: 0.8rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        cursor: pointer;
      }
      .case input {
        margin-top: 0.15rem;
        accent-color: var(--mpc-primaire);
      }
      .case b {
        display: block;
        font-size: 0.85rem;
        color: var(--mpc-bleu);
      }
      .case small {
        display: block;
        font-size: 0.75rem;
        color: var(--mpc-texte-doux);
      }
      .avertissement {
        display: flex;
        gap: 0.5rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.7rem;
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
        font-size: 0.78rem;
        font-weight: 600;
      }
      .actions-form {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 0.35rem;
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

      .spin {
        display: inline-block;
        animation: tourner 0.9s linear infinite;
      }
      @keyframes tourner {
        to {
          transform: rotate(360deg);
        }
      }

      @media (max-width: 620px) {
        .grille {
          grid-template-columns: 1fr;
        }
        .actions {
          width: 100%;
        }
        .mini {
          flex: 1;
          justify-content: center;
        }
        .onglets {
          overflow-x: auto;
          flex-wrap: nowrap;
        }
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>Référentiels</h1>
        <p>
          Les listes de base du cabinet. Rien ne peut être contractualisé avant qu’elles soient là : une affectation
          a besoin d’une matière, d’un enseignant et d’une classe.
        </p>
      </div>
    </div>

    @if (message()) {
      <div class="alerte"><i class="bi bi-exclamation-triangle"></i><span>{{ message() }}</span></div>
    }
    @if (succes()) {
      <div class="succes"><i class="bi bi-check-circle"></i><span>{{ succes() }}</span></div>
    }

    <nav class="onglets" role="tablist">
      @for (o of onglets; track o.id) {
        <button
          class="onglet"
          type="button"
          role="tab"
          [class._actif]="onglet() === o.id"
          [attr.aria-selected]="onglet() === o.id"
          (click)="changerOnglet(o.id)"
        >
          <i class="bi {{ o.icone }}"></i>
          {{ o.libelle }}
          @if (total(o.id); as n) {
            <span class="compte">{{ n }}</span>
          }
        </button>
      }
    </nav>

    <div class="barre">
      <div class="recherche">
        <i class="bi bi-search"></i>
        <input
          type="search"
          [ngModel]="recherche()"
          (ngModelChange)="setRecherche($event)"
          (keyup.enter)="rechercher()"
          [placeholder]="placeholderRecherche()"
          [attr.aria-label]="placeholderRecherche()"
        />
      </div>
      @if (onglet() === 'matieres') {
        <select class="filtre" [ngModel]="filtreActif()" (ngModelChange)="setFiltreActif($event)" aria-label="Filtrer par état">
          <option value="">Toutes</option>
          <option value="1">Actives</option>
          <option value="0">Inactives</option>
        </select>
      }
      <button class="btn-primaire" type="button" (click)="ouvrirCreation()">
        <i class="bi bi-plus-lg"></i> {{ libelleAjout() }}
      </button>
    </div>

    <p class="phrase"><i class="bi bi-info-circle"></i>{{ phrase() }}</p>

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else if (listeCourante().length === 0) {
      <div class="vide">
        <i class="bi bi-inbox"></i>
        @if (recherche()) {
          Aucun résultat pour « {{ recherche() }} ».
        } @else {
          {{ vide() }}
        }
      </div>
    } @else {
      <div class="liste">
        @switch (onglet()) {
          @case ('classes') {
            @for (c of classes(); track c.id) {
              <article class="ligne">
                <span class="pastille">{{ c.sigle }}</span>
                <div class="detail">
                  <b>{{ c.nom }}</b>
                  <small>{{ c.sigle }}</small>
                  @if ((c.nb_eleves ?? 0) > 0) {
                    <span class="note">{{ c.nb_eleves }} élève(s) rattaché(s)</span>
                  }
                </div>
                <div class="badges">
                  <span class="badge _info">{{ c.nb_demandes_cours ?? 0 }} demande(s) de cours</span>
                </div>
                <div class="actions">
                  <button class="mini" type="button" (click)="ouvrirEditionClasse(c)">
                    <i class="bi bi-pencil"></i> Modifier
                  </button>
                  <button
                    class="mini _danger"
                    type="button"
                    [disabled]="estClasseUtilisee(c)"
                    [title]="estClasseUtilisee(c) ? 'Réaffectez ses élèves et demandes de cours avant de la supprimer' : ''"
                    (click)="supprimerClasse(c)"
                  >
                    <i class="bi bi-trash"></i> Supprimer
                  </button>
                </div>
              </article>
            }
          }
          @case ('matieres') {
            @for (m of matieres(); track m.id) {
              <article class="ligne" [class._inactive]="!m.actif">
                <span class="pastille">{{ m.sigle }}</span>
                <div class="detail">
                  <b>{{ m.nom }}</b>
                  <small>{{ m.sigle }}</small>
                  @if (m.description) {
                    <span class="note">{{ m.description }}</span>
                  }
                </div>
                <div class="badges">
                  <span class="badge" [class._actif]="m.actif" [class._inactif]="!m.actif">
                    {{ m.actif ? 'Active' : 'Inactive' }}
                  </span>
                  <span class="badge">{{ m.nb_affectations ?? 0 }} affectation(s)</span>
                  <span class="badge">{{ m.nb_enseignants ?? 0 }} enseignant(s)</span>
                </div>
                <div class="actions">
                  <button class="mini" type="button" (click)="ouvrirEditionMatiere(m)">
                    <i class="bi bi-pencil"></i> Modifier
                  </button>
                  @if (m.actif) {
                    <button
                      class="mini"
                      type="button"
                      [disabled]="actionEnCours()"
                      [title]="(m.nb_affectations ?? 0) > 0 ? 'Retire la matière des listes de saisie sans toucher aux affectations existantes' : ''"
                      (click)="basculerMatiere(m, false)"
                    >
                      <i class="bi bi-pause-circle"></i> Désactiver
                    </button>
                  } @else {
                    <button class="mini" type="button" [disabled]="actionEnCours()" (click)="basculerMatiere(m, true)">
                      <i class="bi bi-play-circle"></i> Réactiver
                    </button>
                  }
                </div>
              </article>
            }
          }
          @case ('types') {
            @for (t of typesCours(); track t.id) {
              <article class="ligne" [class._inactive]="!t.actif">
                <span class="pastille">{{ t.code }}</span>
                <div class="detail">
                  <b>{{ t.libelle }}</b>
                  <small>{{ t.code }}</small>
                  @if (t.description) {
                    <span class="note">{{ t.description }}</span>
                  }
                </div>
                <div class="badges">
                  <span class="badge" [class._actif]="t.actif" [class._inactif]="!t.actif">
                    {{ t.actif ? 'Actif' : 'Inactif' }}
                  </span>
                </div>
                <div class="actions">
                  <button class="mini" type="button" (click)="ouvrirEditionType(t)">
                    <i class="bi bi-pencil"></i> Modifier
                  </button>
                  @if (t.actif) {
                    <button class="mini" type="button" [disabled]="actionEnCours()" (click)="basculerType(t, false)">
                      <i class="bi bi-pause-circle"></i> Désactiver
                    </button>
                  } @else {
                    <button class="mini" type="button" [disabled]="actionEnCours()" (click)="basculerType(t, true)">
                      <i class="bi bi-play-circle"></i> Réactiver
                    </button>
                  }
                </div>
              </article>
            }
          }
          @case ('enseignants') {
            @for (e of enseignants(); track e.id) {
              <article class="ligne" [class._inactive]="!e.statut">
                <span class="pastille _eleve">
                  <i class="bi bi-person"></i>
                </span>
                <div class="detail">
                  <b>{{ e.prenom }} {{ e.nom }}</b>
                  <small>{{ e.email || 'Sans adresse e-mail' }}</small>
                  @if (e.profil?.matieres?.length) {
                    <span class="matieres">
                      @for (m of e.profil?.matieres; track m.id) {
                        <span class="badge">{{ m.sigle }}</span>
                      }
                    </span>
                  } @else {
                    <span class="note">Aucune matière déclarée</span>
                  }
                </div>
                <div class="badges">
                  <span class="badge" [class._actif]="e.statut" [class._inactif]="!e.statut">
                    {{ e.statut ? 'Actif' : 'Désactivé' }}
                  </span>
                </div>
                <div class="actions">
                  <button class="mini" type="button" (click)="ouvrirEditionEnseignant(e)">
                    <i class="bi bi-pencil"></i> Modifier
                  </button>
                </div>
              </article>
            }
          }
        }
      </div>

      @if ((metaCourant()?.last_page ?? 1) > 1) {
        <nav class="pagination" aria-label="Pagination">
          <button
            class="page"
            type="button"
            [disabled]="(metaCourant()?.current_page ?? 1) <= 1"
            (click)="aller((metaCourant()?.current_page ?? 1) - 1)"
          >
            <i class="bi bi-chevron-left"></i>
          </button>
          <span class="page _courante">{{ metaCourant()?.current_page }} / {{ metaCourant()?.last_page }}</span>
          <button
            class="page"
            type="button"
            [disabled]="(metaCourant()?.current_page ?? 1) >= (metaCourant()?.last_page ?? 1)"
            (click)="aller((metaCourant()?.current_page ?? 1) + 1)"
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </nav>
      }
    }

    @if (formulaireOuvert()) {
      <div class="fenetre" (click)="fermerFormulaire()">
        <div class="panneau" (click)="$event.stopPropagation()">
          <div class="panneau-entete">
            <h2>{{ titreFormulaire() }}</h2>
            <button class="fermer" type="button" (click)="fermerFormulaire()" aria-label="Fermer">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          @switch (onglet()) {
            @case ('classes') {
              <div class="formulaire">
                <label class="champ" [class._erreur]="!!erreurs()['nom']">
                  <span>Nom complet *</span>
                  <input [ngModel]="classe().nom" (ngModelChange)="setClasse('nom', $event)" placeholder="Terminale" />
                  @if (erreurs()['nom']; as e) {
                    <span class="erreur">{{ e }}</span>
                  }
                </label>
                <label class="champ" [class._erreur]="!!erreurs()['sigle']">
                  <span>Sigle *</span>
                  <input [ngModel]="classe().sigle" (ngModelChange)="setClasse('sigle', $event)" placeholder="Tle" />
                  @if (erreurs()['sigle']; as e) {
                    <span class="erreur">{{ e }}</span>
                  }
                  <span class="aide">Le sigle est unique dans le cabinet : il sert d’étiquette partout.</span>
                </label>
              </div>
            }
            @case ('matieres') {
              <div class="formulaire">
                <label class="champ" [class._erreur]="!!erreurs()['nom']">
                  <span>Nom *</span>
                  <input [ngModel]="matiere().nom" (ngModelChange)="setMatiere('nom', $event)" placeholder="Mathématiques" />
                  @if (erreurs()['nom']; as e) {
                    <span class="erreur">{{ e }}</span>
                  }
                </label>
                <label class="champ" [class._erreur]="!!erreurs()['sigle']">
                  <span>Sigle *</span>
                  <input [ngModel]="matiere().sigle" (ngModelChange)="setMatiere('sigle', $event)" placeholder="MATH" />
                  @if (erreurs()['sigle']; as e) {
                    <span class="erreur">{{ e }}</span>
                  }
                </label>
                <label class="champ">
                  <span>Description</span>
                  <textarea
                    rows="2"
                    [ngModel]="matiere().description ?? ''"
                    (ngModelChange)="setMatiere('description', $event || null)"
                    placeholder="Calcul, géométrie et statistiques"
                  ></textarea>
                </label>
                <div class="avertissement">
                  <i class="bi bi-info-circle"></i>
                  <span>Une matière naît active. La désactiver la retire des listes de saisie sans toucher aux affectations déjà créées.</span>
                </div>
              </div>
            }
            @case ('types') {
              <div class="formulaire">
                <label class="champ" [class._erreur]="!!erreurs()['libelle']">
                  <span>Libellé *</span>
                  <input [ngModel]="typeCours().libelle" (ngModelChange)="setType('libelle', $event)" placeholder="Domicile" />
                  @if (erreurs()['libelle']; as e) {
                    <span class="erreur">{{ e }}</span>
                  }
                </label>
                <label class="champ" [class._erreur]="!!erreurs()['code']">
                  <span>Code *</span>
                  <input [ngModel]="typeCours().code" (ngModelChange)="setType('code', $event)" placeholder="DOM" />
                  @if (erreurs()['code']; as e) {
                    <span class="erreur">{{ e }}</span>
                  }
                </label>
                <label class="champ">
                  <span>Description</span>
                  <textarea
                    rows="2"
                    [ngModel]="typeCours().description ?? ''"
                    (ngModelChange)="setType('description', $event || null)"
                    placeholder="Cours au domicile de l’élève"
                  ></textarea>
                </label>
              </div>
            }
            @case ('enseignants') {
              <div class="formulaire">
                <div class="grille">
                  <label class="champ" [class._erreur]="!!erreurs()['prenom']">
                    <span>Prénom *</span>
                    <input [ngModel]="enseignant().prenom" (ngModelChange)="setEnseignant('prenom', $event)" />
                    @if (erreurs()['prenom']; as e) {
                      <span class="erreur">{{ e }}</span>
                    }
                  </label>
                  <label class="champ" [class._erreur]="!!erreurs()['nom']">
                    <span>Nom *</span>
                    <input [ngModel]="enseignant().nom" (ngModelChange)="setEnseignant('nom', $event)" />
                    @if (erreurs()['nom']; as e) {
                      <span class="erreur">{{ e }}</span>
                    }
                  </label>
                </div>
                <label class="champ" [class._erreur]="!!erreurs()['email']">
                  <span>Adresse e-mail</span>
                  <input
                    type="email"
                    [ngModel]="enseignant().email ?? ''"
                    (ngModelChange)="setEnseignant('email', $event || null)"
                    placeholder="nom@cabinet.bf"
                  />
                  @if (erreurs()['email']; as e) {
                    <span class="erreur">{{ e }}</span>
                  }
                </label>
                @if (!enseignant().id) {
                  <label class="champ" [class._erreur]="!!erreurs()['password']">
                    <span>Mot de passe *</span>
                    <input
                      type="password"
                      autocomplete="new-password"
                      [ngModel]="enseignant().password ?? ''"
                      (ngModelChange)="setEnseignant('password', $event)"
                    />
                    @if (erreurs()['password']; as e) {
                      <span class="erreur">{{ e }}</span>
                    }
                    <span class="aide">6 caractères minimum. Il sert à sa première connexion à l’espace enseignant.</span>
                  </label>
                }
                <div class="grille">
                  <label class="champ">
                    <span>Téléphone WhatsApp</span>
                    <input
                      [ngModel]="enseignant().telephone_whatsapp ?? ''"
                      (ngModelChange)="setEnseignant('telephone_whatsapp', $event || null)"
                    />
                  </label>
                  <label class="champ">
                    <span>Téléphone d’appel</span>
                    <input
                      [ngModel]="enseignant().telephone_appel ?? ''"
                      (ngModelChange)="setEnseignant('telephone_appel', $event || null)"
                    />
                  </label>
                </div>
                <div class="grille">
                  <label class="champ">
                    <span>Diplôme le plus élevé</span>
                    <input [ngModel]="enseignant().diplome_max ?? ''" (ngModelChange)="setEnseignant('diplome_max', $event || null)" />
                  </label>
                  <label class="champ">
                    <span>Lieu de service</span>
                    <input
                      [ngModel]="enseignant().lieu_de_service ?? ''"
                      (ngModelChange)="setEnseignant('lieu_de_service', $event || null)"
                    />
                  </label>
                </div>
                <div class="grille">
                  <label class="champ">
                    <span>Orange Money</span>
                    <input
                      [ngModel]="enseignant().numero_orange_money ?? ''"
                      (ngModelChange)="setEnseignant('numero_orange_money', $event || null)"
                    />
                  </label>
                  <label class="champ">
                    <span>Domicile</span>
                    <input [ngModel]="enseignant().domicile ?? ''" (ngModelChange)="setEnseignant('domicile', $event || null)" />
                  </label>
                </div>
                <label class="champ">
                  <span>Matières enseignées</span>
                  <div class="matieres">
                    @for (m of toutesMatieres(); track m.id) {
                      <button
                        type="button"
                        class="badge"
                        [class._actif]="matiereChoisie(m.id)"
                        [style.cursor]="'pointer'"
                        (click)="basculerMatiereChoisie(m.id)"
                      >
                        {{ m.sigle }}
                      </button>
                    }
                  </div>
                  <span class="aide">Les matières cochées guident l’administrateur lors d’une affectation à un contrat.</span>
                </label>
                <label class="case">
                  <input
                    type="checkbox"
                    [ngModel]="enseignant().frais_annuel_regle ?? false"
                    (ngModelChange)="setEnseignant('frais_annuel_regle', $event)"
                  />
                  <span>
                    <b>Frais annuels réglés</b>
                    <small>Repère de suivi administratif de l’enseignant.</small>
                  </span>
                </label>
              </div>
            }
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
    }
  `,
})
export class ReferentielsComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly onglets = ONGLETS;

  protected readonly onglet = signal<Onglet>('classes');
  protected readonly charge = signal(true);
  protected readonly message = signal('');
  protected readonly succes = signal('');
  protected readonly actionEnCours = signal(false);
  protected readonly recherche = signal('');
  protected readonly filtreActif = signal('');

  protected readonly classes = signal<ClasseReferentiel[]>([]);
  protected readonly matieres = signal<MatiereReferentiel[]>([]);
  protected readonly typesCours = signal<TypeCoursReferentiel[]>([]);
  protected readonly enseignants = signal<EnseignantReferentiel[]>([]);

  protected readonly metaClasses = signal<MetaPage | null>(null);
  protected readonly metaMatieres = signal<MetaPage | null>(null);
  protected readonly metaTypes = signal<MetaPage | null>(null);
  protected readonly metaEnseignants = signal<MetaPage | null>(null);

  /** Sert au sélecteur « matières enseignées » de la fiche enseignant. */
  protected readonly toutesMatieres = signal<MatiereReferentiel[]>([]);

  protected readonly formulaireOuvert = signal(false);
  protected readonly sauvegarde = signal(false);
  protected readonly erreurs = signal<Record<string, string>>({});

  protected readonly classe = signal<CreneauClasse & { id: number | null }>({ id: null, ...MODELE_CLASSE });
  protected readonly matiere = signal<CreneauMatiere & { id: number | null }>({ id: null, ...MODELE_MATIERE });
  protected readonly typeCours = signal<CreneauTypeCours & { id: number | null }>({ id: null, ...MODELE_TYPE });
  protected readonly enseignant = signal<CreneauEnseignant & { id: number | null; matieres: number[] }>({
    id: null,
    nom: '',
    prenom: '',
    email: null,
    password: null,
    telephone_whatsapp: null,
    telephone_appel: null,
    numero_orange_money: null,
    diplome_max: null,
    lieu_de_service: null,
    domicile: null,
    frais_annuel_regle: false,
    matieres: [],
  });

  protected readonly listeCourante = computed<unknown[]>(() => {
    switch (this.onglet()) {
      case 'classes':
        return this.classes();
      case 'matieres':
        return this.matieres();
      case 'types':
        return this.typesCours();
      default:
        return this.enseignants();
    }
  });

  protected readonly metaCourant = computed<MetaPage | null>(() => {
    switch (this.onglet()) {
      case 'classes':
        return this.metaClasses();
      case 'matieres':
        return this.metaMatieres();
      case 'types':
        return this.metaTypes();
      default:
        return this.metaEnseignants();
    }
  });

  protected readonly phrase = computed(
    () => ONGLETS.find((o) => o.id === this.onglet())?.phrase ?? '',
  );

  ngOnInit(): void {
    void this.charger();
    void this.chargerMatieresPourLeFormulaire();
  }

  /* ---------- Onglets ---------- */

  protected changerOnglet(id: Onglet): void {
    if (this.onglet() === id) return;
    this.onglet.set(id);
    this.message.set('');
    this.succes.set('');
    void this.charger();
  }

  protected total(id: Onglet): number | null {
    switch (id) {
      case 'classes':
        return this.metaClasses()?.total ?? null;
      case 'matieres':
        return this.metaMatieres()?.total ?? null;
      case 'types':
        return this.metaTypes()?.total ?? null;
      default:
        return this.metaEnseignants()?.total ?? null;
    }
  }

  protected placeholderRecherche(): string {
    switch (this.onglet()) {
      case 'classes':
        return 'Rechercher une classe…';
      case 'matieres':
        return 'Rechercher une matière…';
      case 'types':
        return 'Rechercher un type de cours…';
      default:
        return 'Rechercher un enseignant (nom, prénom, e-mail)…';
    }
  }

  protected libelleAjout(): string {
    switch (this.onglet()) {
      case 'classes':
        return 'Nouvelle classe';
      case 'matieres':
        return 'Nouvelle matière';
      case 'types':
        return 'Nouveau type';
      default:
        return 'Nouvel enseignant';
    }
  }

  protected vide(): string {
    switch (this.onglet()) {
      case 'classes':
        return 'Aucune classe créée.';
      case 'matieres':
        return 'Aucune matière créée.';
      case 'types':
        return 'Aucun type de cours créé.';
      default:
        return 'Aucun enseignant enregistré.';
    }
  }

  /* ---------- Données ---------- */

  private async charger(page = 1): Promise<void> {
    this.charge.set(true);
    try {
      const q = this.recherche().trim();
      switch (this.onglet()) {
        case 'classes': {
          const r = await firstValueFrom(this.api.getClasses(page, 15, q));
          this.classes.set(r.data ?? []);
          this.metaClasses.set(r.meta ?? null);
          break;
        }
        case 'matieres': {
          const r = await firstValueFrom(this.api.getMatieres(page, 15, q, this.filtreActif()));
          this.matieres.set(r.data ?? []);
          this.metaMatieres.set(r.meta ?? null);
          break;
        }
        case 'types': {
          const r = await firstValueFrom(this.api.getTypesCours(page, 15, q));
          this.typesCours.set(r.data ?? []);
          this.metaTypes.set(r.meta ?? null);
          break;
        }
        default: {
          const r = await firstValueFrom(this.api.getEnseignantsReferentiel(page, 15, q));
          this.enseignants.set(r.data ?? []);
          this.metaEnseignants.set(r.meta ?? null);
          break;
        }
      }
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.charge.set(false);
    }
  }

  /** Alimente le sélecteur de matières de la fiche enseignant. */
  private async chargerMatieresPourLeFormulaire(): Promise<void> {
    try {
      const r = await firstValueFrom(this.api.getMatieres(1, 100));
      this.toutesMatieres.set(r.data ?? []);
    } catch {
      // Le sélecteur restera vide : la fiche reste saisissable sans elle.
    }
  }

  protected setRecherche(valeur: string): void {
    this.recherche.set(valeur);
  }

  protected rechercher(): void {
    void this.charger(1);
  }

  protected setFiltreActif(valeur: string): void {
    this.filtreActif.set(valeur);
    void this.charger(1);
  }

  protected aller(page: number): void {
    void this.charger(page);
  }

  /* ---------- Formulaire ---------- */

  protected ouvrirCreation(): void {
    this.erreurs.set({});
    switch (this.onglet()) {
      case 'classes':
        this.classe.set({ id: null, ...MODELE_CLASSE });
        break;
      case 'matieres':
        this.matiere.set({ id: null, ...MODELE_MATIERE });
        break;
      case 'types':
        this.typeCours.set({ id: null, ...MODELE_TYPE });
        break;
      default:
        this.enseignant.set({
          id: null,
          nom: '',
          prenom: '',
          email: null,
          password: null,
          telephone_whatsapp: null,
          telephone_appel: null,
          numero_orange_money: null,
          diplome_max: null,
          lieu_de_service: null,
          domicile: null,
          frais_annuel_regle: false,
          matieres: [],
        });
        break;
    }
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEditionClasse(c: ClasseReferentiel): void {
    this.classe.set({ id: c.id, nom: c.nom, sigle: c.sigle });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEditionMatiere(m: MatiereReferentiel): void {
    this.matiere.set({ id: m.id, nom: m.nom, sigle: m.sigle, description: m.description });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEditionType(t: TypeCoursReferentiel): void {
    this.typeCours.set({ id: t.id, code: t.code, libelle: t.libelle, description: t.description });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEditionEnseignant(e: EnseignantReferentiel): void {
    this.enseignant.set({
      id: e.id,
      nom: e.nom,
      prenom: e.prenom,
      email: e.email,
      telephone_whatsapp: e.telephone_whatsapp,
      telephone_appel: e.telephone_appel,
      numero_orange_money: e.profil?.numero_orange_money ?? null,
      diplome_max: e.profil?.diplome_max ?? null,
      lieu_de_service: e.profil?.lieu_de_service ?? null,
      domicile: e.profil?.domicile ?? null,
      frais_annuel_regle: e.profil?.frais_annuel_regle ?? false,
      matieres: e.profil?.matieres?.map((m) => m.id) ?? [],
    });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected fermerFormulaire(): void {
    this.formulaireOuvert.set(false);
  }

  protected titreFormulaire(): string {
    const creation = this.libelleAjout();
    return this.idFormulaire() === null ? creation : creation.replace('Nouveau', 'Modifier le').replace('Nouvelle', 'Modifier la').replace('Nouvel', 'Modifier l’');
  }

  private idFormulaire(): number | null {
    switch (this.onglet()) {
      case 'classes':
        return this.classe().id;
      case 'matieres':
        return this.matiere().id;
      case 'types':
        return this.typeCours().id;
      default:
        return this.enseignant().id;
    }
  }

  protected setClasse(champ: 'nom' | 'sigle', valeur: string): void {
    this.classe.set({ ...this.classe(), [champ]: valeur });
    this.effacerErreur(champ);
  }

  protected setMatiere(champ: 'nom' | 'sigle' | 'description', valeur: string | null): void {
    this.matiere.set({ ...this.matiere(), [champ]: valeur });
    this.effacerErreur(champ);
  }

  protected setType(champ: 'code' | 'libelle' | 'description', valeur: string | null): void {
    this.typeCours.set({ ...this.typeCours(), [champ]: valeur });
    this.effacerErreur(champ);
  }

  protected setEnseignant(champ: keyof CreneauEnseignant, valeur: unknown): void {
    this.enseignant.set({ ...this.enseignant(), [champ]: valeur });
    if (typeof champ === 'string') this.effacerErreur(champ);
  }

  protected matiereChoisie(id: number): boolean {
    return this.enseignant().matieres.includes(id);
  }

  protected basculerMatiereChoisie(id: number): void {
    const actuelles = this.enseignant().matieres;
    this.enseignant.set({
      ...this.enseignant(),
      matieres: actuelles.includes(id) ? actuelles.filter((m) => m !== id) : [...actuelles, id],
    });
  }

  /* ---------- Écriture ---------- */

  protected async enregistrer(): Promise<void> {
    this.message.set('');
    this.succes.set('');
    this.erreurs.set({});

    this.sauvegarde.set(true);
    try {
      switch (this.onglet()) {
        case 'classes': {
          const c = this.classe();
          if (!c.nom.trim() || !c.sigle.trim()) {
            this.erreurs.set({
              [c.nom.trim() ? 'sigle' : 'nom']: 'Le nom et le sigle sont obligatoires.',
            });
            return;
          }
          const payload: CreneauClasse = { nom: c.nom.trim(), sigle: c.sigle.trim() };
          if (c.id) {
            await firstValueFrom(this.api.majClasse(c.id, payload));
          } else {
            await firstValueFrom(this.api.creerClasse(payload));
          }
          break;
        }
        case 'matieres': {
          const m = this.matiere();
          if (!m.nom.trim() || !m.sigle.trim()) {
            this.erreurs.set({ [m.nom.trim() ? 'sigle' : 'nom']: 'Le nom et le sigle sont obligatoires.' });
            return;
          }
          const payload: CreneauMatiere = {
            nom: m.nom.trim(),
            sigle: m.sigle.trim(),
            description: m.description?.trim() || null,
          };
          if (m.id) {
            await firstValueFrom(this.api.majMatiere(m.id, payload));
          } else {
            await firstValueFrom(this.api.creerMatiere(payload));
          }
          break;
        }
        case 'types': {
          const t = this.typeCours();
          if (!t.code.trim() || !t.libelle.trim()) {
            this.erreurs.set({ [t.libelle.trim() ? 'code' : 'libelle']: 'Le code et le libellé sont obligatoires.' });
            return;
          }
          const payload: CreneauTypeCours = {
            code: t.code.trim().toUpperCase(),
            libelle: t.libelle.trim(),
            description: t.description?.trim() || null,
          };
          if (t.id) {
            await firstValueFrom(this.api.majTypeCours(t.id, payload));
          } else {
            await firstValueFrom(this.api.creerTypeCours(payload));
          }
          break;
        }
        default: {
          const e = this.enseignant();
          if (!e.nom.trim() || !e.prenom.trim()) {
            this.erreurs.set({ [e.prenom.trim() ? 'nom' : 'prenom']: 'Le nom et le prénom sont obligatoires.' });
            return;
          }
          if (!e.id && !e.password) {
            this.erreurs.set({ password: 'Un mot de passe est obligatoire à la création.' });
            return;
          }
          const payload: CreneauEnseignant = {
            nom: e.nom.trim(),
            prenom: e.prenom.trim(),
            email: e.email?.trim() || null,
            telephone_whatsapp: e.telephone_whatsapp,
            telephone_appel: e.telephone_appel,
            numero_orange_money: e.numero_orange_money,
            diplome_max: e.diplome_max,
            lieu_de_service: e.lieu_de_service,
            domicile: e.domicile,
            frais_annuel_regle: e.frais_annuel_regle ?? false,
            matieres: e.matieres,
          };
          if (!e.id && e.password) payload.password = e.password;
          if (e.id) {
            await firstValueFrom(this.api.majEnseignant(e.id, payload));
          } else {
            await firstValueFrom(this.api.creerEnseignant(payload));
          }
          break;
        }
      }

      this.formulaireOuvert.set(false);
      this.succes.set(this.libelleAjout().replace('Nouveau ', '').replace('Nouvelle ', '').replace('Nouvel ', '') + ' enregistrée.');
      await this.charger(this.metaCourant()?.current_page ?? 1);
      if (this.onglet() === 'matieres') await this.chargerMatieresPourLeFormulaire();
    } catch (e) {
      const champs = (e as { error?: { erreurs?: Record<string, string[]> } })?.error?.erreurs;
      if (champs) {
        const aplat: Record<string, string> = {};
        for (const champ of Object.keys(champs)) {
          if (champs[champ]?.[0]) aplat[champ] = champs[champ][0];
        }
        this.erreurs.set(aplat);
        if (!Object.keys(aplat).length) this.message.set(messageErreurApi(e));
      } else {
        this.message.set(messageErreurApi(e));
      }
    } finally {
      this.sauvegarde.set(false);
    }
  }

  /* ---------- Actions ---------- */

  /**
   * Une classe utilisée n'est pas supprimable. Le bouton est désactivé avec son
   * explication ; si l'appel part quand même (onglet référencé périmé), le 409
   * est remonté tel quel plutôt qu'un échec muet.
   */
  protected estClasseUtilisee(c: ClasseReferentiel): boolean {
    return (c.nb_eleves ?? 0) > 0 || (c.nb_demandes_cours ?? 0) > 0;
  }

  protected async supprimerClasse(c: ClasseReferentiel): Promise<void> {
    if (this.estClasseUtilisee(c)) {
      this.message.set(
        `« ${c.sigle} » porte encore ${c.nb_eleves ?? 0} élève(s) et ${c.nb_demandes_cours ?? 0} demande(s) de cours : réaffectez-les avant de la supprimer.`,
      );
      return;
    }
    if (!confirm(`Supprimer la classe « ${c.sigle} » ?\n\nAucun élève n'y est rattaché : la suppression est définitive.`)) {
      return;
    }
    this.actionEnCours.set(true);
    this.message.set('');
    this.succes.set('');
    try {
      await firstValueFrom(this.api.supprimerClasse(c.id));
      this.succes.set('Classe supprimée.');
      await this.charger(this.metaClasses()?.current_page ?? 1);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.actionEnCours.set(false);
    }
  }

  protected async basculerMatiere(m: MatiereReferentiel, actif: boolean): Promise<void> {
    const message = actif
      ? `Réactiver « ${m.nom} » ?\n\nElle réapparaîtra dans les listes de saisie des affectations.`
      : `Désactiver « ${m.nom} » ?\n\nElle disparaîtra des listes de saisie. Les ${m.nb_affectations ?? 0} affectation(s) déjà créées et l'historique de facturation ne sont pas touchés.`;
    if (!confirm(message)) return;

    await this.action(
      () =>
        actif
          ? firstValueFrom(this.api.activerMatiere(m.id))
          : firstValueFrom(this.api.desactiverMatiere(m.id)),
      actif ? `« ${m.nom} » est active.` : `« ${m.nom} » est désactivée.`,
    );
  }

  protected async basculerType(t: TypeCoursReferentiel, actif: boolean): Promise<void> {
    const message = actif
      ? `Réactiver le type « ${t.libelle} » ?\n\nIl redeviendra sélectionnable pour un nouveau contrat.`
      : `Désactiver le type « ${t.libelle} » ?\n\nLes contrats qui l'utilisent sont conservés, mais il ne sera plus proposé à la création.`;
    if (!confirm(message)) return;

    await this.action(
      () =>
        actif
          ? firstValueFrom(this.api.activerTypeCours(t.id))
          : firstValueFrom(this.api.desactiverTypeCours(t.id)),
      actif ? `« ${t.libelle} » est actif.` : `« ${t.libelle} » est inactif.`,
    );
  }

  private async action(appel: () => Promise<unknown>, succes: string): Promise<void> {
    this.actionEnCours.set(true);
    this.message.set('');
    this.succes.set('');
    try {
      await appel();
      this.succes.set(succes);
      await this.charger(this.metaCourant()?.current_page ?? 1);
      if (this.onglet() === 'matieres') await this.chargerMatieresPourLeFormulaire();
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.actionEnCours.set(false);
    }
  }

  private effacerErreur(champ: string): void {
    const courantes = this.erreurs();
    if (!courantes[champ]) return;
    const suivante = { ...courantes };
    delete suivante[champ];
    this.erreurs.set(suivante);
  }
}