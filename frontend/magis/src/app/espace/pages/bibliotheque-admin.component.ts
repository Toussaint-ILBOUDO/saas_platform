import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  DocumentBibliothequeEspace,
  MetaPage,
  MOTIFS_SIGNALEMENT,
  SignalementAdmin,
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

const STATUTS_SIGNALEMENT: { valeur: string; libelle: string }[] = [
  { valeur: '', libelle: 'Tous les statuts' },
  { valeur: 'en_attente', libelle: 'En attente' },
  { valeur: 'en_cours', libelle: 'En cours' },
  { valeur: 'traite', libelle: 'Traité' },
  { valeur: 'rejete', libelle: 'Rejeté' },
];

const COULEUR_STATUT_SIGNALEMENT: Record<string, string> = {
  en_attente: 'var(--mpc-avertissement, #d97706)',
  en_cours: 'var(--mpc-bleu-clair, #1d4e89)',
  traite: 'var(--mpc-succes)',
  rejete: 'var(--mpc-texte-doux)',
};

/**
 * Modération de la bibliothèque (admin du cabinet).
 *
 * Deux onglets : la file de validation des documents (liste filtrable avec
 * compteurs de tête, actions valider/refuser/archiver/supprimer) et la file
 * des signalements (traiter = clôturer la trace). Le détail d'un document
 * ouvre la même fenêtre que l'espace membre, enrichie des signalements.
 */
@Component({
  imports: [FormsModule],
  selector: 'espace-bibliotheque-admin',
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
      .compteurs {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
      }
      .compteur {
        padding: 0.5rem 0.9rem;
        border-radius: 0.75rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
      }
      .compteur b {
        color: var(--mpc-bleu);
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
        flex-wrap: wrap;
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
      .mini._valider {
        border-color: var(--mpc-succes);
        color: var(--mpc-succes);
      }
      .mini._refuser,
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
      .signalement {
        padding: 0.65rem 0.8rem;
        border-radius: 0.7rem;
        background: var(--mpc-danger-tint);
        border: 1px solid var(--mpc-danger);
        font-size: 0.85rem;
      }
      .signalement .motif {
        font-weight: 700;
        color: var(--mpc-danger);
      }
      .lien {
        border: none;
        background: none;
        padding: 0;
        font: inherit;
        font-size: inherit;
        font-weight: 700;
        color: var(--mpc-primaire);
        cursor: pointer;
        text-decoration: underline;
      }
      .aide {
        font-size: 0.74rem;
        color: var(--mpc-texte-doux);
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
        <h1>Modération — Bibliothèque</h1>
        <p>Validez, refusez et archivez les documents déposés ; traitez les signalements des membres.</p>
      </div>
    </div>

    @if (message()) {
      <div class="toast"><i class="bi bi-exclamation-triangle"></i>{{ message() }}</div>
    }
    @if (succes()) {
      <div class="toast _succes"><i class="bi bi-check-circle"></i>{{ succes() }}</div>
    }

    <nav class="onglets">
      <button class="onglet" [class._actif]="onglet() === 'documents'" type="button" (click)="basculerOnglet('documents')">
        <i class="bi bi-file-earmark-text"></i> Documents
      </button>
      <button class="onglet" [class._actif]="onglet() === 'signalements'" type="button" (click)="basculerOnglet('signalements')">
        <i class="bi bi-exclamation-triangle"></i> Signalements
      </button>
    </nav>

    @switch (onglet()) {
      @case ('documents') {
        @if (stats(); as s) {
          <div class="compteurs">
            <span class="compteur"><b>{{ s['total'] ?? 0 }}</b> au total</span>
            <span class="compteur"><b>{{ s['en_attente'] ?? 0 }}</b> en attente</span>
            <span class="compteur"><b>{{ s['publies'] ?? 0 }}</b> publiés</span>
            <span class="compteur"><b>{{ s['refuses'] ?? 0 }}</b> refusés</span>
            <span class="compteur"><b>{{ s['archives'] ?? 0 }}</b> archivés</span>
          </div>
        }

        <div class="barre">
          <label class="recherche">
            <i class="bi bi-search"></i>
            <input type="search" #terme [value]="recherche()" (keyup.enter)="rechercher(terme.value)" placeholder="Rechercher…" />
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
          <div class="vide"><i class="bi bi-file-earmark-text"></i> Aucun document à modérer.</div>
        } @else {
          <div class="liste">
            @for (doc of documents(); track doc.id) {
              <article class="carte">
                <div class="carte-entete">
                  <h3>{{ doc.titre }}</h3>
                  <span class="badge" [style.color]="couleurStatut(doc.statut)">{{ libelleStatut(doc.statut) }}</span>
                </div>
                <div class="carte-meta">
                  @if (doc.auteur) {
                    <span><i class="bi bi-person"></i> {{ doc.auteur.prenom }} {{ doc.auteur.nom }}</span>
                  }
                  @if (doc.type_document) {
                    <span><i class="bi bi-bookmark"></i> {{ doc.type_document.sigle }}</span>
                  }
                  @if (doc.matiere) {
                    <span><i class="bi bi-journal"></i> {{ doc.matiere.nom }}</span>
                  }
                  @if (doc.fichier) {
                    <span><i class="bi bi-paperclip"></i> {{ doc.fichier.nom }}</span>
                  }
                  @if (doc.is_public) {
                    <span class="badge" style="background: var(--mpc-succes-tint); color: var(--mpc-succes)">Public</span>
                  } @else {
                    <span class="badge">Privé</span>
                  }
                </div>
                <div class="carte-actions">
                  <button class="mini" type="button" (click)="ouvrirDetail(doc.id)"><i class="bi bi-eye"></i> Détail</button>
                  @if (doc.statut === 'en_attente' || doc.statut === 'brouillon') {
                    <button class="mini _valider" type="button" (click)="valider(doc)"><i class="bi bi-check2"></i> Valider</button>
                    <button class="mini _refuser" type="button" (click)="refuser(doc)"><i class="bi bi-x-lg"></i> Refuser</button>
                  }
                  @if (doc.statut === 'publie' || doc.statut === 'refuse') {
                    <button class="mini" type="button" (click)="archiver(doc)"><i class="bi bi-archive"></i> Archiver</button>
                  }
                  <button class="mini _danger" type="button" (click)="supprimer(doc)"><i class="bi bi-trash"></i></button>
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

      @case ('signalements') {
        @if (stats(); as s) {
          <div class="compteurs">
            <span class="compteur"><b>{{ s['total'] ?? 0 }}</b> au total</span>
            <span class="compteur"><b>{{ s['en_attente'] ?? 0 }}</b> en attente</span>
            <span class="compteur"><b>{{ s['en_cours'] ?? 0 }}</b> en cours</span>
            <span class="compteur"><b>{{ s['traite'] ?? 0 }}</b> traités</span>
            <span class="compteur"><b>{{ s['rejete'] ?? 0 }}</b> rejetés</span>
          </div>
        }

        <div class="barre" style="flex-wrap: wrap; gap: 0.5rem">
          <select class="filtre" [value]="filtreSignalement()" (change)="changerFiltreSignalement($event)">
            @for (s of statutsSignalement; track s.valeur) {
              <option [value]="s.valeur">{{ s.libelle }}</option>
            }
          </select>
          <select class="filtre" [value]="filtreMotifSignalement()" (change)="changerFiltreMotif($event)">
            <option value="">Tous les motifs</option>
            @for (m of motifs; track m.valeur) {
              <option [value]="m.valeur">{{ m.libelle }}</option>
            }
          </select>
          <input
            class="filtre"
            type="date"
            title="Date de début"
            [value]="filtreDateDebut()"
            (change)="changerFiltreDateDebut($event)"
          />
          <input
            class="filtre"
            type="date"
            title="Date de fin"
            [value]="filtreDateFin()"
            (change)="changerFiltreDateFin($event)"
          />
        </div>

        @if (charge()) {
          <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
        } @else if (signalements().length === 0) {
          <div class="vide">
            <i class="bi bi-shield-check"></i>
            {{ filtreSignalementActif() ? 'Aucun signalement ne correspond à ces filtres.' : 'Aucun signalement.' }}
          </div>
        } @else {
          <div class="liste">
            @for (s of signalements(); track s.id) {
              <article class="carte">
                <div class="carte-entete">
                  <h3>{{ s.motif_libelle || s.motif }}</h3>
                  <span class="badge" [style.color]="couleurStatutSignalement(s.statut)">
                    {{ s.statut_libelle || s.statut }}
                  </span>
                </div>
                @if (s.description) {
                  <p class="aide" style="margin: 0">{{ s.description }}</p>
                }
                @if (s.precisions) {
                  <p class="aide" style="margin: 0.25rem 0 0"><b>Précisions :</b> {{ s.precisions }}</p>
                }
                <div class="carte-meta">
                  @if (s.anonyme) {
                    <span><i class="bi bi-person-blind"></i> Anonyme</span>
                  } @else if (s.user) {
                    <span><i class="bi bi-person"></i> {{ s.user.prenom }} {{ s.user.nom }}</span>
                  }
                  @if (s.email_contact) {
                    <span><i class="bi bi-envelope"></i> {{ s.email_contact }}</span>
                  }
                  @if (s.document) {
                    <span><i class="bi bi-file-earmark"></i> {{ s.document.titre }}</span>
                    @if (s.document.statut === 'publie') {
                      <span class="badge" style="background: var(--mpc-succes-tint); color: var(--mpc-succes)">Publié</span>
                    } @else {
                      <span class="badge">{{ s.document.statut }}</span>
                    }
                  }
                  <span><i class="bi bi-clock"></i> {{ dateCourte(s.created_at) }}</span>
                </div>
                @if (s.traite_at || s.traite_par) {
                  <p
                    style="margin: 0.4rem 0 0; font-size: 0.85rem; font-weight: 700"
                    [style.color]="couleurStatutSignalement(s.statut)"
                  >
                    @if (s.statut === 'rejete') {
                      <i class="bi bi-x-octagon"></i> Rejeté
                    } @else {
                      <i class="bi bi-check2-circle"></i> Traité
                    }
                    @if (s.traite_at) {
                      le {{ dateCourte(s.traite_at) }}
                    }
                    @if (s.traite_par) {
                      par {{ s.traite_par.prenom }} {{ s.traite_par.nom }}
                    }
                  </p>
                }
                @if (s.note_admin) {
                  <p style="margin: 0.4rem 0 0; font-size: 0.85rem; color: var(--mpc-texte-doux)">
                    <b>Note :</b> {{ s.note_admin }}
                  </p>
                }
                <div class="carte-actions" style="flex-wrap: wrap">
                  @if (s.document) {
                    <button class="mini" type="button" (click)="ouvrirDetail(s.document.id)"><i class="bi bi-eye"></i> Voir le document</button>
                  }
                  @if (s.statut === 'en_attente') {
                    <button class="mini" type="button" (click)="majStatutSignalement(s, 'en_cours')"><i class="bi bi-hourglass-split"></i> En cours</button>
                  }
                  @if (s.statut !== 'traite') {
                    <button class="mini _valider" type="button" (click)="majStatutSignalement(s, 'traite')"><i class="bi bi-check2"></i> Traité</button>
                  }
                  @if (s.statut !== 'rejete' && s.statut !== 'traite') {
                    <button class="mini _refuser" type="button" (click)="majStatutSignalement(s, 'rejete')"><i class="bi bi-x-lg"></i> Rejeter</button>
                  }
                  <button class="mini" type="button" (click)="ouvrirNoteSignalement(s)"><i class="bi bi-pencil"></i> Note</button>
                  @if (s.document && s.document.statut === 'publie') {
                    <button class="mini _danger" type="button" (click)="retirerDocument(s)"><i class="bi bi-eye-slash"></i> Dépublier</button>
                  }
                  @if (s.document && s.document.statut !== 'publie') {
                    <button class="mini _valider" type="button" (click)="reactiverDocument(s)"><i class="bi bi-eye"></i> Réactiver</button>
                  }
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
            @if (doc.auteur) {
              <span><i class="bi bi-person"></i> {{ doc.auteur.prenom }} {{ doc.auteur.nom }}</span>
            }
            @if (doc.type_document) {
              <span><i class="bi bi-bookmark"></i> {{ doc.type_document.nom }}</span>
            }
            @if (doc.matiere) {
              <span><i class="bi bi-journal"></i> {{ doc.matiere.nom }}</span>
            }
            @if (doc.classe) {
              <span><i class="bi bi-mortarboard"></i> {{ doc.classe.nom }}</span>
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
          @if (doc.fichier) {
            <a class="btn-neutral" style="display: inline-flex; gap: 0.4rem; text-decoration: none; margin-bottom: 0.8rem" [href]="urlTelechargement(doc.id)" target="_blank">
              <i class="bi bi-download"></i> Télécharger {{ doc.fichier.nom }} ({{ taille(doc.fichier.taille) }})
            </a>
          }

          @if ((doc.signalements ?? []).length > 0) {
            <div class="detail-section">
              <h3>Signalements</h3>
              @for (s of doc.signalements ?? []; track s.id) {
                <div class="signalement">
                  <div class="motif">
                    {{ s.motif_libelle || s.motif }} — {{ s.statut_libelle || libelleStatutSignalement(s.statut) }}
                  </div>
                  @if (s.description) {
                    <div>{{ s.description }}</div>
                  }
                  @if (s.precisions) {
                    <div style="margin-top: 0.3rem; font-size: 0.88rem; color: var(--mpc-texte-doux)"><b>Précisions :</b> {{ s.precisions }}</div>
                  }
                  @if (s.note_admin) {
                    <div style="margin-top: 0.3rem; font-size: 0.88rem; color: var(--mpc-texte-doux)"><b>Note admin :</b> {{ s.note_admin }}</div>
                  }
                  @if (s.traite_at || s.traite_par) {
                    <div
                      style="margin-top: 0.35rem; font-size: 0.88rem; font-weight: 700"
                      [style.color]="couleurStatutSignalement(s.statut)"
                    >
                      @if (s.statut === 'rejete') {
                        <i class="bi bi-x-octagon"></i> Rejeté
                      } @else {
                        <i class="bi bi-check2-circle"></i> Traité
                      }
                      @if (s.traite_at) {
                        le {{ dateCourte(s.traite_at) }}
                      }
                      @if (s.traite_par) {
                        par {{ s.traite_par.prenom }} {{ s.traite_par.nom }}
                      }
                    </div>
                  }
                  <div class="aide">{{ s.user?.prenom ?? 'Anonyme' }} {{ s.user?.nom ?? '' }} — {{ dateCourte(s.created_at) }}</div>
                </div>
              }
            </div>
          }

          <div class="detail-section">
            <h3>Commentaires ({{ totalCommentaires(doc.commentaires ?? []) }})</h3>
            @for (c of doc.commentaires ?? []; track c.id) {
              <div class="commentaire">
                <div class="auteur">{{ c.user?.prenom }} {{ c.user?.nom }} — {{ dateCourte(c.created_at) }}</div>
                <div>{{ c.contenu }}</div>
                @for (r of c.replies; track r.id) {
                  <div class="commentaire" style="margin-top: 0.5rem; margin-left: 1.2rem">
                    <div class="auteur">{{ r.user?.prenom }} {{ r.user?.nom }} — {{ dateCourte(r.created_at) }}</div>
                    <div>{{ r.contenu }}</div>
                  </div>
                }
              </div>
            } @empty {
              <span class="aide">Aucun commentaire.</span>
            }
          </div>

          <div class="detail-section">
            <h3>Actions</h3>
            <div class="carte-actions" style="border-top: none; padding-top: 0; margin-top: 0">
              @if (doc.statut === 'en_attente' || doc.statut === 'brouillon') {
                <button class="mini _valider" type="button" (click)="valider(doc); fermerDetail()"><i class="bi bi-check2"></i> Valider et publier</button>
                <button class="mini _refuser" type="button" (click)="refuser(doc); fermerDetail()"><i class="bi bi-x-lg"></i> Refuser</button>
              }
              @if (doc.statut === 'publie' || doc.statut === 'refuse') {
                <button class="mini" type="button" (click)="archiver(doc); fermerDetail()"><i class="bi bi-archive"></i> Archiver</button>
              }
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class BibliothequeAdminComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly statutsDocument = STATUTS_DOCUMENT;
  protected readonly statutsSignalement = STATUTS_SIGNALEMENT;
  protected readonly motifs = MOTIFS_SIGNALEMENT;

  protected readonly onglet = signal<'documents' | 'signalements'>('documents');
  protected readonly charge = signal(true);
  protected readonly message = signal('');
  protected readonly succes = signal('');

  protected readonly documents = signal<DocumentBibliothequeEspace[]>([]);
  protected readonly signalements = signal<SignalementAdmin[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly stats = signal<Record<string, number> | null>(null);
  protected readonly recherche = signal('');
  protected readonly filtreStatut = signal('');
  protected readonly filtreSignalement = signal('');
  protected readonly filtreMotifSignalement = signal('');
  protected readonly filtreDateDebut = signal('');
  protected readonly filtreDateFin = signal('');

  protected readonly detailOuvert = signal(false);
  protected readonly detail = signal<DocumentBibliothequeEspace | null>(null);

  async ngOnInit(): Promise<void> {
    await this.chargerOnglet();
  }

  protected async basculerOnglet(onglet: 'documents' | 'signalements'): Promise<void> {
    this.onglet.set(onglet);
    this.meta.set(null);
    this.stats.set(null);
    await this.chargerOnglet();
  }

  private async chargerOnglet(page = 1): Promise<void> {
    this.charge.set(true);
    this.message.set('');
    try {
      if (this.onglet() === 'documents') {
        const r = await firstValueFrom(
          this.api.getDocumentsModeration(page, 15, { search: this.recherche(), statut: this.filtreStatut() }),
        );
        this.documents.set(r.data ?? []);
        this.meta.set(r.meta ?? null);
        this.stats.set(r.meta?.stats ?? null);
      } else {
        const r = await firstValueFrom(
          this.api.getSignalementsModeration(page, 15, {
            statut: this.filtreSignalement(),
            motif: this.filtreMotifSignalement(),
            date_debut: this.filtreDateDebut(),
            date_fin: this.filtreDateFin(),
          }),
        );
        this.signalements.set(r.data ?? []);
        this.meta.set(r.meta ?? null);
        this.stats.set(r.meta?.stats ?? null);
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

  protected changerFiltreSignalement(evenement: Event): void {
    this.filtreSignalement.set((evenement.target as HTMLSelectElement).value);
    void this.chargerOnglet(1);
  }

  protected changerFiltreMotif(evenement: Event): void {
    this.filtreMotifSignalement.set((evenement.target as HTMLSelectElement).value);
    void this.chargerOnglet(1);
  }

  protected changerFiltreDateDebut(evenement: Event): void {
    this.filtreDateDebut.set((evenement.target as HTMLInputElement).value);
    void this.chargerOnglet(1);
  }

  protected changerFiltreDateFin(evenement: Event): void {
    this.filtreDateFin.set((evenement.target as HTMLInputElement).value);
    void this.chargerOnglet(1);
  }

  protected aller(page: number): void {
    void this.chargerOnglet(page);
  }

  /* ---------- Détail ---------- */

  protected async ouvrirDetail(id: number): Promise<void> {
    try {
      const doc = await firstValueFrom(this.api.getDocumentModeration(id));
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

  /* ---------- Actions ---------- */

  protected async valider(doc: DocumentBibliothequeEspace): Promise<void> {
    if (!confirm(`Valider et publier « ${doc.titre} » ?`)) return;
    await this.action(() => firstValueFrom(this.api.validerDocumentModeration(doc.id)), 'Document validé et publié.');
  }

  protected async refuser(doc: DocumentBibliothequeEspace): Promise<void> {
    if (!confirm(`Refuser « ${doc.titre} » ? L’auteur en sera informé.`)) return;
    await this.action(() => firstValueFrom(this.api.refuserDocumentModeration(doc.id)), 'Document refusé.');
  }

  protected async archiver(doc: DocumentBibliothequeEspace): Promise<void> {
    if (!confirm(`Archiver « ${doc.titre} » ?`)) return;
    await this.action(() => firstValueFrom(this.api.archiverDocumentModeration(doc.id)), 'Document archivé.');
  }

  protected async supprimer(doc: DocumentBibliothequeEspace): Promise<void> {
    if (!confirm(`Supprimer définitivement « ${doc.titre} » et son fichier ?`)) return;
    await this.action(() => firstValueFrom(this.api.supprimerDocumentModeration(doc.id)), 'Document supprimé.');
  }

  protected async traiter(s: SignalementAdmin): Promise<void> {
    await this.action(() => firstValueFrom(this.api.traiterSignalement(s.id)), 'Signalement traité.');
  }

  protected async majStatutSignalement(s: SignalementAdmin, statut: string): Promise<void> {
    const libelle =
      statut === 'traite' ? 'traité' : statut === 'rejete' ? 'rejeté' : statut === 'en_cours' ? 'en cours de traitement' : statut;
    if (!confirm(`Marquer ce signalement comme ${libelle} ?`)) return;
    await this.action(
      () => firstValueFrom(this.api.majSignalementModeration(s.id, { statut })),
      `Signalement marqué « ${libelle} ».`,
    );
  }

  protected async ouvrirNoteSignalement(s: SignalementAdmin): Promise<void> {
    const note = prompt('Note administrative (visible par les modérateurs) :', s.note_admin ?? '');
    if (note === null) return;
    await this.action(
      () => firstValueFrom(this.api.majSignalementModeration(s.id, { note_admin: note.trim() || null })),
      'Note enregistrée.',
    );
  }

  /** Dépublier le document lié (confirmation + autorisation serveur). */
  protected async retirerDocument(s: SignalementAdmin): Promise<void> {
    if (!s.document) return;
    if (!confirm(`Dépublier « ${s.document.titre} » ? Il deviendra invisible du catalogue public.`)) return;
    await this.action(
      () => firstValueFrom(this.api.archiverDocumentModeration(s.document!.id)),
      'Document dépublié (archivé).',
    );
  }

  /** Réactiver / republier un document archivé ou refusé. */
  protected async reactiverDocument(s: SignalementAdmin): Promise<void> {
    if (!s.document) return;
    if (!confirm(`Réactiver et publier « ${s.document.titre} » ?`)) return;
    await this.action(
      () => firstValueFrom(this.api.validerDocumentModeration(s.document!.id)),
      'Document réactivé et publié.',
    );
  }

  protected couleurStatutSignalement(statut: string): string {
    return COULEUR_STATUT_SIGNALEMENT[statut] ?? 'var(--mpc-texte-doux)';
  }

  protected libelleStatutSignalement(statut: string): string {
    return STATUTS_SIGNALEMENT.find((s) => s.valeur === statut)?.libelle ?? statut;
  }

  /** Vrai dès qu'un filtre de la file des signalements est actif. */
  protected filtreSignalementActif(): boolean {
    return (
      this.filtreSignalement() !== '' ||
      this.filtreMotifSignalement() !== '' ||
      this.filtreDateDebut() !== '' ||
      this.filtreDateFin() !== ''
    );
  }

  private async action(appel: () => Promise<unknown>, succes: string): Promise<void> {
    this.message.set('');
    this.succes.set('');
    try {
      await appel();
      this.succes.set(succes);
      await this.chargerOnglet(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  /* ---------- Aides ---------- */

  protected urlTelechargement(id: number): string {
    return this.api.urlTelechargementBibliotheque(id);
  }

  protected totalCommentaires(commentaires: { replies: unknown[] }[]): number {
    return commentaires.reduce(
      (total, c) => total + 1 + this.totalCommentaires(c.replies as { replies: unknown[] }[]),
      0,
    );
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
