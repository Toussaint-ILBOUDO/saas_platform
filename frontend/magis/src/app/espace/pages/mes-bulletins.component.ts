import { Component, OnInit, inject, signal } from '@angular/core';
import { NgFor } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  BulletinContestation,
  BulletinPaie,
  MetaPage,
  MOTIFS_CONTESTATION,
  PeriodeComptable,
  StatutBulletin,
} from '../../models';
import { ouvrirDetailDepuisRoute } from '../detail-de-route';

/**
 * Écran « Mes bulletins de paie » de l'enseignant (T7A.9).
 *
 * Cycle scellé par la machine à états du service : consulter (genere/corrige
 * → consulte), valider ou contester (consulte → valide/conteste, DY-052), puis
 * confirmer la réception du paiement (verse, tant que la date manque).
 * Aucun libre PATCH : les boutons viennent de `actions` du bulletin.
 */
@Component({
  imports: [FormsModule, NgFor],
  selector: 'espace-mes-bulletins',
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
      .badge-genere { background: #e3e8f2; color: #33405c; }
      .badge-consulte { background: var(--mpc-jaune-doux); color: #6b4f00; }
      .badge-valide { background: var(--mpc-vert-pale); color: #0a5d35; }
      .badge-conteste { background: var(--mpc-rouge-clair); color: #7a1d1d; }
      .badge-corrige { background: #e7e2f5; color: #4a3b8c; }
      .badge-verse { background: var(--mpc-bleu-doux); color: #0b3a63; }
      .num-bulletin { font-family: ui-monospace, monospace; font-size: 0.8rem; }
      .montant { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 600; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .lignes-mini { font-size: 0.82rem; }
      .lignes-mini tr:last-child td { border-bottom: none; }
      .contestation { background: var(--mpc-rouge-clair); color: #5c1818; }
    `,
  ],
  template: `
    <section class="entete">
      <h1>Mes bulletins de paie</h1>
      <p>
        Chaque bulletin est établi depuis vos heures de cours validées. Vous
        pouvez le consulter, le valider ou le contester (un motif + une
        explication), puis confirmer la réception de votre paiement.
      </p>
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
    </section>

    @if (alerte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }

    @if (chargement()) {
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
              <th>Bulletin</th><th>Période</th><th class="text-end">Brut</th><th class="text-end">Net</th>
              <th>Statut</th><th></th>
            </tr>
          </thead>
          <tbody>
            @for (b of bulletins(); track b.id) {
              <tr>
                <td class="num-bulletin fw-semibold">{{ b.numero }}</td>
                <td>{{ b.periode?.label ?? '—' }}</td>
                <td class="text-end">{{ monnaie(b.montant_brut) }}</td>
                <td class="text-end montant">{{ monnaie(b.montant_net_final ?? b.montant_net) }}</td>
                <td>
                  <span class="badge-statut badge-{{ b.statut }}">{{ libelleStatut(b.statut) }}</span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-mpc" (click)="ouvrirDetail(b)" title="Consulter"><i class="bi bi-eye"></i></button>
                    @if (b.actions.includes('pdf')) {
                      <a class="btn btn-outline-mpc" [href]="api.urlPdfBulletin(b.id)" target="_blank" title="PDF"><i class="bi bi-filetype-pdf"></i></a>
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
                {{ detail()!.numero }}
                <span class="badge-statut badge-{{ detail()!.statut }} ms-2">{{ libelleStatut(detail()!.statut) }}</span>
              </h5>
              <button type="button" class="btn-close" (click)="fermerDetail()"></button>
            </div>
            <div class="modal-body">
              <p class="text-doux-tiny">{{ detail()!.periode?.label }}</p>

              <table class="table table-sm lignes-mini">
                <thead><tr><th>Élève</th><th>Matière</th><th class="text-end">Heures</th><th class="text-end">Taux/h</th><th class="text-end">Montant</th></tr></thead>
                <tbody>
                  @for (l of detail()!.lignes; track l.id) {
                    <tr>
                      <td>{{ l.eleve }}</td>
                      <td>{{ l.matiere }}</td>
                      <td class="text-end">{{ chiffre(l.nombre_heures) }} h</td>
                      <td class="text-end">{{ monnaie(l.taux_horaire) }}</td>
                      <td class="text-end">{{ monnaie(l.montant) }}</td>
                    </tr>
                  }
                </tbody>
              </table>

              @if (detail()!.ajustements.length > 0) {
                <table class="table table-sm lignes-mini mt-2">
                  <thead><tr><th>Ajustement</th><th class="text-end">Montant</th></tr></thead>
                  <tbody>
                    @for (a of detail()!.ajustements; track a.id) {
                      <tr>
                        <td>{{ a.libelle }}</td>
                        <td class="text-end">{{ a.type === 'retenue' ? '−' : '+' }}{{ monnaie(a.montant) }}</td>
                      </tr>
                    }
                  </tbody>
                </table>
              }

              <div class="d-flex flex-column align-items-end mt-3 gap-1">
                <div class="text-doux-tiny">Brut — <b class="montant">{{ monnaie(detail()!.montant_brut) }}</b></div>
                @if (detail()!.frais_suivi > 0) {
                  <div class="text-doux-tiny">Frais de suivi — <b class="montant">−{{ monnaie(detail()!.frais_suivi) }}</b></div>
                }
                <div class="fw-bold montant fs-5">Net {{ monnaie(detail()!.montant_net_final ?? detail()!.montant_net) }}</div>
              </div>

              @if (detail()!.commentaire_enseignant) {
                <div class="mt-3 p-2 bg-light rounded-3 small">{{ detail()!.commentaire_enseignant }}</div>
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
              <a class="btn btn-outline-mpc" [href]="api.urlPdfBulletin(detail()!.id)" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
              <div class="d-flex gap-2 flex-wrap">
                @if (detail()!.actions.includes('consulter')) {
                  <button class="btn btn-mpc" (click)="transition('consulter')"><i class="bi bi-bookmark-check"></i> Consulter le bulletin</button>
                }
                @if (detail()!.actions.includes('valider')) {
                  <button class="btn btn-mpc" (click)="transition('valider')"><i class="bi bi-check2-circle"></i> Valider</button>
                }
                @if (detail()!.actions.includes('contester')) {
                  <button class="btn btn-outline-danger" (click)="ouvrirContestation()"><i class="bi bi-exclamation-octagon"></i> Contester</button>
                }
                @if (detail()!.actions.includes('confirmer-reception')) {
                  <button class="btn btn-mpc" (click)="transition('confirmer-reception')"><i class="bi bi-envelope-open"></i> Confirmer la réception</button>
                }
              </div>
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Modale contestation -->
    @if (conteste()) {
      <div class="modal d-block" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Contester le bulletin</h5>
              <button type="button" class="btn-close" (click)="fermerContestation()"></button>
            </div>
            <div class="modal-body">
              <p class="text-doux-tiny">
                Décrivez précisément ce que vous contestez : l'administration
                corrigera le bulletin si la contestation est fondée.
              </p>
              <div class="mb-3">
                <label class="form-label">Motif</label>
                <select class="form-select" [(ngModel)]="contestation.motif_contestation">
                  <option value="" disabled>Choisissez un motif…</option>
                  <option *ngFor="let m of motifs" [value]="m.valeur">{{ m.libelle }}</option>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label">Explication</label>
                <textarea class="form-control" rows="4" [(ngModel)]="contestation.commentaire_enseignant" placeholder="Détaillez ce qui est contesté…"></textarea>
              </div>
              @if (erreurContestation()) {
                <div class="alerte small">{{ erreurContestation() }}</div>
              }
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" (click)="fermerContestation()">Annuler</button>
              <button type="button" class="btn btn-outline-danger" [disabled]="envoi()" (click)="soumettreContestation()">
                <i class="bi bi-exclamation-octagon"></i> Envoyer la contestation
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class MesBulletinsComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected bulletins = signal<BulletinPaie[]>([]);
  protected periodes = signal<PeriodeComptable[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected page = signal(1);
  protected chargement = signal(false);
  protected alerte = signal('');
  protected detail = signal<BulletinPaie | null>(null);
  protected conteste = signal(false);
  protected envoi = signal(false);
  protected erreurContestation = signal('');

  protected filtres = signal<{ periode_id: number | null; statut: string }>({
    periode_id: null,
    statut: '',
  });

  protected readonly motifs = MOTIFS_CONTESTATION;

  protected contestation: BulletinContestation = {
    motif_contestation: '',
    commentaire_enseignant: '',
  };

  async ngOnInit(): Promise<void> {
    await Promise.all([this.charger(), this.chargerPeriodes()]);

    // Cible d'une notification : ouvre directement la fiche `…/{id}`.
    ouvrirDetailDepuisRoute(
      this.route,
      (id) => this.api.getMesBulletin(id),
      (bulletin) => this.ouvrirDetail(bulletin),
      (erreur) => this.alerte.set(messageErreurApi(erreur))
    );
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');
    try {
      const r = await firstValueFrom(
        this.api.getMesBulletins(this.page(), 20, {
          periode_id: this.filtres().periode_id,
          statut: this.filtres().statut,
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

  protected ouvrirDetail(b: BulletinPaie): void {
    this.detail.set(b);
  }

  protected fermerDetail(): void {
    this.detail.set(null);
  }

  protected siFond(ev: MouseEvent): void {
    if (ev.target === ev.currentTarget) this.fermerDetail();
  }

  /** Applique une transition si l'état le permet : aucun PATCH libre. */
  protected async transition(action: string): Promise<void> {
    const b = this.detail();
    if (!b) return;

    try {
      let maj: BulletinPaie;
      switch (action) {
        case 'consulter':
          maj = await firstValueFrom(this.api.consulterBulletin(b.id));
          break;
        case 'valider':
          maj = await firstValueFrom(this.api.validerBulletin(b.id));
          break;
        case 'confirmer-reception':
          maj = await firstValueFrom(this.api.confirmerReceptionBulletin(b.id));
          break;
        default:
          return;
      }
      this.detail.set(maj);
      this.remplacerDansListe(maj);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
      this.fermerDetail();
    }
  }

  protected ouvrirContestation(): void {
    this.contestation = { motif_contestation: '', commentaire_enseignant: '' };
    this.erreurContestation.set('');
    this.conteste.set(true);
  }

  protected fermerContestation(): void {
    this.conteste.set(false);
    this.erreurContestation.set('');
  }

  protected async soumettreContestation(): Promise<void> {
    const b = this.detail();
    if (!b) return;

    this.envoi.set(true);
    this.erreurContestation.set('');
    try {
      const maj = await firstValueFrom(
        this.api.contesterBulletin(b.id, this.contestation)
      );
      this.conteste.set(false);
      this.detail.set(maj);
      this.remplacerDansListe(maj);
    } catch (e) {
      this.erreurContestation.set(messageErreurApi(e));
    } finally {
      this.envoi.set(false);
    }
  }

  private remplacerDansListe(maj: BulletinPaie): void {
    this.bulletins.update((liste) =>
      liste.map((b) => (b.id === maj.id ? maj : b))
    );
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