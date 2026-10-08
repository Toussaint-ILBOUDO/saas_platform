import { Component, ElementRef, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { messageErreurApi } from '../../services/messages';
import {
  AffectationCahierTexte,
  CahierTexte,
  CahierTexteFormulaire,
  EnfantConsulter,
  MetaPage,
} from '../../models';

/**
 * Écran « Cahier de texte » (T7A.5) — enseignant, parent et élève.
 *
 * Un seul composant, deux lectures. L'enseignant **écrit** : il saisit, corrige
 * et retire ses séances. La famille **lit** : elle consulte l'historique de son
 * enfant et l'exporte en PDF, sans aucune écriture possible. Même donnée, deux
 * questions — d'où un composant unique qui décide des actions offertes, pas
 * deux écrans qui divergeraient.
 *
 * Deux règles de métier-conditionnent l'interface plutôt que d'être laissées au
 * serveur :
 *
 *  - la **date est figée** à la saisie. Le formulaire de modification ne propose
 *    donc pas de champ date ; pour corriger une date, l'enseignant supprime et
 *    ressaisit, ce que la période ouverte autorise et qu'un garde serveur
 *    impose ;
 *  - la saisie reste possible dans le **passé** (ratifier une séance oubliée),
 *    jamais dans le futur — des heures qui n'ont pas eu lieu ne peuvent pas
 *    gonfler une facture. La borne est donc posée sur le champ date, pour ne
 *    pas proposer à l'enseignant une saisie que le serveur refuserait.
 */

/** Modèle vide de la modale. */
const MODELE_VIDE: CahierTexteFormulaire = {
  affectation_enseignant_id: null,
  date_seance: '',
  heure_debut: '',
  heure_fin: '',
  contenu_cours: '',
};

@Component({
  imports: [FormsModule],
  selector: 'espace-cahier-de-texte',
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
        max-width: 68ch;
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
      .alerte.succes {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }

      /* ---------- Barre de filtres ---------- */
      .filtres {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        align-items: flex-end;
        padding: 0.85rem 1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        margin-bottom: 1rem;
      }
      .filtre {
        display: grid;
        gap: 0.25rem;
      }
      .filtre > span {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
      }
      .filtre input,
      .filtre select {
        padding: 0.5rem 0.65rem;
        border-radius: 0.65rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.84rem;
        outline: none;
      }
      .filtre input:focus,
      .filtre select:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .filtre.recherche {
        flex: 1;
        min-width: 190px;
      }

      .btn-primaire,
      .btn-neutre,
      .btn-danger,
      .btn-fantome {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 1rem;
        border-radius: 0.8rem;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        white-space: nowrap;
      }
      .btn-primaire {
        border: none;
        background: var(--mpc-primaire);
        color: #fff;
      }
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-neutre,
      .btn-fantome {
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
      }
      .btn-fantome {
        border-color: transparent;
        background: transparent;
        color: var(--mpc-texte-doux);
        padding: 0.35rem 0.5rem;
      }
      .btn-danger {
        border: none;
        background: var(--mpc-danger);
        color: #fff;
      }
      .btn-primaire:disabled,
      .btn-neutre:disabled,
      .btn-danger:disabled,
      .btn-fantome:disabled {
        opacity: 0.4;
        cursor: not-allowed;
      }
      .btn-fantome:disabled:hover {
        background: transparent;
        color: var(--mpc-texte-doux);
      }

      /* ---------- Liste des séances ---------- */
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

      .liste {
        display: grid;
        gap: 0.8rem;
      }
      .seance {
        border-radius: 1.05rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        overflow: hidden;
      }
      .seance-entete {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-wrap: wrap;
        padding: 0.85rem 1rem;
      }
      .pastille {
        flex: 0 0 auto;
        min-width: 58px;
        height: 46px;
        padding: 0 0.4rem;
        display: grid;
        place-items: center;
        border-radius: 0.85rem;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
        font-size: 0.78rem;
        font-weight: 800;
        text-align: center;
        line-height: 1.15;
      }
      .titre {
        flex: 1;
        min-width: 200px;
      }
      .titre b {
        display: block;
        font-size: 0.94rem;
        color: var(--mpc-bleu);
      }
      .titre small {
        display: block;
        font-size: 0.77rem;
        color: var(--mpc-texte-doux);
      }
      .badges {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        align-items: center;
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
      .badge.info {
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
      }
      .badge.succes {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .actions {
        display: flex;
        gap: 0.2rem;
      }

      .contenu {
        border-top: 1px solid var(--mpc-separateur);
        padding: 0.75rem 1rem 0.9rem;
        background: var(--mpc-surface-teintee);
        display: grid;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--mpc-texte);
      }
      .contenu p {
        margin: 0;
        white-space: pre-wrap;
      }
      .note {
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
      }
      .note b {
        color: var(--mpc-texte);
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
        max-width: 540px;
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
      .champ textarea {
        resize: vertical;
        min-height: 84px;
      }
      .champ input:focus,
      .champ select:focus,
      .champ textarea:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .champ._erreur input,
      .champ._erreur select,
      .champ._erreur textarea {
        border-color: var(--mpc-danger);
      }
      .champ.fige input:disabled {
        opacity: 0.6;
      }
      .erreur {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--mpc-danger);
      }
      .grille {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
      }

      /* Durée calculée à partir des deux champs horaires, en pied de grille. */
      .duree {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.75rem;
        border-radius: 0.7rem;
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
        font-size: 0.82rem;
        font-weight: 600;
      }

      /* Récapitulatif des 422 : la modale occupe tout l'écran, le bandeau de
         la page serait masqué derrière elle. */
      .resume-erreurs {
        margin: 0;
        padding-left: 1.1rem;
        display: grid;
        gap: 0.2rem;
        font-size: 0.78rem;
        font-weight: 600;
      }
      .indices {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        margin-top: -0.3rem;
      }
      .indice {
        padding: 0.3rem 0.45rem;
        border-radius: 0.5rem;
        font-size: 0.72rem;
        font-weight: 700;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        border: none;
        cursor: pointer;
      }
      .indice:hover {
        background: var(--mpc-primaire);
        color: #fff;
      }
      .avertissement {
        display: flex;
        gap: 0.5rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.7rem;
        background: var(--mpc-attention-tint);
        color: var(--mpc-attention);
        font-size: 0.78rem;
        font-weight: 600;
      }
      .info-bloc {
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
        gap: 0.6rem;
        flex-wrap: wrap;
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
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>{{ estEnseignant() ? 'Cahier de texte' : 'Cahier de texte de ' + (enfantCourant()?.prenom ?? '') }}</h1>
        <p>
          @if (estEnseignant()) {
            Les séances que vous donnez : ces heures sont facturées à la famille et rémunérées à vous. Une
            période close gèle la saisie.
          } @else {
            L’historique des séances de votre enfant, matière par matière.
          }
        </p>
      </div>

      @if (estEnseignant()) {
        <button class="btn-primaire" type="button" (click)="nouvelle()" [disabled]="affectations().length === 0">
          <i class="bi bi-plus-lg"></i> Saisir une séance
        </button>
      } @else {
        <button class="btn-neutre" type="button" (click)="telechargerHistorique()" [disabled]="!enfantCourant()">
          <i class="bi bi-file-earmark-pdf"></i> Exporter l’historique
        </button>
      }
    </div>

    @if (message() && !formulaireOuvert()) {
      <div class="alerte" [class.succes]="succes()">
        <i class="bi" [class.bi-exclamation-triangle]="!succes()" [class.bi-check-circle]="succes()"></i>
        <span>{{ message() }}</span>
      </div>
    }

    <div class="filtres">
      @if (!estEnseignant()) {
        <label class="filtre">
          <span>Enfant</span>
          <select [ngModel]="enfantId()" (ngModelChange)="changerEnfant($event)">
            @for (e of enfants(); track e.id) {
              <option [ngValue]="e.id">
                {{ e.prenom }} {{ e.nom }}@if (e.classe) { — {{ e.classe.sigle }} }
              </option>
            }
          </select>
        </label>
      }

      <label class="filtre recherche">
        <span>Recherche</span>
        <input
          type="search"
          placeholder="Contenu de la séance…"
          [ngModel]="recherche()"
          (ngModelChange)="setRecherche($event)"
        />
      </label>

      <label class="filtre">
        <span>Du</span>
        <input type="date" [ngModel]="dateDebut()" (ngModelChange)="setDateDebut($event)" />
      </label>

      <label class="filtre">
        <span>Au</span>
        <input type="date" [ngModel]="dateFin()" (ngModelChange)="setDateFin($event)" />
      </label>

      <button class="btn-neutre" type="button" (click)="reinitialiserFiltres()">
        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
      </button>
    </div>

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else if (seances().length === 0) {
      <div class="vide">
        <i class="bi bi-journal-x"></i>
        @if (estEnseignant()) {
          Aucune séance saisie pour ces critères.
        } @else {
          Aucune séance enregistrée pour cet enfant sur cette période.
        }
      </div>
    } @else {
      <div class="liste">
        @for (s of seances(); track s.id) {
          <article class="seance">
            <div class="seance-entete">
              <span class="pastille">
                <span>
                  {{ dateJour(s.date_seance) }}<br />
                  {{ s.heure_debut ?? '' }}
                </span>
              </span>

              <div class="titre">
                <b>{{ s.matiere?.nom ?? 'Matière' }}</b>
                <small>
                  {{ s.heure_debut ?? '—' }} – {{ s.heure_fin ?? '—' }} · {{ duree(s) }} h ·
                  {{ dateLongue(s.date_seance) }}
                  @if (estEnseignant()) {
                    · {{ s.eleve?.prenom }} {{ s.eleve?.nom }}
                  } @else if (s.enseignant) {
                    · {{ s.enseignant.prenom }} {{ s.enseignant.nom }}
                  }
                </small>
              </div>

              <div class="badges">
                @if (s.objectifs_atteints) {
                  <span class="badge succes">Objectifs atteints</span>
                }

                @if (estEnseignant()) {
                  <div class="actions">
                    <button
                      class="btn-fantome"
                      type="button"
                      (click)="modifier(s)"
                      [disabled]="!peutModifier(s)"
                      [title]="peutModifier(s) ? 'Corriger la séance' : raisonCorrectionBloquee(s)"
                      [attr.aria-label]="'Modifier la séance du ' + s.date_seance"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-fantome" type="button" (click)="supprimer(s)" [attr.aria-label]="'Supprimer la séance du ' + s.date_seance">
                      <i class="bi bi-trash"></i>
                    </button>
                    <a class="btn-fantome" [href]="pdfSeance(s.id)" [attr.aria-label]="'Télécharger le PDF de la séance du ' + s.date_seance">
                      <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                  </div>
                }
              </div>
            </div>

            <div class="contenu">
              <p>{{ s.contenu_cours }}</p>
              @if (s.objectifs_atteints) {
                <p class="note"><b>Objectifs :</b> {{ s.objectifs_atteints }}</p>
              }
              @if (s.observations) {
                <p class="note"><b>Observations :</b> {{ s.observations }}</p>
              }
            </div>
          </article>
        }
      </div>

      @if ((meta()?.last_page ?? 1) > 1) {
        <nav class="pagination" aria-label="Pagination">
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
            <h2>{{ enEdition() ? 'Corriger la séance' : 'Saisir une séance' }}</h2>
            <button class="fermer" type="button" (click)="fermerFormulaire()" aria-label="Fermer">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="formulaire">
            @if (enEdition()) {
              <div class="info-bloc">
                <i class="bi bi-lock"></i>
                <span>La date d’une séance ne se modifie pas : supprimez la séance et ressaisissez-la.</span>
              </div>
            }

            <label class="champ" [class._erreur]="!!erreurs()['affectation_enseignant_id']">
              <span>Élève & matière *</span>
              <select
                [ngModel]="modele().affectation_enseignant_id"
                (ngModelChange)="setAffectation($event)"
                [disabled]="enEdition()"
              >
                <option [ngValue]="null">Choisir…</option>
                @for (a of affectations(); track a.id) {
                  <option [ngValue]="a.id">{{ a.eleve }} — {{ a.matiere }}</option>
                }
              </select>
              @if (erreurs()['affectation_enseignant_id']; as e) {
                <span class="erreur">{{ e }}</span>
              }
            </label>

            <label class="champ fige" [class._erreur]="!!erreurs()['date_seance'] || !!erreurs()['date'] || !!erreurs()['periode']">
              <span>Date de la séance *</span>
              <input
                type="date"
                [ngModel]="modele().date_seance"
                (ngModelChange)="setDate($event)"
                [disabled]="enEdition()"
                [max]="aujourdhui()"
              />
              @if (erreurs()['date_seance'] || erreurs()['date'] || erreurs()['periode']; as e) {
                <span class="erreur">{{ e }}</span>
              }
            </label>

            <div class="indices">
              @for (h of horairesCourants; track h.debut + h.fin) {
                <button class="indice" type="button" (click)="setHoraire(h.debut, h.fin)">
                  {{ h.debut }}–{{ h.fin }}
                </button>
              }
            </div>

            <div class="grille">
              <label class="champ" [class._erreur]="!!erreurs()['heure_debut']">
                <span>Début *</span>
                <input type="time" [ngModel]="modele().heure_debut" (ngModelChange)="setDebut($event)" />
                @if (erreurs()['heure_debut']; as e) {
                  <span class="erreur">{{ e }}</span>
                }
              </label>

              <label class="champ" [class._erreur]="!!erreurs()['heure_fin']">
                <span>Fin *</span>
                <input type="time" [ngModel]="modele().heure_fin" (ngModelChange)="setFin($event)" />
                @if (erreurs()['heure_fin']; as e) {
                  <span class="erreur">{{ e }}</span>
                }
              </label>

              @if (dureeSaisie()) {
                <div class="duree">
                  <i class="bi bi-hourglass-split"></i>
                  Durée de la séance : <b>{{ dureeSaisie() }}</b>
                </div>
              }
            </div>

            <label class="champ" [class._erreur]="!!erreurs()['contenu_cours']">
              <span>Contenu du cours *</span>
              <textarea
                rows="4"
                [ngModel]="modele().contenu_cours"
                (ngModelChange)="setContenu($event)"
                placeholder="Ce qui a été fait pendant la séance…"
              ></textarea>
              @if (erreurs()['contenu_cours']; as e) {
                <span class="erreur">{{ e }}</span>
              }
            </label>

            <label class="champ">
              <span>Objectifs atteints (optionnel)</span>
              <textarea rows="2" [ngModel]="modele().objectifs_atteints ?? ''" (ngModelChange)="setObjectifs($event)"></textarea>
            </label>

            <label class="champ">
              <span>Observations (optionnel)</span>
              <textarea rows="2" [ngModel]="modele().observations ?? ''" (ngModelChange)="setObservations($event)"></textarea>
            </label>

            <div class="avertissement">
              <i class="bi bi-info-circle"></i>
              <span>
                Vous pouvez saisir une séance des jours précédents, mais pas une séance future. Si la période
                comptable est close, la saisie sera refusée.
              </span>
            </div>

            @if (resumeErreurs().length > 0) {
              <div class="alerte" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <div>
                  <b>Le formulaire contient des erreurs.</b>
                  <ul class="resume-erreurs">
                    @for (e of resumeErreurs(); track e.champ) {
                      <li>{{ e.libelle }} — {{ e.message }}</li>
                    }
                  </ul>
                </div>
              </div>
            } @else if (message() && !succes()) {
              <div class="alerte" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ message() }}</span>
              </div>
            }

            <div class="actions-form">
              <button class="btn-neutre" type="button" (click)="fermerFormulaire()">Annuler</button>
              <button class="btn-primaire" type="button" (click)="enregistrer()" [disabled]="sauvegarde()">
                @if (sauvegarde()) {
                  <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
                } @else {
                  <i class="bi bi-check2"></i> {{ enEdition() ? 'Enregistrer la correction' : 'Saisir' }}
                }
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class CahierDeTexteComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly auth = inject(AuthService);
  private readonly el: ElementRef<HTMLElement> = inject(ElementRef);

  protected readonly horairesCourants = [
    { debut: '08:00', fin: '09:00' },
    { debut: '14:00', fin: '15:00' },
    { debut: '16:00', fin: '17:00' },
    { debut: '17:00', fin: '18:00' },
    { debut: '18:00', fin: '19:00' },
    { debut: '19:00', fin: '20:00' },
  ];

  protected readonly seances = signal<CahierTexte[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly affectations = signal<AffectationCahierTexte[]>([]);
  protected readonly enfants = signal<EnfantConsulter[]>([]);

  protected readonly charge = signal(true);
  protected readonly sauvegarde = signal(false);
  protected readonly message = signal('');
  protected readonly succes = signal(false);

  protected readonly recherche = signal('');
  protected readonly dateDebut = signal('');
  protected readonly dateFin = signal('');
  protected readonly enfantId = signal<number | null>(null);

  protected readonly formulaireOuvert = signal(false);
  protected readonly edition = signal<CahierTexte | null>(null);
  protected readonly modele = signal<CahierTexteFormulaire>({ ...MODELE_VIDE });
  protected readonly erreurs = signal<Record<string, string>>({});

  protected readonly estEnseignant = computed(() => this.auth.roleActif() === 'enseignant');

  /**
   * `edition()` vaut `null` en création ; `[disabled]` n'accepte pas `null`, et
   * la modale doit de toute façon pouvoir décider de l'immuabilité à partir
   * d'un booléen.
   */
  protected readonly enEdition = computed(() => this.edition() !== null);

  protected readonly enfantCourant = computed(
    () => this.enfants().find((e) => e.id === this.enfantId()) ?? null
  );

  /**
   * Borne haute du champ date : une séance future n'a pas eu lieu.
   *
   * La date du jour est calculée dans le fuseau du **serveur** (D-003 :
   * `Africa/Ouagadougou`), pas celui du navigateur. Un enseignant qui ouvre
   * l'écran à 00 h 30 depuis Paris est encore à la veille à Ouagadougou : avec
   * la date locale, le champ autoriserait une saisie que le serveur refuse.
   */
  protected aujourdhui(): string {
    const morceaux = new Intl.DateTimeFormat('en-CA', {
      timeZone: 'Africa/Ouagadougou',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).format(new Date());

    return morceaux;
  }

  /**
   * Le serveur n'accepte la correction que **le jour de la séance** lui-même
   * (une fois la période close, plus rien n'est modifiable — D-051). Plutôt que
   * de laisser l'enseignant cliquer pour apprendre la règle par une 422, le
   * bouton est désactivé et explique pourquoi (D-056).
   */
  protected peutModifier(s: CahierTexte): boolean {
    return s.date_seance === this.aujourdhui();
  }

  protected raisonCorrectionBloquee(s: CahierTexte): string {
    return `Une séance ne peut être corrigée que le jour même (celle-ci : ${this.dateLongue(
      s.date_seance
    )}). Pour changer son contenu, supprimez-la et ressaisissez-la.`;
  }

  ngOnInit(): void {
    void this.demarrer();
  }

  private async demarrer(): Promise<void> {
    if (this.estEnseignant()) {
      try {
        this.affectations.set(await firstValueFrom(this.api.getAffectationsCahierTexte()));
      } catch (e) {
        this.message.set(messageErreurApi(e));
        this.succes.set(false);
      }
    } else {
      try {
        const liste = await firstValueFrom(this.api.getMesEnfants());
        this.enfants.set(liste);
        this.enfantId.set(liste[0]?.id ?? null);
      } catch (e) {
        this.message.set(messageErreurApi(e));
        this.succes.set(false);
      }
    }

    await this.charger(1);
  }

  /* ---------- filtres ---------- */

  protected setRecherche(valeur: string): void {
    this.recherche.set(valeur ?? '');
    this.recharger();
  }

  protected setDateDebut(valeur: string): void {
    this.dateDebut.set(valeur ?? '');
    this.recharger();
  }

  protected setDateFin(valeur: string): void {
    this.dateFin.set(valeur ?? '');
    this.recharger();
  }

  protected reinitialiserFiltres(): void {
    this.recherche.set('');
    this.dateDebut.set('');
    this.dateFin.set('');
    this.recharger();
  }

  protected changerEnfant(id: number): void {
    this.enfantId.set(id);
    this.recharger();
  }

  /** Efface le bandeau puis recharge : le message précédent datait d'autre chose. */
  private recharger(): void {
    this.message.set('');
    void this.charger(1);
  }

  protected aller(page: number): void {
    void this.charger(page);
  }

  /**
   * Rafraîchit la liste. Ne **pas** vider `message` ici : cette méthode est
   * aussi appelée juste après une écriture, et effacer le message ferait
   * disparaître la confirmation avant que l'utilisateur ne la lise. C'est
   * l'action déclenchante (changement de filtre, ouverture de formulaire) qui
   * remet le bandeau à zéro.
   */
  private async charger(page: number): Promise<void> {
    this.charge.set(true);

    const filtres = {
      search: this.recherche(),
      date_debut: this.dateDebut(),
      date_fin: this.dateFin(),
    };

    try {
      if (this.estEnseignant()) {
        const r = await firstValueFrom(this.api.getCahiersTexte(page, 20, filtres));
        this.seances.set(r.data ?? []);
        this.meta.set(r.meta ?? null);
      } else {
        const id = this.enfantId();

        if (id === null) {
          this.seances.set([]);
          this.meta.set(null);
        } else {
          const r = await firstValueFrom(this.api.getHistoriqueCahierTexte(id, page, 20, filtres));
          this.seances.set(r.data ?? []);
          this.meta.set(r.meta ?? null);
        }
      }
    } catch (e) {
      this.message.set(messageErreurApi(e));
      this.succes.set(false);
    } finally {
      this.charge.set(false);
    }
  }

  /* ---------- affichage ---------- */

  protected duree(s: CahierTexte): string {
    return Number.isInteger(s.duree_heures) ? String(s.duree_heures) : s.duree_heures.toFixed(2).replace(/0$/, '');
  }

  protected dateJour(iso: string): string {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;

    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
  }

  protected dateLongue(iso: string): string {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;

    return d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  }

  /** URL du PDF d'une séance : le template n'a pas besoin du service lui-même. */
  protected pdfSeance(id: number): string {
    return this.api.urlPdfCahierTexte(id);
  }

  protected telechargerHistorique(): void {
    const id = this.enfantId();
    if (id === null) return;

    window.open(this.api.urlHistoriquePdf(id, this.dateDebut(), this.dateFin()), '_blank');
  }

  /* ---------- formulaire ---------- */

  protected nouvelle(): void {
    this.message.set('');
    this.edition.set(null);
    this.erreurs.set({});
    this.modele.set({ ...MODELE_VIDE, date_seance: this.aujourdhui() });
    this.formulaireOuvert.set(true);
  }

  protected modifier(s: CahierTexte): void {
    this.edition.set(s);
    this.erreurs.set({});
    this.message.set('');
    this.modele.set({
      affectation_enseignant_id: s.affectation_enseignant_id,
      date_seance: s.date_seance,
      heure_debut: s.heure_debut ?? '',
      heure_fin: s.heure_fin ?? '',
      contenu_cours: s.contenu_cours,
      objectifs_atteints: s.objectifs_atteints ?? '',
      observations: s.observations ?? '',
    });
    this.formulaireOuvert.set(true);
  }

  protected fermerFormulaire(): void {
    this.formulaireOuvert.set(false);
    this.edition.set(null);
    this.erreurs.set({});
    // Un message d'erreur ne survit pas à la fermeture : il ne signifierait
    // plus rien une fois le formulaire refermé. Celui du succès reste — il
    // résume l'enregistrement venu de se fermer tout seul.
    if (!this.succes()) this.message.set('');
  }

  protected setAffectation(valeur: number | null): void {
    this.majModele({ affectation_enseignant_id: valeur });
    this.effacerErreur('affectation_enseignant_id');
  }

  protected setDate(valeur: string): void {
    this.majModele({ date_seance: valeur });
    this.effacerErreur('date_seance');
  }

  protected setDebut(valeur: string): void {
    this.majModele({ heure_debut: valeur });
    this.effacerErreur('heure_debut');
  }

  protected setFin(valeur: string): void {
    this.majModele({ heure_fin: valeur });
    this.effacerErreur('heure_fin');
  }

  protected setHoraire(debut: string, fin: string): void {
    this.majModele({ heure_debut: debut, heure_fin: fin });
    this.effacerErreur('heure_debut');
    this.effacerErreur('heure_fin');
  }

  protected setContenu(valeur: string): void {
    this.majModele({ contenu_cours: valeur });
    this.effacerErreur('contenu_cours');
  }

  protected setObjectifs(valeur: string): void {
    this.majModele({ objectifs_atteints: valeur });
  }

  protected setObservations(valeur: string): void {
    this.majModele({ observations: valeur });
  }

  private majModele(partiel: Partial<CahierTexteFormulaire>): void {
    this.modele.update((m) => ({ ...m, ...partiel }));
  }

  private effacerErreur(champ: string): void {
    const courantes = this.erreurs();
    if (!courantes[champ]) return;

    const suivante = { ...courantes };
    delete suivante[champ];
    this.erreurs.set(suivante);
  }

  protected async enregistrer(): Promise<void> {
    const courant = this.edition();

    this.erreurs.set({});
    this.message.set('');
    this.succes.set(false);

    // Règles serveur rejouées ici : l'enseignant apprend l'absence de champ ou
    // une heure de fin antérieure au début SANS aller et venir avec l'API, et
    // surtout SANS que la modale — qui masque le bandeau de page — laisse croire
    // que l'enregistrement a échoué sans raison.
    const locales = this.validerFormulaire();
    if (Object.keys(locales).length > 0) {
      this.erreurs.set(locales);
      this.focusPremiereErreur();
      return;
    }

    this.sauvegarde.set(true);

    try {
      if (courant) {
        // Ni `affectation_enseignant_id` ni `uuid_client` : le serveur les
        // ignorerait (D-054 pour l'affectation, idempotence pour l'uuid).
        // `date_seance` est renvoyée à l'identique — elle est acceptée mais
        // vérifiée, ce qui évite d'avoir à savoir ici ce qui peut être omis.
        await firstValueFrom(
          this.api.majCahierTexte(courant.id, {
            date_seance: this.modele().date_seance,
            heure_debut: this.modele().heure_debut,
            heure_fin: this.modele().heure_fin,
            contenu_cours: this.modele().contenu_cours,
            objectifs_atteints: this.modele().objectifs_atteints || undefined,
            observations: this.modele().observations || undefined,
          })
        );

        this.message.set('Séance corrigée.');
      } else {
        const reponse = await firstValueFrom(this.api.creerCahierTexte(this.modele()));

        // `cree` vient du statut HTTP : une reprise idempotente ne doit pas
        // être annoncée comme une nouvelle saisie.
        this.message.set(reponse.cree ? 'Séance enregistrée.' : 'Séance déjà enregistrée : elle n’a pas été dupliquée.');
      }

      this.succes.set(true);
      this.fermerFormulaire();
      await this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      // Les erreurs sont affichées sous le champ concerné ET récapitulées
      // dans la modale : c'est là que l'enseignant regarde, le bandeau de page
      // étant masqué par le calque. Le gel de période arrive ici sous `date`
      // ou `periode` (garde Finance partagé), pas sous `date_seance`.
      const champs = (e as { error?: { erreurs?: Record<string, string[]> } })?.error?.erreurs;

      if (champs) {
        const aplat: Record<string, string> = {};
        for (const champ of Object.keys(champs)) {
          if (champs[champ]?.[0]) aplat[champ] = champs[champ][0];
        }
        this.erreurs.set(aplat);
        this.succes.set(false);
        this.focusPremiereErreur();
      } else {
        this.message.set(messageErreurApi(e));
        this.succes.set(false);
      }
    } finally {
      this.sauvegarde.set(false);
    }
  }

  /**
   * Miroir des règles de `StoreCahierTexteApiRequest` / `UpdateCahierTexteApiRequest`.
   * Seule la date future est exclue du contrôle en correction : le serveur sait
   * que `date_seance` y est immuable.
   */
  private validerFormulaire(): Record<string, string> {
    const m = this.modele();
    const erreurs: Record<string, string> = {};

    if (!m.affectation_enseignant_id) {
      erreurs['affectation_enseignant_id'] = 'Veuillez choisir le cours concerné.';
    }

    if (!m.date_seance) {
      erreurs['date_seance'] = 'La date de la séance est obligatoire.';
    } else if (m.date_seance > this.aujourdhui()) {
      erreurs['date_seance'] = 'Une séance ne peut pas être saisie à une date future.';
    }

    if (!m.heure_debut) {
      erreurs['heure_debut'] = 'L’heure de début est obligatoire.';
    }

    if (!m.heure_fin) {
      erreurs['heure_fin'] = 'L’heure de fin est obligatoire.';
    } else if (m.heure_debut && m.heure_fin <= m.heure_debut) {
      erreurs['heure_fin'] = 'L’heure de fin doit être après l’heure de début.';
    }

    const contenu = (m.contenu_cours ?? '').trim();
    if (!contenu) {
      erreurs['contenu_cours'] = 'Le contenu du cours est obligatoire.';
    } else if (contenu.length < 10) {
      erreurs['contenu_cours'] = 'Décrivez le cours en quelques mots (10 caractères minimum).';
    }

    return erreurs;
  }

  /**
   * Le récapitulatif affiché dans la modale : un libellé de champ lisible par
   * message reçu (serveur compris — `date` et `periode` désignent la même
   * date que `date_seance`, mais l'enseignant ne parle pas de clés JSON).
   */
  protected resumeErreurs(): { champ: string; libelle: string; message: string }[] {
    const libelles: Record<string, string> = {
      affectation_enseignant_id: 'Élève & matière',
      date_seance: 'Date de la séance',
      date: 'Date de la séance',
      periode: 'Date de la séance',
      heure_debut: 'Début',
      heure_fin: 'Fin',
      contenu_cours: 'Contenu du cours',
      objectifs_atteints: 'Objectifs atteints',
      observations: 'Observations',
      uuid_client: 'Identifiant de séance',
    };

    const erreurs = this.erreurs();

    return Object.keys(erreurs).map((champ) => ({
      champ,
      libelle: libelles[champ] ?? champ,
      message: erreurs[champ],
    }));
  }

  /**
   * Durée calculée à partir des deux champs horaires, affichée dès qu'ils sont
   * renseignés — c'est ce que l'enseignant veut vérifier avant d'enregistrer,
   * et le serveur la recalcule de toute façon.
   */
  protected dureeSaisie(): string {
    const { heure_debut, heure_fin } = this.modele();
    if (!heure_debut || !heure_fin) return '';

    const minutes = CahierDeTexteComponent.enMinutes(heure_fin) - CahierDeTexteComponent.enMinutes(heure_debut);
    if (!Number.isFinite(minutes) || minutes <= 0) return '';

    const heures = Math.floor(minutes / 60);
    const reste = minutes % 60;

    if (heures && reste) return `${heures} h ${String(reste).padStart(2, '0')}`;
    if (heures) return `${heures} h`;
    return `${reste} min`;
  }

  /** `14:30` → 870. Le format H:i est garantir par le serveur, pas ici. */
  private static enMinutes(horaire: string): number {
    const [heures, minutes] = horaire.split(':').map((n) => Number(n));
    return heures * 60 + minutes;
  }

  /** Ramène le premier champ en erreur dans le champ de vision de la modale. */
  private focusPremiereErreur(): void {
    setTimeout(() => {
      const champ = this.el.nativeElement.querySelector<HTMLElement>(
        '.champ._erreur input, .champ._erreur select, .champ._erreur textarea'
      );
      champ?.scrollIntoView({ block: 'center', behavior: 'smooth' });
      champ?.focus();
    });
  }

  protected async supprimer(s: CahierTexte): Promise<void> {
    const confirmation = confirm(
      `Supprimer la séance du ${this.dateLongue(s.date_seance)} ?\n\n` +
        'Ces heures seront retirées de la facture et de votre paie. ' +
        'L’opération est refusée si la période est close.'
    );

    if (!confirmation) return;

    try {
      await firstValueFrom(this.api.supprimerCahierTexte(s.id));
      this.message.set('Séance supprimée.');
      this.succes.set(true);
      await this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.message.set(messageErreurApi(e));
      this.succes.set(false);
    }
  }
}
