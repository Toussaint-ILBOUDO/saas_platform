import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  ClasseReferentiel,
  EleveCabinet,
  EleveEdition,
  EleveScolarite,
  MetaPage,
  ParentCabinet,
  ParentEdition,
  ParentScolarite,
} from '../../models';

type Onglet = 'parents' | 'eleves';

interface DescriptionOnglet {
  id: Onglet;
  libelle: string;
  icone: string;
  phrase: string;
}

/**
 * M3 — Scolarité : les deux visages de la même question « qui sont les
 * familles du cabinet ? ». La CRÉATION vit ailleurs (Utilisateurs, contrats,
 * demandes de cours) : ici l'admin consulte, corrige les fiches, rattachements
 * compris, et pilote l'activation du compte élève.
 */
const ONGLETS: DescriptionOnglet[] = [
  {
    id: 'parents',
    libelle: 'Parents',
    icone: 'bi-person-badge',
    phrase:
      'Les familles du cabinet. La fiche réunit le compte, le profil et les enfants rattachés — la création se fait depuis Utilisateurs, un contrat ou une demande de cours.',
  },
  {
    id: 'eleves',
    libelle: 'Élèves',
    icone: 'bi-mortarboard',
    phrase:
      'Les élèves et leur fiche scolaire. Un élève ne se connecte qu’une fois son compte activé : l’activation lui donne son mot de passe.',
  },
];

@Component({
  imports: [FormsModule],
  selector: 'espace-scolarite',
  styles: [    `
      :host {
        display: block;
      }
      .entete h1 {
        margin: 0 0 0.2rem;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .entete p {
        margin: 0 0 1.1rem;
        color: var(--mpc-texte-doux);
        font-size: 0.86rem;
        max-width: 64ch;
      }
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
      .onglet._actif {
        background: var(--mpc-surface);
        color: var(--mpc-primaire);
        box-shadow: 0 1px 3px rgba(10, 14, 26, 0.08);
      }
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
      .alerte,
      .succes {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.8rem 0.95rem;
        border-radius: 0.9rem;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
      }
      .alerte {
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .succes {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
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
      .pastille {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        font-weight: 800;
        font-size: 0.85rem;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
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
      .actions {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        align-items: center;
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
        max-width: 640px;
        border-radius: 1.1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
        padding: 1.3rem 1.4rem;
      }
      .panneau._etroit {
        max-width: 440px;
      }
      .panneau-entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        margin-bottom: 1rem;
      }
      .panneau-entete h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .fermer {
        flex: 0 0 auto;
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
      .sous-titre {
        margin: 0.35rem 0 -0.3rem;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--mpc-primaire);
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
      .champ textarea,
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
      .champ textarea:focus,
      .champ select:focus {
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
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 0.85rem;
      }
      .etat-compte {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        padding: 0.7rem 0.85rem;
        border-radius: 0.8rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
      }
      .enfants {
        display: grid;
        gap: 0.45rem;
      }
      .enfant {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        padding: 0.5rem 0.7rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        font-size: 0.82rem;
      }
      .enfant b {
        color: var(--mpc-bleu);
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
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-primaire:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }
      .btn-neutral {
        padding: 0.55rem 0.95rem;
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.8rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-weight: 700;
        font-size: 0.86rem;
        cursor: pointer;
      }
    `,
  ],
  template: `
    <div class="entete">
      <h1>Scolarité</h1>
      <p>
        Parents, élèves, rattachements et comptes : la fiche complète que les listes de création
        n'exposent pas — y compris l'activation du compte élève.
      </p>
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
          @if (onglet() === 'parents') {
            Aucun parent pour le moment.
          } @else {
            Aucun élève pour le moment.
          }
        }
      </div>
    } @else {
      <div class="liste">
        @switch (onglet()) {
          @case ('parents') {
            @for (p of parents(); track p.id) {
              <article class="ligne">
                <span class="pastille">{{ initiales(p.prenom, p.nom) }}</span>
                <div class="detail">
                  <b>{{ p.prenom }} {{ p.nom }}</b>
                  <small>
                    {{ p.telephone_whatsapp || p.telephone_appel || 'sans numéro' }}
                    @if (p.email) {
                      · {{ p.email }}
                    }
                  </small>
                </div>
                <div class="badges">
                  <span class="badge" [class._actif]="p.statut" [class._inactif]="!p.statut">
                    {{ p.statut ? 'Compte actif' : 'Compte suspendu' }}
                  </span>
                  <span class="badge _info">{{ p.nombre_enfants ?? 0 }} enfant(s)</span>
                </div>
                <div class="actions">
                  <button class="mini" type="button" (click)="ouvrirParent(p.id)">
                    <i class="bi bi-folder2-open"></i> Ouvrir la fiche
                  </button>
                </div>
              </article>
            }
          }
          @case ('eleves') {
            @for (e of eleves(); track e.id) {
              <article class="ligne">
                <span class="pastille">{{ initiales(e.prenom, e.nom) }}</span>
                <div class="detail">
                  <b>{{ e.prenom }} {{ e.nom }}</b>
                  <small>
                    {{ e.classe ? e.classe.sigle + ' · ' + e.classe.nom : 'sans classe' }}
                    @if (e.parent) {
                      · parent : {{ e.parent.prenom }} {{ e.parent.nom }}
                    }
                  </small>
                </div>
                <div class="badges">
                  <span class="badge" [class._actif]="e.statut_compte" [class._inactif]="!e.statut_compte">
                    {{ e.statut_compte ? 'Compte actif' : 'Compte inactif' }}
                  </span>
                </div>
                <div class="actions">
                  <button class="mini" type="button" (click)="ouvrirEleve(e.id)">
                    <i class="bi bi-folder2-open"></i> Ouvrir la fiche
                  </button>
                </div>
              </article>
            }
          }
        }
      </div>
    }

    @if ((metaCourant()?.last_page ?? 1) > 1) {
      <nav class="pagination" aria-label="Pagination">
        <button
          class="page"
          type="button"
          aria-label="Page précédente"
          [disabled]="(metaCourant()?.current_page ?? 1) <= 1"
          (click)="aller((metaCourant()?.current_page ?? 1) - 1)"
        >
          <i class="bi bi-chevron-left"></i>
        </button>
        <span class="page _courante">{{ metaCourant()?.current_page }} / {{ metaCourant()?.last_page }}</span>
        <button
          class="page"
          type="button"
          aria-label="Page suivante"
          [disabled]="(metaCourant()?.current_page ?? 1) >= (metaCourant()?.last_page ?? 1)"
          (click)="aller((metaCourant()?.current_page ?? 1) + 1)"
        >
          <i class="bi bi-chevron-right"></i>
        </button>
      </nav>
    }

    <!-- ================= Fiche ouverte ================= -->
    @if (fenetreOuverte()) {
      <div class="fenetre" (click)="fermerFenetre()">
        <div class="panneau" (click)="$event.stopPropagation()">
          <div class="panneau-entete">
            <h2>{{ titreFiche() }}</h2>
            <button class="fermer" type="button" (click)="fermerFenetre()" aria-label="Fermer">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          @switch (onglet()) {
            @case ('parents') {
              @if (parentForm(); as p) {
                <div class="formulaire">
                  <div class="grille">
                    <label class="champ" [class._erreur]="!!erreurs()['nom']">
                      <span>Nom *</span>
                      <input [ngModel]="p.nom" (ngModelChange)="setParent('nom', $event)" />
                      @if (erreurs()['nom']; as e) {
                        <span class="erreur">{{ e }}</span>
                      }
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['prenom']">
                      <span>Prénom *</span>
                      <input [ngModel]="p.prenom" (ngModelChange)="setParent('prenom', $event)" />
                      @if (erreurs()['prenom']; as e) {
                        <span class="erreur">{{ e }}</span>
                      }
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['email']">
                      <span>Email</span>
                      <input
                        type="email"
                        [ngModel]="p.email ?? ''"
                        (ngModelChange)="setParent('email', $event || null)"
                      />
                      @if (erreurs()['email']; as e) {
                        <span class="erreur">{{ e }}</span>
                      }
                    </label>
                    <label class="champ">
                      <span>WhatsApp</span>
                      <input [ngModel]="p.telephone_whatsapp ?? ''" (ngModelChange)="setParent('telephone_whatsapp', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Téléphone d'appel</span>
                      <input [ngModel]="p.telephone_appel ?? ''" (ngModelChange)="setParent('telephone_appel', $event || null)" />
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['password']">
                      <span>Nouveau mot de passe (optionnel)</span>
                      <input
                        type="password"
                        autocomplete="new-password"
                        [ngModel]="p.password ?? ''"
                        (ngModelChange)="setParent('password', $event)"
                      />
                      @if (erreurs()['password']; as e) {
                        <span class="erreur">{{ e }}</span>
                      } @else {
                        <span class="aide">Laisser vide pour conserver le mot de passe actuel.</span>
                      }
                    </label>
                  </div>

                  <h3 class="sous-titre">Profil</h3>
                  <div class="grille">
                    <label class="champ">
                      <span>Profession</span>
                      <input [ngModel]="p.profession ?? ''" (ngModelChange)="setParent('profession', $event || null)" />
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['nombre_enfants']">
                      <span>Nombre d'enfants</span>
                      <input
                        type="number"
                        min="0"
                        [ngModel]="p.nombre_enfants ?? 0"
                        (ngModelChange)="setParent('nombre_enfants', +$event || 0)"
                      />
                      @if (erreurs()['nombre_enfants']; as e) {
                        <span class="erreur">{{ e }}</span>
                      }
                    </label>
                    <label class="champ" style="grid-column: 1 / -1">
                      <span>Adresse du domicile</span>
                      <input [ngModel]="p.adresse_domicile ?? ''" (ngModelChange)="setParent('adresse_domicile', $event || null)" />
                    </label>
                  </div>

                  <h3 class="sous-titre">Enfants rattachés</h3>
                  @if (parentDetail()?.enfants?.length; as n) {
                    <div class="enfants">
                      @for (enfant of parentDetail()!.enfants!; track enfant.id) {
                        <div class="enfant">
                          <b>{{ enfant.prenom }} {{ enfant.nom }}</b>
                          @if (enfant.classe) {
                            <span class="badge _info">{{ enfant.classe.sigle }}</span>
                          }
                          <span class="badge" [class._actif]="enfant.statut_compte" [class._inactif]="!enfant.statut_compte">
                            {{ enfant.statut_compte ? 'Compte actif' : 'Compte inactif' }}
                          </span>
                          <button class="mini" type="button" (click)="voirEleve(enfant.id)">
                            <i class="bi bi-arrow-right"></i> Fiche élève
                          </button>
                        </div>
                      }
                    </div>
                  } @else {
                    <p class="aide">Aucun enfant rattaché à ce parent.</p>
                  }

                  <div class="actions-form">
                    <button class="btn-neutral" type="button" (click)="fermerFenetre()">Fermer</button>
                    <button class="btn-primaire" type="button" [disabled]="sauvegarde()" (click)="enregistrerParent()">
                      @if (sauvegarde()) {
                        <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
                      } @else {
                        <i class="bi bi-check-lg"></i> Enregistrer
                      }
                    </button>
                  </div>
                </div>
              }
            }

            @case ('eleves') {
              @if (eleveForm(); as e) {
                <div class="formulaire">
                  <div class="etat-compte">
                    <span class="badge" [class._actif]="eleveDetail()?.statut_compte" [class._inactif]="!eleveDetail()?.statut_compte">
                      {{ eleveDetail()?.statut_compte ? 'Compte actif' : 'Compte inactif' }}
                    </span>
                    <span class="aide">L'élève ne peut se connecter que si le compte est actif.</span>
                    <div class="actions" style="margin-left: auto">
                      @if (!eleveDetail()?.statut_compte) {
                        <button class="mini" type="button" [disabled]="actionEnCours()" (click)="ouvrirActivation()">
                          <i class="bi bi-unlock"></i> Activer le compte
                        </button>
                      } @else {
                        <button class="mini _danger" type="button" [disabled]="actionEnCours()" (click)="suspendreEleve()">
                          <i class="bi bi-lock"></i> Suspendre
                        </button>
                      }
                    </div>
                  </div>

                  <h3 class="sous-titre">Identité</h3>
                  <div class="grille">
                    <label class="champ" [class._erreur]="!!erreurs()['nom']">
                      <span>Nom</span>
                      <input [ngModel]="e.nom ?? ''" (ngModelChange)="setEleve('nom', $event || null)" />
                      @if (erreurs()['nom']; as err) {
                        <span class="erreur">{{ err }}</span>
                      }
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['prenom']">
                      <span>Prénom *</span>
                      <input [ngModel]="e.prenom" (ngModelChange)="setEleve('prenom', $event)" />
                      @if (erreurs()['prenom']; as err) {
                        <span class="erreur">{{ err }}</span>
                      }
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['email']">
                      <span>Email</span>
                      <input type="email" [ngModel]="e.email ?? ''" (ngModelChange)="setEleve('email', $event || null)" />
                      @if (erreurs()['email']; as err) {
                        <span class="erreur">{{ err }}</span>
                      }
                    </label>
                    <label class="champ">
                      <span>WhatsApp</span>
                      <input [ngModel]="e.telephone_whatsapp ?? ''" (ngModelChange)="setEleve('telephone_whatsapp', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Téléphone d'appel</span>
                      <input [ngModel]="e.telephone_appel ?? ''" (ngModelChange)="setEleve('telephone_appel', $event || null)" />
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['password']">
                      <span>Nouveau mot de passe (optionnel)</span>
                      <input
                        type="password"
                        autocomplete="new-password"
                        [ngModel]="e.password ?? ''"
                        (ngModelChange)="setEleve('password', $event)"
                      />
                      @if (erreurs()['password']; as err) {
                        <span class="erreur">{{ err }}</span>
                      } @else {
                        <span class="aide">Laisser vide pour conserver le mot de passe actuel.</span>
                      }
                    </label>
                  </div>

                  <h3 class="sous-titre">Rattachements</h3>
                  <div class="grille">
                    <label class="champ" [class._erreur]="!!erreurs()['parent_id']">
                      <span>Parent *</span>
                      <select [ngModel]="e.parent_id" (ngModelChange)="setEleve('parent_id', +$event)">
                        @for (pa of parentsDisponibles(); track pa.id) {
                          <option [value]="pa.id">{{ pa.prenom }} {{ pa.nom }}</option>
                        }
                      </select>
                      @if (erreurs()['parent_id']; as err) {
                        <span class="erreur">{{ err }}</span>
                      }
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['classe_id']">
                      <span>Classe *</span>
                      <select [ngModel]="e.classe_id" (ngModelChange)="setEleve('classe_id', +$event)">
                        @for (c of classes(); track c.id) {
                          <option [value]="c.id">{{ c.nom }} ({{ c.sigle }})</option>
                        }
                      </select>
                      @if (erreurs()['classe_id']; as err) {
                        <span class="erreur">{{ err }}</span>
                      }
                    </label>
                  </div>

                  <h3 class="sous-titre">Fiche scolaire</h3>
                  <div class="grille">
                    <label class="champ">
                      <span>École</span>
                      <input [ngModel]="e.ecole ?? ''" (ngModelChange)="setEleve('ecole', $event || null)" />
                    </label>
                    <label class="champ" [class._erreur]="!!erreurs()['date_naissance']">
                      <span>Date de naissance</span>
                      <input
                        type="date"
                        [ngModel]="e.date_naissance ?? ''"
                        (ngModelChange)="setEleve('date_naissance', $event || null)"
                      />
                      @if (erreurs()['date_naissance']; as err) {
                        <span class="erreur">{{ err }}</span>
                      }
                    </label>
                    <label class="champ">
                      <span>Lieu de naissance</span>
                      <input [ngModel]="e.lieu_naissance ?? ''" (ngModelChange)="setEleve('lieu_naissance', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Parent en charge</span>
                      <input [ngModel]="e.parent_charge ?? ''" (ngModelChange)="setEleve('parent_charge', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Établissement d'origine</span>
                      <input [ngModel]="e.etablissement_origine ?? ''" (ngModelChange)="setEleve('etablissement_origine', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Profession du père</span>
                      <input [ngModel]="e.profession_pere ?? ''" (ngModelChange)="setEleve('profession_pere', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Profession de la mère</span>
                      <input [ngModel]="e.profession_mere ?? ''" (ngModelChange)="setEleve('profession_mere', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Régime d'études</span>
                      <select [ngModel]="e.regime_etude ?? ''" (ngModelChange)="setEleve('regime_etude', $event || null)">
                        <option value="">Non renseigné</option>
                        <option value="interne">Interne</option>
                        <option value="externe">Externe</option>
                      </select>
                    </label>
                    <label class="champ">
                      <span>Loisirs / sport</span>
                      <input [ngModel]="e.loisirs_sport ?? ''" (ngModelChange)="setEleve('loisirs_sport', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Religion</span>
                      <input [ngModel]="e.religion_enfant ?? ''" (ngModelChange)="setEleve('religion_enfant', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Boisson préférée</span>
                      <input [ngModel]="e.boisson_preferee ?? ''" (ngModelChange)="setEleve('boisson_preferee', $event || null)" />
                    </label>
                    <label class="champ">
                      <span>Nourriture préférée</span>
                      <input [ngModel]="e.nourriture_preferee ?? ''" (ngModelChange)="setEleve('nourriture_preferee', $event || null)" />
                    </label>
                    <label class="champ" style="grid-column: 1 / -1">
                      <span>Maladies / allergies</span>
                      <textarea rows="2" [ngModel]="e.maladies_allergies ?? ''" (ngModelChange)="setEleve('maladies_allergies', $event || null)"></textarea>
                    </label>
                    <label class="champ" style="grid-column: 1 / -1">
                      <span>Interdits familiaux</span>
                      <textarea rows="2" [ngModel]="e.interdits_familiaux ?? ''" (ngModelChange)="setEleve('interdits_familiaux', $event || null)"></textarea>
                    </label>
                    <label class="champ" style="grid-column: 1 / -1">
                      <span>Autres précautions</span>
                      <textarea rows="2" [ngModel]="e.autres_precautions ?? ''" (ngModelChange)="setEleve('autres_precautions', $event || null)"></textarea>
                    </label>
                    <label class="champ" style="grid-column: 1 / -1">
                      <span>Autres observations</span>
                      <textarea rows="2" [ngModel]="e.autres_observations ?? ''" (ngModelChange)="setEleve('autres_observations', $event || null)"></textarea>
                    </label>
                  </div>

                  <div class="avertissement">
                    <i class="bi bi-shield-lock"></i>
                    <span>
                      Le parent et la classe sont réservés à l'administration : ce sont eux qui
                      ouvrent l'accès aux contrats, au planning et aux cahiers de texte.
                    </span>
                  </div>

                  <div class="actions-form">
                    <button class="btn-neutral" type="button" (click)="fermerFenetre()">Fermer</button>
                    <button class="btn-primaire" type="button" [disabled]="sauvegarde()" (click)="enregistrerEleve()">
                      @if (sauvegarde()) {
                        <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
                      } @else {
                        <i class="bi bi-check-lg"></i> Enregistrer
                      }
                    </button>
                  </div>
                </div>
              }
            }
          }
        </div>
      </div>
    }

    <!-- ================= Activation du compte élève ================= -->
    @if (activationOuverte()) {
      <div class="fenetre" (click)="fermerActivation()">
        <div class="panneau _etroit" (click)="$event.stopPropagation()">
          <div class="panneau-entete">
            <h2>Activer le compte de {{ eleveDetail()?.prenom }} {{ eleveDetail()?.nom }}</h2>
            <button class="fermer" type="button" (click)="fermerActivation()" aria-label="Fermer">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="formulaire">
            <label class="champ" [class._erreur]="!!erreurs()['password']">
              <span>Nouveau mot de passe (optionnel)</span>
              <input
                type="password"
                autocomplete="new-password"
                [ngModel]="motDePasseActivation()"
                (ngModelChange)="motDePasseActivation.set($event)"
                placeholder="Laisser vide pour conserver"
              />
              <span class="aide">L'élève se connectera avec ce mot de passe et son email.</span>
            </label>
            @if (messageActivation()) {
              <div class="alerte" style="margin-bottom: 0">
                <i class="bi bi-exclamation-triangle"></i><span>{{ messageActivation() }}</span>
              </div>
            }
          </div>

          <div class="actions-form">
            <button class="btn-neutral" type="button" (click)="fermerActivation()">Annuler</button>
            <button
              class="btn-primaire"
              type="button"
              [disabled]="actionEnCours() || (motDePasseActivation().trim() !== '' && motDePasseActivation().trim().length < 8)"
              (click)="confirmerActivation()"
            >
              @if (actionEnCours()) {
                <i class="bi bi-arrow-repeat spin"></i> Activation…
              } @else {
                <i class="bi bi-unlock"></i> Activer
              }
            </button>
          </div>
        </div>
      </div>
    }
  `,
})
export class ScolariteComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly onglets = ONGLETS;
  protected readonly onglet = signal<Onglet>('parents');
  protected readonly parents = signal<ParentCabinet[]>([]);
  protected readonly eleves = signal<EleveCabinet[]>([]);
  protected readonly metaParents = signal<MetaPage | null>(null);
  protected readonly metaEleves = signal<MetaPage | null>(null);
  protected readonly charge = signal(true);
  protected readonly recherche = signal('');
  protected readonly message = signal('');
  protected readonly succes = signal('');
  protected readonly erreurs = signal<Record<string, string>>({});
  protected readonly sauvegarde = signal(false);
  protected readonly actionEnCours = signal(false);

  protected readonly fenetreOuverte = signal(false);
  protected readonly parentDetail = signal<ParentScolarite | null>(null);
  protected readonly parentForm = signal<ParentEdition | null>(null);
  protected readonly eleveDetail = signal<EleveScolarite | null>(null);
  protected readonly eleveForm = signal<EleveEdition | null>(null);

  protected readonly parentsDisponibles = signal<ParentCabinet[]>([]);
  protected readonly classes = signal<ClasseReferentiel[]>([]);

  protected readonly activationOuverte = signal(false);
  protected readonly motDePasseActivation = signal('');
  protected readonly messageActivation = signal('');

  private minuterieRecherche: ReturnType<typeof setTimeout> | null = null;

  protected readonly metaCourant = computed<MetaPage | null>(() =>
    this.onglet() === 'parents' ? this.metaParents() : this.metaEleves()
  );

  protected readonly listeCourante = computed<ParentCabinet[] | EleveCabinet[]>(() =>
    this.onglet() === 'parents' ? this.parents() : this.eleves()
  );

  protected readonly phrase = computed(
    () => ONGLETS.find((o) => o.id === this.onglet())?.phrase ?? ''
  );

  protected readonly placeholderRecherche = computed(() =>
    this.onglet() === 'parents'
      ? 'Rechercher un parent (nom, prénom, email)'
      : 'Rechercher un élève (nom, prénom, email)'
  );

  protected readonly titreFiche = computed(() => {
    if (this.onglet() === 'parents') {
      const p = this.parentDetail();
      return p ? `Fiche de ${p.prenom} ${p.nom}` : 'Fiche parent';
    }
    const e = this.eleveDetail();
    return e ? `Fiche de ${e.prenom} ${e.nom}` : 'Fiche élève';
  });

  ngOnInit(): void {
    void this.charger();
  }

  /* ---------- Liste ---------- */

  protected async charger(page = 1): Promise<void> {
    this.charge.set(true);
    const q = this.recherche().trim();
    try {
      if (this.onglet() === 'parents') {
        const r = await firstValueFrom(this.api.getParentsReferentiel(page, 15, q));
        this.parents.set(r.data);
        this.metaParents.set(r.meta);
      } else {
        const r = await firstValueFrom(this.api.getElevesReferentiel(page, 15, q));
        this.eleves.set(r.data);
        this.metaEleves.set(r.meta);
      }
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.charge.set(false);
    }
  }

  protected setRecherche(valeur: string): void {
    this.recherche.set(valeur);
    if (this.minuterieRecherche) clearTimeout(this.minuterieRecherche);
    this.minuterieRecherche = setTimeout(() => void this.charger(1), 350);
  }

  protected rechercher(): void {
    if (this.minuterieRecherche) clearTimeout(this.minuterieRecherche);
    void this.charger(1);
  }

  protected aller(page: number): void {
    void this.charger(page);
  }

  protected changerOnglet(id: Onglet): void {
    if (this.onglet() === id) return;
    this.onglet.set(id);
    this.recherche.set('');
    this.fermerFenetre();
    void this.charger(1);
  }

  protected initiales(prenom?: string | null, nom?: string | null): string {
    return ((prenom?.[0] ?? '?') + (nom?.[0] ?? '')).toUpperCase();
  }

  /* ---------- Fiches ---------- */

  protected async ouvrirParent(id: number): Promise<void> {
    this.preparerOuverture();
    try {
      const p = await firstValueFrom(this.api.getParentScolarite(id));
      this.parentDetail.set(p);
      this.parentForm.set(this.versFormParent(p));
      this.fenetreOuverte.set(true);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  protected async ouvrirEleve(id: number): Promise<void> {
    this.preparerOuverture();
    try {
      await this.chargerReferentiels();
      const e = await firstValueFrom(this.api.getEleveScolarite(id));
      this.eleveDetail.set(e);
      this.eleveForm.set(this.versFormEleve(e));
      this.fenetreOuverte.set(true);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  /** Bascule vers l'onglet élèves puis ouvre la fiche (« Fiche élève »). */
  protected async voirEleve(id: number): Promise<void> {
    this.fermerFenetre();
    this.onglet.set('eleves');
    this.recherche.set('');
    await this.charger(1);
    await this.ouvrirEleve(id);
  }

  protected fermerFenetre(): void {
    this.fenetreOuverte.set(false);
    this.parentDetail.set(null);
    this.parentForm.set(null);
    this.eleveDetail.set(null);
    this.eleveForm.set(null);
    this.erreurs.set({});
    this.message.set('');
    this.succes.set('');
  }

  private preparerOuverture(): void {
    this.erreurs.set({});
    this.message.set('');
    this.succes.set('');
  }

  private async chargerReferentiels(): Promise<void> {
    try {
      if (this.parentsDisponibles().length === 0) {
        const r = await firstValueFrom(this.api.getParentsReferentiel(1, 200));
        this.parentsDisponibles.set(r.data);
      }
      if (this.classes().length === 0) {
        const r = await firstValueFrom(this.api.getClasses(1, 200));
        this.classes.set(r.data);
      }
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  private versFormParent(p: ParentScolarite): ParentEdition {
    return {
      nom: p.nom,
      prenom: p.prenom,
      email: p.email,
      telephone_whatsapp: p.telephone_whatsapp,
      telephone_appel: p.telephone_appel,
      adresse_domicile: p.profil?.adresse_domicile ?? null,
      profession: p.profil?.profession ?? null,
      nombre_enfants: p.profil?.nombre_enfants ?? 0,
    };
  }

  private versFormEleve(e: EleveScolarite): EleveEdition {
    return {
      parent_id: e.parent_id,
      classe_id: e.classe_id,
      nom: e.nom,
      prenom: e.prenom,
      email: e.email,
      telephone_whatsapp: e.telephone_whatsapp,
      telephone_appel: e.telephone_appel,
      ecole: e.ecole,
      date_naissance: e.date_naissance,
      lieu_naissance: e.lieu_naissance,
      ...e.fiche,
    };
  }

  protected setParent<K extends keyof ParentEdition>(champ: K, valeur: ParentEdition[K]): void {
    const f = this.parentForm();
    if (!f) return;
    this.parentForm.set({ ...f, [champ]: valeur });
  }

  protected setEleve<K extends keyof EleveEdition>(champ: K, valeur: EleveEdition[K]): void {
    const f = this.eleveForm();
    if (!f) return;
    this.eleveForm.set({ ...f, [champ]: valeur });
  }

  /* ---------- Écritures ---------- */

  protected async enregistrerParent(): Promise<void> {
    const f = this.parentForm();
    const detail = this.parentDetail();
    if (!f || !detail) return;

    this.sauvegarde.set(true);
    this.erreurs.set({});
    this.message.set('');
    this.succes.set('');
    try {
      const payload: ParentEdition = { ...f };
      if (!payload.password?.trim()) {
        delete payload.password;
      } else {
        payload.password = payload.password.trim();
      }
      const maj = await firstValueFrom(this.api.majParentScolarite(detail.id, payload));
      this.parentDetail.set(maj);
      this.parentForm.set(this.versFormParent(maj));
      this.succes.set('Fiche parent enregistrée.');
      // Le formulaire élève liste les parents : la liste doit suivre.
      this.parentsDisponibles.set([]);
      await this.charger(this.metaCourant()?.current_page ?? 1);
    } catch (e) {
      this.aplatisirErreurs(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async enregistrerEleve(): Promise<void> {
    const f = this.eleveForm();
    const detail = this.eleveDetail();
    if (!f || !detail) return;

    this.sauvegarde.set(true);
    this.erreurs.set({});
    this.message.set('');
    this.succes.set('');
    try {
      const payload: EleveEdition = { ...f };
      if (!payload.password?.trim()) {
        delete payload.password;
      } else {
        payload.password = payload.password.trim();
      }
      const maj = await firstValueFrom(this.api.majEleveScolarite(detail.id, payload));
      this.eleveDetail.set(maj);
      this.eleveForm.set(this.versFormEleve(maj));
      this.succes.set('Fiche élève enregistrée.');
      await this.charger(this.metaCourant()?.current_page ?? 1);
    } catch (e) {
      this.aplatisirErreurs(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  /** 422 : les messages par champ d'abord, sinon le message global. */
  private aplatisirErreurs(e: unknown): void {
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
  }

  /* ---------- Compte élève ---------- */

  protected ouvrirActivation(): void {
    this.motDePasseActivation.set('');
    this.messageActivation.set('');
    this.erreurs.set({});
    this.activationOuverte.set(true);
  }

  protected fermerActivation(): void {
    this.activationOuverte.set(false);
    this.motDePasseActivation.set('');
    this.messageActivation.set('');
    this.erreurs.set({});
  }

  protected async confirmerActivation(): Promise<void> {
    const detail = this.eleveDetail();
    if (!detail) return;

    const mdp = this.motDePasseActivation().trim();
    this.actionEnCours.set(true);
    this.messageActivation.set('');
    try {
      const maj = await firstValueFrom(this.api.activerCompteEleve(detail.id, mdp || undefined));
      this.appliquerMajEleve(maj);
      this.fermerActivation();
      this.succes.set('Compte élève activé : il peut maintenant se connecter.');
      await this.charger(this.metaCourant()?.current_page ?? 1);
    } catch (e) {
      // La modale reste ouverte : le message doit y être visible.
      this.messageActivation.set(messageErreurApi(e));
    } finally {
      this.actionEnCours.set(false);
    }
  }

  protected async suspendreEleve(): Promise<void> {
    const detail = this.eleveDetail();
    if (!detail) return;

    const prenom = detail.prenom;
    if (!confirm(`Suspendre le compte de ${prenom} ? Il ne pourra plus se connecter (ses contrats et factures sont conservés).`)) {
      return;
    }

    this.actionEnCours.set(true);
    this.message.set('');
    this.succes.set('');
    try {
      const maj = await firstValueFrom(this.api.suspendreCompteEleve(detail.id));
      this.appliquerMajEleve(maj);
      this.succes.set('Compte élève suspendu.');
      await this.charger(this.metaCourant()?.current_page ?? 1);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.actionEnCours.set(false);
    }
  }

  private appliquerMajEleve(maj: EleveScolarite): void {
    this.eleveDetail.set(maj);
    this.eleveForm.set(this.versFormEleve(maj));
  }
}
