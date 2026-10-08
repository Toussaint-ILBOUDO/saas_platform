import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  Affectation,
  AffectationFormulaire,
  ContratCours,
  ContratFormulaire,
  EnseignantReferentiel,
  MatiereReferentiel,
  MetaPage,
  STATUTS_CONTRAT,
  StatutAffectation,
  StatutContrat,
  TypeCoursReferentiel,
  UtilisateurAdmin,
} from '../../models';

const STATUTS_AFFECTATION: { valeur: StatutAffectation; libelle: string }[] = [
  { valeur: 'actif', libelle: 'Active' },
  { valeur: 'suspendu', libelle: 'Suspendue' },
  { valeur: 'termine', libelle: 'Terminée' },
];

const LIEU_AFFECTATION: AffectationFormulaire = {
  enseignant_id: null,
  matiere_id: null,
  taux_horaire_enseignant: null,
  nombre_heures_prevues: null,
  date_affectation: null,
};

const JOUR = new Date().toISOString().slice(0, 10);

/**
 * Écran admin « Contrats de cours » (T7A.3).
 *
 * Un contrat n'est pas une ligne de base de données : c'est un **engagement de
 * facturation**. L'écran est donc construit pour rendre visible ce qui est
 * economically figé — le taux horaire de chaque affectation, le volume
 * d'heures prévu, le montant correspondant — parce que c'est ce trio qui part
 * dans la facture parent et le bulletin de l'enseignant.
 *
 * Corollaire : la suppression n'existe nulle part dans cet écran. Un contrat
 * porte des heures facturées et payées ; il se suspend ou se termine.
 */
@Component({
  imports: [FormsModule],
  selector: 'espace-contrats',
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
      .recherche input,
      .filtre {
        width: 100%;
        padding: 0.6rem 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-surface);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.85rem;
        font-weight: 600;
        outline: none;
      }
      .recherche input {
        padding-left: 2.15rem;
        font-weight: 400;
      }
      .recherche input:focus,
      .filtre:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .filtre {
        width: auto;
        min-width: 130px;
      }

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

      /* ---------- Carte contrat ---------- */
      .liste {
        display: grid;
        gap: 0.85rem;
      }
      .carte {
        border-radius: 1.05rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        overflow: hidden;
      }
      .carte._suspendu {
        border-style: dashed;
        background: var(--mpc-fond);
      }
      .carte-entete {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-wrap: wrap;
        padding: 0.95rem 1rem;
      }
      .eleve {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--mpc-commune);
        color: #fff;
        font-size: 1rem;
      }
      .titre {
        flex: 1;
        min-width: 180px;
      }
      .titre b {
        display: block;
        font-size: 0.97rem;
        color: var(--mpc-bleu);
      }
      .titre small {
        display: block;
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
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
      .badge._suspendu {
        background: var(--mpc-attention-tint);
        color: var(--mpc-attention);
      }
      .badge._termine {
        background: var(--mpc-ordre-tint);
        color: var(--mpc-ordre);
      }
      .badge._info {
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
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

      /* ---------- Affectations ---------- */
      .affectations {
        border-top: 1px solid var(--mpc-separateur);
        padding: 0.75rem 1rem 0.9rem;
        background: var(--mpc-surface-teintee);
      }
      .affectations > b {
        display: block;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--mpc-texte-doux);
        margin-bottom: 0.5rem;
      }
      .aff {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        flex-wrap: wrap;
        padding: 0.55rem 0;
        font-size: 0.82rem;
      }
      .aff + .aff {
        border-top: 1px dashed var(--mpc-separateur);
      }
      .aff .qui {
        flex: 1;
        min-width: 150px;
        color: var(--mpc-bleu);
        font-weight: 700;
      }
      .aff .qui small {
        display: block;
        font-weight: 400;
        color: var(--mpc-texte-doux);
        font-size: 0.74rem;
      }
      .aff .montant {
        font-weight: 800;
        color: var(--mpc-bleu);
        white-space: nowrap;
      }
      .aff .detail-econ {
        color: var(--mpc-texte-doux);
        font-size: 0.75rem;
        white-space: nowrap;
      }
      .ajout-aff {
        margin-top: 0.6rem;
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
        max-width: 720px;
        max-height: calc(100vh - 2rem);
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
        gap: 0.9rem;
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
        font-size: 0.87rem;
        outline: none;
      }
      .champ input:focus,
      .champ select:focus,
      .champ textarea:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .champ._erreur input,
      .champ._erreur select {
        border-color: var(--mpc-danger);
      }
      .erreur {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--mpc-danger);
      }
      .aide {
        font-size: 0.74rem;
        color: var(--mpc-texte-doux);
      }
      .grille {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
      }
      .section {
        border-top: 1px solid var(--mpc-separateur);
        padding-top: 0.9rem;
        margin-top: 0.2rem;
      }
      .section > b {
        display: block;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--mpc-bleu);
        margin-bottom: 0.6rem;
      }
      .ligne-aff {
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.9rem;
        padding: 0.75rem;
        margin-bottom: 0.65rem;
        background: var(--mpc-fond);
        display: grid;
        gap: 0.7rem;
      }
      .ligne-aff-entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
      }
      .ligne-aff-entete b {
        font-size: 0.8rem;
        color: var(--mpc-bleu);
      }
      .prevu {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--mpc-bleu-clair);
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
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>Contrats de cours</h1>
        <p>
          Chaque contrat engage la facturation : le taux horaire et le volume d’heures de ses affectations partent dans la
          facture du parent et le bulletin de paie de l’enseignant. Un contrat se suspend ou se termine, il ne se
          supprime pas.
        </p>
      </div>
      <button class="btn-primaire" type="button" [disabled]="!peutCreer()" [title]="raisonBlocageCreation()" (click)="ouvrirCreation()">
        <i class="bi bi-plus-lg"></i> Nouveau contrat
      </button>
    </div>

    @if (message()) {
      <div class="alerte"><i class="bi bi-exclamation-triangle"></i><span>{{ message() }}</span></div>
    }
    @if (succes()) {
      <div class="succes"><i class="bi bi-check-circle"></i><span>{{ succes() }}</span></div>
    }

    <div class="barre">
      <div class="recherche">
        <i class="bi bi-search"></i>
        <input
          type="search"
          [ngModel]="recherche()"
          (ngModelChange)="setRecherche($event)"
          (keyup.enter)="rechercher()"
          placeholder="Rechercher un élève…"
          aria-label="Rechercher un élève"
        />
      </div>
      <select class="filtre" [ngModel]="filtreStatut()" (ngModelChange)="setFiltreStatut($event)" aria-label="Filtrer par statut">
        <option value="">Tous les statuts</option>
        @for (s of statutsContrat; track s.valeur) {
          <option [value]="s.valeur">{{ s.libelle }}</option>
        }
      </select>
      <select class="filtre" [ngModel]="filtreType()" (ngModelChange)="setFiltreType($event)" aria-label="Filtrer par type de cours">
        <option value="">Tous les types</option>
        @for (t of typesCours(); track t.id) {
          <option [value]="t.id">{{ t.libelle }}</option>
        }
      </select>
    </div>

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else if (contrats().length === 0) {
      <div class="vide">
        <i class="bi bi-file-earmark-text"></i>
        @if (recherche() || filtreStatut() || filtreType()) {
          Aucun contrat ne correspond à ces filtres.
        } @else {
          Aucun contrat de cours. Créez le premier pour affecter un enseignant à un élève.
        }
      </div>
    } @else {
      <div class="liste">
        @for (c of contrats(); track c.id) {
          <article class="carte" [class._suspendu]="c.statut !== 'actif'">
            <div class="carte-entete">
              <span class="eleve"><i class="bi bi-person"></i></span>
              <div class="titre">
                <b>{{ c.eleve?.prenom }} {{ c.eleve?.nom }}</b>
                <small>
                  {{ c.type_cours?.libelle }} · du {{ dateCourte(c.date_debut) }}
                  {{ c.date_fin ? 'au ' + dateCourte(c.date_fin) : '(sans fin)' }}
                </small>
              </div>
              <div class="badges">
                <span class="badge" [class._actif]="c.statut === 'actif'" [class._suspendu]="c.statut === 'suspendu'" [class._termine]="c.statut === 'termine'">
                  {{ libelleStatut(c.statut) }}
                </span>
                <span class="badge _info">{{ totalHeures(c) }} h · {{ formatMontant(totalPrevu(c)) }} FCFA</span>
              </div>
              <div class="actions">
                <button class="mini" type="button" (click)="ouvrirEdition(c)">
                  <i class="bi bi-pencil"></i> Dates & frais
                </button>
                @if (c.statut === 'actif') {
                  <button class="mini" type="button" [disabled]="actionEnCours()" (click)="changerStatut(c, 'suspendu')">
                    <i class="bi bi-pause-circle"></i> Suspendre
                  </button>
                } @else if (c.statut === 'suspendu') {
                  <button class="mini" type="button" [disabled]="actionEnCours()" (click)="changerStatut(c, 'actif')">
                    <i class="bi bi-play-circle"></i> Réactiver
                  </button>
                  <button class="mini" type="button" [disabled]="actionEnCours()" (click)="changerStatut(c, 'termine')">
                    <i class="bi bi-flag"></i> Terminer
                  </button>
                } @else {
                  <button class="mini" type="button" disabled title="Un contrat terminé ne se réactive pas">
                    <i class="bi bi-flag"></i> Terminé
                  </button>
                }
              </div>
            </div>

            <div class="affectations">
              <b>Affectations ({{ c.affectations.length }})</b>
              @if (c.affectations.length === 0) {
                <div class="aide">Aucune affectation : ce contrat ne produit ni rapport ni facture d’enseignant.</div>
              }
              @for (a of c.affectations; track a.id) {
                <div class="aff">
                  <div class="qui">
                    {{ a.enseignant?.prenom }} {{ a.enseignant?.nom }}
                    <small>{{ a.matiere?.nom }} · {{ libelleStatutAffectation(a.statut) }}</small>
                  </div>
                  <span class="detail-econ">
                    {{ a.nombre_heures_prevues }} h × {{ formatMontant(a.taux_horaire_enseignant) }} FCFA
                  </span>
                  <span class="montant">{{ formatMontant(a.montant_prevu) }} FCFA</span>
                  <div class="actions">
                    @if (c.statut === 'actif' && a.statut !== 'actif') {
                      <button class="mini" type="button" [disabled]="actionEnCours()" (click)="changerStatutAffectation(c, a, 'actif')">
                        <i class="bi bi-play-circle"></i> Réactiver
                      </button>
                    } @else if (c.statut === 'actif' && a.statut === 'actif') {
                      <button class="mini" type="button" [disabled]="actionEnCours()" (click)="changerStatutAffectation(c, a, 'suspendu')">
                        <i class="bi bi-pause-circle"></i> Suspendre
                      </button>
                    }
                  </div>
                </div>
              }
              @if (c.statut === 'actif') {
                <div class="ajout-aff">
                  <button class="mini" type="button" [disabled]="!peutAffecter()" (click)="ajouterAffectation(c)">
                    <i class="bi bi-person-plus"></i> Affecter un enseignant
                  </button>
                </div>
              }
            </div>
          </article>
        }
      </div>

      @if ((meta()?.last_page ?? 1) > 1) {
        <nav class="pagination" aria-label="Pagination des contrats">
          <button class="page" type="button" [disabled]="(meta()?.current_page ?? 1) <= 1" (click)="aller((meta()?.current_page ?? 1) - 1)">
            <i class="bi bi-chevron-left"></i>
          </button>
          <span class="page _courante">{{ meta()?.current_page }} / {{ meta()?.last_page }}</span>
          <button
            class="page"
            type="button"
            [disabled]="(meta()?.current_page ?? 1) >= (meta()?.last_page ?? 1)"
            (click)="aller((meta()?.current_page ?? 1) + 1)"
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
            <h2>{{ modele().id ? 'Contrat — dates et frais' : 'Nouveau contrat de cours' }}</h2>
            <button class="fermer" type="button" (click)="fermerFormulaire()" aria-label="Fermer">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="formulaire">
            @if (!modele().id) {
              <label class="champ" [class._erreur]="!!erreurs()['eleve_id']">
                <span>Élève *</span>
                <select [ngModel]="modele().eleve_id" (ngModelChange)="setChamp('eleve_id', $event)">
                  <option [ngValue]="null">— Choisir un élève —</option>
                  @for (e of eleves(); track e.eleve!.id) {
                    <option [ngValue]="e.eleve!.id">
                      {{ e.prenom }} {{ e.nom }}@if (e.eleve?.classe) { ({{ e.eleve?.classe?.sigle }}) }
                    </option>
                  }
                </select>
                @if (erreurs()['eleve_id']; as e) {
                  <span class="erreur">{{ e }}</span>
                }
                @if (eleves().length === 0) {
                  <span class="aide">Aucun élève disponible : créez d’abord les élèves du cabinet.</span>
                }
              </label>

              <label class="champ" [class._erreur]="!!erreurs()['type_cours_id']">
                <span>Type de cours *</span>
                <select [ngModel]="modele().type_cours_id" (ngModelChange)="setChamp('type_cours_id', $event)">
                  <option [ngValue]="null">— Choisir un type —</option>
                  @for (t of typesCours(); track t.id) {
                    <option [ngValue]="t.id">{{ t.libelle }} ({{ t.code }})</option>
                  }
                </select>
                @if (erreurs()['type_cours_id']; as e) {
                  <span class="erreur">{{ e }}</span>
                }
              </label>
            } @else {
              <div class="avertissement">
                <i class="bi bi-lock"></i>
                <span>
                  L’élève et le type de cours ne sont pas modifiables : ils sont portés par les factures et les
                  rapports déjà produits. Modifiez les dates et les frais, ou suspendez le contrat.
                </span>
              </div>
            }

            <div class="grille">
              <label class="champ" [class._erreur]="!!erreurs()['date_debut']">
                <span>Date de début *</span>
                <input type="date" [ngModel]="modele().date_debut" (ngModelChange)="setChamp('date_debut', $event)" />
                @if (erreurs()['date_debut']; as e) {
                  <span class="erreur">{{ e }}</span>
                }
              </label>
              <label class="champ" [class._erreur]="!!erreurs()['date_fin']">
                <span>Date de fin</span>
                <input type="date" [ngModel]="modele().date_fin ?? ''" (ngModelChange)="setChamp('date_fin', $event || null)" />
                @if (erreurs()['date_fin']; as e) {
                  <span class="erreur">{{ e }}</span>
                }
              </label>
            </div>

            <label class="champ">
              <span>Autres frais de suivi (FCFA)</span>
              <input
                type="number"
                min="0"
                step="1"
                [ngModel]="modele().autres_frais_suivi ?? 0"
                (ngModelChange)="setChamp('autres_frais_suivi', $event)"
              />
              <span class="aide">Frais forfaitaires en plus des cours, ajoutés à la facture du parent.</span>
            </label>

            <label class="champ">
              <span>Notes internes</span>
              <textarea rows="2" [ngModel]="modele().notes_admin ?? ''" (ngModelChange)="setChamp('notes_admin', $event || null)"></textarea>
            </label>

            @if (!modele().id) {
              <div class="section">
                <b>Affectations (au moins une)</b>
                <div class="avertissement" style="margin-bottom: 0.7rem">
                  <i class="bi bi-cash-coin"></i>
                  <span>
                    Le taux horaire et le volume d’heures de cette ligne partent dans la facture du parent et le
                    bulletin de paie de l’enseignant. Une fois facturée, ils ne sont plus modifiables.
                  </span>
                </div>

                @for (ligne of modele().affectations; track $index) {
                  <div class="ligne-aff">
                    <div class="ligne-aff-entete">
                      <b>Affectation {{ $index + 1 }}</b>
                      <span class="prevu">{{ montantPrevuLigne(ligne) }}</span>
                    </div>
                    <div class="grille">
                      <label class="champ">
                        <span>Enseignant *</span>
                        <select [ngModel]="ligne.enseignant_id" (ngModelChange)="setLigne($index, 'enseignant_id', $event)">
                          <option [ngValue]="null">— Choisir —</option>
                          @for (p of enseignantsFiltres($index); track p.id) {
                            <option [ngValue]="p.profil?.id ?? p.id">{{ p.prenom }} {{ p.nom }}</option>
                          }
                        </select>
                      </label>
                      <label class="champ">
                        <span>Matière *</span>
                        <select [ngModel]="ligne.matiere_id" (ngModelChange)="setLigne($index, 'matiere_id', $event)">
                          <option [ngValue]="null">— Choisir —</option>
                          @for (m of matieres(); track m.id) {
                            <option [ngValue]="m.id">{{ m.nom }}</option>
                          }
                        </select>
                      </label>
                    </div>
                    <div class="grille">
                      <label class="champ" [class._erreur]="!!erreurs()['affectations.' + $index + '.nombre_heures_prevues']">
                        <span>Heures prévues *</span>
                        <input
                          type="number"
                          min="0"
                          step="0.5"
                          [ngModel]="ligne.nombre_heures_prevues"
                          (ngModelChange)="setLigne($index, 'nombre_heures_prevues', $event)"
                        />
                        @if (erreurs()['affectations.' + $index + '.nombre_heures_prevues']; as e) {
                          <span class="erreur">{{ e }}</span>
                        }
                      </label>
                      <label class="champ" [class._erreur]="!!erreurs()['affectations.' + $index + '.taux_horaire_enseignant']">
                        <span>Taux horaire enseignant (FCFA) *</span>
                        <input
                          type="number"
                          min="0"
                          step="100"
                          [ngModel]="ligne.taux_horaire_enseignant"
                          (ngModelChange)="setLigne($index, 'taux_horaire_enseignant', $event)"
                        />
                        @if (erreurs()['affectations.' + $index + '.taux_horaire_enseignant']; as e) {
                          <span class="erreur">{{ e }}</span>
                        }
                      </label>
                    </div>
                    @if (modele().affectations.length > 1) {
                      <button class="btn-neutral" type="button" (click)="retirerLigne($index)">
                        <i class="bi bi-x-lg"></i> Retirer cette affectation
                      </button>
                    }
                  </div>
                }

                <button class="btn-neutral" type="button" (click)="ajouterLigne()">
                  <i class="bi bi-plus-lg"></i> Ajouter une affectation
                </button>
              </div>
            }
          </div>

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

    @if (affectationOuverte()) {
      <div class="fenetre" (click)="fermerAffectation()">
        <div class="panneau" (click)="$event.stopPropagation()" style="max-width: 520px">
          <div class="panneau-entete">
            <h2>Affecter un enseignant</h2>
            <button class="fermer" type="button" (click)="fermerAffectation()" aria-label="Fermer">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="formulaire">
            <div class="avertissement">
              <i class="bi bi-cash-coin"></i>
              <span>Le montant prévu ci-dessous partira dans la facture du parent et le bulletin de l’enseignant.</span>
            </div>

            <label class="champ">
              <span>Enseignant *</span>
              <select [ngModel]="ligne().enseignant_id" (ngModelChange)="setLigneSeule('enseignant_id', $event)">
                <option [ngValue]="null">— Choisir —</option>
                @for (p of enseignants(); track p.id) {
                  <option [ngValue]="p.id">{{ p.prenom }} {{ p.nom }}</option>
                }
              </select>
            </label>
            <label class="champ">
              <span>Matière *</span>
              <select [ngModel]="ligne().matiere_id" (ngModelChange)="setLigneSeule('matiere_id', $event)">
                <option [ngValue]="null">— Choisir —</option>
                @for (m of matieres(); track m.id) {
                  <option [ngValue]="m.id">{{ m.nom }}</option>
                }
              </select>
            </label>
            <div class="grille">
              <label class="champ">
                <span>Heures prévues *</span>
                <input
                  type="number"
                  min="0"
                  step="0.5"
                  [ngModel]="ligne().nombre_heures_prevues"
                  (ngModelChange)="setLigneSeule('nombre_heures_prevues', $event)"
                />
              </label>
              <label class="champ">
                <span>Taux horaire (FCFA) *</span>
                <input
                  type="number"
                  min="0"
                  step="100"
                  [ngModel]="ligne().taux_horaire_enseignant"
                  (ngModelChange)="setLigneSeule('taux_horaire_enseignant', $event)"
                />
              </label>
            </div>
            <div class="prevu" style="font-size: 0.9rem">Montant prévu : {{ montantPrevuLigne(ligne()) }}</div>

            <div class="actions-form">
              <button class="btn-neutral" type="button" (click)="fermerAffectation()">Annuler</button>
              <button class="btn-primaire" type="button" [disabled]="sauvegarde()" (click)="enregistrerAffectation()">
                @if (sauvegarde()) {
                  <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
                } @else {
                  <i class="bi bi-check2"></i> Affecter
                }
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class ContratsComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected readonly statutsContrat = STATUTS_CONTRAT;
  protected readonly statutsAffectation = STATUTS_AFFECTATION;

  protected readonly contrats = signal<ContratCours[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly charge = signal(true);
  protected readonly message = signal('');
  protected readonly succes = signal('');
  protected readonly actionEnCours = signal(false);
  protected readonly recherche = signal('');
  protected readonly filtreStatut = signal('');
  protected readonly filtreType = signal('');

  protected readonly eleves = signal<UtilisateurAdmin[]>([]);
  protected readonly enseignants = signal<EnseignantReferentiel[]>([]);
  protected readonly matieres = signal<MatiereReferentiel[]>([]);
  protected readonly typesCours = signal<TypeCoursReferentiel[]>([]);

  protected readonly formulaireOuvert = signal(false);
  protected readonly affectationOuverte = signal(false);
  protected readonly sauvegarde = signal(false);
  protected readonly erreurs = signal<Record<string, string>>({});
  protected readonly contratCible = signal<number | null>(null);

  protected readonly modele = signal<ContratFormulaire & { id: number | null }>({
    id: null,
    eleve_id: null,
    type_cours_id: null,
    date_debut: JOUR,
    date_fin: null,
    autres_frais_suivi: 0,
    notes_admin: null,
    affectations: [{ ...LIEU_AFFECTATION }],
  });

  protected readonly ligne = signal<AffectationFormulaire>({ ...LIEU_AFFECTATION });

  /** Sans matière ni enseignant, un contrat ne peut pas être créé. */
  protected readonly peutCreer = computed(
    () => this.enseignants().length > 0 && this.matieres().length > 0 && this.eleves().length > 0,
  );
  protected readonly peutAffecter = computed(() => this.enseignants().length > 0 && this.matieres().length > 0);

  protected enseignantsFiltres(index: number): EnseignantReferentiel[] {
    const matiereId = this.modele().affectations[index]?.matiere_id;
    if (!matiereId) return this.enseignants();
    return this.enseignants().filter((p) =>
      p.profil?.matieres?.some((m) => m.id === matiereId)
    );
  }

  protected readonly raisonBlocageCreation = computed(() => {
    if (this.enseignants().length === 0) return 'Aucun enseignant enregistré : créez-en un dans les référentiels.';
    if (this.matieres().length === 0) return 'Aucune matière créée : créez-en une dans les référentiels.';
    if (this.eleves().length === 0) return 'Aucun élève disponible.';
    return '';
  });

  async ngOnInit(): Promise<void> {
    await Promise.all([this.charger(), this.chargerReferentiels()]);
    this.ouvrirDepuisRoute();
  }

  /**
   * Fiche ciblée par une notification (`/espace/pedagogie/contrats/{id}`).
   *
   * La liste est paginée : le contrat peut ne pas y figurer, on le charge donc
   * par son identifiant. L'abonnement aux paramètres couvre le cas où l'on
   * clique deux notifications successives — Angular réutilise le composant.
   */
  private ouvrirDepuisRoute(): void {
    this.route.paramMap.subscribe((params) => {
      const brut = params.get('id');
      if (!brut) return;

      const id = Number(brut);
      if (!Number.isFinite(id) || id <= 0) return;

      firstValueFrom(this.api.getContrat(id))
        .then((c) => this.ouvrirEdition(c))
        .catch((e) => this.message.set(messageErreurApi(e)));
    });
  }

  /* ---------- Aides ---------- */

  protected libelleStatut(statut: StatutContrat): string {
    return STATUTS_CONTRAT.find((s) => s.valeur === statut)?.libelle ?? statut;
  }

  protected libelleStatutAffectation(statut: StatutAffectation): string {
    return STATUTS_AFFECTATION.find((s) => s.valeur === statut)?.libelle ?? statut;
  }

  protected formatMontant(valeur: number): string {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Math.round(valeur || 0));
  }

  protected dateCourte(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  protected totalHeures(c: ContratCours): string {
    return String(c.affectations.reduce((total, a) => total + (a.nombre_heures_prevues || 0), 0));
  }

  protected totalPrevu(c: ContratCours): number {
    return c.affectations.reduce((total, a) => total + (a.montant_prevu || 0), 0);
  }

  protected montantPrevuLigne(l: AffectationFormulaire): string {
    const heures = l.nombre_heures_prevues ?? 0;
    const taux = l.taux_horaire_enseignant ?? 0;

    return `${this.formatMontant(heures * taux)} FCFA`;
  }

  /* ---------- Données ---------- */

  private async charger(page = 1): Promise<void> {
    this.charge.set(true);
    try {
      const r = await firstValueFrom(
        this.api.getContrats(page, 15, this.recherche(), this.filtreStatut(), this.filtreType()),
      );
      this.contrats.set(r.data ?? []);
      this.meta.set(r.meta ?? null);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.charge.set(false);
    }
  }

  private async chargerReferentiels(): Promise<void> {
    try {
      const [eleves, enseignants, matieres, types] = await Promise.all([
        firstValueFrom(this.api.getUtilisateurs(1, 50, '', 'eleve')),
        firstValueFrom(this.api.getEnseignantsReferentiel(1, 50)),
        firstValueFrom(this.api.getMatieres(1, 50)),
        firstValueFrom(this.api.getTypesCours(1, 50)),
      ]);
      this.eleves.set((eleves.data ?? []).filter((u) => !!u.eleve));
      this.enseignants.set(enseignants.data ?? []);
      this.matieres.set(matieres.data ?? []);
      this.typesCours.set(types.data ?? []);
    } catch {
      this.message.set('Certaines listes de référence n’ont pas pu être chargées.');
    }
  }

  protected setRecherche(valeur: string): void {
    this.recherche.set(valeur);
  }

  protected rechercher(): void {
    void this.charger(1);
  }

  protected setFiltreStatut(valeur: string): void {
    this.filtreStatut.set(valeur);
    void this.charger(1);
  }

  protected setFiltreType(valeur: string): void {
    this.filtreType.set(valeur);
    void this.charger(1);
  }

  protected aller(page: number): void {
    void this.charger(page);
  }

  /* ---------- Formulaire contrat ---------- */

  protected ouvrirCreation(): void {
    if (!this.peutCreer()) return;
    this.modele.set({
      id: null,
      eleve_id: null,
      type_cours_id: null,
      date_debut: JOUR,
      date_fin: null,
      autres_frais_suivi: 0,
      notes_admin: null,
      affectations: [{ ...LIEU_AFFECTATION }],
    });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEdition(c: ContratCours): void {
    this.modele.set({
      id: c.id,
      eleve_id: c.eleve?.id ?? null,
      type_cours_id: c.type_cours?.id ?? null,
      date_debut: c.date_debut,
      date_fin: c.date_fin,
      autres_frais_suivi: c.autres_frais_suivi,
      notes_admin: c.notes_admin,
      affectations: [],
    });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected fermerFormulaire(): void {
    this.formulaireOuvert.set(false);
  }

  protected setChamp(champ: 'eleve_id' | 'type_cours_id' | 'date_debut' | 'date_fin' | 'autres_frais_suivi' | 'notes_admin', valeur: unknown): void {
    this.modele.set({ ...this.modele(), [champ]: valeur });
    this.effacerErreur(champ);
  }

  protected ajouterLigne(): void {
    const lignes = [...this.modele().affectations, { ...LIEU_AFFECTATION }];
    this.modele.set({ ...this.modele(), affectations: lignes });
  }

  protected retirerLigne(index: number): void {
    const lignes = this.modele().affectations.filter((_, i) => i !== index);
    this.modele.set({ ...this.modele(), affectations: lignes });
  }

  protected setLigne(index: number, champ: keyof AffectationFormulaire, valeur: unknown): void {
    const lignes = this.modele().affectations.map((l, i) => (i === index ? { ...l, [champ]: valeur } : l));
    this.modele.set({ ...this.modele(), affectations: lignes });
    this.effacerErreur(`affectations.${index}.${champ}`);
  }

  protected async enregistrer(): Promise<void> {
    this.message.set('');
    this.succes.set('');
    this.erreurs.set({});

    const m = this.modele();

    if (!m.id && !m.eleve_id) {
      this.erreurs.set({ eleve_id: 'Choisissez l’élève concerné.' });
      return;
    }
    if (!m.id && !m.type_cours_id) {
      this.erreurs.set({ type_cours_id: 'Choisissez le type de cours.' });
      return;
    }
    if (m.date_fin && m.date_fin < m.date_debut) {
      this.erreurs.set({ date_fin: 'La date de fin doit être postérieure à la date de début.' });
      return;
    }

    this.sauvegarde.set(true);
    try {
      if (m.id) {
        await firstValueFrom(
          this.api.majContrat(m.id, {
            date_debut: m.date_debut,
            date_fin: m.date_fin,
            autres_frais_suivi: m.autres_frais_suivi ?? 0,
            notes_admin: m.notes_admin,
          }),
        );
        this.succes.set('Contrat mis à jour.');
      } else {
        // Le contrat naît avec au moins une affectation : on valide ici plutôt
        // que d'envoyer un 422 au serveur, la ligne affichant déjà l'erreur.
        const incompletes = m.affectations.filter(
          (a) => !a.enseignant_id || !a.matiere_id || a.taux_horaire_enseignant === null || a.nombre_heures_prevues === null,
        );
        if (incompletes.length) {
          const index = m.affectations.indexOf(incompletes[0]);
          this.erreurs.set(
            Object.keys(this.erreurs()).length
              ? this.erreurs()
              : { [`affectations.${index}.enseignant_id`]: 'Complétez chaque affectation : enseignant, matière, heures et taux.' },
          );
          return;
        }

        await firstValueFrom(
          this.api.creerContrat({
            eleve_id: m.eleve_id,
            type_cours_id: m.type_cours_id,
            date_debut: m.date_debut,
            date_fin: m.date_fin,
            autres_frais_suivi: m.autres_frais_suivi ?? 0,
            notes_admin: m.notes_admin,
            affectations: m.affectations.map((a) => ({
              ...a,
              date_affectation: a.date_affectation ?? m.date_debut,
            })),
          }),
        );
        this.succes.set('Contrat créé.');
      }

      this.formulaireOuvert.set(false);
      await this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.appliquerErreurs(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  /* ---------- Affectation rapide ---------- */

  protected ajouterAffectation(c: ContratCours): void {
    if (!this.peutAffecter()) return;
    this.contratCible.set(c.id);
    this.ligne.set({ ...LIEU_AFFECTATION });
    this.erreurs.set({});
    this.affectationOuverte.set(true);
  }

  protected fermerAffectation(): void {
    this.affectationOuverte.set(false);
  }

  protected setLigneSeule(champ: keyof AffectationFormulaire, valeur: unknown): void {
    this.ligne.set({ ...this.ligne(), [champ]: valeur });
  }

  protected async enregistrerAffectation(): Promise<void> {
    const contratId = this.contratCible();
    if (!contratId) return;

    const l = this.ligne();
    if (!l.enseignant_id || !l.matiere_id || l.taux_horaire_enseignant === null || l.nombre_heures_prevues === null) {
      this.message.set('Renseignez l’enseignant, la matière, les heures et le taux horaire.');
      return;
    }

    this.sauvegarde.set(true);
    this.message.set('');
    this.succes.set('');
    try {
      await firstValueFrom(
        this.api.creerAffectation(contratId, {
          ...l,
          date_affectation: l.date_affectation ?? new Date().toISOString().slice(0, 10),
        }),
      );
      this.affectationOuverte.set(false);
      this.succes.set('Enseignant affecté au contrat.');
      await this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.appliquerErreurs(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  /* ---------- Statuts ---------- */

  protected async changerStatut(c: ContratCours, statut: StatutContrat): Promise<void> {
    const consequences =
      statut === 'suspendu'
        ? `Suspendre le contrat de ${c.eleve?.prenom} ${c.eleve?.nom} ?\n\nLa facturation s'arrête et toutes les affectations actives sont suspendues avec. Vous pourrez le réactiver.`
        : `Terminer définitivement ce contrat ?\n\nAction définitive : il ne pourra plus être réactivé. Si le contrat porte déjà une facture, l'opération sera refusée — il faudra le suspendre.`;
    if (!confirm(consequences)) return;

    await this.action(
      () => firstValueFrom(this.api.changerStatutContrat(c.id, statut)),
      statut === 'actif' ? 'Contrat réactivé.' : statut === 'suspendu' ? 'Contrat suspendu.' : 'Contrat terminé.',
    );
  }

  protected async changerStatutAffectation(c: ContratCours, a: Affectation, statut: StatutAffectation): Promise<void> {
    if (!confirm(`${statut === 'actif' ? 'Réactiver' : 'Suspendre'} l’affectation de ${a.enseignant?.prenom} ${a.enseignant?.nom} ?`)) {
      return;
    }

    await this.action(
      () => firstValueFrom(this.api.changerStatutAffectation(c.id, a.id, statut)),
      statut === 'actif' ? 'Affectation réactivée.' : 'Affectation suspendue.',
    );
  }

  private async action(appel: () => Promise<unknown>, succes: string): Promise<void> {
    this.actionEnCours.set(true);
    this.message.set('');
    this.succes.set('');
    try {
      await appel();
      this.succes.set(succes);
      await this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.appliquerErreurs(e);
    } finally {
      this.actionEnCours.set(false);
    }
  }

  /* ---------- Erreurs ---------- */

  private appliquerErreurs(e: unknown): void {
    const champs = (e as { error?: { erreurs?: Record<string, string[]> } })?.error?.erreurs;
    if (champs) {
      const aplat: Record<string, string> = {};
      for (const champ of Object.keys(champs)) {
        if (champs[champ]?.[0]) aplat[champ] = champs[champ][0];
      }
      this.erreurs.set(aplat);
      if (Object.keys(aplat).length) return;
    }
    this.message.set(messageErreurApi(e));
  }

  private effacerErreur(champ: string): void {
    const courantes = this.erreurs();
    if (!courantes[champ]) return;
    const suivante = { ...courantes };
    delete suivante[champ];
    this.erreurs.set(suivante);
  }
}