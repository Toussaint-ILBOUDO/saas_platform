import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { NgFor } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  ContratCours,
  Facture,
  FactureApercu,
  FactureFormulaire,
  FacturePaiement,
  LigneFacture,
  MetaPage,
  MODES_PAIEMENT,
  PeriodeComptable,
  StatutPaiement,
} from '../../models';
import { ouvrirDetailDepuisRoute } from '../detail-de-route';

/**
 * Écran « Factures » de l'administration (T7A.8).
 *
 * L'admin aperçoit la facture depuis les RAPPORTS VALIDÉS du contrat (D-051),
 * la génère, puis enregistre le règlement. La création est un acte d'argent :
 * un aperçu sans écriture s'affiche avant toute génération, et le règlement
 * est une modale distincte avec un motif clair.
 */
@Component({
  imports: [FormsModule, NgFor],
  selector: 'espace-factures-admin',
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
      .badge-statut { font-size: 0.74rem; font-weight: 700; padding: 0.28rem 0.55rem; border-radius: 99px; white-space: nowrap; }
      .badge-en_attente { background: var(--mpc-jaune-doux); color: #6b4f00; }
      .badge-payee { background: var(--mpc-vert-pale); color: #0a5d35; }
      .num-facture { font-family: ui-monospace, monospace; font-size: 0.8rem; }
      .montant { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 600; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .lignes-mini { font-size: 0.82rem; }
      .lignes-mini tr:last-child td { border-bottom: none; }
      .apercu-zone { background: #f8fafc; border: 1px dashed var(--mpc-bordure); border-radius: 1rem; padding: 1rem; }
    `,
  ],
  template: `
    <section class="entete">
      <div>
        <h1>Factures</h1>
        <p>
          Chaque facture reprend les heures des rapports <b>validés</b> du
          contrat. Aperçu avant génération, puis enregistrement du règlement —
          jamais de saisie libre des montants de cours.
        </p>
      </div>
      <button class="btn btn-mpc" (click)="ouvrirCreation()" [disabled]="chargement()">
        <i class="bi bi-plus-lg"></i> Générer une facture
      </button>
    </section>

    <section class="filtres">
      <select class="form-select form-select-sm w-auto" [ngModel]="filtres().periode_id" (ngModelChange)="filtres.update(f => ({ ...f, periode_id: $event })); charger()">
        <option [ngValue]="null">Toutes les périodes</option>
        <option *ngFor="let p of periodes()" [ngValue]="p.id">{{ p.label }}</option>
      </select>
      <select class="form-select form-select-sm w-auto" [ngModel]="filtres().statut" (ngModelChange)="filtres.update(f => ({ ...f, statut: $event })); charger()">
        <option value="">Tous les statuts</option>
        <option value="en_attente">En attente</option>
        <option value="payee">Payée</option>
      </select>
      <form class="d-flex gap-2" (ngSubmit)="rechercher()">
        <input class="form-control form-control-sm" style="min-width: 200px"
               placeholder="Élève…" [(ngModel)]="recherche" name="recherche" />
        <button class="btn btn-outline-mpc btn-sm" type="submit"><i class="bi bi-search"></i></button>
      </form>
    </section>

    @if (alerte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }

    @if (chargement() && !creation() && !detail()) {
      <div class="d-flex justify-content-center py-5"><div class="spinner-border text-mpc" role="status"></div></div>
    } @else if (factures().length === 0) {
      <div class="alert alert-light border text-center py-5">
        <i class="bi bi-receipt fs-1"></i>
        <div class="mt-2">Aucune facture pour ces critères.</div>
      </div>
    } @else {
      <div class="table-responsive bg-white rounded-4 border">
        <table class="table table-hover align-middle table-clair mb-0">
          <thead>
            <tr>
              <th>Facture</th><th>Élève</th><th>Période</th><th class="text-end">Heures</th>
              <th class="text-end">Montant</th><th>Statut</th><th></th>
            </tr>
          </thead>
          <tbody>
            @for (f of factures(); track f.id) {
              <tr>
                <td class="num-facture">{{ f.numero_facture }}</td>
                <td class="fw-semibold">{{ f.eleve?.nom ?? '—' }}</td>
                <td>{{ f.periode?.label }}</td>
                <td class="text-end">{{ chiffre(f.volume_horaire_total) }} h</td>
                <td class="text-end montant">{{ monnaie(f.montant_total) }}</td>
                <td>
                  <span class="badge-statut badge-{{ f.statut_paiement }}">{{ libelleStatut(f.statut_paiement) }}</span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-mpc" (click)="ouvrirDetail(f)" title="Consulter"><i class="bi bi-eye"></i></button>
                    <a class="btn btn-outline-mpc" [href]="api.urlPdfFactureAdmin(f.id)" target="_blank" title="PDF"><i class="bi bi-filetype-pdf"></i></a>
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

    <!-- Modale génération -->
    @if (creation()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'creation')">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Générer une facture</h5>
              <button type="button" class="btn-close" (click)="fermerCreation()"></button>
            </div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-7">
                  <label class="form-label fw-semibold">Contrat de cours</label>
                  <select class="form-select" [(ngModel)]="form.contrat_cours_id" name="contrat_cours_id">
                    <option [ngValue]="null">— Choisir un contrat —</option>
                    <option *ngFor="let c of contrats()" [ngValue]="c.id">{{ libelleContrat(c) }}</option>
                  </select>
                </div>
                <div class="col-md-5">
                  <label class="form-label fw-semibold">Période</label>
                  <select class="form-select" [(ngModel)]="form.periode_id" name="periode_id">
                    <option [ngValue]="null">— Choisir —</option>
                    <option *ngFor="let p of periodes()" [ngValue]="p.id">{{ p.label }}</option>
                  </select>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-4">
                  <label class="form-label">Frais de suivi</label>
                  <input type="number" min="0" class="form-control" [(ngModel)]="form.frais_suivi" name="frais_suivi" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Autres frais</label>
                  <input type="number" min="0" class="form-control" [(ngModel)]="form.autres_frais" name="autres_frais" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Remise</label>
                  <input type="number" min="0" class="form-control" [(ngModel)]="form.remise" name="remise" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Date limite de paiement</label>
                  <input type="date" class="form-control" [(ngModel)]="form.date_limite_paiement" name="date_limite_paiement" />
                </div>
                <div class="col-12">
                  <label class="form-label">Commentaire (optionnel)</label>
                  <textarea class="form-control" rows="2" [(ngModel)]="form.commentaire" name="commentaire"></textarea>
                </div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <button class="btn btn-outline-mpc" (click)="demanderApercu()" [disabled]="apercuChargement() || !form.contrat_cours_id || !form.periode_id">
                  @if (apercuChargement()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                  Aperçu de la facture
                </button>
              </div>

              @if (apercu() && apercu()!.lignes.length > 0) {
                <div class="apercu-zone mt-3">
                  <div class="fw-bold mb-2">
                    Aperçu — {{ apercu()!.eleve }} · {{ apercu()!.periode_label }}
                    <span class="text-doux-tiny fw-normal"> (parent : {{ apercu()!.parent }})</span>
                  </div>
                  <table class="table table-sm lignes-mini">
                    <thead><tr><th>Enseignant</th><th>matière</th><th class="text-end">Heures</th><th class="text-end">Taux/h</th><th class="text-end">Montant</th></tr></thead>
                    <tbody>
                      @for (l of apercu()!.lignes; track l.affectation_enseignant_id) {
                        <tr>
                          <td>{{ l.enseignant }}</td><td>{{ l.matiere }}</td>
                          <td class="text-end">{{ chiffre(l.nombre_heures) }} h</td>
                          <td class="text-end">{{ monnaie(l.taux_horaire) }}</td>
                          <td class="text-end">{{ monnaie(l.montant) }}</td>
                        </tr>
                      }
                    </tbody>
                  </table>
                  <div class="d-flex justify-content-end gap-3 mt-2">
                    <span>Cours : <b class="montant">{{ monnaie(apercu()!.montant_cours) }}</b></span>
                    <span>Frais : <b class="montant">{{ monnaie(fraisCibles()) }}</b></span>
                    @if (remiseCible() > 0) { <span>Remise : <b class="montant">−{{ monnaie(remiseCible()) }}</b></span> }
                    <span class="fs-5 fw-bold montant">Total {{ monnaie(totalCible()) }}</span>
                  </div>
                </div>
              } @else if (apercu() !== null) {
                <div class="alert alert-warning mt-3 mb-0">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  Aucune heure validée sur ce contrat pour cette période : tous les
                  rapports doivent être validés avant la facturation (D-051).
                </div>
              }
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" (click)="fermerCreation()">Annuler</button>
              <button type="button" class="btn btn-mpc" (click)="generer()" [disabled]="soumission() || !form.contrat_cours_id || !form.periode_id">
                @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                Générer la facture
              </button>
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Modale détail -->
    @if (detail()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'detail')">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                {{ detail()!.numero_facture }}
                <span class="badge-statut badge-{{ detail()!.statut_paiement }} ms-2">{{ libelleStatut(detail()!.statut_paiement) }}</span>
              </h5>
              <button type="button" class="btn-close" (click)="fermerDetail()"></button>
            </div>
            <div class="modal-body">
              <p class="text-doux-tiny">
                {{ detail()!.eleve?.nom }} · {{ detail()!.parent?.nom }} ·
                {{ detail()!.periode?.label }} · {{ detail()!.type_cours?.libelle }}
              </p>

              <table class="table table-sm lignes-mini">
                <thead><tr><th>Enseignant</th><th>Matière</th><th class="text-end">Heures</th><th class="text-end">Taux/h</th><th class="text-end">Montant</th></tr></thead>
                <tbody>
                  @for (l of detail()!.lignes; track l.id) {
                    <tr>
                      <td>{{ l.enseignant }}</td><td>{{ l.matiere }}</td>
                      <td class="text-end">{{ chiffre(l.nombre_heures) }} h</td>
                      <td class="text-end">{{ monnaie(l.taux_horaire) }}</td>
                      <td class="text-end">{{ monnaie(l.montant) }}</td>
                    </tr>
                  }
                </tbody>
              </table>

              <div class="d-flex flex-column align-items-end mt-3 gap-1">
                <div class="text-doux-tiny">Cours — <b class="montant">{{ monnaie(montantCoursDetail()) }}</b></div>
                @if (detail()!.frais_suivi > 0) { <div class="text-doux-tiny">Frais de suivi — <b class="montant">{{ monnaie(detail()!.frais_suivi) }}</b></div> }
                @if (detail()!.autres_frais > 0) { <div class="text-doux-tiny">Autres frais — <b class="montant">{{ monnaie(detail()!.autres_frais) }}</b></div> }
                @if (detail()!.remise > 0) { <div class="text-doux-tiny">Remise — <b class="montant">−{{ monnaie(detail()!.remise) }}</b></div> }
                <div class="fw-bold montant fs-5">Total {{ monnaie(detail()!.montant_total) }}</div>
              </div>

              @if (detail()!.commentaire) { <div class="mt-3 p-2 bg-light rounded-3 small">{{ detail()!.commentaire }}</div> }
              @if (detail()!.est_payee) {
                <div class="mt-3 p-2 rounded-3 small" style="background: var(--mpc-vert-pale)">
                  <i class="bi bi-check-circle-fill me-1"></i>
                  Payée le {{ detail()!.date_paiement }} — {{ libelleMode(detail()!.mode_paiement) }}
                  @if (detail()!.reference_paiement) { · réf. {{ detail()!.reference_paiement }} }
                </div>
              }
            </div>
            <div class="modal-footer">
              @if (detail()!.actions.includes('payer')) {
                <button type="button" class="btn btn-outline-mpc text-success" (click)="ouvrirPaiement(detail()!)"><i class="bi bi-check2-circle"></i> Enregistrer le paiement</button>
              }
              <a class="btn btn-mpc" [href]="api.urlPdfFactureAdmin(detail()!.id)" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Modale règlement -->
    @if (paiement()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'paiement')">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Règlement de {{ paiement()!.numero_facture }}</h5>
              <button type="button" class="btn-close" (click)="fermerPaiement()"></button>
            </div>
            <div class="modal-body">
              <div class="alert alert-light border small">
                Montant à régler : <b class="montant">{{ monnaie(paiement()!.montant_total) }}</b>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Date du paiement</label>
                <input type="date" class="form-control" [(ngModel)]="paiementForm.date_paiement" name="date_paiement" />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Mode de paiement</label>
                <select class="form-select" [(ngModel)]="paiementForm.mode_paiement" name="mode_paiement">
                  <option *ngFor="let m of MODES" [ngValue]="m.valeur">{{ m.libelle }}</option>
                </select>
              </div>
              <div>
                <label class="form-label">Référence (optionnel)</label>
                <input class="form-control" [(ngModel)]="paiementForm.reference_paiement" name="reference_paiement" maxlength="100" />
              </div>
              @if (erreurPaiement()) { <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ erreurPaiement() }}</div> }
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" (click)="fermerPaiement()">Annuler</button>
              <button type="button" class="btn btn-success" (click)="confirmerPaiement()" [disabled]="soumission()">
                @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                Marquer comme payée
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class FacturesAdminComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected factures = signal<Facture[]>([]);
  protected periodes = signal<PeriodeComptable[]>([]);
  protected contrats = signal<ContratCours[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected page = signal(1);
  protected chargement = signal(false);
  protected soumission = signal(false);
  protected apercuChargement = signal(false);
  protected alerte = signal('');
  protected recherche = '';

  protected creation = signal(false);
  protected detail = signal<Facture | null>(null);
  protected paiement = signal<Facture | null>(null);
  protected apercu = signal<FactureApercu | null>(null);
  protected erreurPaiement = signal('');

  protected filtres = signal<{ periode_id: number | null; statut: string; search: string }>({
    periode_id: null,
    statut: '',
    search: '',
  });

  protected MODES = MODES_PAIEMENT;

  protected form: FactureFormulaire = { contrat_cours_id: null, periode_id: null, frais_suivi: 0, autres_frais: 0, remise: 0, date_limite_paiement: null, commentaire: '' };
  protected paiementForm: FacturePaiement = { date_paiement: new Date().toISOString().slice(0, 10), mode_paiement: 'especes', reference_paiement: '' };

  protected montantCoursDetail = computed<number>(() =>
    (this.detail()?.lignes ?? []).reduce((s: number, l: LigneFacture) => s + l.montant, 0)
  );

  /**
   * Frais / remise / total de l'aperçu : méthodes et non `computed()`.
   *
   * `form` est un objet plain lié par `ngModel`, sans signal : un `computed`
   * ne se réévaluerait jamais et l'aperçu afficherait un total figé sur les
   * valeurs initiales. Le template relit ces méthodes à chaque cycle de détection.
   */
  protected fraisCibles(): number {
    return Number(this.form.frais_suivi ?? 0) + Number(this.form.autres_frais ?? 0);
  }

  protected remiseCible(): number {
    return Number(this.form.remise ?? 0);
  }

  /** Total recalculé à la volée, l'aperçu serveur servant de base au cours. */
  protected totalCible(): number {
    return (this.apercu()?.montant_cours ?? 0) + this.fraisCibles() - this.remiseCible();
  }

  async ngOnInit(): Promise<void> {
    await Promise.all([this.charger(), this.chargerContextes()]);

    // Cible d'une notification : ouvre directement la fiche `…/{id}`.
    ouvrirDetailDepuisRoute(
      this.route,
      (id) => this.api.getFacture(id),
      (facture) => this.ouvrirDetail(facture),
      (erreur) => this.alerte.set(messageErreurApi(erreur))
    );
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');
    try {
      const r = await firstValueFrom(
        this.api.getFacturesAdmin(this.page(), 20, {
          periode_id: this.filtres().periode_id,
          statut: this.filtres().statut,
          search: this.filtres().search,
        })
      );
      this.factures.set(r.data);
      this.meta.set(r.meta ?? null);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.chargement.set(false);
    }
  }

  private async chargerContextes(): Promise<void> {
    try {
      const [p, c] = await Promise.all([
        firstValueFrom(this.api.getPeriodes(1, 100)),
        firstValueFrom(this.api.getContrats(1, 200)),
      ]);
      this.periodes.set(p.data);
      this.contrats.set(c.data);
    } catch {
      // Les listes de contexte manquantes ne bloquent pas la consultation.
    }
  }

  protected rechercher(): void {
    this.page.set(1);
    this.filtres.update((f) => ({ ...f, search: this.recherche.trim() }));
    void this.charger();
  }

  protected changerPage(p: number): void {
    if (p < 1) return;
    this.page.set(p);
    void this.charger();
  }

  protected ouvrirCreation(): void {
    this.creation.set(true);
    this.apercu.set(null);
    this.form = { contrat_cours_id: null, periode_id: null, frais_suivi: 0, autres_frais: 0, remise: 0, date_limite_paiement: null, commentaire: '' };
  }

  protected fermerCreation(): void {
    this.creation.set(false);
    this.apercu.set(null);
  }

  protected async demanderApercu(): Promise<void> {
    if (!this.form.contrat_cours_id || !this.form.periode_id) return;
    this.apercuChargement.set(true);
    this.alerte.set('');
    try {
      this.apercu.set(await firstValueFrom(this.api.apercuFacture(this.form)));
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
      this.apercu.set(null);
    } finally {
      this.apercuChargement.set(false);
    }
  }

  protected async generer(): Promise<void> {
    if (!this.form.contrat_cours_id || !this.form.periode_id) return;
    this.soumission.set(true);
    this.alerte.set('');
    try {
      await firstValueFrom(this.api.creerFacture(this.form));
      this.fermerCreation();
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected ouvrirDetail(f: Facture): void {
    this.detail.set(f);
  }

  protected fermerDetail(): void {
    this.detail.set(null);
  }

  protected ouvrirPaiement(f: Facture): void {
    this.paiement.set(f);
    this.erreurPaiement.set('');
    this.paiementForm = { date_paiement: new Date().toISOString().slice(0, 10), mode_paiement: 'especes', reference_paiement: '' };
  }

  protected fermerPaiement(): void {
    this.paiement.set(null);
  }

  protected async confirmerPaiement(): Promise<void> {
    const cible = this.paiement();
    if (!cible) return;

    if (!this.paiementForm.date_paiement) {
      this.erreurPaiement.set('La date du paiement est obligatoire.');
      return;
    }

    this.soumission.set(true);
    this.erreurPaiement.set('');
    this.alerte.set('');
    try {
      await firstValueFrom(this.api.marquerFacturePayee(cible.id, this.paiementForm));
      this.fermerPaiement();
      this.fermerDetail();
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected siFond(ev: MouseEvent, quelle: 'creation' | 'detail' | 'paiement'): void {
    if (ev.target !== ev.currentTarget) return;
    if (quelle === 'creation') this.fermerCreation();
    else if (quelle === 'detail') this.fermerDetail();
    else this.fermerPaiement();
  }

  protected libelleContrat(c: ContratCours): string {
    const matieres = (c.affectations ?? []).map((a) => a.matiere?.nom).filter(Boolean).join(', ');
    return `${c.eleve?.nom ?? 'Élève'} — ${matieres || 'Cours'}`;
  }

  protected libelleStatut(s: StatutPaiement): string {
    return s === 'payee' ? 'Payée' : 'En attente';
  }

  protected libelleMode(m: string | null): string {
    if (!m) return '';
    const libelles: Record<string, string> = {
      especes: 'Espèces',
      orange_money: 'Orange Money',
      moov_money: 'Moov Money',
      virement: 'Virement',
      cheque: 'Chèque',
      autre: 'Autre',
    };
    return libelles[m] ?? m;
  }

  protected chiffre(v: number | null | undefined): string {
    return Number(v ?? 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
  }

  protected monnaie(v: number | null | undefined): string {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(v ?? 0)) + ' FCFA';
  }
}