import { Component, ElementRef, OnInit, ViewChild, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { AuthService, libelleRole } from '../../services/auth.service';
import { messageErreurApi } from '../../services/messages';
import { EnfantSynthese, FicheEleve, ReferenceMatiere, SessionUtilisateur } from '../../models';

/**
 * Écran « Mon profil » : identité du compte, photo, blocs de profil par
 * rôle actif (enseignant / parent / élève), édition d'une fiche élève
 * (la sienne pour l'élève, celle d'un enfant pour le parent) et sécurité
 * (mot de passe, déconnexion).
 */
@Component({
  imports: [FormsModule],
  selector: 'espace-profil',
  styles: [
    `
      :host {
        display: block;
        max-width: 720px;
      }
      .carte {
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        border-radius: 1rem;
        padding: 1.3rem 1.4rem;
        margin-bottom: 1rem;
      }
      .identite {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
      }
      .avatar-zone {
        position: relative;
        flex: 0 0 auto;
      }
      .avatar {
        width: 84px;
        height: 84px;
        display: grid;
        place-items: center;
        border-radius: 1.1rem;
        font-weight: 800;
        font-size: 1.6rem;
        color: #fff;
        background: var(--mpc-bleu);
        overflow: hidden;
        object-fit: cover;
      }
      img.avatar {
        display: block;
      }
      .btn-photo {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.5rem;
        padding: 0.35rem 0.65rem;
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.6rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
      }
      .btn-photo:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .identite-texte {
        flex: 1 1 200px;
        min-width: 180px;
      }
      .identite-texte b {
        display: block;
        font-size: 1.15rem;
        color: var(--mpc-bleu);
      }
      .identite-texte small {
        color: var(--mpc-texte-doux);
        font-size: 0.85rem;
      }
      .roles {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
      }
      .badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.25rem 0.6rem;
        border-radius: 999px;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
      }
      .badge._actif {
        background: var(--mpc-primaire);
        color: #fff;
      }
      h2 {
        margin: 0 0 1rem;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      h3 {
        margin: 0 0 0.6rem;
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--mpc-texte);
      }
      .form {
        display: grid;
        gap: 0.85rem;
      }
      .form._deux-colonnes {
        grid-template-columns: 1fr 1fr;
      }
      @media (max-width: 560px) {
        .form._deux-colonnes {
          grid-template-columns: 1fr;
        }
      }
      .champ {
        display: grid;
        gap: 0.3rem;
      }
      .champ > span {
        font-size: 0.78rem;
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
        font-size: 0.9rem;
        outline: none;
      }
      .champ textarea {
        min-height: 70px;
        resize: vertical;
      }
      .champ input:focus,
      .champ select:focus,
      .champ textarea:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        align-items: center;
      }
      .btn-primaire {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        padding: 0.7rem 1.1rem;
        border: none;
        border-radius: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
      }
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-primaire:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .btn-neutral {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.7rem 1.1rem;
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.8rem;
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
      }
      .btn-neutral:hover:not(:disabled) {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .btn-neutral:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .btn-neutral._danger:hover {
        border-color: var(--mpc-danger);
        color: var(--mpc-danger);
      }
      .btn-mini {
        padding: 0.35rem 0.7rem;
        font-size: 0.75rem;
        border-radius: 0.55rem;
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
      }
      .toast._succes {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .toast._erreur {
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .ligne {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
      }
      .matieres {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
      }
      .matiere {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.6rem;
        border: 1px solid var(--mpc-separateur);
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
      }
      .matiere input {
        accent-color: var(--mpc-primaire);
      }
      .matiere._choisie {
        border-color: var(--mpc-primaire);
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
      }
      .liste-enfants {
        display: grid;
        gap: 0.5rem;
        margin-top: 0.8rem;
      }
      .enfant {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        padding: 0.65rem 0.8rem;
        border: 1px solid var(--mpc-separateur);
        border-radius: 0.8rem;
        background: var(--mpc-fond);
        flex-wrap: wrap;
      }
      .enfant b {
        display: block;
        font-size: 0.9rem;
      }
      .enfant small {
        color: var(--mpc-texte-doux);
        font-size: 0.78rem;
      }
      .sous-titre {
        margin: 1rem 0 0.3rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }
      input[type='file'] {
        display: none;
      }
    `,
  ],
  template: `
    <section class="carte">
      <div class="identite">
        <div class="avatar-zone">
          @if (photoUrl()) {
            <img class="avatar" [src]="photoUrl()" alt="Photo de profil" />
          } @else {
            <span class="avatar">{{ auth.initiales() }}</span>
          }
          <div class="actions">
            <button class="btn-photo" type="button" (click)="fichierPhoto?.click()">
              <i class="bi bi-camera"></i> {{ photoUrl() ? 'Changer' : 'Ajouter' }}
            </button>
            @if (photoUrl()) {
              <button class="btn-photo" type="button" (click)="supprimerPhoto()">
                <i class="bi bi-trash"></i>
              </button>
            }
          </div>
          <input
            type="file"
            accept="image/*"
            #fichierPhoto
            (change)="televerserPhoto($event)"
          />
        </div>
        <div class="identite-texte">
          <b>{{ auth.nomComplet() }}</b>
          <small>{{ utilisateur()?.email }}</small>
          <div class="roles">
            @for (r of utilisateur()?.roles ?? []; track r) {
              <span class="badge" [class._actif]="r === auth.roleActif()">{{ roleLibelle(r) }}</span>
            }
          </div>
        </div>
      </div>
    </section>

    @if (toast()) {
      <div class="toast" [class._succes]="succes()" [class._erreur]="!succes()">
        <i class="bi" [class.bi-check-circle]="succes()" [class.bi-exclamation-triangle]="!succes()"></i>
        {{ toast() }}
      </div>
    }

    <section class="carte">
      <h2>Mon compte</h2>
      <div class="form _deux-colonnes">
        <label class="champ">
          <span>Prénom *</span>
          <input type="text" [(ngModel)]="compte.prenom" name="prenom" autocomplete="given-name" />
        </label>
        <label class="champ">
          <span>Nom *</span>
          <input type="text" [(ngModel)]="compte.nom" name="nom" autocomplete="family-name" />
        </label>
        <label class="champ">
          <span>Adresse email</span>
          <input type="email" [(ngModel)]="compte.email" name="email" autocomplete="email" />
        </label>
        <label class="champ">
          <span>Téléphone WhatsApp</span>
          <input type="tel" [(ngModel)]="compte.telephone_whatsapp" name="twhatsapp" placeholder="Ex. 70 00 00 00" />
        </label>
        <label class="champ">
          <span>Téléphone appel</span>
          <input type="tel" [(ngModel)]="compte.telephone_appel" name="tappel" placeholder="Ex. 70 00 00 00" />
        </label>
      </div>
      <div class="actions" style="margin-top: 1rem">
        <button class="btn-primaire" type="button" (click)="enregistrerCompte()" [disabled]="sauvegarde()">
          @if (sauvegarde()) {
            <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
          } @else {
            <i class="bi bi-check-lg"></i> Enregistrer mes informations
          }
        </button>
      </div>
    </section>

    <!-- ================= Bloc enseignant ================= -->
    @if (profils()?.role === 'enseignant') {
      <section class="carte">
        <h2>Profil enseignant</h2>
        <div class="form _deux-colonnes">
          <label class="champ">
            <span>Numéro Orange Money</span>
            <input type="tel" [(ngModel)]="enseignant.numero_orange_money" name="om" placeholder="Ex. 70 11 22 33" />
          </label>
          <label class="champ">
            <span>Diplôme le plus élevé</span>
            <input type="text" [(ngModel)]="enseignant.diplome_max" name="diplome" placeholder="Ex. Licence Mathématiques" />
          </label>
          <label class="champ">
            <span>Lieu de service</span>
            <input type="text" [(ngModel)]="enseignant.lieu_de_service" name="lieu" placeholder="Ex. Lycée Municipal" />
          </label>
          <label class="champ">
            <span>Domicile</span>
            <input type="text" [(ngModel)]="enseignant.domicile" name="domicile" placeholder="Ex. Nioko 2" />
          </label>
        </div>
        <div class="sous-titre">Matières enseignées</div>
        <div class="matieres">
          @for (m of matieres(); track m.id) {
            <label class="matiere" [class._choisie]="estChoisie(m.id)">
              <input type="checkbox" [checked]="estChoisie(m.id)" (change)="basculerMatiere(m.id)" />
              {{ m.nom }}
            </label>
          } @empty {
            <small style="color: var(--mpc-texte-doux)">Aucune matière de référence disponible.</small>
          }
        </div>
        <div class="actions" style="margin-top: 1rem">
          <button class="btn-primaire" type="button" (click)="enregistrerEnseignant()" [disabled]="sauvegarde()">
            <i class="bi bi-check-lg"></i> Enregistrer le profil enseignant
          </button>
        </div>
      </section>
    }

    <!-- ================= Bloc parent ================= -->
    @if (profils()?.role === 'parent') {
      <section class="carte">
        <h2>Profil parent</h2>
        <div class="form _deux-colonnes">
          <label class="champ">
            <span>Adresse du domicile</span>
            <input type="text" [(ngModel)]="parent.adresse_domicile" name="adresse" placeholder="Ex. Secteur 41" />
          </label>
          <label class="champ">
            <span>Profession</span>
            <input type="text" [(ngModel)]="parent.profession" name="profession" placeholder="Ex. Commerçant" />
          </label>
          <label class="champ">
            <span>Nombre d'enfants</span>
            <input type="number" min="0" [(ngModel)]="parent.nombre_enfants" name="nbenfants" />
          </label>
        </div>
        <div class="actions" style="margin-top: 1rem">
          <button class="btn-primaire" type="button" (click)="enregistrerParent()" [disabled]="sauvegarde()">
            <i class="bi bi-check-lg"></i> Enregistrer le profil parent
          </button>
        </div>

        <div class="sous-titre">Mes enfants</div>
        <div class="liste-enfants">
          @for (e of profils()?.enfants ?? []; track e.id) {
            <div class="enfant">
              <div>
                <b>{{ e.nom_complet }}</b>
                <small>
                  {{ e.classe || 'Classe non renseignée' }}
                  @if (e.ecole) {
                    · {{ e.ecole }}
                  }
                </small>
              </div>
              <button class="btn-neutral btn-mini" type="button" (click)="ouvrirFicheEnfant(e)">
                <i class="bi bi-pencil"></i> Modifier la fiche
              </button>
            </div>
          } @empty {
            <small style="color: var(--mpc-texte-doux)">Aucun enfant rattaché à ce compte.</small>
          }
        </div>
      </section>
    }

    <!-- ================= Bloc élève (sa propre fiche) ================= -->
    @if (profils()?.role === 'eleve' && profils()?.eleve) {
      <section class="carte">
        <div class="ligne">
          <h2 style="margin: 0">Ma fiche élève</h2>
          <button class="btn-neutral btn-mini" type="button" (click)="ouvrirMaFiche()">
            <i class="bi bi-pencil"></i> Modifier
          </button>
        </div>
        <div class="liste-enfants" style="margin-top: 0.8rem">
          <div class="enfant">
            <div>
              <b>{{ profils()?.eleve?.classe || 'Classe non renseignée' }}</b>
              <small>{{ profils()?.eleve?.ecole || 'Établissement non renseigné' }}</small>
            </div>
          </div>
        </div>
      </section>
    }

    <!-- ================= Fiche élève éditable ================= -->
    @if (fiche) {
      <section class="carte">
        <div class="ligne">
          <h2 style="margin: 0">{{ titreFiche }}</h2>
          <button class="btn-neutral btn-mini" type="button" (click)="fermerFiche()">
            <i class="bi bi-x-lg"></i> Fermer
          </button>
        </div>
        <div class="form _deux-colonnes" style="margin-top: 1rem">
          <label class="champ">
            <span>École / établissement</span>
            <input type="text" [(ngModel)]="fiche.ecole" name="fecole" />
          </label>
          <label class="champ">
            <span>Date de naissance</span>
            <input type="date" [(ngModel)]="fiche.date_naissance" name="fdaten" />
          </label>
          <label class="champ">
            <span>Lieu de naissance</span>
            <input type="text" [(ngModel)]="fiche.lieu_naissance" name="flieun" />
          </label>
          <label class="champ">
            <span>Parent en charge</span>
            <input type="text" [(ngModel)]="fiche.parent_charge" name="fparent" />
          </label>
          <label class="champ">
            <span>Établissement d'origine</span>
            <input type="text" [(ngModel)]="fiche.etablissement_origine" name="forigine" />
          </label>
          <label class="champ">
            <span>Régime d'études</span>
            <select [(ngModel)]="fiche.regime_etude" name="fregime">
              <option [ngValue]="null">— Non renseigné —</option>
              <option value="externe">Externe</option>
              <option value="interne">Interne</option>
            </select>
          </label>
          <label class="champ">
            <span>Profession du père</span>
            <input type="text" [(ngModel)]="fiche.profession_pere" name="fper" />
          </label>
          <label class="champ">
            <span>Profession de la mère</span>
            <input type="text" [(ngModel)]="fiche.profession_mere" name="fmere" />
          </label>
          <label class="champ">
            <span>Loisirs / sport</span>
            <input type="text" [(ngModel)]="fiche.loisirs_sport" name="floisirs" />
          </label>
          <label class="champ">
            <span>Religion</span>
            <input type="text" [(ngModel)]="fiche.religion_enfant" name="freligion" />
          </label>
          <label class="champ">
            <span>Boisson préférée</span>
            <input type="text" [(ngModel)]="fiche.boisson_preferee" name="fboisson" />
          </label>
          <label class="champ">
            <span>Nourriture préférée</span>
            <input type="text" [(ngModel)]="fiche.nourriture_preferee" name="fnourriture" />
          </label>
          <label class="champ" style="grid-column: 1 / -1">
            <span>Maladies / allergies</span>
            <textarea [(ngModel)]="fiche.maladies_allergies" name="fsante"></textarea>
          </label>
          <label class="champ" style="grid-column: 1 / -1">
            <span>Interdits familiaux</span>
            <textarea [(ngModel)]="fiche.interdits_familiaux" name="finterdits"></textarea>
          </label>
          <label class="champ" style="grid-column: 1 / -1">
            <span>Autres précautions</span>
            <textarea [(ngModel)]="fiche.autres_precautions" name="fprecautions"></textarea>
          </label>
          <label class="champ" style="grid-column: 1 / -1">
            <span>Autres observations</span>
            <textarea [(ngModel)]="fiche.autres_observations" name="fobs"></textarea>
          </label>
        </div>
        <div class="actions" style="margin-top: 1rem">
          <button class="btn-primaire" type="button" (click)="enregistrerFiche()" [disabled]="sauvegarde()">
            @if (sauvegarde()) {
              <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
            } @else {
              <i class="bi bi-check-lg"></i> Enregistrer la fiche
            }
          </button>
          <button class="btn-neutral" type="button" (click)="fermerFiche()">Annuler</button>
        </div>
      </section>
    }

    <section class="carte">
      <h2>Changer mon mot de passe</h2>
      <div class="form">
        <label class="champ">
          <span>Mot de passe actuel</span>
          <input type="password" [(ngModel)]="actuel" name="actuel" autocomplete="current-password" />
        </label>
        <label class="champ">
          <span>Nouveau mot de passe <small>(8 caractères minimum)</small></span>
          <input type="password" [(ngModel)]="nouveau" name="nouveau" autocomplete="new-password" />
        </label>
        <label class="champ">
          <span>Confirmation</span>
          <input type="password" [(ngModel)]="confirmation" name="confirmation" autocomplete="new-password" />
        </label>
        <div>
          <button class="btn-primaire" type="button" (click)="changerMotDePasse()" [disabled]="sauvegarde()">
            @if (sauvegarde()) {
              <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
            } @else {
              <i class="bi bi-key"></i> Mettre à jour le mot de passe
            }
          </button>
        </div>
      </div>
    </section>

    <section class="carte">
      <div class="ligne">
        <div>
          <b style="display: block; color: var(--mpc-bleu)">Se déconnecter</b>
          <small style="color: var(--mpc-texte-doux)">Fermer la session sur cet appareil.</small>
        </div>
        <button class="btn-neutral _danger" type="button" (click)="deconnexion()">
          <i class="bi bi-box-arrow-right"></i> Déconnexion
        </button>
      </div>
    </section>
  `,
})
export class ProfilComponent implements OnInit {
  @ViewChild('fichierPhoto') fichierPhoto?: ElementRef<HTMLInputElement>;

  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  protected readonly auth = inject(AuthService);
  protected readonly roleLibelle = libelleRole;

  protected readonly profils = signal<{ role: string | null; enfants?: EnfantSynthese[]; eleve?: FicheEleve | null } | null>(null);
  protected readonly matieres = signal<ReferenceMatiere[]>([]);
  protected readonly photoUrl = signal<string | null>(null);
  protected readonly sauvegarde = signal(false);
  protected readonly toast = signal('');
  protected readonly succes = signal(true);

  protected compte = { prenom: '', nom: '', email: '', telephone_whatsapp: '', telephone_appel: '' };
  protected enseignant = {
    numero_orange_money: '',
    diplome_max: '',
    lieu_de_service: '',
    domicile: '',
    matieresChoisies: [] as number[],
  };
  protected parent = { adresse_domicile: '', profession: '', nombre_enfants: 0 };

  protected fiche: FicheEleve | null = null;
  protected titreFiche = '';

  protected actuel = '';
  protected nouveau = '';
  protected confirmation = '';

  protected utilisateur(): SessionUtilisateur | null {
    return this.auth.utilisateur();
  }

  ngOnInit(): void {
    const u = this.auth.utilisateur();
    if (u) {
      this.photoUrl.set(u.photo_url ?? null);
      this.compte = {
        prenom: u.prenom ?? '',
        nom: u.nom ?? '',
        email: u.email ?? '',
        telephone_whatsapp: u.telephone_whatsapp ?? '',
        telephone_appel: u.telephone_appel ?? '',
      };
    }
    void this.chargerProfils();
  }

  protected estChoisie(id: number): boolean {
    return this.enseignant.matieresChoisies.includes(id);
  }

  protected basculerMatiere(id: number): void {
    const i = this.enseignant.matieresChoisies.indexOf(id);
    if (i >= 0) {
      this.enseignant.matieresChoisies.splice(i, 1);
    } else {
      this.enseignant.matieresChoisies.push(id);
    }
  }

  protected async chargerProfils(): Promise<void> {
    try {
      const reponse = await firstValueFrom(this.api.getProfils());
      this.profils.set(reponse.data);
      const bloc = reponse.data;
      if (bloc.enseignant) {
        this.enseignant = {
          numero_orange_money: bloc.enseignant.numero_orange_money ?? '',
          diplome_max: bloc.enseignant.diplome_max ?? '',
          lieu_de_service: bloc.enseignant.lieu_de_service ?? '',
          domicile: bloc.enseignant.domicile ?? '',
          matieresChoisies: [...(bloc.enseignant.matieres ?? [])],
        };
        if (this.matieres().length === 0) {
          const refs = await firstValueFrom(this.api.getReferences());
          this.matieres.set(refs.matieres ?? []);
        }
      }
      if (bloc.parent) {
        this.parent = {
          adresse_domicile: bloc.parent.adresse_domicile ?? '',
          profession: bloc.parent.profession ?? '',
          nombre_enfants: bloc.parent.nombre_enfants ?? 0,
        };
      }
    } catch (e) {
      this.afficherErreur(e);
    }
  }

  protected async enregistrerCompte(): Promise<void> {
    this.toast.set('');
    if (!this.compte.nom.trim() || !this.compte.prenom.trim()) {
      this.afficherMessage('Le nom et le prénom sont obligatoires.', false);
      return;
    }
    this.sauvegarde.set(true);
    try {
      await firstValueFrom(
        this.api.updateMoi({
          nom: this.compte.nom.trim(),
          prenom: this.compte.prenom.trim(),
          email: this.compte.email?.trim() || null,
          telephone_whatsapp: this.compte.telephone_whatsapp?.trim() || null,
          telephone_appel: this.compte.telephone_appel?.trim() || null,
        })
      );
      await this.auth.rechargerSession();
      this.afficherMessage('Informations du compte mises à jour.', true);
    } catch (e) {
      this.afficherErreur(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async televerserPhoto(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const fichier = input.files?.[0];
    input.value = '';
    if (!fichier) return;
    if (fichier.size > 2 * 1024 * 1024) {
      this.afficherMessage('La photo ne doit pas dépasser 2 Mo.', false);
      return;
    }
    this.sauvegarde.set(true);
    try {
      const reponse = await firstValueFrom(this.api.updatePhoto(fichier));
      this.photoUrl.set(reponse.photo_url);
      await this.auth.rechargerSession();
      this.afficherMessage('Photo de profil mise à jour.', true);
    } catch (e) {
      this.afficherErreur(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async supprimerPhoto(): Promise<void> {
    this.sauvegarde.set(true);
    try {
      await firstValueFrom(this.api.deletePhoto());
      this.photoUrl.set(null);
      await this.auth.rechargerSession();
      this.afficherMessage('Photo de profil supprimée.', true);
    } catch (e) {
      this.afficherErreur(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async enregistrerEnseignant(): Promise<void> {
    this.sauvegarde.set(true);
    try {
      await firstValueFrom(
        this.api.updateProfilEnseignant({
          numero_orange_money: this.enseignant.numero_orange_money?.trim() || null,
          diplome_max: this.enseignant.diplome_max?.trim() || null,
          lieu_de_service: this.enseignant.lieu_de_service?.trim() || null,
          domicile: this.enseignant.domicile?.trim() || null,
          matieres: this.enseignant.matieresChoisies,
        })
      );
      this.afficherMessage('Profil enseignant mis à jour.', true);
    } catch (e) {
      this.afficherErreur(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async enregistrerParent(): Promise<void> {
    this.sauvegarde.set(true);
    try {
      await firstValueFrom(
        this.api.updateProfilParent({
          adresse_domicile: this.parent.adresse_domicile?.trim() || null,
          profession: this.parent.profession?.trim() || null,
          nombre_enfants: this.parent.nombre_enfants || 0,
        })
      );
      this.afficherMessage('Profil parent mis à jour.', true);
    } catch (e) {
      this.afficherErreur(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected ouvrirFicheEnfant(enfant: EnfantSynthese): void {
    this.titreFiche = `Fiche de ${enfant.nom_complet}`;
    void this.chargerFicheEleve(enfant.id, `Fiche de ${enfant.nom_complet}`);
  }

  protected ouvrirMaFiche(): void {
    const bloc = this.profils()?.eleve;
    if (!bloc) return;
    this.fiche = { ...bloc };
    this.titreFiche = 'Ma fiche élève';
  }

  protected fermerFiche(): void {
    this.fiche = null;
    this.titreFiche = '';
  }

  protected async enregistrerFiche(): Promise<void> {
    const fiche = this.fiche;
    if (!fiche) return;
    this.sauvegarde.set(true);
    try {
      const reponse = await firstValueFrom(this.api.updateEleve(fiche.id, { ...fiche }));
      this.fiche = { ...reponse.eleve };
      this.afficherMessage('Fiche élève mise à jour.', true);
      await this.chargerProfils();
    } catch (e) {
      this.afficherErreur(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async changerMotDePasse(): Promise<void> {
    this.toast.set('');
    if (!this.actuel || this.nouveau.length < 8) {
      this.afficherMessage('Vérifiez votre mot de passe actuel et la longueur du nouveau (8 caractères minimum).', false);
      return;
    }
    if (this.nouveau !== this.confirmation) {
      this.afficherMessage('Les deux mots de passe ne correspondent pas.', false);
      return;
    }
    this.sauvegarde.set(true);
    try {
      await this.api.changerMotDePasse(this.actuel, this.nouveau).toPromise();
      this.afficherMessage('Mot de passe mis à jour avec succès.', true);
      this.actuel = '';
      this.nouveau = '';
      this.confirmation = '';
    } catch (e) {
      this.afficherErreur(e);
    } finally {
      this.sauvegarde.set(false);
    }
  }

  protected async deconnexion(): Promise<void> {
    await this.auth.deconnexion();
    await this.router.navigateByUrl('/connexion');
  }

  private async chargerFicheEleve(eleveId: number, titre: string): Promise<void> {
    try {
      const reponse = await firstValueFrom(this.api.getEleve(eleveId));
      this.fiche = { ...reponse.eleve };
      this.titreFiche = titre;
    } catch (e) {
      this.afficherErreur(e);
    }
  }

  private afficherMessage(message: string, ok: boolean): void {
    this.toast.set(message);
    this.succes.set(ok);
  }

  private afficherErreur(e: unknown): void {
    this.afficherMessage(messageErreurApi(e), false);
  }
}
