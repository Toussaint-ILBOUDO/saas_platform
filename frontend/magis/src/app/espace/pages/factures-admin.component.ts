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
  FactureAjustement,
  FactureApercu,
  FactureFormulaire,
  FacturePaiement,
  LigneFacture,
  MetaPage,
  MODES_PAIEMENT,
  PeriodeComptable,
  StatutPaiement,
  TypeAjustement,
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

      /* En-tête de facture (identité + métriques) — même dessin que le bulletin */
      .identite { display: flex; flex-wrap: wrap; gap: 0.4rem 1.4rem; margin-bottom: 0.9rem; }
      .identite .ident-item { font-size: 0.85rem; }
      .identite .ident-label { display: block; font-size: 0.68rem; letter-spacing: 0.05em; text-transform: uppercase; color: var(--mpc-texte-doux); }
      .identite .ident-valeur { font-weight: 600; color: #1f2937; }
      .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.7rem; margin-bottom: 1.1rem; }
      .metric { background: #f8fafc; border: 1px solid var(--mpc-bordure); border-radius: 0.9rem; padding: 0.7rem 0.9rem; }
      .metric-label { font-size: 0.68rem; letter-spacing: 0.05em; text-transform: uppercase; color: var(--mpc-texte-doux); }
      .metric-value { font-size: 1.1rem; font-weight: 800; color: var(--mpc-bleu); font-variant-numeric: tabular-nums; }
      .metric-value.accent { color: #0a5d35; }
      .section-titre { font-size: 0.78rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; color: var(--mpc-texte-doux); margin: 1.1rem 0 0.5rem; }
      .table-clair tfoot th { background: #f1f5f9; font-size: 0.82rem; }

      .recap-ligne { display: flex; justify-content: space-between; gap: 1rem; font-size: 0.85rem; padding: 0.12rem 0; min-width: 240px; }
      .recap-total { border-top: 1px solid var(--mpc-bordure); margin-top: 0.25rem; padding-top: 0.35rem; font-weight: 800; font-size: 1rem; color: #0a5d35; }

      .badge-direction { font-size: 0.66rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 99px; white-space: nowrap; }
      .badge-credit { background: var(--mpc-vert-pale); color: #0a5d35; }
      .badge-debit { background: var(--mpc-rouge-clair); color: #7a1d1d; }
      .ajout-ajustement { background: #f8fafc; border: 1px dashed var(--mpc-bordure); border-radius: 0.9rem; padding: 0.85rem; }

      /* Aperçu de génération — panneau par facture (même dessin que la paie) */
      .apercu-facture { background: #f8fafc; border: 1px solid var(--mpc-bordure); border-radius: 1rem; padding: 0.9rem 1rem; margin-top: 1rem; }
      .apercu-entete { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline; gap: 0.5rem; margin-bottom: 0.6rem; }
      .apercu-nom { font-weight: 800; color: var(--mpc-bleu); font-size: 0.95rem; }
      .apercu-resume { font-size: 0.82rem; color: var(--mpc-texte-doux); }
      .apercu-reglages { display: flex; flex-wrap: wrap; gap: 0.9rem; align-items: flex-end; margin-top: 0.7rem; padding-top: 0.7rem; border-top: 1px dashed var(--mpc-bordure); }
      .reglage { display: flex; flex-direction: column; gap: 0.2rem; }
      .reglage-label { font-size: 0.7rem; font-weight: 700; color: var(--mpc-texte-doux); }
      .reglage input { width: 130px; }
      .apercu-net { margin-left: auto; text-align: right; }
      .apercu-net .net-label { font-size: 0.68rem; letter-spacing: 0.05em; text-transform: uppercase; color: var(--mpc-texte-doux); }
      .apercu-net .net-valeur { font-size: 1.05rem; font-weight: 800; color: #0a5d35; font-variant-numeric: tabular-nums; }
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
                <div class="apercu-facture">
                  <div class="apercu-entete">
                    <div>
                      <span class="apercu-nom">{{ apercu()!.eleve }}</span>
                      <span class="ms-2 text-doux-tiny">
                        parent : {{ apercu()!.parent }} · {{ apercu()!.periode_label }}
                      </span>
                    </div>
                    <div class="apercu-resume">
                      Heures <b>{{ chiffre(apercu()!.volume_horaire_total) }}</b> ·
                      Cours <b>{{ monnaie(apercu()!.montant_cours) }}</b>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-sm lignes-mini table-clair mb-0 align-middle">
                      <thead>
                        <tr><th>Enseignant</th><th>Matière</th><th class="text-end">Heures</th><th class="text-end">Taux/h</th><th class="text-end">Montant</th></tr>
                      </thead>
                      <tbody>
                        @for (l of apercu()!.lignes; track l.affectation_enseignant_id) {
                          <tr>
                            <td>{{ l.enseignant }}</td>
                            <td>{{ l.matiere }}</td>
                            <td class="text-end">{{ chiffre(l.nombre_heures) }} h</td>
                            <td class="text-end">{{ monnaie(l.taux_horaire) }}</td>
                            <td class="text-end">{{ monnaie(l.montant) }}</td>
                          </tr>
                        }
                      </tbody>
                      <tfoot>
                        <tr>
                          <th colspan="2">Total</th>
                          <th class="text-end">{{ chiffre(apercu()!.volume_horaire_total) }} h</th>
                          <th></th>
                          <th class="text-end montant">{{ monnaie(apercu()!.montant_cours) }}</th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>

                  @if (ajustementsSaisis().length > 0) {
                    <div class="section-titre">Bonus &amp; débits</div>
                    <table class="table table-sm lignes-mini table-clair mb-0">
                      <thead><tr><th>Libellé</th><th>Nature</th><th class="text-end">Montant</th><th></th></tr></thead>
                      <tbody>
                        @for (a of ajustementsSaisis(); track $index) {
                          <tr>
                            <td>{{ a.libelle }}</td>
                            <td>
                              <span class="badge-direction" [class.badge-credit]="directionType(a.type_ajustement_id) === 'credit'" [class.badge-debit]="directionType(a.type_ajustement_id) === 'debit'">
                                {{ directionType(a.type_ajustement_id) === 'credit' ? 'Crédit' : 'Débit' }}
                              </span>
                            </td>
                            <td class="text-end montant">{{ directionType(a.type_ajustement_id) === 'credit' ? '+' : '−' }}{{ monnaie(a.montant) }}</td>
                            <td class="text-end">
                              <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" (click)="retirerAjustementCreation($index)" title="Retirer cette ligne"><i class="bi bi-x"></i></button>
                            </td>
                          </tr>
                        }
                      </tbody>
                      <tfoot>
                        <tr>
                          <th colspan="2">Totaux bonus / débits</th>
                          <th class="text-end">+{{ monnaie(totauxCreation().credits) }} / −{{ monnaie(totauxCreation().debits) }}</th>
                          <th></th>
                        </tr>
                      </tfoot>
                    </table>
                  }

                  <div class="ajout-ajustement mt-3">
                    <div class="fw-semibold mb-2"><i class="bi bi-plus-circle me-1"></i>Ajouter une ligne (bonus ou débit)</div>
                    <div class="row g-2 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label small mb-1">Type</label>
                        <select class="form-select form-select-sm" [(ngModel)]="ajustementCreationForm.type_ajustement_id" name="crea_aj_type">
                          <option [ngValue]="null">— Choisir —</option>
                          <option *ngFor="let t of typesAjustement()" [ngValue]="t.id">{{ t.libelle }} ({{ t.direction === 'credit' ? 'crédit' : 'débit' }})</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small mb-1">Libellé</label>
                        <input class="form-control form-control-sm" [(ngModel)]="ajustementCreationForm.libelle" name="crea_aj_libelle" maxlength="255" placeholder="Ex. Remise fidélité" />
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small mb-1">Montant</label>
                        <input type="number" min="1" class="form-control form-control-sm" [(ngModel)]="ajustementCreationForm.montant" name="crea_aj_montant" />
                      </div>
                      <div class="col-md-2">
                        <button type="button" class="btn btn-mpc btn-sm w-100" (click)="ajouterAjustementCreation()">
                          <i class="bi bi-plus-lg"></i> Ajouter
                        </button>
                      </div>
                    </div>
                    @if (erreurAjustementCreation()) {
                      <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ erreurAjustementCreation() }}</div>
                    }
                  </div>

                  <div class="apercu-reglages">
                    <div class="reglage">
                      <span class="reglage-label">Frais de suivi</span>
                      <b class="montant">{{ monnaie(form.frais_suivi) }}</b>
                    </div>
                    <div class="reglage">
                      <span class="reglage-label">Autres frais</span>
                      <b class="montant">{{ monnaie(form.autres_frais) }}</b>
                    </div>
                    <div class="reglage">
                      <span class="reglage-label">Remise</span>
                      <b class="montant">{{ remiseCible() > 0 ? '−' : '' }}{{ monnaie(remiseCible()) }}</b>
                    </div>
                    <div class="reglage">
                      <span class="reglage-label">Bonus (crédits)</span>
                      <b class="montant">+{{ monnaie(totauxCreation().credits) }}</b>
                    </div>
                    <div class="reglage">
                      <span class="reglage-label">Débits</span>
                      <b class="montant">−{{ monnaie(totauxCreation().debits) }}</b>
                    </div>
                    <div class="apercu-net">
                      <div class="net-label">Total à payer</div>
                      <div class="net-valeur">{{ monnaie(totalCible()) }}</div>
                    </div>
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
              <div class="identite">
                <div class="ident-item">
                  <span class="ident-label">Élève</span>
                  <span class="ident-valeur">{{ detail()!.eleve?.nom ?? '—' }}</span>
                </div>
                <div class="ident-item">
                  <span class="ident-label">Parent</span>
                  <span class="ident-valeur">{{ detail()!.parent?.nom ?? '—' }}</span>
                </div>
                <div class="ident-item">
                  <span class="ident-label">Période</span>
                  <span class="ident-valeur">{{ detail()!.periode?.label ?? '—' }}</span>
                </div>
                <div class="ident-item">
                  <span class="ident-label">Type de cours</span>
                  <span class="ident-valeur">{{ detail()!.type_cours?.libelle ?? '—' }}</span>
                </div>
                <div class="ident-item">
                  <span class="ident-label">Émise le</span>
                  <span class="ident-valeur">{{ dateCourte(detail()!.created_at) }}</span>
                </div>
                @if (detail()!.date_limite_paiement) {
                  <div class="ident-item">
                    <span class="ident-label">Échéance</span>
                    <span class="ident-valeur">{{ detail()!.date_limite_paiement }}</span>
                  </div>
                }
              </div>

              <div class="metrics">
                <div class="metric">
                  <div class="metric-label">Heures facturées</div>
                  <div class="metric-value">{{ chiffre(volumeDetail()) }} h</div>
                </div>
                <div class="metric">
                  <div class="metric-label">Montant cours</div>
                  <div class="metric-value">{{ monnaie(montantCoursDetail()) }}</div>
                </div>
                <div class="metric">
                  <div class="metric-label">Frais &amp; déductions</div>
                  <div class="metric-value">{{ monnaie(fraisEtDeductionsDetail()) }}</div>
                </div>
                <div class="metric">
                  <div class="metric-label">Total à payer</div>
                  <div class="metric-value accent">{{ monnaie(detail()!.montant_total) }}</div>
                </div>
              </div>

              <div class="section-titre">Détail des lignes de cours</div>
              <table class="table table-sm lignes-mini table-clair">
                <thead>
                  <tr><th>Enseignant</th><th>Matière</th><th class="text-end">Heures</th><th class="text-end">Taux/h</th><th class="text-end">Montant</th></tr>
                </thead>
                <tbody>
                  @for (l of detail()!.lignes; track l.id) {
                    <tr>
                      <td>{{ l.enseignant ?? '—' }}</td>
                      <td>{{ l.matiere ?? '—' }}</td>
                      <td class="text-end">{{ chiffre(l.nombre_heures) }} h</td>
                      <td class="text-end">{{ monnaie(l.taux_horaire) }}</td>
                      <td class="text-end">{{ monnaie(l.montant) }}</td>
                    </tr>
                  } @empty {
                    <tr><td colspan="5" class="text-center text-doux-tiny py-3">Aucune ligne sur cette facture.</td></tr>
                  }
                </tbody>
                @if (detail()!.lignes.length > 0) {
                  <tfoot>
                    <tr>
                      <th colspan="2">Total</th>
                      <th class="text-end">{{ chiffre(volumeDetail()) }} h</th>
                      <th></th>
                      <th class="text-end montant">{{ monnaie(montantCoursDetail()) }}</th>
                    </tr>
                  </tfoot>
                }
              </table>

              @if (detail()!.ajustements.length > 0) {
                <div class="section-titre">Bonus &amp; débits</div>
                <table class="table table-sm lignes-mini table-clair">
                  <thead><tr><th>Libellé</th><th>Nature</th><th class="text-end">Montant</th><th></th></tr></thead>
                  <tbody>
                    @for (a of detail()!.ajustements; track a.id) {
                      <tr>
                        <td>{{ a.libelle }}</td>
                        <td>
                          <span class="badge-direction" [class.badge-credit]="a.type === 'credit'" [class.badge-debit]="a.type === 'debit'">
                            {{ a.type === 'credit' ? 'Crédit' : 'Débit' }}
                          </span>
                        </td>
                        <td class="text-end montant">{{ a.type === 'credit' ? '+' : '−' }}{{ monnaie(a.montant) }}</td>
                        <td class="text-end">
                          @if (detail()!.actions.includes('ajuster')) {
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" (click)="supprimerAjustement(a)" title="Retirer cette ligne"><i class="bi bi-x"></i></button>
                          }
                        </td>
                      </tr>
                    }
                  </tbody>
                  <tfoot>
                    <tr>
                      <th colspan="2">Totaux bonus / débits</th>
                      <th class="text-end">+{{ monnaie(detail()!.total_credits ?? 0) }} / −{{ monnaie(detail()!.total_debits ?? 0) }}</th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              }

              @if (detail()!.actions.includes('ajuster')) {
                <div class="section-titre">Ajouter un bonus ou un débit</div>
                <div class="ajout-ajustement">
                  <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                      <label class="form-label small mb-1">Type</label>
                      <select class="form-select form-select-sm" [(ngModel)]="ajustementForm.type_ajustement_id" name="aj_type">
                        <option [ngValue]="null">— Choisir —</option>
                        <option *ngFor="let t of typesAjustement()" [ngValue]="t.id">{{ t.libelle }} ({{ t.direction === 'credit' ? 'crédit' : 'débit' }})</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small mb-1">Libellé</label>
                      <input class="form-control form-control-sm" [(ngModel)]="ajustementForm.libelle" name="aj_libelle" maxlength="255" placeholder="Ex. Remise inscription" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small mb-1">Montant</label>
                      <input type="number" min="1" class="form-control form-control-sm" [(ngModel)]="ajustementForm.montant" name="aj_montant" />
                    </div>
                    <div class="col-md-2">
                      <button type="button" class="btn btn-mpc btn-sm w-100" (click)="ajouterAjustement()" [disabled]="ajustementEnregistrement()">
                        @if (ajustementEnregistrement()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                        Ajouter
                      </button>
                    </div>
                  </div>
                  @if (erreurAjustement()) { <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ erreurAjustement() }}</div> }
                </div>
              }

              <div class="d-flex justify-content-end mt-3">
                <div>
                  <div class="recap-ligne"><span>Cours ({{ chiffre(volumeDetail()) }} h)</span><b class="montant">{{ monnaie(montantCoursDetail()) }}</b></div>
                  @if (detail()!.frais_suivi > 0) {
                    <div class="recap-ligne"><span>Frais de suivi</span><b class="montant">{{ monnaie(detail()!.frais_suivi) }}</b></div>
                  }
                  @if (detail()!.autres_frais > 0) {
                    <div class="recap-ligne"><span>Autres frais</span><b class="montant">{{ monnaie(detail()!.autres_frais) }}</b></div>
                  }
                  @if (detail()!.remise > 0) {
                    <div class="recap-ligne"><span>Remise</span><b class="montant">−{{ monnaie(detail()!.remise) }}</b></div>
                  }
                  @if ((detail()!.total_credits ?? 0) > 0) {
                    <div class="recap-ligne"><span>Bonus (crédits)</span><b class="montant">+{{ monnaie(detail()!.total_credits) }}</b></div>
                  }
                  @if ((detail()!.total_debits ?? 0) > 0) {
                    <div class="recap-ligne"><span>Débits</span><b class="montant">−{{ monnaie(detail()!.total_debits) }}</b></div>
                  }
                  <div class="recap-ligne recap-total"><span>Total à payer</span><b class="montant">{{ monnaie(detail()!.montant_total) }}</b></div>
                </div>
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

  /** Types d'ajustement (bonus/débit) et état de la saisie d'ajustement. */
  protected typesAjustement = signal<TypeAjustement[]>([]);
  protected ajustementForm: {
    type_ajustement_id: number | null;
    libelle: string;
    montant: number | null;
  } = { type_ajustement_id: null, libelle: '', montant: null };
  protected ajustementEnregistrement = signal(false);
  protected erreurAjustement = signal('');

  /**
   * Lignes bonus/débit saisies dans l'aperçu de GÉNÉRATION : elles ne sont
   * écrites qu'à la création, comme les frais, et voyagent dans `form`.
   */
  protected ajustementCreationForm: {
    type_ajustement_id: number | null;
    libelle: string;
    montant: number | null;
  } = { type_ajustement_id: null, libelle: '', montant: null };
  protected erreurAjustementCreation = signal('');

  protected filtres = signal<{ periode_id: number | null; statut: string; search: string }>({
    periode_id: null,
    statut: '',
    search: '',
  });

  protected MODES = MODES_PAIEMENT;

  protected form: FactureFormulaire = { contrat_cours_id: null, periode_id: null, frais_suivi: 0, autres_frais: 0, remise: 0, date_limite_paiement: null, commentaire: '', ajustements: [] };
  protected paiementForm: FacturePaiement = { date_paiement: new Date().toISOString().slice(0, 10), mode_paiement: 'especes', reference_paiement: '' };

  protected montantCoursDetail = computed<number>(() =>
    (this.detail()?.lignes ?? []).reduce((s: number, l: LigneFacture) => s + l.montant, 0)
  );

  protected volumeDetail = computed<number>(() =>
    (this.detail()?.lignes ?? []).reduce((s: number, l: LigneFacture) => s + l.nombre_heures, 0)
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
    const { credits, debits } = this.totauxCreation();
    return (
      (this.apercu()?.montant_cours ?? 0)
      + this.fraisCibles()
      - this.remiseCible()
      + credits
      - debits
    );
  }

  /** Lignes bonus/débit saisies dans l'aperçu de génération. */
  protected ajustementsSaisis(): { type_ajustement_id: number; libelle: string; montant: number }[] {
    return this.form.ajustements ?? [];
  }

  /** Direction (crédit/débit) d'un type d'ajustement. */
  protected directionType(typeId: number | null): 'credit' | 'debit' {
    return this.typesAjustement().find((t) => t.id === typeId)?.direction ?? 'credit';
  }

  protected totauxCreation(): { credits: number; debits: number } {
    let credits = 0;
    let debits = 0;
    for (const a of this.ajustementsSaisis()) {
      if (this.directionType(a.type_ajustement_id) === 'debit') {
        debits += Number(a.montant || 0);
      } else {
        credits += Number(a.montant || 0);
      }
    }
    return { credits, debits };
  }

  /** Ajoute une ligne bonus/débit à l'aperçu (aucune écriture avant génération). */
  protected ajouterAjustementCreation(): void {
    const { type_ajustement_id, libelle, montant } = this.ajustementCreationForm;
    if (!type_ajustement_id) {
      this.erreurAjustementCreation.set('Choisissez un type d\'ajustement.');
      return;
    }
    if (!libelle.trim()) {
      this.erreurAjustementCreation.set('Le libellé est obligatoire.');
      return;
    }
    if (!montant || montant <= 0) {
      this.erreurAjustementCreation.set('Le montant doit être supérieur à zéro.');
      return;
    }
    this.form.ajustements = [
      ...this.ajustementsSaisis(),
      { type_ajustement_id, libelle: libelle.trim(), montant: Math.round(Number(montant)) },
    ];
    this.ajustementCreationForm = { type_ajustement_id: null, libelle: '', montant: null };
    this.erreurAjustementCreation.set('');
  }

  protected retirerAjustementCreation(index: number): void {
    this.form.ajustements = this.ajustementsSaisis().filter((_, i) => i !== index);
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

    // Les types d'ajustement ne bloquent pas non plus l'écran : la saisie
    // d'une ligne bonus/débit reste indisponible si la liste est vide.
    try {
      this.typesAjustement.set(
        await firstValueFrom(this.api.typesAjustementFacture())
      );
    } catch {
      this.typesAjustement.set([]);
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
    this.form = { contrat_cours_id: null, periode_id: null, frais_suivi: 0, autres_frais: 0, remise: 0, date_limite_paiement: null, commentaire: '', ajustements: [] };
    this.ajustementCreationForm = { type_ajustement_id: null, libelle: '', montant: null };
    this.erreurAjustementCreation.set('');
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

  protected async ouvrirDetail(f: Facture): Promise<void> {
    // La liste ne transporte pas les lignes ni les ajustements : on affiche
    // la ligne tout de suite puis on recharge le détail complet.
    this.detail.set({ ...f, lignes: f.lignes ?? [], ajustements: f.ajustements ?? [] });
    this.erreurAjustement.set('');
    this.reinitialiserAjustement();
    try {
      this.detail.set(await firstValueFrom(this.api.getFacture(f.id)));
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    }
  }

  protected fermerDetail(): void {
    this.detail.set(null);
    this.erreurAjustement.set('');
  }

  private reinitialiserAjustement(): void {
    this.ajustementForm = { type_ajustement_id: null, libelle: '', montant: null };
  }

  /** Ajoute une ligne bonus/débit à la facture ouverte, puis rafraîchit. */
  protected async ajouterAjustement(): Promise<void> {
    const cible = this.detail();
    if (!cible) return;

    const { type_ajustement_id, libelle, montant } = this.ajustementForm;
    if (!type_ajustement_id) {
      this.erreurAjustement.set('Choisissez un type d\'ajustement.');
      return;
    }
    if (!libelle.trim()) {
      this.erreurAjustement.set('Le libellé est obligatoire.');
      return;
    }
    if (!montant || montant <= 0) {
      this.erreurAjustement.set('Le montant doit être supérieur à zéro.');
      return;
    }

    this.ajustementEnregistrement.set(true);
    this.erreurAjustement.set('');
    try {
      await firstValueFrom(
        this.api.ajouterAjustementFacture(cible.id, {
          type_ajustement_id,
          libelle: libelle.trim(),
          montant,
        })
      );
      this.reinitialiserAjustement();
      this.detail.set(await firstValueFrom(this.api.getFacture(cible.id)));
      await this.charger();
    } catch (e) {
      this.erreurAjustement.set(messageErreurApi(e));
    } finally {
      this.ajustementEnregistrement.set(false);
    }
  }

  /** Retire une ligne bonus/débit de la facture ouverte. */
  protected async supprimerAjustement(ajustement: FactureAjustement): Promise<void> {
    const cible = this.detail();
    if (!cible) return;
    if (!confirm(`Retirer « ${ajustement.libelle} » ?`)) return;

    this.erreurAjustement.set('');
    try {
      await firstValueFrom(
        this.api.supprimerAjustementFacture(cible.id, ajustement.id)
      );
      this.detail.set(await firstValueFrom(this.api.getFacture(cible.id)));
      await this.charger();
    } catch (e) {
      this.erreurAjustement.set(messageErreurApi(e));
    }
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

  /** Frais, remises et ajustements : l'écart entre le total et les cours. */
  protected fraisEtDeductionsDetail(): number {
    const d = this.detail();
    return (d?.montant_total ?? 0) - this.montantCoursDetail();
  }

  protected dateCourte(v: string | null | undefined): string {
    if (!v) return '—';
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('fr-FR');
  }

  protected monnaie(v: number | null | undefined): string {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(v ?? 0)) + ' FCFA';
  }
}