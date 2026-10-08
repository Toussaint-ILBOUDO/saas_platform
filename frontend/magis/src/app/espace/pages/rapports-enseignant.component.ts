import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { NgFor } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  ContratCours,
  MetaPage,
  PeriodeComptable,
  RapportMensuel,
  RapportMensuelApercu,
  RapportMensuelFormulaire,
  RapportMensuelLigne,
  RapportSection,
} from '../../models';
import { ouvrirDetailDepuisRoute } from '../detail-de-route';

/**
 * Écran « Rapports mensuels » de l'enseignant (T7A.7).
 *
 * L'enseignant dépose, corrige, re-soumet et supprime SES rapports, et les
 * télécharge en PDF. Le volume horaire n'est pas à saisir : le serveur le
 * calcule depuis le cahier de texte. L'écran ne propose que les transitions
 * que le serveur autorise (`data.actions`) — un rapport validé n'a donc
 * qu'un bouton PDF.
 *
 * Deux invariants servis par l'interface :
 *
 *  - un rapport ne peut être déposé que sur une **période ouverte** (D-051),
 *    d'où la source `periodes` filtrée côté serveur ;
 *  - un rapport « rejete » porte le motif : l'enseignant le voit en tête de
 *    ligne et sait quoi corriger avant de re-soumettre.
 */
@Component({
  imports: [FormsModule, NgFor],
  selector: 'espace-rapports-enseignant',
  styles: [
    `
      :host { display: block; }
      .entete { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
      .entete h1 { margin: 0 0 0.2rem; font-size: 1.25rem; font-weight: 800; color: var(--mpc-bleu); }
      .entete p { margin: 0; color: var(--mpc-texte-doux); font-size: 0.86rem; max-width: 68ch; }
      .filtres { display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center; margin-bottom: 1rem; }
      .filtres select, .filtres input { font-size: 0.85rem; }
      .table-clair { width: 100%; font-size: 0.86rem; }
      .table-clair th { font-size: 0.72rem; letter-spacing: 0.04em; text-transform: uppercase; color: var(--mpc-texte-doux); white-space: nowrap; }
      .récap { display: flex; gap: 0.5rem; flex-wrap: wrap; }
      .pastille { display: inline-block; min-width: 0.9rem; height: 0.9rem; border-radius: 0.45rem; margin-left: 0.3rem; font-size: 0.66rem; line-height: 0.9rem; text-align: center; color: #fff; }
      .badge-statut { font-size: 0.74rem; font-weight: 700; padding: 0.28rem 0.55rem; border-radius: 99px; white-space: nowrap; }
      .badge-soumis { background: var(--mpc-jaune-doux); color: #6b4f00; }
      .badge-valide { background: var(--mpc-vert-pale); color: #0a5d35; }
      .badge-rejete { background: var(--mpc-rouge-clair); color: #7a1d1d; }
      .motif { margin-top: 0.35rem; font-size: 0.78rem; color: #7a1d1d; }
      .montant { font-variant-numeric: tabular-nums; white-space: nowrap; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .succes { display: flex; align-items: center; gap: 0.5rem; padding: 0.7rem 0.95rem; border-radius: 0.9rem; background: #e7f6ec; color: #14523a; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .modale-carte { max-width: 640px; }
      /* Le <form> s'intercale entre .modal-content et .modal-body : la chaîne
         flex de Bootstrap se brise alors, .modal-body n'est plus borné par
         .modal-dialog-scrollable et la fenêtre devient un bloc figé tronqué en
         bas. On recolle les maillons pour que seul le corps défile. */
      .modale-carte > form { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
      .modale-carte > form > .zone-saisies { min-height: 0; overflow-y: auto; }
      .zone-saisies label { font-size: 0.8rem; font-weight: 700; color: var(--mpc-texte-doux); }
      .lignes-mini { font-size: 0.82rem; }
      .lignes-mini tr:last-child td { border-bottom: none; }
    `,
  ],
  template: `
    <section class="entete">
      <div>
        <h1>Rapports mensuels</h1>
        <p>
          Le volume horaire est calculé d'après votre cahier de texte, jamais saisi.
          Un rapport validé fige vos heures : il est envoyé à la facturation et à la paie.
        </p>
      </div>
      <button type="button" class="btn btn-mpc" (click)="ouvrirSaisie(null)" [disabled]="chargement()">
        <i class="bi bi-plus-lg"></i> Déposer un rapport
      </button>
    </section>

    <section class="filtres">
      <select class="form-select form-select-sm w-auto" [ngModel]="filtres().periode_id" (ngModelChange)="filtres.update(f => ({ ...f, periode_id: $event })); charger()">
        <option [ngValue]="null">Toutes les périodes</option>
        <option *ngFor="let p of periodesAll()" [ngValue]="p.id">{{ p.label }}</option>
      </select>
      <select class="form-select form-select-sm w-auto" [ngModel]="filtres().statut" (ngModelChange)="filtres.update(f => ({ ...f, statut: $event })); charger()">
        <option value="">Tous les statuts</option>
        <option value="soumis">Soumis</option>
        <option value="valide">Validé</option>
        <option value="rejete">Rejeté</option>
      </select>
    </section>

    @if (alerte() && !saisieOuverte() && !detail()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }
    @if (succes() && !saisieOuverte() && !detail()) { <div class="succes"><i class="bi bi-check-circle"></i>{{ succes() }}</div> }

    @if (chargement()) {
      <div class="d-flex justify-content-center py-5"><div class="spinner-border text-mpc" role="status"></div></div>
    } @else if (rapports().length === 0) {
      <div class="alert alert-light border text-center py-5">
        <i class="bi bi-clipboard-data fs-1"></i>
        <div class="mt-2">Aucun rapport pour ces critères.</div>
      </div>
    } @else {
      <div class="table-responsive bg-white rounded-4 border">
        <table class="table table-hover align-middle table-clair mb-0">
          <thead>
            <tr>
              <th>Élève</th><th>Période</th><th>Matières</th><th class="text-end">Heures</th>
              <th class="text-end">Montant estimé</th><th>Statut</th><th></th>
            </tr>
          </thead>
          <tbody>
            @for (r of rapports(); track r.id) {
              <tr>
                <td class="fw-semibold">{{ r.eleve?.nom ?? '—' }}<div class="text-doux-tiny">{{ r.type_cours?.libelle }}</div></td>
                <td>{{ r.periode?.label }}</td>
                <td>{{ resumeMatieres(r) }}</td>
                <td class="text-end montant">{{ chiffre(r.total_heures_lignes) }} h</td>
                <td class="text-end montant">{{ monnaie(r.montant_estime) }}</td>
                <td>
                  <span class="badge-statut badge-{{ r.statut }}">{{ libelleStatut(r.statut) }}</span>
                  @if (r.motif_rejet) { <div class="motif"><i class="bi bi-chat-left-quote"></i> {{ r.motif_rejet }}</div> }
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-mpc" (click)="ouvrirDetail(r)" title="Consulter"><i class="bi bi-eye"></i></button>
                    @if (r.actions.includes('modifier')) {
                      <button class="btn btn-outline-mpc" (click)="ouvrirSaisie(r)" title="Modifier"><i class="bi bi-pencil"></i></button>
                    }
                    @if (r.actions.includes('resoumettre')) {
                      <button class="btn btn-outline-mpc" (click)="ouvrirSaisie(r, true)" title="Re-soumettre"><i class="bi bi-arrow-repeat"></i></button>
                    }
                    @if (r.actions.includes('supprimer')) {
                      <button class="btn btn-outline-mpc text-danger" (click)="supprimer(r)" title="Supprimer"><i class="bi bi-trash"></i></button>
                    }
                    @if (r.actions.includes('pdf')) {
                      <a class="btn btn-outline-mpc" [href]="api.urlPdfRapport(r.id)" target="_blank" title="PDF"><i class="bi bi-filetype-pdf"></i></a>
                    }
                  </div>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>

      @if (meta() && meta()!.last_page > 1) {
        <nav class="mt-3">
          <ul class="pagination pagination-sm justify-content-center">
            <li class="page-item" [class.disabled]="page() <= 1"><button class="page-link" (click)="changerPage(page() - 1)">Précédent</button></li>
            <li class="page-item disabled"><span class="page-link">Page {{ page() }} / {{ meta()!.last_page }}</span></li>
            <li class="page-item" [class.disabled]="page() >= meta()!.last_page"><button class="page-link" (click)="changerPage(page() + 1)">Suivant</button></li>
          </ul>
        </nav>
      }
    }

    <!-- Modale détail -->
    @if (detail()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'detail')">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content modale-carte">
            <div class="modal-header">
              <h5 class="modal-title">
                Rapport — {{ detail()!.eleve?.nom }}
                <span class="badge-statut badge-{{ detail()!.statut }} ms-2">{{ libelleStatut(detail()!.statut) }}</span>
              </h5>
              <button type="button" class="btn-close" (click)="fermerDetail()"></button>
            </div>
            <div class="modal-body">
              @if (detail()!.motif_rejet) {
                <div class="alerte"><i class="bi bi-chat-left-quote"></i><div>Motif du rejet : {{ detail()!.motif_rejet }}</div></div>
              }
              <p class="text-doux-tiny mb-3">
                {{ detail()!.periode?.label }} · {{ detail()!.enseignant?.nom }} ·
                {{ chiffre(detail()!.volume_horaire_cumule) }} h au total
              </p>
              <table class="table table-sm lignes-mini">
                <thead><tr><th>Matière</th><th class="text-end">Séances</th><th class="text-end">Heures</th><th class="text-end">Taux/h</th><th class="text-end">Montant</th></tr></thead>
                <tbody>
                  @for (l of detail()!.lignes; track l.id) {
                    <tr>
                      <td>{{ l.matiere_nom }}</td>
                      <td class="text-end">{{ l.nombre_seances }}</td>
                      <td class="text-end montant">{{ chiffre(l.nombre_heures) }} h</td>
                      <td class="text-end montant">{{ monnaie(l.taux_horaire) }}</td>
                      <td class="text-end montant">{{ monnaie(l.montant_estime) }}</td>
                    </tr>
                  }
                </tbody>
              </table>
              @if (detail()!.bilan_activites) {
                <div class="mt-3"><strong class="d-block">Bilan des activités réalisées</strong><div class="text-doux-tiny">{{ detail()!.bilan_activites }}</div></div>
              }
              @for (section of (detail()?.sections ?? []); track section.id) {
                <div class="mt-3"><strong class="d-block">{{ section.libelle }}</strong></div>
                @for (element of (section.elements ?? []); track element.id) {
                  <div class="mt-2">
                    <div class="text-doux-tiny">
                      {{ element.libelle }}
                      @if (element.obligatoire) { <span class="text-danger">*</span> }
                    </div>
                    @if (element.reponse !== null && element.reponse !== undefined && element.reponse.trim() !== '') {
                      <div class="text-justify">{{ element.reponse }}</div>
                    } @else {
                      <div class="text-doux-tiny"><em>Non renseigné.</em></div>
                    }
                  </div>
                }
              }
            </div>
            <div class="modal-footer">
              <span class="ms-auto me-2 fw-bold">Montant estimé {{ monnaie(totalDetail()) }}</span>
              @if (detail()!.actions.includes('pdf')) {
                <a class="btn btn-mpc" [href]="api.urlPdfRapport(detail()!.id)" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
              }
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Modale saisie (dépôt / correction / re-soumission) — l'ouverture est un
         état à part entière : au dépôt, saisie() reste null (aucun rapport
         n'existe encore), la modale ne peut donc pas dépendre de sa valeur. -->
    @if (saisieOuverte()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'saisie')">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content modale-carte">
            <form (ngSubmit)="enregistrer()" #formAnnee="ngForm">
              <div class="modal-header">
                <h5 class="modal-title">{{ saisie()?.id ? (reSoumission() ? 'Re-soumettre le rapport' : 'Modifier le rapport') : 'Déposer un rapport' }}</h5>
                <button type="button" class="btn-close" (click)="fermerSaisie()"></button>
              </div>
              <div class="modal-body zone-saisies">
                <!-- Même bandeau qu'en page : ici il est visible malgré le fond. -->
                @if (alerte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }
                @if (!saisie()?.id) {
                  <div class="row g-3">
                    <div class="col-md-7">
                      <label class="form-label">Cours</label>
                      <select class="form-select" [(ngModel)]="form.contrat_cours_id" name="contrat_cours_id" required (ngModelChange)="majApercu()">
                        <option [ngValue]="null">— Choisir un cours —</option>
                        <option *ngFor="let c of contrats()" [ngValue]="c.id">{{ libelleContrat(c) }}</option>
                      </select>
                    </div>
                    <div class="col-md-5">
                      <label class="form-label">Période</label>
                      <select class="form-select" [(ngModel)]="form.periode_id" name="periode_id" required (ngModelChange)="majApercu()">
                        <option [ngValue]="null">— Choisir —</option>
                        <option *ngFor="let p of periodesOuvertes()" [ngValue]="p.id">{{ p.label }}</option>
                      </select>
                      <div class="form-text">Seules les périodes ouvertes acceptent un dépôt (D-051).</div>
                    </div>
                  </div>

                  <!-- Listes vides : on explique — ou on propose de relancer —
                       au lieu d'un formulaire muet. -->
                  @if (contexteEnEchec()) {
                    <div class="alert alert-light border small mt-3 mb-0 d-flex align-items-center gap-2">
                      <i class="bi bi-exclamation-triangle"></i>
                      <span>Vos cours et les périodes n'ont pas pu être chargés.</span>
                      <button type="button" class="btn btn-outline-mpc btn-sm ms-auto" (click)="rechargerContexte()">Réessayer</button>
                    </div>
                  } @else if (contrats().length === 0) {
                    <div class="alert alert-light border small mt-3 mb-0">
                      Aucun cours actif : le dépôt deviendra possible dès qu'un contrat vous sera affecté.
                    </div>
                  } @else if (periodesOuvertes().length === 0) {
                    <div class="alert alert-light border small mt-3 mb-0">
                      Aucune période ouverte : le dépôt sera possible à la prochaine ouverture.
                    </div>
                  }

                  @if (apercuChargement()) {
                    <div class="d-flex align-items-center gap-2 mt-3 text-doux-tiny"><div class="spinner-border spinner-border-sm text-mpc"></div> Calcul des heures depuis le cahier de texte…</div>
                  } @else if (apercu()) {
                    <div class="border rounded-3 p-3 mt-3">
                      <div class="d-flex flex-wrap gap-3 small">
                        <span><strong class="d-block text-doux-tiny">Séances</strong>{{ apercu()!.nombre_seances }}</span>
                        <span><strong class="d-block text-doux-tiny">Volume horaire</strong>{{ chiffre(apercu()!.volume_horaire) }} h</span>
                      </div>
                      <div class="table-responsive mt-2">
                        <table class="table table-sm lignes-mini mb-0">
                          <thead><tr><th>Matière</th><th class="text-end">Séances</th><th class="text-end">Heures</th></tr></thead>
                          <tbody>
                            @for (l of (apercu()?.ventilation ?? []); track $index) {
                              <tr>
                                <td>{{ l.matiere ?? '—' }}</td>
                                <td class="text-end">{{ l.nombre_seances }}</td>
                                <td class="text-end">{{ chiffre(l.nombre_heures) }} h</td>
                              </tr>
                            }
                          </tbody>
                        </table>
                      </div>
                      <div class="mt-2 text-doux-tiny text-justify">{{ apercu()!.bilan }}</div>
                    </div>
                  }
                } @else {
                  <p class="text-doux-tiny mb-3">
                    {{ saisie()?.eleve?.nom }} · {{ saisie()?.periode?.label }} ·
                    {{ chiffre(saisie()?.total_heures_lignes) }} h déjà calculées
                  </p>
                }

                @for (section of (sections() ?? []); track section.id) {
                  <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-2 mb-1">
                      {{ section.libelle }}
                    </label>
                    @if (section.description) { <div class="form-text mt-0 mb-2">{{ section.description }}</div> }
                    @for (element of (section.elements ?? []); track element.id) {
                      <div class="mb-3">
                        <label class="form-label">
                          {{ element.libelle }}
                          @if (element.obligatoire) { <span class="text-danger">*</span> }
                        </label>
                        @if (element.aide) { <div class="form-text mt-0 mb-1">{{ element.aide }}</div> }
                        @if (element.type === 'text') {
                          <input class="form-control" type="text" [ngModel]="reponseDe(element.id)" (ngModelChange)="setReponse(element.id, $event)" [name]="'reponse_' + element.id" />
                        } @else {
                          <textarea class="form-control" rows="3" [ngModel]="reponseDe(element.id)" (ngModelChange)="setReponse(element.id, $event)" [name]="'reponse_' + element.id"></textarea>
                        }
                      </div>
                    }
                  </div>
                }
                @if ((sections() ?? []).length === 0 && saisie()?.id) {
                  <p class="text-doux-tiny">Aucune section dans le modèle de rapport.</p>
                }
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light" (click)="fermerSaisie()">Annuler</button>
                <button type="submit" class="btn btn-mpc" [disabled]="soumission()">
                  @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                  {{ saisie()?.id ? (reSoumission() ? 'Re-soumettre' : 'Enregistrer') : 'Déposer le rapport' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    }
  `,
})
export class RapportsEnseignantComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected rapports = signal<RapportMensuel[]>([]);
  protected periodes = signal<PeriodeComptable[]>([]);
  /** Noyau durable pour le filtre : toutes les périodes, même clôturées. */
  protected periodesAll = signal<PeriodeComptable[]>([]);
  protected contrats = signal<ContratCours[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected page = signal(1);
  protected chargement = signal(false);
  protected soumission = signal(false);
  protected alerte = signal('');
  protected succes = signal('');
  protected detail = signal<RapportMensuel | null>(null);
  protected saisie = signal<RapportMensuel | null>(null);
  /** État d'ouverture de la modale : distinct de `saisie()`, qui est null au
   *  dépôt (aucun rapport n'existe encore). */
  protected saisieOuverte = signal(false);
  protected reSoumission = signal(false);

  /** Modèle de rapport administré : sections et éléments à remplir (lecture
   *  seule pour l'enseignant — la configuration est côté admin). */
  protected sections = signal<RapportSection[]>([]);
  protected apercu = signal<RapportMensuelApercu | null>(null);
  protected apercuChargement = signal(false);
  /** True quand le chargement des cours/périodes a échec (≠ liste vide). */
  protected contexteEnEchec = signal(false);

  protected filtres = signal<{ periode_id: number | null; statut: string }>({ periode_id: null, statut: '' });

  protected form: RapportMensuelFormulaire = { contrat_cours_id: null, periode_id: null, reponses: {} };

  /** Périodes réellement déposables : seules les ouvertes (D-051). */
  protected periodesOuvertes = computed<PeriodeComptable[]>(() =>
    this.periodes().filter((p) => p.est_ouverte)
  );

  protected totalDetail = computed<number>(() =>
    (this.detail()?.lignes ?? []).reduce((s: number, l: RapportMensuelLigne) => s + l.montant_estime, 0)
  );

  async ngOnInit(): Promise<void> {
    await Promise.all([this.charger(), this.chargerContextes()]);

    // Cible d'une notification : ouvre directement la fiche `…/{id}`.
    ouvrirDetailDepuisRoute(
      this.route,
      (id) => this.api.getRapport(id),
      (rapport) => this.ouvrirDetail(rapport),
      (erreur) => this.alerte.set(messageErreurApi(erreur))
    );
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');
    try {
      const r = await firstValueFrom(
        this.api.getRapports(this.page(), 20, {
          periode_id: this.filtres().periode_id,
          statut: this.filtres().statut,
        })
      );
      this.rapports.set(r.data);
      this.meta.set(r.meta ?? null);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.chargement.set(false);
    }
  }

  /** Données communes aux formulaires : périodes du cabinet + contrats de l'enseignant. */
  private async chargerContextes(): Promise<void> {
    // Indépendantes : une requête en échec ne doit pas vider l'autre liste
    // (Promise.all rejetait le couple entier → deux selects muets d'un coup).
    const [periodesRes, contratsRes] = await Promise.allSettled([
      firstValueFrom(this.api.getPeriodesRapport()),
      firstValueFrom(this.api.getMesCours(1, 200)),
    ]);

    if (periodesRes.status === 'fulfilled') {
      this.periodes.set(periodesRes.value);
      this.periodesAll.set(periodesRes.value);
    }
    if (contratsRes.status === 'fulfilled') {
      // Seuls les contrats actifs ouvrent un dépôt : « suspendu » ne lève pas
      // d'erreur, il ne figure simplement pas dans la liste.
      this.contrats.set(contratsRes.value.data.filter((c) => c.statut === 'actif'));
    }

    const echecs: string[] = [];
    if (periodesRes.status === 'rejected') {
      echecs.push(`périodes : ${messageErreurApi(periodesRes.reason)}`);
    }
    if (contratsRes.status === 'rejected') {
      echecs.push(`cours : ${messageErreurApi(contratsRes.reason)}`);
    }

    if (echecs.length > 0) {
      this.contexteEnEchec.set(true);
      this.alerte.set(`Chargement incomplet — ${echecs.join(' ; ')}`);
      return;
    }

    this.contexteEnEchec.set(false);
    if (this.alerte().startsWith('Chargement incomplet')) {
      this.alerte.set('');
    }
    // Un choix unique se fait tout seul : un seul cours affecté, une seule
    // période ouverte → rien à cliquer, le couple est déjà complet.
    this.ajusterSelections();
  }

  /** Relance le chargement des cours/périodes (bouton « Réessayer »). */
  protected rechargerContexte(): void {
    void this.chargerContextes();
  }

  /**
   * Pré-remplit la sélection quand il n'existe qu'une possibilité : un seul
   * cours affecté ou une seule période ouverte. Jamais en édition (`saisie().id`)
   * — les valeurs du rapport sont alors celles de l'enregistrement.
   */
  private ajusterSelections(): void {
    if (this.saisie()?.id) return;

    if (this.form.contrat_cours_id === null && this.contrats().length === 1) {
      this.form.contrat_cours_id = this.contrats()[0].id;
    }
    if (this.form.periode_id === null && this.periodesOuvertes().length === 1) {
      this.form.periode_id = this.periodesOuvertes()[0].id;
    }
  }

  protected changerPage(p: number): void {
    if (p < 1) return;
    this.page.set(p);
    void this.charger();
  }

  protected ouvrirDetail(r: RapportMensuel): void {
    // Depuis la liste, le détail avec `sections` est absent : on le relit.
    if (r.sections?.length) {
      this.detail.set(r);
      return;
    }

    void firstValueFrom(this.api.getRapport(r.id))
      .then((complet) => this.detail.set(complet))
      .catch((e) => this.alerte.set(messageErreurApi(e)));
  }

  protected fermerDetail(): void {
    this.detail.set(null);
  }

  protected fermerSaisie(): void {
    this.saisieOuverte.set(false);
    this.saisie.set(null);
    this.reSoumission.set(false);
    this.apercu.set(null);
  }

  protected async ouvrirSaisie(r: RapportMensuel | null, resoumettre = false): Promise<void> {
    this.alerte.set('');
    this.succes.set('');
    this.saisieOuverte.set(true);
    this.reSoumission.set(resoumettre);
    this.saisie.set(r);
    this.apercu.set(null);
    this.form = {
      contrat_cours_id: r?.contrat_cours_id ?? null,
      periode_id: r?.periode_id ?? null,
      reponses: {},
    };

    try {
      // Listes vides au premier chargement (session fraîche, erreur
      // transitoire…) : on retente à l'ouverture plutôt que de présenter deux
      // sélecteurs muets.
      const contexte =
        !r && (this.contrats().length === 0 || this.periodes().length === 0)
          ? this.chargerContextes()
          : Promise.resolve();

      const [sections, detail] = await Promise.all([
        firstValueFrom(this.api.getModeleRapport()),
        r ? firstValueFrom(this.api.getRapport(r.id)) : Promise.resolve(null),
      ]);

      this.sections.set(Array.isArray(sections) ? sections : []);
      await contexte;

      // Pré-remplissage des réponses existantes au moment d'une correction.
      if (detail) {
        for (const section of detail.sections ?? []) {
          for (const element of section.elements ?? []) {
            if (element.reponse != null) this.form.reponses![element.id] = element.reponse;
          }
        }
      }

      // Un seul cours / une seule période ouverte : déjà sélectionnés.
      this.ajusterSelections();

      // Aperçu du calcul (lu depuis le cahier de texte) dès que le couple est
      // complet : l'enseignant voit ce qui sera figé avant de soumettre.
      if (this.form.contrat_cours_id && this.form.periode_id) {
        await this.majApercu();
      }
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    }
  }

  /** Recalcule l'aperçu du rapport (volume, séances, ventilation, bilan). */
  protected async majApercu(): Promise<void> {
    if (!this.form.contrat_cours_id || !this.form.periode_id) {
      this.apercu.set(null);
      return;
    }

    this.apercuChargement.set(true);
    this.apercu.set(null);
    try {
      this.apercu.set(
        await firstValueFrom(
          this.api.apercuRapport({
            contrat_cours_id: this.form.contrat_cours_id,
            periode_id: this.form.periode_id,
          })
        )
      );
    } catch (e) {
      this.apercu.set(null);
      // L'aperçu ne peut pas être calculé : on le dit, plutôt qu'un bloc vide.
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.apercuChargement.set(false);
    }
  }

  /** Valeur saisie pour l'élément (aucune écriture si la section est absente). */
  protected reponseDe(id: number): string {
    return this.form.reponses?.[id] ?? '';
  }

  /** Écrit la réponse d'un élément dans le formulaire de dépôt. */
  protected setReponse(id: number, valeur: string): void {
    if (!this.form.reponses) this.form.reponses = {};
    if (valeur === '' || valeur.trim() === '') {
      delete this.form.reponses[id];
    } else {
      this.form.reponses[id] = valeur;
    }
  }

  protected async enregistrer(): Promise<void> {
    if (!this.form.contrat_cours_id || !this.form.periode_id) {
      this.succes.set('');
      this.alerte.set('Choisissez le cours et la période avant d\'enregistrer.');
      return;
    }

    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      const cible = this.saisie();
      if (!cible) {
        await firstValueFrom(this.api.creerRapport(this.form));
        this.succes.set('Rapport déposé.');
      } else if (this.reSoumission()) {
        await firstValueFrom(this.api.resoumettreRapport(cible.id, this.form));
        this.succes.set('Rapport re-soumis.');
      } else {
        await firstValueFrom(this.api.corrigerRapport(cible.id, this.form));
        this.succes.set('Rapport enregistré.');
      }
      this.fermerSaisie();
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async supprimer(r: RapportMensuel): Promise<void> {
    if (!confirm('Supprimer ce rapport ?')) return;
    this.alerte.set('');
    this.succes.set('');
    try {
      await firstValueFrom(this.api.supprimerRapport(r.id));
      this.succes.set('Rapport supprimé.');
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    }
  }

  /** Clic sur le fond de la modale. Le clic sur le fond ferme ; un clic sur le
     *  panneau ne se propage pas au fond (Angular stoppe l'événement au niveau
     *  du panneau), d'où ce garde qui compare l'événement à sa cible. */
  protected siFond(ev: MouseEvent, quelle: 'detail' | 'saisie'): void {
    if (ev.target !== ev.currentTarget) return;
    if (quelle === 'detail') this.fermerDetail();
    else this.fermerSaisie();
  }

  protected resumeMatieres(r: RapportMensuel): string {
    const noms = r.lignes.map((l) => l.matiere_nom).filter(Boolean);
    return noms.length ? noms.join(', ') : '—';
  }

  protected libelleStatut(s: string): string {
    return { soumis: 'Soumis', valide: 'Validé', rejete: 'Rejeté' }[s] ?? s;
  }

  protected libelleContrat(c: ContratCours): string {
    return `${c.eleve?.nom ?? 'Élève'} — ${(c.affectations?.map((a) => a.matiere?.nom)).filter(Boolean).join(', ') || 'Cours'}`;
  }

  protected chiffre(v: number | null | undefined): string {
    return Number(v ?? 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
  }

  protected monnaie(v: number | null | undefined): string {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(v ?? 0)) + ' FCFA';
  }
}