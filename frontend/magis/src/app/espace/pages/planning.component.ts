import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { NgTemplateOutlet } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { messageErreurApi } from '../../services/messages';
import {
  AffectationPlanning,
  CreneauFormulaire,
  CreneauPlanning,
  EnfantPlanning,
  PlanningEnseignant,
} from '../../models';

/** Lundi → dimanche : l'API indexe les jours de 1 à 7 (ISO 8601). */
const JOURS = [
  { num: 1, court: 'Lun', long: 'Lundi' },
  { num: 2, court: 'Mar', long: 'Mardi' },
  { num: 3, court: 'Mer', long: 'Mercredi' },
  { num: 4, court: 'Jeu', long: 'Jeudi' },
  { num: 5, court: 'Ven', long: 'Vendredi' },
  { num: 6, court: 'Sam', long: 'Samedi' },
  { num: 7, court: 'Dim', long: 'Dimanche' },
];

const MODEL_VIDE: CreneauFormulaire = {
  affectation_enseignant_id: null,
  jour_semaine: 1,
  heure_debut: '17:00',
  heure_fin: '18:00',
};

/**
 * Écran « Planning » (T7A.4) — un seul écran pour trois usages, parce que les
 * données sont les mêmes : l'enseignant y gère ses créneaux, le parent et
 * l'élève les consultent.
 *
 * Deux lisibilités cohabitent volontairement :
 *  - ses **propres** créneaux (cartes pleines, bordure orange) ;
 *  - les créneaux des **autres enseignants du même contrat** (cartes en
 *    pointillés, teinte bleue) — l'enseignant doit voir quand l'élève est déjà
 *    occupé avec quelqu'un d'autre, mais ce n'est pas son planning à gérer.
 */
@Component({
  imports: [FormsModule, NgTemplateOutlet],
  selector: 'espace-planning',
  styles: [
    `
      :host {
        display: block;
      }

      /* ---------- En-tête ---------- */
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
        max-width: 56ch;
      }
      .btn-primaire {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1.05rem;
        border: none;
        border-radius: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.88rem;
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

      /* ---------- Bandeau de synthèse ---------- */
      .synthese {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0.7rem;
        margin-bottom: 1.1rem;
      }
      .stat {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.75rem 0.85rem;
        border-radius: 0.95rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
      }
      .stat i {
        width: 36px;
        height: 36px;
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        border-radius: 0.7rem;
        background: var(--mpc-primaire-tint);
        color: var(--mpc-primaire);
        font-size: 1rem;
      }
      .stat b {
        display: block;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--mpc-bleu);
        line-height: 1.2;
      }
      .stat span {
        font-size: 0.72rem;
        color: var(--mpc-texte-doux);
        font-weight: 600;
      }

      /* ---------- Grille semaine ---------- */
      .semaine {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 0.55rem;
        align-items: start;
      }
      .jour {
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.95rem;
        padding: 0.6rem 0.5rem;
        min-height: 120px;
      }
      .jour-tete {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.35rem;
        padding: 0 0.15rem 0.5rem;
        margin-bottom: 0.45rem;
        border-bottom: 1px solid var(--mpc-separateur);
      }
      .jour-tete b {
        font-size: 0.8rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .jour-tete span {
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
      }
      .jour._aujourdhui {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 1px var(--mpc-primaire-tint);
      }
      .jour._aujourdhui .jour-tete b {
        color: var(--mpc-primaire);
      }
      .jour-vide {
        font-size: 0.7rem;
        color: var(--mpc-texte-doux);
        opacity: 0.7;
        text-align: center;
        padding: 0.5rem 0;
      }

      /* ---------- Carte créneau ---------- */
      .creneau {
        position: relative;
        display: block;
        width: 100%;
        text-align: left;
        border: 1px solid var(--mpc-primaire);
        border-left-width: 3px;
        border-radius: 0.7rem;
        background: var(--mpc-primaire-tint);
        padding: 0.45rem 0.5rem;
        margin-bottom: 0.4rem;
        cursor: pointer;
        font: inherit;
        transition: transform 0.12s ease, box-shadow 0.12s ease;
      }
      .creneau:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(18, 48, 94, 0.1);
      }
      .creneau.lecture {
        cursor: default;
      }
      .creneau.lecture:hover {
        transform: none;
        box-shadow: none;
      }
      .creneau.partage {
        border-style: dashed;
        border-color: var(--mpc-bleu-clair);
        background: var(--mpc-bleu-tint);
      }
      .heure {
        display: block;
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--mpc-primaire);
        letter-spacing: -0.01em;
      }
      .partage .heure {
        color: var(--mpc-bleu-clair);
      }
      .cible {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--mpc-texte);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .matiere {
        display: block;
        font-size: 0.68rem;
        color: var(--mpc-texte-doux);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .marqueur-partage {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        margin-top: 0.25rem;
        font-size: 0.62rem;
        font-weight: 700;
        color: var(--mpc-bleu-clair);
      }

      /* ---------- Vue mobile : liste par jour ---------- */
      .jour-mobile {
        margin-bottom: 0.6rem;
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.95rem;
        background: var(--mpc-surface);
        overflow: hidden;
      }
      .jour-mobile > header {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.65rem 0.85rem;
        border-bottom: 1px solid var(--mpc-separateur);
      }
      .jour-mobile > header b {
        font-size: 0.84rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .jour-mobile > header span {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
      }
      .corps-jour {
        padding: 0.7rem 0.85rem 0.85rem;
      }
      .corps-jour .creneau:last-child {
        margin-bottom: 0;
      }

      /* ---------- Parent : un panneau par enfant ---------- */
      .enfant {
        margin-bottom: 1.2rem;
      }
      .enfant-tete {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 0.7rem;
        flex-wrap: wrap;
      }
      .enfant-tete b {
        font-size: 1rem;
        color: var(--mpc-bleu);
      }
      .pastille {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
      }
      .pastille._actif {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .pastille._inactif {
        background: var(--mpc-attention-tint);
        color: var(--mpc-attention);
      }

      /* ---------- États ---------- */
      .alerte {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.85rem 1rem;
        border-radius: 0.9rem;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .info {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.85rem 1rem;
        border-radius: 0.9rem;
        font-size: 0.85rem;
        margin-bottom: 1rem;
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
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

      /* ---------- Modale de formulaire ---------- */
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
        max-width: 520px;
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
      .champ select {
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
      .champ select:focus {
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
      .grille {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
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
      .btn-danger {
        padding: 0.55rem 0.95rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-danger);
        background: transparent;
        color: var(--mpc-danger);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
      }
      .btn-danger:hover:not(:disabled) {
        background: var(--mpc-danger);
        color: #fff;
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

      /* ---------- Responsive : la semaine en colonnes devient une liste ---------- */
      @media (max-width: 900px) {
        .semaine {
          display: none;
        }
        .grille {
          grid-template-columns: 1fr;
        }
      }
      @media (min-width: 901px) {
        .liste-mobile {
          display: none;
        }
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>{{ titre() }}</h1>
        <p>{{ sousTitre() }}</p>
      </div>
      @if (peutEcrire() && affectations().length > 0) {
        <button class="btn-primaire" type="button" (click)="ouvrirCreation()">
          <i class="bi bi-plus-lg"></i> Nouveau créneau
        </button>
      }
    </div>

    @if (message()) {
      <div class="alerte"><i class="bi bi-exclamation-triangle"></i><span>{{ message() }}</span></div>
    }

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement du planning…</div>
    } @else if (compteActifEleve() === false) {
      <div class="info">
        <i class="bi bi-info-circle"></i>
        <span>
          Votre compte n’est pas encore activé, donc vos horaires ne sont pas affichés. Ils réapparaîtront
          dès que le cabinet aura validé votre inscription.
        </span>
      </div>
    } @else if (totalCreneaux() === 0) {
      <div class="vide">
        <i class="bi bi-calendar3"></i>
        Aucun cours planifié pour le moment.
      </div>
    } @else {
      @if (peutEcrire()) {
        <div class="synthese">
          <div class="stat"><i class="bi bi-calendar-check"></i><span><b>{{ mesCreneaux().length }}</b> créneaux</span></div>
          <div class="stat"><i class="bi bi-clock-history"></i><span><b>{{ heuresHebdo() }} h</b> par semaine</span></div>
          <div class="stat"><i class="bi bi-people"></i><span><b>{{ nombreEleves() }}</b> élèves suivis</span></div>
          <div class="stat"><i class="bi bi-person-check"></i><span><b>{{ partages().length }}</b> cours en commun</span></div>
        </div>

        @if (partages().length > 0) {
          <div class="info">
            <i class="bi bi-people"></i>
            <span>
              Les créneaux en pointillés sont ceux des autres enseignants de vos contrats : ils ne sont pas
              modifiables depuis cet écran, mais ils vous disent quand l’élève est déjà occupé.
            </span>
          </div>
        }
      }

      @if (estParent()) {
        @for (enfant of enfants(); track enfant.eleve.id) {
          <section class="enfant">
            <div class="enfant-tete">
              <b>{{ nomEleve(enfant.eleve) }}</b>
              <span class="pastille" [class._actif]="enfant.eleve.compte_actif" [class._inactif]="!enfant.eleve.compte_actif">
                @if (enfant.eleve.compte_actif) {
                  <i class="bi bi-person-check"></i> Compte activé
                } @else {
                  <i class="bi bi-person-exclamation"></i> Compte non activé
                }
              </span>
              <span class="pastille" style="background: var(--mpc-abandon); color: var(--mpc-texte-doux)">
                {{ enfant.creneaux.length }} cours / semaine
              </span>
            </div>
            <ng-container *ngTemplateOutlet="grilleSemaine; context: { $implicit: enfant.creneaux }" />
          </section>
        }
      } @else {
        <ng-container *ngTemplateOutlet="grilleSemaine; context: { $implicit: creneauxAffiches() }" />
      }
    }

    <!--
      Grille semaine réutilisée (enseignant, parent, élève).
      La directive @let regroupe les créneaux par jour une seule fois par rendu : sans
      cela le filtrage serait refait trois fois par colonne et à chaque cycle
      de détection de changement.
    -->
    <ng-template #grilleSemaine let-creneaux>
      @let parJour = grouperParJour(creneaux);
      <div class="semaine">
        @for (j of jours; track j.num) {
          @let duJour = parJour.get(j.num) ?? [];
          <div class="jour" [class._aujourdhui]="j.num === jourIso()">
            <div class="jour-tete">
              <b>{{ j.court }}</b>
              <span>{{ duJour.length || '' }}</span>
            </div>
            @for (c of duJour; track c.id) {
              <ng-container *ngTemplateOutlet="carteCreneau; context: { $implicit: c }" />
            }
            @if (duJour.length === 0) {
              <div class="jour-vide">—</div>
            }
          </div>
        }
      </div>

      <!-- Sur mobile les colonnes sont illisibles : on empile les joursactyliques. -->
      <div class="liste-mobile">
        @for (j of jours; track j.num) {
          @let duJour = parJour.get(j.num) ?? [];
          @if (duJour.length > 0) {
            <section class="jour-mobile">
              <header>
                <b>{{ j.long }}</b>
                <span>{{ duJour.length }} cours</span>
              </header>
              <div class="corps-jour">
                @for (c of duJour; track c.id) {
                  <ng-container *ngTemplateOutlet="carteCreneau; context: { $implicit: c }" />
                }
              </div>
            </section>
          }
        }
      </div>
    </ng-template>

    <ng-template #carteCreneau let-c>
      @if (estPartage(c)) {
        <div class="creneau partage lecture">
          <span class="heure">{{ c.tranche_horaire }}</span>
          <span class="cible">{{ nomEleve(c.eleve) }}</span>
          @if (c.matiere) {
            <span class="matiere">{{ c.matiere.nom }}</span>
          }
          @if (peutEcrire() && c.enseignant) {
            <span class="marqueur-partage"><i class="bi bi-person"></i> {{ nomEleve(c.enseignant) }}</span>
          }
        </div>
      } @else if (peutEcrire()) {
        <button class="creneau" type="button" (click)="ouvrirEdition(c)">
          <span class="heure">{{ c.tranche_horaire }}</span>
          <span class="cible">{{ nomEleve(c.eleve) }}</span>
          @if (c.matiere) {
            <span class="matiere">{{ c.matiere.nom }}</span>
          }
        </button>
      } @else {
        <div class="creneau lecture">
          <span class="heure">{{ c.tranche_horaire }}</span>
          <span class="cible">{{ c.matiere?.nom ?? 'Cours' }}</span>
          @if (c.enseignant) {
            <span class="matiere">{{ nomEleve(c.enseignant) }}</span>
          }
        </div>
      }
    </ng-template>

    @if (formulaireOuvert()) {
      <div class="fenetre" (click)="fermerFormulaire()">
        <div class="panneau" (click)="$event.stopPropagation()">
          <div class="panneau-entete">
            <h2>{{ modele().id ? 'Modifier le créneau' : 'Nouveau créneau' }}</h2>
            <button class="fermer" type="button" (click)="fermerFormulaire()" aria-label="Fermer">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="formulaire">
            <label class="champ" [class._erreur]="!!erreurs()['affectation_enseignant_id']">
              <span>Élève & matière *</span>
              <select [ngModel]="modele().affectation_enseignant_id" (ngModelChange)="setAffectation($event)">
                <option [ngValue]="null">Choisir…</option>
                @for (a of affectations(); track a.id) {
                  <option [ngValue]="a.id">{{ a.eleve }} — {{ a.matiere }}</option>
                }
              </select>
              @if (erreurs()['affectation_enseignant_id']; as e) {
                <span class="erreur">{{ e }}</span>
              }
            </label>

            <label class="champ" [class._erreur]="!!erreurs()['jour_semaine']">
              <span>Jour *</span>
              <select [ngModel]="modele().jour_semaine" (ngModelChange)="setJour($event)">
                @for (j of jours; track j.num) {
                  <option [ngValue]="j.num">{{ j.long }}</option>
                }
              </select>
            </label>

            <!-- Raccourcis : les horaires les plus demandés en cours à domicile. -->
            <div class="indices">
              @for (h of creneauxCourants; track h.debut + h.fin) {
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
            </div>

            <div class="avertissement">
              <i class="bi bi-exclamation-triangle"></i>
              <span>
                Un créneau ne peut pas chevaucher un cours déjà prévu pour vous ni pour cet élève. Les horaires
                qui se touchent sont acceptés (18h–19h puis 19h–20h).
              </span>
            </div>

            @if (modele().id) {
              <button class="btn-danger" type="button" (click)="supprimer()" [disabled]="sauvegarde()">
                <i class="bi bi-trash"></i> Supprimer ce créneau
              </button>
            }

            <div class="actions-form">
              <button class="btn-neutral" type="button" (click)="fermerFormulaire()">Annuler</button>
              <button class="btn-primaire" type="button" (click)="enregistrer()" [disabled]="sauvegarde()">
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
  `,
})
export class PlanningComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly auth = inject(AuthService);

  protected readonly jours = JOURS;
  protected readonly creneauxCourants = [
    { debut: '08:00', fin: '09:00' },
    { debut: '14:00', fin: '15:00' },
    { debut: '16:00', fin: '17:00' },
    { debut: '17:00', fin: '18:00' },
    { debut: '18:00', fin: '19:00' },
    { debut: '19:00', fin: '20:00' },
  ];

  protected readonly role = signal('');
  protected readonly charge = signal(true);
  protected readonly message = signal('');

  /** Enseignant : la source de vérité pour l'édition. */
  protected readonly mesCreneaux = signal<CreneauPlanning[]>([]);
  protected readonly partages = signal<CreneauPlanning[]>([]);
  protected readonly affectations = signal<AffectationPlanning[]>([]);

  /** Parent : un planning par enfant. Élève : le sien. */
  protected readonly enfants = signal<EnfantPlanning[]>([]);
  protected readonly creneauxEleve = signal<CreneauPlanning[]>([]);
  protected readonly compteActifEleve = signal<boolean | null>(null);

  protected readonly formulaireOuvert = signal(false);
  protected readonly sauvegarde = signal(false);
  protected readonly modele = signal<CreneauFormulaire & { id: number | null }>({ id: null, ...MODEL_VIDE });
  protected readonly erreurs = signal<Record<string, string>>({});

  /* ---------- Vue dérivée ---------- */

  protected readonly peutEcrire = computed(() => this.role() === 'enseignant');
  protected readonly estParent = computed(() => this.role() === 'parent');

  protected readonly tousCreneaux = computed(() =>
    this.role() === 'eleve' ? this.creneauxEleve() : this.mesCreneaux(),
  );

  /**
   * Ce que la grille affiche réellement.
   *
   * Pour l'enseignant ce sont ses créneaux **plus** ceux des collègues de ses
   * contrats : sans eux, il ne verrait jamais quand l'élève est déjà occupé avec
   * quelqu'un d'autre, alors que c'est précisément l'intérêt de l'écran.
   */
  protected readonly creneauxAffiches = computed(() =>
    this.peutEcrire() ? [...this.mesCreneaux(), ...this.partages()] : this.tousCreneaux(),
  );

  /**
   * Compte sur la même source que la grille (`creneauxAffiches`), jamais sur les
   * seuls créneaux propres : sinon un enseignant qui n'a que des cours en commun
   * — cas réel d'un remplaçant ou d'un intervenant ponctuel — tombait sur l'état
   * vide alors que la grille, elle, a des lignes à afficher.
   */
  protected readonly totalCreneaux = computed(() =>
    this.estParent()
      ? this.enfants().reduce((n, e) => n + e.creneaux.length, 0)
      : this.creneauxAffiches().length,
  );

  /** Volume horaire hebdomadaire de l'enseignant, en heures décimales. */
  protected readonly heuresHebdo = computed(() => {
    const minutes = this.mesCreneaux().reduce((total, c) => total + this.dureeMinutes(c), 0);
    return (minutes / 60).toFixed(minutes % 60 === 0 ? 0 : 1);
  });

  protected readonly nombreEleves = computed(
    () => new Set(this.mesCreneaux().map((c) => c.eleve?.id).filter(Boolean)).size,
  );

  protected readonly titre = computed(() => {
    if (this.peutEcrire()) return 'Mon planning';
    if (this.estParent()) return 'Planning des enfants';
    return 'Mon planning';
  });

  protected readonly sousTitre = computed(() => {
    if (this.peutEcrire()) {
      return 'Vos cours hebdomadaires. Les créneaux en pointillés appartiennent aux autres enseignants de vos contrats.';
    }
    if (this.estParent()) {
      return 'Les cours planifiés pour chacun de vos enfants.';
    }
    return 'Vos cours de la semaine, tels que planifiés par vos enseignants.';
  });

  ngOnInit(): void {
    this.role.set(this.auth.roleActif());
    this.charger();
  }

  /* ---------- Données ---------- */

  private async charger(): Promise<void> {
    this.charge.set(true);
    this.message.set('');
    try {
      if (this.peutEcrire()) {
        const data: PlanningEnseignant = await firstValueFrom(this.api.getPlanningEnseignant());
        this.mesCreneaux.set(data.mes_creneaux ?? []);
        this.partages.set(data.creneaux_partages ?? []);
        this.affectations.set(data.affectations ?? []);
      } else {
        // `getMesPlanning()` renvoie une union : on affine selon le rôle plutôt
        // que de lire les deux clés avec `?? []`, ce qui masquerait une éventuelle
        // régression du contrat de réponse côté serveur.
        const data = await firstValueFrom(this.api.getMesPlanning());
        if (this.estParent()) {
          this.enfants.set('eleves' in data ? data.eleves : []);
        } else if ('creneaux' in data) {
          this.compteActifEleve.set(data.compte_actif ?? false);
          this.creneauxEleve.set(data.creneaux ?? []);
        }
      }
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.charge.set(false);
    }
  }

  /* ---------- Aides d'affichage ---------- */

  protected jourIso(): number {
    const jour = new Date().getDay();
    return jour === 0 ? 7 : jour;
  }

  /** Index jour → créneaux triés par heure, calculé une fois par grille rendue. */
  protected grouperParJour(creneaux: CreneauPlanning[]): Map<number, CreneauPlanning[]> {
    const parJour = new Map<number, CreneauPlanning[]>();
    for (const c of creneaux) {
      const liste = parJour.get(c.jour_semaine);
      if (liste) liste.push(c);
      else parJour.set(c.jour_semaine, [c]);
    }
    for (const liste of parJour.values()) {
      liste.sort((a, b) => a.heure_debut.localeCompare(b.heure_debut));
    }
    return parJour;
  }

  /**
   * Un créneau est « partagé » s'il ne fait pas partie des siens — c'est le
   * seul moyen fiable de le distinguer, l'API renvoyant les deux listes à part
   * mais des identifiants distincts dans les deux.
   */
  protected estPartage(c: CreneauPlanning): boolean {
    return this.peutEcrire() && !this.mesCreneaux().some((m) => m.id === c.id);
  }

  protected nomEleve(personne: { nom?: string | null; prenom?: string | null } | null): string {
    if (!personne) return '—';
    const nom = [personne.prenom, personne.nom].filter(Boolean).join(' ').trim();
    return nom || '—';
  }

  private dureeMinutes(c: CreneauPlanning): number {
    const [hd, md] = (c.heure_debut ?? '').split(':').map(Number);
    const [hf, mf] = (c.heure_fin ?? '').split(':').map(Number);
    return Math.max(0, hf * 60 + mf - (hd * 60 + md));
  }

  /* ---------- Formulaire ---------- */

  protected ouvrirCreation(): void {
    this.modele.set({ id: null, ...MODEL_VIDE });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEdition(c: CreneauPlanning): void {
    this.modele.set({
      id: c.id,
      affectation_enseignant_id: c.affectation_enseignant_id,
      jour_semaine: c.jour_semaine,
      heure_debut: c.heure_debut,
      heure_fin: c.heure_fin,
    });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected fermerFormulaire(): void {
    this.formulaireOuvert.set(false);
  }

  protected setAffectation(valeur: number | null): void {
    this.modele.set({ ...this.modele(), affectation_enseignant_id: valeur });
    this.effacerErreur('affectation_enseignant_id');
  }

  protected setJour(valeur: number): void {
    this.modele.set({ ...this.modele(), jour_semaine: valeur });
    this.effacerErreur('jour_semaine');
  }

  protected setDebut(valeur: string): void {
    this.modele.set({ ...this.modele(), heure_debut: valeur });
    this.effacerErreur('heure_debut');
  }

  protected setFin(valeur: string): void {
    this.modele.set({ ...this.modele(), heure_fin: valeur });
    this.effacerErreur('heure_fin');
  }

  protected setHoraire(debut: string, fin: string): void {
    this.modele.set({ ...this.modele(), heure_debut: debut, heure_fin: fin });
    this.erreurs.set({});
  }

  protected async enregistrer(): Promise<void> {
    this.message.set('');
    this.erreurs.set({});

    const m = this.modele();
    if (!m.affectation_enseignant_id) {
      this.erreurs.set({ affectation_enseignant_id: 'Choisissez un élève et une matière.' });
      return;
    }
    if (m.heure_fin <= m.heure_debut) {
      this.erreurs.set({ heure_fin: 'L’heure de fin doit être postérieure à l’heure de début.' });
      return;
    }

    this.sauvegarde.set(true);
    try {
      const payload: CreneauFormulaire = {
        affectation_enseignant_id: m.affectation_enseignant_id,
        jour_semaine: m.jour_semaine,
        heure_debut: m.heure_debut,
        heure_fin: m.heure_fin,
      };
      if (m.id) {
        await firstValueFrom(this.api.majCreneau(m.id, payload));
      } else {
        await firstValueFrom(this.api.creerCreneau(payload));
      }
      this.formulaireOuvert.set(false);
      await this.charger();
    } catch (e) {
      // Les conflits horaires reviennent en 422 sur le champ `heure_debut` :
      // on les affiche sous le champ plutôt qu'en bannière, c'est là que
      // l'utilisateur regarde.
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

  protected async supprimer(): Promise<void> {
    const m = this.modele();
    if (!m.id) return;
    if (!confirm('Supprimer ce créneau du planning ?')) return;

    this.sauvegarde.set(true);
    try {
      await firstValueFrom(this.api.supprimerCreneau(m.id));
      this.formulaireOuvert.set(false);
      await this.charger();
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.sauvegarde.set(false);
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