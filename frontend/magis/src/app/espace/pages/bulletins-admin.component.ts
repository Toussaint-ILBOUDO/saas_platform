import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { NgFor } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  BulletinApercu,
  BulletinGenererFormulaire,
  BulletinPaie,
  BulletinPaieAjustement,
  BulletinVersement,
  MetaPage,
  MODES_PAIEMENT,
  PeriodeComptable,
  StatutBulletin,
  TypeAjustement,
} from '../../models';
import { ouvrirDetailDepuisRoute } from '../detail-de-route';

/**
 * Écran « Bulletins de paie » de l'administration (T7A.9).
 *
 * La paie de la période est issue des RAPPORTS VALIDÉS (D-051). L'admin aperçoit
 * (aucune écriture), ajuste frais/ajustements, génère, puis corrige/paie/ajuste
 * au fil de la machine à états. Le mot d'ordre est le même que pour les
 * factures : jamais de saisie libre des montants de cours.
 */
@Component({
  imports: [FormsModule, NgFor],
  selector: 'espace-bulletins-admin',
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
      .badge-genere { background: #e3e8f2; color: #33405c; }
      .badge-consulte { background: var(--mpc-jaune-doux); color: #6b4f00; }
      .badge-valide { background: var(--mpc-vert-pale); color: #0a5d35; }
      .badge-conteste { background: var(--mpc-rouge-clair); color: #7a1d1d; }
      .badge-corrige { background: #e7e2f5; color: #4a3b8c; }
      .badge-verse { background: var(--mpc-bleu-doux); color: #0b3a63; }
      .num-bulletin { font-family: ui-monospace, monospace; font-size: 0.8rem; }
      .montant { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 600; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .succes { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-vert-pale); color: #0a5d35; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .lignes-mini { font-size: 0.82rem; }
      .lignes-mini tr:last-child td { border-bottom: none; }
      .apercu-zone { background: #f8fafc; border: 1px dashed var(--mpc-bordure); border-radius: 1rem; padding: 1rem; overflow-x: auto; }
      .input-ajust { width: 110px; }
      .contestation { background: var(--mpc-rouge-clair); color: #5c1818; }
    `,
  ],
  template: `
    <section class="entete">
      <div>
        <h1>Bulletins de paie</h1>
        <p>
          Chaque bulletin reprend les heures des rapports <b>validés</b> de la
          période. Aperçu sans écriture avant génération, puis correction en cas
          de contestation, paiement et ajustements.
        </p>
      </div>
      <button class="btn btn-mpc" (click)="ouvrirGeneration()" [disabled]="chargement()">
        <i class="bi bi-plus-lg"></i> Générer les bulletins
      </button>
    </section>

    <section class="filtres">
      <select class="form-select form-select-sm w-auto" [ngModel]="filtres().periode_id" (ngModelChange)="filtres.update(f => ({ ...f, periode_id: $event })); charger()">
        <option [ngValue]="null">Toutes les périodes</option>
        <option *ngFor="let p of periodes()" [ngValue]="p.id">{{ p.label }}</option>
      </select>
      <select class="form-select form-select-sm w-auto" [ngModel]="filtres().statut" (ngModelChange)="filtres.update(f => ({ ...f, statut: $event })); charger()">
        <option value="">Tous les statuts</option>
        <option value="genere">Généré</option>
        <option value="consulte">Consulté</option>
        <option value="valide">Validé</option>
        <option value="conteste">Contesté</option>
        <option value="corrige">Corrigé</option>
        <option value="verse">Versé</option>
      </select>
      <form class="d-flex gap-2" (ngSubmit)="rechercher()">
        <input class="form-control form-control-sm" style="min-width: 200px"
               placeholder="Enseignant…" [(ngModel)]="recherche" name="recherche" />
        <button class="btn btn-outline-mpc btn-sm" type="submit"><i class="bi bi-search"></i></button>
      </form>
    </section>

    @if (succes()) { <div class="succes"><i class="bi bi-check-circle-fill"></i>{{ succes() }}</div> }
    @if (alerte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }

    @if (chargement() && !generation() && !detail()) {
      <div class="d-flex justify-content-center py-5"><div class="spinner-border text-mpc" role="status"></div></div>
    } @else if (bulletins().length === 0) {
      <div class="alert alert-light border text-center py-5">
        <i class="bi bi-cash-stack fs-1"></i>
        <div class="mt-2">Aucun bulletin pour ces critères.</div>
      </div>
    } @else {
      <div class="table-responsive bg-white rounded-4 border">
        <table class="table table-hover align-middle table-clair mb-0">
          <thead>
            <tr>
              <th>Bulletin</th><th>Enseignant</th><th>Période</th>
              <th class="text-end">Heures</th><th class="text-end">Net</th><th>Statut</th><th></th>
            </tr>
          </thead>
          <tbody>
            @for (b of bulletins(); track b.id) {
              <tr>
                <td class="num-bulletin">{{ b.numero }}</td>
                <td class="fw-semibold">{{ b.enseignant?.nom ?? '—' }}</td>
                <td>{{ b.periode?.label }}</td>
                <td class="text-end">{{ chiffre(b.total_heures) }} h</td>
                <td class="text-end montant">{{ monnaie(b.montant_net_final ?? b.montant_net) }}</td>
                <td><span class="badge-statut badge-{{ b.statut }}">{{ libelleStatut(b.statut) }}</span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-mpc" (click)="ouvrirDetail(b)" title="Consulter"><i class="bi bi-eye"></i></button>
                    @if (b.actions.includes('pdf')) {
                      <a class="btn btn-outline-mpc" [href]="api.urlPdfBulletinAdmin(b.id)" target="_blank" title="PDF"><i class="bi bi-filetype-pdf"></i></a>
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

    <!-- Modale génération -->
    @if (generation()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'generation')">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Générer les bulletins de paie</h5>
              <button type="button" class="btn-close" (click)="fermerGeneration()"></button>
            </div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                  <label class="form-label fw-semibold">Période</label>
                  <select class="form-select" [(ngModel)]="form.periode_id" name="periode_id">
                    <option [ngValue]="null">— Choisir —</option>
                    <option *ngFor="let p of periodes()" [ngValue]="p.id">{{ p.label }}</option>
                  </select>
                </div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <button class="btn btn-outline-mpc" (click)="demanderApercu()" [disabled]="apercuChargement() || !form.periode_id">
                  @if (apercuChargement()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                  Aperçu de la paie
                </button>
                @if (apercu() && apercu()!.data.length > 0) {
                  <span class="align-self-center text-doux-tiny">
                    {{ apercu()!.total_enseignants }} enseignant(s) · {{ chiffre(apercu()!.total_heures) }} h ·
                    {{ monnaie(apercu()!.total_montant) }} de net
                  </span>
                }
              </div>

              @if (apercu() && apercu()!.data.length > 0) {
                <div class="apercu-zone mt-3">
                  <table class="table table-sm lignes-mini mb-0 align-middle">
                    <thead>
                      <tr>
                        <th>Enseignant</th><th class="text-end">Heures</th><th class="text-end">Brut</th>
                        <th>Frais de suivi</th>
                        @for (t of apercu()!.types_ajustement; track t.id) {
                          <th class="text-end" [title]="t.direction === 'credit' ? 'Prime' : 'Retenue'">
                            {{ t.libelle }}
                          </th>
                        }
                        <th class="text-end">Net</th>
                      </tr>
                    </thead>
                    <tbody>
                      @for (item of apercu()!.data; track item.enseignant.id) {
                        <tr>
                          <td class="fw-semibold">{{ item.enseignant.nom ?? '—' }}</td>
                          <td class="text-end">{{ chiffre(item.total_heures) }}</td>
                          <td class="text-end">{{ monnaie(item.montant_brut) }}</td>
                          <td>
                            <input type="number" min="0" step="100" class="form-control form-control-sm input-ajust"
                                   [ngModel]="item.frais_suivi" (ngModelChange)="item.frais_suivi = +$event" name="frais_{{ item.enseignant.id }}" />
                          </td>
                          @for (t of apercu()!.types_ajustement; track t.id) {
                            <td>
                              <input type="number" min="0" step="100" class="form-control form-control-sm input-ajust text-end"
                                     [ngModel]="montantAjust(item, t.id)"
                                     (ngModelChange)="definirAjust(item, t.id, +$event)"
                                     name="aj_{{ item.enseignant.id }}_{{ t.id }}" />
                            </td>
                          }
                          <td class="text-end montant">{{ monnaie(netItem(item)) }}</td>
                        </tr>
                      }
                    </tbody>
                  </table>
                </div>
              } @else if (apercu() !== null) {
                <div class="alert alert-warning mt-3 mb-0">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  Aucun rapport validé sur cette période : la paie ne peut pas être
                  établie tant que les rapports mensuels ne sont pas validés (D-051).
                </div>
              }
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" (click)="fermerGeneration()">Annuler</button>
              <button type="button" class="btn btn-mpc" (click)="generer()" [disabled]="soumission() || !form.periode_id || !apercu() || apercu()!.data.length === 0">
                @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                Générer les bulletins
              </button>
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Modale détail -->
    @if (detail()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'detail')">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                {{ detail()!.numero }}
                <span class="badge-statut badge-{{ detail()!.statut }} ms-2">{{ libelleStatut(detail()!.statut) }}</span>
              </h5>
              <button type="button" class="btn-close" (click)="fermerDetail()"></button>
            </div>
            <div class="modal-body">
              <p class="text-doux-tiny">
                {{ detail()!.enseignant?.nom ?? '—' }} ·
                {{ detail()!.periode?.label }}
              </p>

              <table class="table table-sm lignes-mini">
                <thead><tr><th>Élève</th><th>Matière</th><th class="text-end">Heures</th><th class="text-end">Taux/h</th><th class="text-end">Montant</th></tr></thead>
                <tbody>
                  @for (l of detail()!.lignes; track l.id) {
                    <tr>
                      <td>{{ l.eleve }}</td><td>{{ l.matiere }}</td>
                      <td class="text-end">{{ chiffre(l.nombre_heures) }} h</td>
                      <td class="text-end">{{ monnaie(l.taux_horaire) }}</td>
                      <td class="text-end">{{ monnaie(l.montant) }}</td>
                    </tr>
                  }
                </tbody>
              </table>

              @if (detail()!.lignes.length > 0 && detail()!.ajustements.length > 0) {
                <table class="table table-sm lignes-mini mt-2">
                  <thead><tr><th>Ajustement</th><th class="text-end">Montant</th><th></th></tr></thead>
                  <tbody>
                    @for (a of detail()!.ajustements; track a.id) {
                      <tr>
                        <td>{{ a.libelle }}</td>
                        <td class="text-end">{{ a.type === 'retenue' ? '−' : '+' }}{{ monnaie(a.montant) }}</td>
                        <td class="text-end">
                          @if (detail()!.actions.includes('ajuster')) {
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" (click)="retirerAjustement(a)" title="Retirer"><i class="bi bi-x"></i></button>
                          }
                        </td>
                      </tr>
                    }
                  </tbody>
                </table>
              }

              @if (detail()!.actions.includes('ajuster')) {
                <div class="row g-2 mt-2">
                  <div class="col-md-4">
                    <label class="form-label">Type d'ajustement</label>
                    <select class="form-select form-select-sm" [(ngModel)]="nouvelAjustement.type_ajustement_id" name="type_ajustement_id">
                      <option [ngValue]="null">— Choisir —</option>
                      <option *ngFor="let t of types()" [ngValue]="t.id">{{ t.libelle }}</option>
                    </select>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Libellé</label>
                    <input class="form-control form-control-sm" [(ngModel)]="nouvelAjustement.libelle" name="libelle_ajustement" maxlength="255" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Montant</label>
                    <input type="number" min="1" step="100" class="form-control form-control-sm" [(ngModel)]="nouvelAjustement.montant" name="montant_ajustement" />
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-sm btn-mpc" (click)="ajouterAjustement()" [disabled]="soumission() || !nouvelAjustement.type_ajustement_id || !nouvelAjustement.libelle || !nouvelAjustement.montant">
                      @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                      <i class="bi bi-plus-lg"></i> Ajouter l'ajustement
                    </button>
                  </div>
                </div>
              }

              <div class="d-flex flex-column align-items-end mt-3 gap-1">
                <div class="text-doux-tiny">Brut — <b class="montant">{{ monnaie(detail()!.montant_brut) }}</b></div>
                @if (detail()!.frais_suivi > 0) { <div class="text-doux-tiny">Frais de suivi — <b class="montant">−{{ monnaie(detail()!.frais_suivi) }}</b></div> }
                @if (detail()!.total_primes) { <div class="text-doux-tiny">Primes — <b class="montant">+{{ monnaie(detail()!.total_primes) }}</b></div> }
                @if (detail()!.total_retenues) { <div class="text-doux-tiny">Retenues — <b class="montant">−{{ monnaie(detail()!.total_retenues) }}</b></div> }
                <div class="fw-bold montant fs-5">Net {{ monnaie(detail()!.montant_net_final ?? detail()!.montant_net) }}</div>
              </div>

              @if (detail()!.commentaire_enseignant) {
                <div class="mt-3 p-2 bg-light rounded-3 small">
                  <b>Commentaire de l'enseignant :</b><br />{{ detail()!.commentaire_enseignant }}
                </div>
              }
              @if (detail()!.motif_contestation) {
                <div class="mt-3 p-2 rounded-3 small contestation">
                  <i class="bi bi-exclamation-octagon-fill me-1"></i>
                  Contesté — {{ detail()!.libelle_motif_contestation }}
                </div>
              }
              @if (detail()!.est_verse) {
                <div class="mt-3 p-2 rounded-3 small" style="background: var(--mpc-vert-pale)">
                  <i class="bi bi-check-circle-fill me-1"></i>
                  Versé le {{ detail()!.date_paiement }} — {{ libelleMode(detail()!.mode_paiement) }}
                  @if (detail()!.reference_paiement) { · réf. {{ detail()!.reference_paiement }} }
                </div>
              }
              @if (detail()!.est_recu) {
                <div class="mt-2 p-2 rounded-3 small" style="background: var(--mpc-bleu-doux)">
                  <i class="bi bi-check2-circle me-1"></i>
                  Réception confirmée le {{ detail()!.date_reception }}
                  @if (detail()!.recu_par) { par {{ detail()!.recu_par }} }
                </div>
              }
            </div>
            <div class="modal-footer d-flex justify-content-between flex-wrap gap-2">
              <a class="btn btn-outline-mpc" [href]="api.urlPdfBulletinAdmin(detail()!.id)" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
              <div class="d-flex gap-2 flex-wrap">
                @if (detail()!.actions.includes('corriger')) {
                  <button type="button" class="btn btn-outline-warning" (click)="ouvrirCorrection()"><i class="bi bi-tools"></i> Corriger</button>
                }
                @if (detail()!.actions.includes('payer')) {
                  <button type="button" class="btn btn-success" (click)="ouvrirVersement(detail()!)"><i class="bi bi-cash-coin"></i> Enregistrer le versement</button>
                }
              </div>
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Modale versement -->
    @if (versement()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'versement')">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Versement de {{ versement()!.numero }}</h5>
              <button type="button" class="btn-close" (click)="fermerVersement()"></button>
            </div>
            <div class="modal-body">
              <div class="alert alert-light border small">
                Montant à verser : <b class="montant">{{ monnaie(versement()!.montant_net_final ?? versement()!.montant_net) }}</b>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Date du paiement</label>
                <input type="date" class="form-control" [(ngModel)]="versementForm.date_paiement" name="date_paiement" />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Mode de paiement</label>
                <select class="form-select" [(ngModel)]="versementForm.mode_paiement" name="mode_paiement">
                  <option *ngFor="let m of MODES" [ngValue]="m.valeur">{{ m.libelle }}</option>
                </select>
              </div>
              <div>
                <label class="form-label">Référence (optionnel)</label>
                <input class="form-control" [(ngModel)]="versementForm.reference_paiement" name="reference_paiement" maxlength="255" />
              </div>
              @if (erreurVersement()) { <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ erreurVersement() }}</div> }
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" (click)="fermerVersement()">Annuler</button>
              <button type="button" class="btn btn-success" (click)="confirmerVersement()" [disabled]="soumission()">
                @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                Enregistrer le versement
              </button>
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Modale correction -->
    @if (correction()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'correction')">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Corriger un bulletin contesté</h5>
              <button type="button" class="btn-close" (click)="fermerCorrection()"></button>
            </div>
            <div class="modal-body">
              <p class="text-doux-tiny">
                Le bulletin repasse à l'enseignant qui pourra le consulter.
                Laissez une trace du correctif pour l'enseignant.
              </p>
              <div>
                <label class="form-label">Commentaire de correction (optionnel)</label>
                <textarea class="form-control" rows="3" [(ngModel)]="commentaireCorrection" name="commentaire_correction" maxlength="1000"></textarea>
              </div>
              @if (erreurVersement()) { <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ erreurVersement() }}</div> }
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" (click)="fermerCorrection()">Annuler</button>
              <button type="button" class="btn btn-outline-warning" (click)="confirmerCorrection()" [disabled]="soumission()">
                @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                Corriger le bulletin
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class BulletinsAdminComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected bulletins = signal<BulletinPaie[]>([]);
  protected periodes = signal<PeriodeComptable[]>([]);
  protected types = signal<TypeAjustement[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected page = signal(1);
  protected chargement = signal(false);
  protected soumission = signal(false);
  protected apercuChargement = signal(false);
  protected alerte = signal('');
  protected succes = signal('');
  protected recherche = '';

  protected generation = signal(false);
  protected detail = signal<BulletinPaie | null>(null);
  protected versement = signal<BulletinPaie | null>(null);
  protected correction = signal(false);
  protected apercu = signal<BulletinApercu | null>(null);
  protected erreurVersement = signal('');
  protected commentaireCorrection = '';

  protected filtres = signal<{ periode_id: number | null; statut: string; search: string }>({
    periode_id: null,
    statut: '',
    search: '',
  });

  protected MODES = MODES_PAIEMENT;

  protected form: BulletinGenererFormulaire = { periode_id: null };
  protected versementForm: BulletinVersement = { date_paiement: new Date().toISOString().slice(0, 10), mode_paiement: 'especes', reference_paiement: '' };
  protected nouvelAjustement = { type_ajustement_id: null as number | null, libelle: '', montant: 0 };

  protected montantNetDetail = computed<number>(() =>
    this.detail()?.montant_net_final ?? this.detail()?.montant_net ?? 0
  );

  async ngOnInit(): Promise<void> {
    await Promise.all([this.charger(), this.chargerContextes()]);

    // Cible d'une notification : ouvre directement la fiche `…/{id}`.
    ouvrirDetailDepuisRoute(
      this.route,
      (id) => this.api.getBulletin(id),
      (bulletin) => this.ouvrirDetail(bulletin),
      (erreur) => this.alerte.set(messageErreurApi(erreur))
    );
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');
    try {
      const r = await firstValueFrom(
        this.api.getBulletinsAdmin(this.page(), 20, {
          periode_id: this.filtres().periode_id,
          statut: this.filtres().statut,
          search: this.filtres().search,
        })
      );
      this.bulletins.set(r.data);
      this.meta.set(r.meta ?? null);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.chargement.set(false);
    }
  }

  private async chargerContextes(): Promise<void> {
    try {
      const [p, t] = await Promise.all([
        firstValueFrom(this.api.getPeriodes(1, 100)),
        firstValueFrom(this.api.typesAjustement()),
      ]);
      this.periodes.set(p.data);
      this.types.set(t);
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

  /* ---------- Génération ---------- */

  protected ouvrirGeneration(): void {
    this.generation.set(true);
    this.apercu.set(null);
    this.form = { periode_id: null };
    this.succes.set('');
  }

  protected fermerGeneration(): void {
    this.generation.set(false);
    this.apercu.set(null);
  }

  protected async demanderApercu(): Promise<void> {
    if (!this.form.periode_id) return;
    this.apercuChargement.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      this.apercu.set(await firstValueFrom(this.api.apercuBulletins({ periode_id: this.form.periode_id })));
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
      this.apercu.set(null);
    } finally {
      this.apercuChargement.set(false);
    }
  }

  protected montantAjust(item: BulletinApercu['data'][number], typeId: number): number {
    return (item.ajustements.find((a) => a.type_ajustement_id === typeId)?.montant ?? 0);
  }

  protected definirAjust(
    item: BulletinApercu['data'][number],
    typeId: number,
    montant: number
  ): void {
    const fond = item.ajustements.find((a) => a.type_ajustement_id === typeId);
    if (fond) {
      fond.montant = Number.isNaN(montant) || montant < 0 ? 0 : montant;
    }
    this.apercu.update((a) => (a ? { ...a } : a));
  }

  /** Net recalculé à la volée depuis la matrice, l'aperçu servant de base. */
  protected netItem(item: BulletinApercu['data'][number]): number {
    const credits = item.ajustements
      .filter((a) => a.direction === 'credit')
      .reduce((s, a) => s + Number(a.montant || 0), 0);
    const debits = item.ajustements
      .filter((a) => a.direction === 'debit')
      .reduce((s, a) => s + Number(a.montant || 0), 0);
    return item.montant_brut - Number(item.frais_suivi || 0) + credits - debits;
  }

  protected async generer(): Promise<void> {
    if (!this.form.periode_id || !this.apercu()) return;
    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');

    const frais_suivi: Record<number, number> = {};
    const ajustements: Record<number, Record<number, number>> = {};

    for (const item of this.apercu()!.data) {
      const id = item.enseignant.id;
      if (Number(item.frais_suivi) > 0) {
        frais_suivi[id] = Math.round(Number(item.frais_suivi));
      }
      const parType: Record<number, number> = {};
      for (const a of item.ajustements) {
        if (Number(a.montant) > 0) {
          parType[a.type_ajustement_id] = Math.round(Number(a.montant));
        }
      }
      if (Object.keys(parType).length > 0) {
        ajustements[id] = parType;
      }
    }

    try {
      const crees = await firstValueFrom(
        this.api.genererBulletins({
          periode_id: this.form.periode_id,
          frais_suivi,
          ajustements,
        })
      );
      this.fermerGeneration();
      this.succes.set(`${crees.length} bulletin(s) de paie généré(s).`);
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  /* ---------- Détail / versement / correction / ajustements ---------- */

  protected ouvrirDetail(b: BulletinPaie): void {
    this.detail.set(b);
    this.nouvelAjustement = { type_ajustement_id: null, libelle: '', montant: 0 };
  }

  protected fermerDetail(): void {
    this.detail.set(null);
  }

  protected ouvrirVersement(b: BulletinPaie): void {
    this.versement.set(b);
    this.erreurVersement.set('');
    this.versementForm = { date_paiement: new Date().toISOString().slice(0, 10), mode_paiement: 'especes', reference_paiement: '' };
  }

  protected fermerVersement(): void {
    this.versement.set(null);
  }

  protected async confirmerVersement(): Promise<void> {
    const cible = this.versement();
    if (!cible) return;

    if (!this.versementForm.date_paiement) {
      this.erreurVersement.set('La date du paiement est obligatoire.');
      return;
    }

    this.soumission.set(true);
    this.erreurVersement.set('');
    this.alerte.set('');
    try {
      const maj = await firstValueFrom(this.api.payerBulletin(cible.id, this.versementForm));
      this.fermerVersement();
      this.detail.set(maj);
      this.remplacerDansListe(maj);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected ouvrirCorrection(): void {
    this.commentaireCorrection = '';
    this.erreurVersement.set('');
    this.correction.set(true);
  }

  protected fermerCorrection(): void {
    this.correction.set(false);
  }

  protected async confirmerCorrection(): Promise<void> {
    const cible = this.detail();
    if (!cible) return;

    this.soumission.set(true);
    this.erreurVersement.set('');
    this.alerte.set('');
    try {
      const maj = await firstValueFrom(
        this.api.corrigerBulletin(cible.id, {
          commentaire_admin: this.commentaireCorrection.trim() || null,
        })
      );
      this.fermerCorrection();
      this.detail.set(maj);
      this.remplacerDansListe(maj);
    } catch (e) {
      this.erreurVersement.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async ajouterAjustement(): Promise<void> {
    const cible = this.detail();
    if (!cible || !this.nouvelAjustement.type_ajustement_id) return;

    this.soumission.set(true);
    this.alerte.set('');
    try {
      await firstValueFrom(
        this.api.ajouterAjustement(cible.id, {
          type_ajustement_id: this.nouvelAjustement.type_ajustement_id,
          libelle: this.nouvelAjustement.libelle.trim(),
          montant: Math.round(Number(this.nouvelAjustement.montant)),
        })
      );
      const maj = await firstValueFrom(this.api.getBulletin(cible.id));
      this.detail.set(maj);
      this.remplacerDansListe(maj);
      this.nouvelAjustement = { type_ajustement_id: null, libelle: '', montant: 0 };
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async retirerAjustement(a: BulletinPaieAjustement): Promise<void> {
    const cible = this.detail();
    if (!cible) return;

    this.soumission.set(true);
    this.alerte.set('');
    try {
      await firstValueFrom(this.api.supprimerAjustement(cible.id, a.id));
      const maj = await firstValueFrom(this.api.getBulletin(cible.id));
      this.detail.set(maj);
      this.remplacerDansListe(maj);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  private remplacerDansListe(maj: BulletinPaie): void {
    this.bulletins.update((liste) =>
      liste.map((b) => (b.id === maj.id ? maj : b))
    );
  }

  protected siFond(ev: MouseEvent, quelle: 'generation' | 'detail' | 'versement' | 'correction'): void {
    if (ev.target !== ev.currentTarget) return;
    if (quelle === 'generation') this.fermerGeneration();
    else if (quelle === 'detail') this.fermerDetail();
    else if (quelle === 'versement') this.fermerVersement();
    else this.fermerCorrection();
  }

  protected libelleStatut(s: StatutBulletin): string {
    const libelles: Record<StatutBulletin, string> = {
      genere: 'Généré',
      consulte: 'Consulté',
      valide: 'Validé',
      conteste: 'Contesté',
      corrige: 'Corrigé',
      verse: 'Versé',
    };
    return libelles[s] ?? s;
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