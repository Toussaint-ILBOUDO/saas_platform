import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { CONTENT } from '../../content';
import { ApiService } from '../services/api.service';
import { SeoService } from '../services/seo.service';
import { RevealDirective } from '../directives/reveal.directive';
import { PageHeroComponent } from '../components/page-hero.component';
import {
  ReferenceClasse,
  ReferenceMatiere,
  ReferenceTypeCours,
} from '../models';

@Component({
  selector: 'app-demande-cours',
  imports: [CommonModule, FormsModule, RevealDirective, PageHeroComponent],
  styles: [
    `
      .formulaire {
        background: var(--mpc-fond);
        border: 1px solid var(--mpc-bordure);
        border-radius: 22px;
        padding: 1.8rem 1.4rem 2rem;
        max-width: 780px;
        margin: 0 auto;
        box-shadow: 0 8px 30px rgba(18, 48, 94, 0.06);
      }
      @media (min-width: 768px) {
        .formulaire {
          padding: 2.4rem 2.6rem 2.6rem;
        }
      }
      .etape-titre {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        color: var(--mpc-bleu);
        font-weight: 700;
        margin: 1.2rem 0 1rem;
      }
      .etape-titre:first-child {
        margin-top: 0;
      }
      .etape-num {
        flex-shrink: 0;
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 10px;
        background: var(--mpc-primaire);
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 0.9rem;
      }
      label {
        display: block;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--mpc-texte);
        margin-bottom: 0.35rem;
      }
      label .obligatoire {
        color: var(--mpc-primaire);
      }
      .champ {
        width: 100%;
        border: 1px solid var(--mpc-bordure);
        border-radius: 12px;
        padding: 0.7rem 1rem;
        font-size: 0.95rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        outline: none;
        margin-bottom: 1rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
      }
      .champ:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px rgba(232, 97, 12, 0.14);
      }
      select.champ {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%2312305e' d='M6 8 0 0h12z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.9rem center;
        padding-right: 2.4rem;
      }
      .case-matiere {
        margin-bottom: 1rem;
      }
      .case-matiere label {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border: 1px solid var(--mpc-bordure);
        border-radius: 999px;
        padding: 0.45rem 0.9rem;
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--mpc-texte);
        cursor: pointer;
        margin: 0 0.45rem 0.45rem 0;
        transition: all 0.2s ease;
        background: var(--mpc-fond);
      }
      .case-matiere input {
        display: none;
      }
      .case-matiere input:checked + label {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .envoyer {
        width: 100%;
        background: var(--mpc-primaire);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: 0.95rem 1.6rem;
        font-weight: 700;
        font-size: 1.05rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        box-shadow: 0 8px 20px rgba(232, 97, 12, 0.35);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .envoyer:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 12px 26px rgba(232, 97, 12, 0.45);
      }
      .envoyer:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .alerte-success {
        background: var(--mpc-bleu-tint);
        border: 1px solid var(--mpc-bleu);
        color: var(--mpc-bleu);
        border-radius: 14px;
        padding: 1.1rem 1.4rem;
        display: flex;
        gap: 0.9rem;
        align-items: flex-start;
        margin-bottom: 1.5rem;
      }
      .alerte-success i {
        font-size: 1.3rem;
      }
      .erreur {
        color: var(--mpc-primaire);
        font-size: 0.82rem;
        margin: -0.6rem 0 0.9rem;
      }
    `,
  ],
  template: `
    <app-page-hero [titre]="CONTENT.demandeCours.titre" [sousTitre]="CONTENT.demandeCours.sousTitre"></app-page-hero>

    <section class="py-5">
      <div class="container">
        <div class="formulaire" appReveal>
          <div class="alerte-success" *ngIf="envoye()">
            <i class="bi bi-check-circle-fill"></i>
            <div>
              <strong>Votre demande a bien été reçue.</strong>
              <div style="color: var(--mpc-bleu); font-size:.94rem; line-height:1.6">{{ CONTENT.demandeCours.success }}</div>
            </div>
          </div>

          <form #formul="ngForm" (ngSubmit)="soumettre(formul)" *ngIf="!envoye()">

            <div class="etape-titre"><span class="etape-num">1</span>{{ CONTENT.demandeCours.etapesForm[0] }}</div>
            <div class="row g-0" style="column-gap: 1rem">
              <div class="col-12 col-sm-6">
                <label for="nom">Nom <span class="obligatoire">*</span></label>
                <input class="champ" id="nom" name="nom_parent" type="text" required
                       [(ngModel)]="demande.nom_parent" #nom="ngModel" />
                <p class="erreur" *ngIf="nom.invalid && (nom.touched || tente)">Veuillez saisir votre nom.</p>
              </div>
              <div class="col-12 col-sm-6">
                <label for="prenom">Prénom <span class="obligatoire">*</span></label>
                <input class="champ" id="prenom" name="prenom_parent" type="text" required
                       [(ngModel)]="demande.prenom_parent" #prenom="ngModel" />
                <p class="erreur" *ngIf="prenom.invalid && (prenom.touched || tente)">Veuillez saisir votre prénom.</p>
              </div>
            </div>
            <label for="telephone">Téléphone (informations utiles au format +226) <span class="obligatoire">*</span></label>
            <input class="champ" id="telephone" name="telephone" type="tel" required
                   placeholder="+226 …" [(ngModel)]="demande.telephone" #tel="ngModel" />
            <p class="erreur" *ngIf="tel.invalid && (tel.touched || tente)">Veuillez saisir votre numéro de téléphone.</p>

            <div class="etape-titre"><span class="etape-num">2</span>{{ CONTENT.demandeCours.etapesForm[1] }}</div>
            <div class="row g-0" style="column-gap: 1rem">
              <div class="col-12 col-sm-6">
                <label for="type">Type de cours <span class="obligatoire">*</span></label>
                <select class="champ" id="type" name="type_cours_id" required [(ngModel)]="typeCoursId">
                  <option value="" disabled selected>Choisir…</option>
                  <option *ngFor="let type of types()" [value]="type.id">{{ type.libelle }}</option>
                </select>
              </div>
              <div class="col-12 col-sm-6">
                <label for="classe">Classe <span class="obligatoire">*</span></label>
                <select class="champ" id="classe" name="classe_id" required [(ngModel)]="classeId">
                  <option value="" disabled selected>Choisir…</option>
                  <option *ngFor="let classe of classes()" [value]="classe.id">{{ classe.nom }}</option>
                </select>
              </div>
            </div>
            <label for="volume">Volume horaire estimé (heures / semaine) <span class="obligatoire">*</span></label>
            <input class="champ" id="volume" name="volume_horaire_estime" type="number" min="1" required
                   [(ngModel)]="demande.volume_horaire_estime" #vol="ngModel" />
            <p class="erreur" *ngIf="vol.invalid && (vol.touched || tente)">Précisez un volume horaire minimal de 1 heure.</p>

            <label>Matières souhaitées <span class="obligatoire">*</span></label>
            <div class="case-matiere">
              <ng-container *ngFor="let matiere of matieres()">
                <input type="checkbox" [id]="'mat-' + matiere.id" [value]="matiere.id"
                       (change)="basculerMatiere(matiere.id, $event)"/>
                <label [attr.for]="'mat-' + matiere.id"><i class="bi bi-bookmark-check"></i>{{ matiere.nom }}</label>
              </ng-container>
            </div>
            <p class="erreur" *ngIf="tente && matieresChoisies.length === 0">Sélectionnez au moins une matière.</p>

            <div class="etape-titre"><span class="etape-num">3</span>{{ CONTENT.demandeCours.etapesForm[2] }}</div>
            <label for="message">Message complémentaire</label>
            <textarea class="champ" id="message" name="message" rows="4"
                      placeholder="Précisions utiles : situation de l'élève, disponibilités, objectifs…"
                      [(ngModel)]="demande.message"></textarea>

            <button type="submit" class="envoyer" [disabled]="envoiEnCours()">
              <i class="bi bi-send-fill"></i>{{ envoiEnCours() ? 'Envoi en cours…' : CONTENT.demandeCours.envoi }}
            </button>
          </form>
        </div>
      </div>
    </section>
  `,
})
export class DemandeCoursComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly seo = inject(SeoService);

  protected readonly CONTENT = CONTENT;

  protected readonly types = signal<ReferenceTypeCours[]>([]);
  protected readonly classes = signal<ReferenceClasse[]>([]);
  protected readonly matieres = signal<ReferenceMatiere[]>([]);

  protected typeCoursId = 0;
  protected classeId = 0;
  protected matieresChoisies: number[] = [];

  protected demande: {
    nom_parent: string;
    prenom_parent: string;
    telephone: string;
    volume_horaire_estime: number | null;
    message?: string;
  } = { nom_parent: '', prenom_parent: '', telephone: '', volume_horaire_estime: null, message: '' };

  protected readonly envoye = signal(false);
  protected readonly envoiEnCours = signal(false);
  protected tente = false;

  ngOnInit(): void {
    this.seo.definir('Demande de cours | Magis Plus Center', CONTENT.demandeCours.sousTitre);
    this.api.getReferences().subscribe((references) => {
      this.types.set(references.type_cours);
      this.classes.set(references.classes);
      this.matieres.set(references.matieres);
    });
  }

  protected basculerMatiere(id: number, evenement: Event): void {
    const cochee = (evenement.target as HTMLInputElement).checked;
    if (cochee) {
      this.matieresChoisies = [...this.matieresChoisies, id];
    } else {
      this.matieresChoisies = this.matieresChoisies.filter((m) => m !== id);
    }
  }

  protected soumettre(formul: NgForm): void {
    this.tente = true;
    if (
      formul.invalid ||
      this.matieresChoisies.length === 0 ||
      !this.typeCoursId ||
      !this.classeId
    ) {
      return;
    }
    this.envoiEnCours.set(true);
    this.api
      .envoyerDemandeCours({
        nom_parent: this.demande.nom_parent,
        prenom_parent: this.demande.prenom_parent,
        telephone: this.demande.telephone,
        type_cours_id: this.typeCoursId,
        classe_id: this.classeId,
        volume_horaire_estime: this.demande.volume_horaire_estime ?? 1,
        matieres: this.matieresChoisies,
        message: this.demande.message,
      })
      .subscribe({
        next: () => {
          this.envoye.set(true);
          this.envoiEnCours.set(false);
        },
        error: () => {
          this.envoiEnCours.set(false);
          this.tente = true;
        },
      });
  }
}