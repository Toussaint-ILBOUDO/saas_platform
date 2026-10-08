import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { NgFor } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import { Facture, LigneFacture, MetaPage, PeriodeComptable, StatutPaiement } from '../../models';
import { ouvrirDetailDepuisRoute } from '../detail-de-route';

/**
 * Écran « Mes factures » du parent (T7A.8).
 *
 * Strictement en lecture : la facture est générée par l'administration et le
 * règlement est un acte administratif. Le parent consulte ses factures, les
 * ouvre en PDF, et voit ici exactement les montants qu'il doit — issus des
 * rapports validés (D-051).
 */
@Component({
  imports: [FormsModule, NgFor],
  selector: 'espace-mes-factures',
  styles: [
    `
      :host { display: block; }
      .entete { margin-bottom: 1.1rem; }
      .entete h1 { margin: 0 0 0.2rem; font-size: 1.25rem; font-weight: 800; color: var(--mpc-bleu); }
      .entete p { margin: 0; color: var(--mpc-texte-doux); font-size: 0.86rem; max-width: 68ch; }
      .filtres { display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center; margin-bottom: 1rem; }
      .filtres select { font-size: 0.85rem; }
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
    `,
  ],
  template: `
    <section class="entete">
      <h1>Mes factures</h1>
      <p>
        Vos factures sont émises chaque mois à partir des heures de cours
        validées. Cliquez sur une ligne pour le détail, ou téléchargez le PDF.
      </p>
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
    </section>

    @if (alerte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }

    @if (chargement()) {
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
              <th>Facture</th><th>Élève</th><th>Période</th><th>Type de cours</th>
              <th class="text-end">Montant</th><th>Statut</th><th></th>
            </tr>
          </thead>
          <tbody>
            @for (f of factures(); track f.id) {
              <tr>
                <td class="num-facture fw-semibold">{{ f.numero_facture }}</td>
                <td>{{ f.eleve?.nom ?? '—' }}</td>
                <td>{{ f.periode?.label }}</td>
                <td>{{ f.type_cours?.libelle }}</td>
                <td class="text-end montant">{{ monnaie(f.montant_total) }}</td>
                <td>
                  <span class="badge-statut badge-{{ f.statut_paiement }}">{{ libelleStatut(f.statut_paiement) }}</span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-mpc" (click)="ouvrirDetail(f)" title="Consulter"><i class="bi bi-eye"></i></button>
                    @if (f.actions.includes('pdf')) {
                      <a class="btn btn-outline-mpc" [href]="api.urlPdfFacture(f.id)" target="_blank" title="PDF"><i class="bi bi-filetype-pdf"></i></a>
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
      <div class="modal d-block" tabindex="-1" (click)="siFond($event)">
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
                {{ detail()!.eleve?.nom }} · {{ detail()!.periode?.label }} ·
                {{ detail()!.type_cours?.libelle }}
              </p>

              <table class="table table-sm lignes-mini">
                <thead><tr><th>Enseignant</th><th>Matière</th><th class="text-end">Heures</th><th class="text-end">Taux/h</th><th class="text-end">Montant</th></tr></thead>
                <tbody>
                  @for (l of detail()!.lignes; track l.id) {
                    <tr>
                      <td>{{ l.enseignant }}</td>
                      <td>{{ l.matiere }}</td>
                      <td class="text-end">{{ chiffre(l.nombre_heures) }} h</td>
                      <td class="text-end">{{ monnaie(l.taux_horaire) }}</td>
                      <td class="text-end">{{ monnaie(l.montant) }}</td>
                    </tr>
                  }
                </tbody>
              </table>

              <div class="d-flex flex-column align-items-end mt-3 gap-1">
                <div class="text-doux-tiny">Cours — <b class="montant">{{ monnaie(montantCours()) }}</b></div>
                @if (detail()!.frais_suivi > 0) {
                  <div class="text-doux-tiny">Frais de suivi — <b class="montant">{{ monnaie(detail()!.frais_suivi) }}</b></div>
                }
                @if (detail()!.autres_frais > 0) {
                  <div class="text-doux-tiny">Autres frais — <b class="montant">{{ monnaie(detail()!.autres_frais) }}</b></div>
                }
                @if (detail()!.remise > 0) {
                  <div class="text-doux-tiny">Remise — <b class="montant">−{{ monnaie(detail()!.remise) }}</b></div>
                }
                <div class="fw-bold montant fs-5">Total {{ monnaie(detail()!.montant_total) }}</div>
              </div>

              @if (detail()!.commentaire) {
                <div class="mt-3 p-2 bg-light rounded-3 small">{{ detail()!.commentaire }}</div>
              }
              @if (detail()!.est_payee) {
                <div class="mt-3 p-2 rounded-3 small" style="background: var(--mpc-vert-pale)">
                  <i class="bi bi-check-circle-fill me-1"></i>
                  Payée le {{ detail()!.date_paiement }} — {{ libelleMode(detail()!.mode_paiement) }}
                  @if (detail()!.reference_paiement) { · réf. {{ detail()!.reference_paiement }} }
                </div>
              }
            </div>
            <div class="modal-footer">
              <a class="btn btn-mpc" [href]="api.urlPdfFacture(detail()!.id)" target="_blank"><i class="bi bi-filetype-pdf"></i> Télécharger le PDF</a>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class MesFacturesComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected factures = signal<Facture[]>([]);
  protected periodes = signal<PeriodeComptable[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected page = signal(1);
  protected chargement = signal(false);
  protected alerte = signal('');
  protected detail = signal<Facture | null>(null);

  protected filtres = signal<{ periode_id: number | null; statut: string }>({ periode_id: null, statut: '' });

  protected montantCours = computed<number>(() =>
    (this.detail()?.lignes ?? []).reduce((s: number, l: LigneFacture) => s + l.montant, 0)
  );

  async ngOnInit(): Promise<void> {
    await Promise.all([this.charger(), this.chargerPeriodes()]);

    // Cible d'une notification : ouvre directement la fiche `…/{id}`.
    ouvrirDetailDepuisRoute(
      this.route,
      (id) => this.api.getMesFacture(id),
      (facture) => this.ouvrirDetail(facture),
      (erreur) => this.alerte.set(messageErreurApi(erreur))
    );
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');
    try {
      const r = await firstValueFrom(
        this.api.getMesFactures(this.page(), 20, {
          periode_id: this.filtres().periode_id,
          statut: this.filtres().statut,
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

  private async chargerPeriodes(): Promise<void> {
    try {
      const r = await firstValueFrom(this.api.getPeriodes(1, 100));
      this.periodes.set(r.data);
    } catch {
      // Le filtre par période reste indisponible au pire.
    }
  }

  protected changerPage(p: number): void {
    if (p < 1) return;
    this.page.set(p);
    void this.charger();
  }

  protected ouvrirDetail(f: Facture): void {
    this.detail.set(f);
  }

  protected fermerDetail(): void {
    this.detail.set(null);
  }

  protected siFond(ev: MouseEvent): void {
    if (ev.target === ev.currentTarget) this.fermerDetail();
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