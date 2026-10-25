import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { messageErreurApi } from '../../services/messages';
import {
  LigneObjectifMatiere,
  MatiereRefObjectif,
  MetaPage,
  ObjectifFormulaire,
  ObjectifPedagogique,
  PeriodeObjectif,
  RefEleveObjectif,
} from '../../models';

/**
 * Écran « Objectifs pédagogiques » (T7A.6, D-050).
 *
 * Un même composant sert les deux usages de l'API, tranchés par le rôle
 * connecté :
 *
 *  - l'**enseignant** crée, corrige et supprime SES objectifs, sur les
 *    seuls élèves qu'il suit et sur une période ouverte (le serveur refuse
 *    toute écriture sur une période clôturée — D-051) ;
 *  - l'**admin** supervise tous les objectifs du cabinet, complète les
 *    moyennes obtenues et son commentaire, et supprime (la création reste
 *    enseignant : c'est lui qui porte la relation).
 *
 * L'unité de travail est le triplet élève × période, ventilé par matière :
 * chaque ligne de matière porte ses propres moyennes visée et obtenue
 * (D-050), d'où le tableau de saisie en modale plutôt qu'un simple champ
 * global.
 */
@Component({
  imports: [FormsModule],
  selector: 'espace-objectifs',
  styles: [
    `
      :host { display: block; }
      .entete { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
      .entete h1 { margin: 0 0 0.2rem; font-size: 1.25rem; font-weight: 800; color: var(--mpc-bleu); }
      .entete p { margin: 0; color: var(--mpc-texte-doux); font-size: 0.86rem; max-width: 68ch; }
      .filtres { display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center; margin-bottom: 1rem; }
      .filtres input, .filtres select { font-size: 0.85rem; }
      .table-clair { width: 100%; font-size: 0.86rem; }
      .table-clair th { font-size: 0.72rem; letter-spacing: 0.04em; text-transform: uppercase; color: var(--mpc-texte-doux); white-space: nowrap; }
      .montant { font-variant-numeric: tabular-nums; white-space: nowrap; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .succes { display: flex; align-items: center; gap: 0.5rem; padding: 0.7rem 0.95rem; border-radius: 0.9rem; background: #e7f6ec; color: #14523a; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .modale-carte { max-width: 760px; }
      /* Même raccord que l'écran rapports : <form> entre .modal-content et
         .modal-body, les maillons flex sont recollés pour que seul le corps
         défile. */
      .modale-carte > form { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
      .modale-carte > form > .zone-saisies { min-height: 0; overflow-y: auto; }
      .zone-saisies label { font-size: 0.8rem; font-weight: 700; color: var(--mpc-texte-doux); }
      .lignes-mini { font-size: 0.82rem; }
      .lignes-mini tr:last-child td { border-bottom: none; }
      .lignes-mini th { font-size: 0.7rem; }
    `,
  ],
  template: `
    <section class="entete">
      <div>
        <h1>Objectifs pédagogiques</h1>
        @if (estAdmin()) {
          <p>
            Suivi des objectifs fixés par les enseignants : moyennes visées et obtenues,
            ventilation par matière, matériel. Corrigez et complétez ici — la création
            appartient à l'enseignant.
          </p>
        } @else {
          <p>
            Fixez vos objectifs pour chaque élève et chaque période : moyennes visées,
            ventilation par matière et matériel nécessaire. Une écriture n'est possible
            que sur une période ouverte (D-051).
          </p>
        }
      </div>
      @if (!estAdmin()) {
        <button type="button" class="btn btn-mpc" (click)="ouvrirSaisie(null)" [disabled]="chargement()">
          <i class="bi bi-plus-lg"></i> Nouvel objectif
        </button>
      }
    </section>

    <section class="filtres">
      <input
        type="search"
        class="form-control form-control-sm w-auto"
        placeholder="Rechercher un élève…"
        [(ngModel)]="recherche"
        (keyup.enter)="rechercher()"
      />
      <button type="button" class="btn btn-outline-mpc btn-sm" (click)="rechercher()" title="Rechercher">
        <i class="bi bi-search"></i>
      </button>
      <select
        class="form-select form-select-sm w-auto"
        [ngModel]="filtres().periode_id"
        (ngModelChange)="filtres.update((f) => ({ ...f, periode_id: $event })); changerPage(1)"
      >
        <option [ngValue]="null">Toutes les périodes</option>
        @for (p of periodes(); track p.id) {
          <option [ngValue]="p.id">{{ p.label }}</option>
        }
      </select>
    </section>

    @if (alerte() && !saisieOuverte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }
    @if (succes()) { <div class="succes"><i class="bi bi-check-circle"></i>{{ succes() }}</div> }

    @if (chargement()) {
      <div class="d-flex justify-content-center py-5"><div class="spinner-border text-mpc" role="status"></div></div>
    } @else if (objectifs().length === 0) {
      <div class="alert alert-light border text-center py-5">
        <i class="bi bi-bullseye fs-1"></i>
        <div class="mt-2">Aucun objectif pour ces critères.</div>
        @if (!estAdmin() && contexteOk()) {
          <button type="button" class="btn btn-mpc btn-sm mt-3" (click)="ouvrirSaisie(null)">
            <i class="bi bi-plus-lg"></i> Créer un objectif
          </button>
        }
      </div>
    } @else {
      <div class="table-responsive bg-white rounded-4 border">
        <table class="table table-hover align-middle table-clair mb-0">
          <thead>
            <tr>
              <th>Élève</th>
              <th>Période</th>
              @if (estAdmin()) { <th>Enseignant</th> }
              <th class="text-end">Visée</th>
              <th class="text-end">Obtenue</th>
              <th>Matières</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @for (o of objectifs(); track o.id) {
              <tr>
                <td class="fw-semibold">
                  {{ libelleEleve(o.eleve) }}
                  @if (o.eleve?.classe) { <div class="text-doux-tiny">{{ o.eleve!.classe }}</div> }
                </td>
                <td>{{ o.periode?.label ?? '—' }}</td>
                @if (estAdmin()) { <td>{{ libelleEnseignant(o) }}</td> }
                <td class="text-end montant">{{ moyenne(o.moyenne_visee) }}</td>
                <td class="text-end montant">{{ moyenne(o.moyenne_obtenue) }}</td>
                <td>{{ resumeMatieres(o) }}</td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-mpc" (click)="ouvrirSaisie(o)" title="Modifier">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-mpc text-danger" (click)="supprimer(o)" title="Supprimer">
                      <i class="bi bi-trash"></i>
                    </button>
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

    <!-- Modale saisie (création enseignant / correction des deux rôles) -->
    @if (saisieOuverte()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event)">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content modale-carte">
            <form (ngSubmit)="enregistrer()">
              <div class="modal-header">
                <h5 class="modal-title">{{ edition()?.id ? 'Modifier l\'objectif' : 'Nouvel objectif' }}</h5>
                <button type="button" class="btn-close" (click)="fermerSaisie()"></button>
              </div>
              <div class="modal-body zone-saisies">
                @if (alerte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }

                <div class="row g-3">
                  <div class="col-md-7">
                    <label class="form-label">Élève <span class="text-danger">*</span></label>
                    @if (edition()) {
                      <input class="form-control" [value]="libelleEleve(edition()!.eleve)" [disabled]="true" name="eleve" />
                    } @else {
                      <select class="form-select" [(ngModel)]="form.eleve_id" name="eleve_id" required>
                        <option [ngValue]="null">— Choisir un élève —</option>
                        @for (e of eleves(); track e.id) {
                          <option [ngValue]="e.id">{{ libelleEleve(e) }}{{ e.classe ? ' — ' + e.classe : '' }}</option>
                        }
                      </select>
                      @if (eleves().length === 0 && contexteOk()) {
                        <div class="form-text">Aucun élève suivi : un objectif deviendra possible dès qu'un cours vous sera affecté.</div>
                      }
                    }
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Période <span class="text-danger">*</span></label>
                    <select class="form-select" [(ngModel)]="form.periode_id" name="periode_id" required>
                      <option [ngValue]="null">— Choisir —</option>
                      @for (p of periodesChoix(); track p.id) {
                        <option [ngValue]="p.id">{{ p.label }}{{ p.est_ouverte ? '' : ' (clôturée)' }}</option>
                      }
                    </select>
                    <div class="form-text">Seules les périodes ouvertes acceptent une création (D-051).</div>
                  </div>
                </div>

                <div class="row g-3 mt-0">
                  <div class="col-md-4">
                    <label class="form-label">Moyenne visée (global)</label>
                    <input type="number" class="form-control" min="0" max="20" step="0.5" [(ngModel)]="form.moyenne_visee" name="moyenne_visee" />
                  </div>
                  @if (estAdmin() && edition()) {
                    <div class="col-md-4">
                      <label class="form-label">Moyenne obtenue (global)</label>
                      <input type="number" class="form-control" min="0" max="20" step="0.5" [(ngModel)]="form.moyenne_obtenue" name="moyenne_obtenue" />
                    </div>
                  }
                </div>

                <div class="mt-3">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                    <label class="form-label mb-0">Ventilation par matière <span class="text-danger">*</span></label>
                    <button type="button" class="btn btn-outline-mpc btn-sm" (click)="ajouterLigne()">
                      <i class="bi bi-plus-lg"></i> Ajouter une matière
                    </button>
                  </div>
                  <div class="form-text mb-2">Chaque matière couverte porte ses propres moyennes (D-050).</div>
                  <div class="table-responsive">
                    <table class="table table-sm lignes-mini mb-0">
                      <thead>
                        <tr>
                          <th>Matière</th>
                          <th style="width: 14%">Visée</th>
                          @if (estAdmin()) { <th style="width: 14%">Obtenue</th> }
                          <th>Commentaire</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        @for (ligne of form.matieres; track $index) {
                          <tr>
                            <td>
                              <select class="form-select form-select-sm" [(ngModel)]="ligne.matiere_id" [name]="'matiere_' + $index">
                                <option [ngValue]="null">— Choisir —</option>
                                @for (m of matieresPourLigne($index); track m.id) {
                                  <option [ngValue]="m.id">{{ m.nom }}</option>
                                }
                              </select>
                            </td>
                            <td>
                              <input type="number" class="form-control form-control-sm" min="0" max="20" step="0.5" [(ngModel)]="ligne.moyenne_visee" [name]="'visee_' + $index" />
                            </td>
                            @if (estAdmin()) {
                              <td>
                                <input type="number" class="form-control form-control-sm" min="0" max="20" step="0.5" [(ngModel)]="ligne.moyenne_obtenue" [name]="'obtenue_' + $index" />
                              </td>
                            }
                            <td>
                              <input type="text" class="form-control form-control-sm" [(ngModel)]="ligne.commentaire" [name]="'commentaire_' + $index" />
                            </td>
                            <td class="text-end">
                              <button
                                type="button"
                                class="btn btn-outline-mpc btn-sm text-danger"
                                (click)="retirerLigne($index)"
                                [disabled]="form.matieres.length <= 1"
                                title="Retirer la ligne"
                              >
                                <i class="bi bi-x-lg"></i>
                              </button>
                            </td>
                          </tr>
                        }
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="row g-3 mt-1">
                  <div class="col-md-6">
                    <label class="form-label">Matériel disponible</label>
                    <textarea class="form-control" rows="2" [(ngModel)]="form.materiel_disponible" name="materiel_disponible"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Matériel manquant</label>
                    <textarea class="form-control" rows="2" [(ngModel)]="form.materiel_manquant" name="materiel_manquant"></textarea>
                  </div>
                </div>

                @if (estAdmin() && edition()) {
                  <div class="mt-3">
                    <label class="form-label">Commentaire de l'administration</label>
                    <textarea class="form-control" rows="2" [(ngModel)]="form.commentaire_admin" name="commentaire_admin"></textarea>
                  </div>
                }

                @if (!contexteOk()) {
                  <div class="alert alert-light border small mt-3 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Listes incomplètes — les périodes ou les matières n'ont pas pu être chargées.</span>
                    <button type="button" class="btn btn-outline-mpc btn-sm ms-auto" (click)="rechargerContexte()">Réessayer</button>
                  </div>
                }
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light" (click)="fermerSaisie()">Annuler</button>
                <button type="submit" class="btn btn-mpc" [disabled]="soumission()">
                  @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                  {{ edition()?.id ? 'Enregistrer' : 'Créer l\'objectif' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    }
  `,
})
export class ObjectifsComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly auth = inject(AuthService);

  protected readonly estAdmin = computed(() => this.auth.roleActif() === 'admin_cabinet');

  protected objectifs = signal<ObjectifPedagogique[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected page = signal(1);
  protected filtres = signal<{ search: string; periode_id: number | null }>({ search: '', periode_id: null });
  protected recherche = '';
  protected chargement = signal(false);
  protected soumission = signal(false);
  protected alerte = signal('');
  protected succes = signal('');

  protected periodes = signal<PeriodeObjectif[]>([]);
  protected eleves = signal<RefEleveObjectif[]>([]);
  protected matieres = signal<MatiereRefObjectif[]>([]);
  /** True quand le chargement des listes a échec (≠ liste vide). */
  protected contexteEnEchec = signal(false);

  protected saisieOuverte = signal(false);
  /** Objectif en édition — null = création enseignant. */
  protected edition = signal<ObjectifPedagogique | null>(null);

  protected periodesOuvertes = computed<PeriodeObjectif[]>(() =>
    this.periodes().filter((p) => p.est_ouverte)
  );

  /** Sélecteur de période : toutes les périodes en édition (la courante peut
   *  être close), seules les ouvertes à la création (D-051). */
  protected periodesChoix = computed<PeriodeObjectif[]>(() =>
    this.edition() ? this.periodes() : this.periodesOuvertes()
  );

  protected contexteOk = computed(() => !this.contexteEnEchec());

  /** Déclaré après les computed : `formVide()` pré-remplit la première
   *  période ouverte. */
  protected form: ObjectifFormulaire = this.formVide();

  async ngOnInit(): Promise<void> {
    await Promise.all([this.charger(), this.chargerContextes()]);
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');
    try {
      const f = this.filtres();
      const r = await firstValueFrom(
        this.estAdmin()
          ? this.api.getObjectifsAdmin(this.page(), 15, f)
          : this.api.getObjectifs(this.page(), 15, f)
      );
      this.objectifs.set(r.data);
      this.meta.set(r.meta ?? null);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.chargement.set(false);
    }
  }

  /** Listes du formulaire : périodes, matières, élèves (enseignant seul). */
  private async chargerContextes(): Promise<void> {
    const [periodesRes, matieresRes, elevesRes] = await Promise.allSettled([
      this.estAdmin()
        ? firstValueFrom(this.api.getPeriodes(1, 200)).then((r) => r.data)
        : firstValueFrom(this.api.periodesObjectifs()),
      firstValueFrom(this.estAdmin() ? this.api.matieresObjectifsAdmin() : this.api.matieresObjectifs()),
      this.estAdmin()
        ? Promise.resolve([] as RefEleveObjectif[])
        : firstValueFrom(this.api.elevesObjectifs()),
    ]);

    if (periodesRes.status === 'fulfilled') this.periodes.set(periodesRes.value);
    if (matieresRes.status === 'fulfilled') this.matieres.set(matieresRes.value);
    if (elevesRes.status === 'fulfilled') this.eleves.set(elevesRes.value);

    const echecs: string[] = [];
    if (periodesRes.status === 'rejected') echecs.push(`périodes : ${messageErreurApi(periodesRes.reason)}`);
    if (matieresRes.status === 'rejected') echecs.push(`matières : ${messageErreurApi(matieresRes.reason)}`);
    if (elevesRes.status === 'rejected') echecs.push(`élèves : ${messageErreurApi(elevesRes.reason)}`);

    this.contexteEnEchec.set(echecs.length > 0);
    if (echecs.length > 0) {
      this.alerte.set(`Chargement incomplet — ${echecs.join(' ; ')}`);
    } else if (this.alerte().startsWith('Chargement incomplet')) {
      this.alerte.set('');
    }
  }

  protected rechargerContexte(): void {
    void this.chargerContextes();
  }

  protected rechercher(): void {
    this.filtres.update((f) => ({ ...f, search: this.recherche }));
    this.changerPage(1);
  }

  protected changerPage(p: number): void {
    this.page.set(p);
    void this.charger();
  }

  protected ouvrirSaisie(o: ObjectifPedagogique | null): void {
    this.alerte.set('');
    this.succes.set('');
    this.edition.set(o);
    this.saisieOuverte.set(true);

    this.form = o
      ? {
          eleve_id: o.eleve?.id ?? null,
          periode_id: o.periode?.id ?? null,
          moyenne_visee: o.moyenne_visee,
          moyenne_obtenue: o.moyenne_obtenue,
          materiel_disponible: o.materiel_disponible,
          materiel_manquant: o.materiel_manquant,
          commentaire_admin: o.commentaire_admin,
          matieres: o.matieres.map((l) => ({
            matiere_id: l.matiere?.id ?? null,
            moyenne_visee: l.moyenne_visee,
            moyenne_obtenue: l.moyenne_obtenue,
            commentaire: l.commentaire,
          })),
        }
      : this.formVide();

    // Listes vides au premier chargement (session fraîche, erreur
    // transitoire…) : on retente à l'ouverture plutôt que de présenter des
    // sélecteurs muets.
    if (this.periodes().length === 0 || this.matieres().length === 0) {
      void this.chargerContextes();
    }
  }

  protected fermerSaisie(): void {
    this.saisieOuverte.set(false);
    this.edition.set(null);
  }

  protected ajouterLigne(): void {
    this.form.matieres.push(this.ligneVide());
  }

  protected retirerLigne(index: number): void {
    if (this.form.matieres.length <= 1) return;
    this.form.matieres.splice(index, 1);
  }

  /** Matières encore disponibles pour la ligne : pas deux fois la même. */
  protected matieresPourLigne(index: number): MatiereRefObjectif[] {
    const prises = new Set(
      this.form.matieres.filter((l, i) => i !== index && l.matiere_id != null).map((l) => l.matiere_id)
    );
    return this.matieres().filter((m) => !prises.has(m.id));
  }

  protected async enregistrer(): Promise<void> {
    this.alerte.set('');
    this.succes.set('');

    const edition = this.edition();
    if (!edition && !this.form.eleve_id) {
      this.alerte.set("Choisissez l'élève concerné.");
      return;
    }
    if (!this.form.periode_id) {
      this.alerte.set('Choisissez la période comptable.');
      return;
    }
    if (this.form.matieres.length === 0 || this.form.matieres.some((l) => l.matiere_id == null)) {
      this.alerte.set('Chaque ligne doit préciser une matière (minimum une).');
      return;
    }

    this.soumission.set(true);
    try {
      if (!edition) {
        await firstValueFrom(this.api.creerObjectif(this.form));
      } else if (this.estAdmin()) {
        await firstValueFrom(this.api.modifierObjectifAdmin(edition.id, this.form));
      } else {
        await firstValueFrom(this.api.modifierObjectif(edition.id, this.form));
      }
      this.fermerSaisie();
      this.succes.set(edition ? 'Objectif mis à jour.' : 'Objectif créé.');
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async supprimer(o: ObjectifPedagogique): Promise<void> {
    if (!confirm(`Supprimer l'objectif de ${this.libelleEleve(o.eleve)} (${o.periode?.label ?? 'période inconnue'}) ?`)) {
      return;
    }
    this.alerte.set('');
    this.succes.set('');
    try {
      if (this.estAdmin()) {
        await firstValueFrom(this.api.supprimerObjectifAdmin(o.id));
      } else {
        await firstValueFrom(this.api.supprimerObjectif(o.id));
      }
      this.succes.set('Objectif supprimé.');
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    }
  }

  /** Clic sur le fond de la modale : ferme (le panneau ne se propage pas). */
  protected siFond(ev: MouseEvent): void {
    if (ev.target !== ev.currentTarget) return;
    this.fermerSaisie();
  }

  private formVide(): ObjectifFormulaire {
    return {
      eleve_id: null,
      periode_id: this.periodesOuvertes()[0]?.id ?? null,
      moyenne_visee: null,
      moyenne_obtenue: null,
      materiel_disponible: null,
      materiel_manquant: null,
      commentaire_admin: null,
      matieres: [this.ligneVide()],
    };
  }

  private ligneVide(): LigneObjectifMatiere {
    return { matiere_id: null, moyenne_visee: null, moyenne_obtenue: null, commentaire: null };
  }

  protected libelleEleve(e: RefEleveObjectif | null | undefined): string {
    if (!e) return '—';
    const nom = `${e.nom ?? ''} ${e.prenom ?? ''}`.trim();
    return nom || '—';
  }

  protected libelleEnseignant(o: ObjectifPedagogique): string {
    const e = o.enseignant;
    if (!e) return '—';
    const nom = `${e.nom ?? ''} ${e.prenom ?? ''}`.trim();
    return nom || '—';
  }

  protected resumeMatieres(o: ObjectifPedagogique): string {
    const noms = (o.matieres ?? []).map((l) => l.matiere?.nom).filter(Boolean);
    return noms.length ? noms.join(', ') : '—';
  }

  protected moyenne(v: number | null | undefined): string {
    return v == null ? '—' : Number(v).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
  }
}
