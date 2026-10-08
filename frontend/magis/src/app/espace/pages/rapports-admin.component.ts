import { Component, OnInit, inject, signal } from '@angular/core';
import { NgFor } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import { MetaPage, PeriodeComptable, RapportMensuel, RapportMensuelLigne } from '../../models';
import { ouvrirDetailDepuisRoute } from '../detail-de-route';

/**
 * Écran « Rapports mensuels » de l'administration (T7A.7).
 *
 * L'admin valide ou rejette (motif obligatoire). Valider fige le rapport : il
 * devient la source de vérité de la facture parent et du bulletin de paie.
 * Rejeter rend le rapport à son enseignant, qui devra le re-soumettre — il ne
 * peut plus être validé tel quel.
 *
 * Les boutons ne s'affichent que pour les transitions que le serveur autorise
 * (`data.actions`) ; le statut « valide » n'offre donc qu'un PDF, garantissant
 * qu'il n'y a pas de second chemin de modification.
 */
@Component({
  imports: [FormsModule, NgFor],
  selector: 'espace-rapports-admin',
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
      .badge-soumis { background: var(--mpc-jaune-doux); color: #6b4f00; }
      .badge-valide { background: var(--mpc-vert-pale); color: #0a5d35; }
      .badge-rejete { background: var(--mpc-rouge-clair); color: #7a1d1d; }
      .motif { margin-top: 0.35rem; font-size: 0.78rem; color: #7a1d1d; }
      .montant { font-variant-numeric: tabular-nums; white-space: nowrap; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .motif-saisie textarea { min-height: 110px; }
      .lignes-mini { font-size: 0.82rem; }
      .lignes-mini tr:last-child td { border-bottom: none; }
      .en-tete-criteres { font-size: 0.74rem; color: var(--mpc-texte-doux); border-bottom: 1px dashed; padding-bottom: 0.4rem; margin-bottom: 0.6rem; }
    `,
  ],
  template: `
    <section class="entete">
      <div>
        <h1>Rapports mensuels</h1>
        <p>
          Valider un rapport fige les heures de l'enseignant : la facture parent
          et le bulletin de paie s'en nourrissent. Rejeter (motif obligatoire)
          le renvoie à l'enseignant pour correction.
        </p>
      </div>
    </section>

    <section class="filtres">
      <select class="form-select form-select-sm w-auto" [ngModel]="filtres().periode_id" (ngModelChange)="filtres.update(f => ({ ...f, periode_id: $event })); charger()">
        <option [ngValue]="null">Toutes les périodes</option>
        <option *ngFor="let p of periodes()" [ngValue]="p.id">{{ p.label }}</option>
      </select>
      <select class="form-select form-select-sm w-auto" [ngModel]="filtres().statut" (ngModelChange)="filtres.update(f => ({ ...f, statut: $event })); charger()">
        <option value="">Tous les statuts</option>
        <option value="soumis">Soumis</option>
        <option value="valide">Validé</option>
        <option value="rejete">Rejeté</option>
      </select>
      <form class="d-flex gap-2" (ngSubmit)="rechercher()">
        <input class="form-control form-control-sm" style="min-width: 220px"
               placeholder="Enseignant ou élève…" [(ngModel)]="recherche" name="recherche" />
        <button class="btn btn-outline-mpc btn-sm" type="submit"><i class="bi bi-search"></i></button>
      </form>
    </section>

    @if (alerte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }

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
              <th>Enseignant</th><th>Élève</th><th>Période</th><th class="text-end">Heures</th>
              <th class="text-end">Montant estimé</th><th>Statut</th><th></th>
            </tr>
          </thead>
          <tbody>
            @for (r of rapports(); track r.id) {
              <tr>
                <td class="fw-semibold">{{ r.enseignant?.nom ?? '—' }}</td>
                <td>{{ r.eleve?.nom ?? '—' }}</td>
                <td>{{ r.periode?.label }}</td>
                <td class="text-end montant">{{ chiffre(r.total_heures_lignes) }} h</td>
                <td class="text-end montant">{{ monnaie(r.montant_estime) }}</td>
                <td>
                  <span class="badge-statut badge-{{ r.statut }}">{{ libelleStatut(r.statut) }}</span>
                  @if (r.motif_rejet) { <div class="motif"><i class="bi bi-chat-left-quote"></i> {{ r.motif_rejet }}</div> }
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-mpc" (click)="ouvrirDetail(r)" title="Consulter"><i class="bi bi-eye"></i></button>
                    @if (r.actions.includes('valider')) {
                      <button class="btn btn-outline-mpc text-success" (click)="valider(r)" title="Valider"><i class="bi bi-check-lg"></i></button>
                    }
                    @if (r.actions.includes('rejeter')) {
                      <button class="btn btn-outline-mpc text-danger" (click)="ouvrirRejet(r)" title="Rejeter"><i class="bi bi-x-lg"></i></button>
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
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                Rapport — {{ detail()!.enseignant?.nom }}
                <span class="badge-statut badge-{{ detail()!.statut }} ms-2">{{ libelleStatut(detail()!.statut) }}</span>
              </h5>
              <button type="button" class="btn-close" (click)="fermerDetail()"></button>
            </div>
            <div class="modal-body">
              @if (detail()!.motif_rejet) {
                <div class="alerte"><i class="bi bi-chat-left-quote"></i><div>Motif du rejet : {{ detail()!.motif_rejet }}</div></div>
              }
              <p class="text-doux-tiny mb-3">
                {{ detail()!.eleve?.nom }} · {{ detail()!.periode?.label }} ·
                {{ detail()!.type_cours?.libelle }} ·
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

              <div class="en-tete-criteres">Bilan des activités réalisées</div>
              @if (detail()!.bilan_activites) {
                <div class="mt-2 text-justify">{{ detail()!.bilan_activites }}</div>
              }

              @for (section of (detail()?.sections ?? []); track section.id) {
                <div class="en-tete-criteres mt-3">{{ section.libelle }}</div>
                @for (element of (section.elements ?? []); track element.id) {
                  <div class="mt-2">
                    <strong class="d-block">
                      {{ element.libelle }}
                      @if (element.obligatoire) { <span class="text-danger">*</span> }
                    </strong>
                    <div class="text-justify">
                      @if (element.reponse !== null && element.reponse !== undefined && element.reponse.trim() !== '') {
                        {{ element.reponse }}
                      } @else {
                        <span class="text-doux-tiny">Non renseigné.</span>
                      }
                    </div>
                  </div>
                }
              }
            </div>
            <div class="modal-footer">
              <span class="ms-auto me-2 fw-bold">Montant {{ monnaie(totalDetail()) }}</span>
              @if (detail()!.actions.includes('valider')) {
                <button type="button" class="btn btn-outline-mpc text-success" (click)="valider(detail()!)"><i class="bi bi-check-lg"></i> Valider</button>
              }
              @if (detail()!.actions.includes('rejeter')) {
                <button type="button" class="btn btn-outline-mpc text-danger" (click)="ouvrirRejet(detail()!)"><i class="bi bi-x-lg"></i> Rejeter</button>
              }
              @if (detail()!.actions.includes('pdf')) {
                <a class="btn btn-mpc" [href]="api.urlPdfRapport(detail()!.id)" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
              }
            </div>
          </div>
        </div>
      </div>
    }

    <!-- Modale rejet motivé -->
    @if (rejet()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event, 'rejet')">
        <div class="modal-dialog">
          <div class="modal-content motif-saisie">
            <div class="modal-header">
              <h5 class="modal-title">Rejeter le rapport de {{ rejet()!.enseignant?.nom ?? 'l' }}enseignant</h5>
              <button type="button" class="btn-close" (click)="fermerRejet()"></button>
            </div>
            <div class="modal-body">
              <div class="alert alert-warning small">
                L'enseignant devra corriger et <b>re-soumettre</b> : il ne pourra plus être validé tel quel.
              </div>
              <label class="form-label fw-semibold">Motif du rejet (obligatoire)</label>
              <textarea class="form-control" [(ngModel)]="motif" name="motif" placeholder="Décrire précisément ce qui bloque la validation…"></textarea>
              @if (erreurMotif()) { <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle"></i> {{ erreurMotif() }}</div> }
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" (click)="fermerRejet()">Annuler</button>
              <button type="button" class="btn btn-danger" (click)="confirmerRejet()" [disabled]="soumission()">
                @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                Rejeter
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class RapportsAdminComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected rapports = signal<RapportMensuel[]>([]);
  protected periodes = signal<PeriodeComptable[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected page = signal(1);
  protected chargement = signal(false);
  protected soumission = signal(false);
  protected alerte = signal('');
  protected detail = signal<RapportMensuel | null>(null);
  protected rejet = signal<RapportMensuel | null>(null);
  protected recherche = '';
  protected motif = '';
  protected erreurMotif = signal('');

  protected filtres = signal<{ periode_id: number | null; statut: string; search: string }>({
    periode_id: null,
    statut: '',
    search: '',
  });

  protected totalDetail = (): number =>
    (this.detail()?.lignes ?? []).reduce((s: number, l: RapportMensuelLigne) => s + l.montant_estime, 0);

  async ngOnInit(): Promise<void> {
    await Promise.all([this.charger(), this.chargerPeriodes()]);

    // Cible d'une notification : ouvre directement la fiche `…/{id}`.
    ouvrirDetailDepuisRoute(
      this.route,
      (id) => this.api.getRapportAdmin(id),
      (rapport) => this.ouvrirDetail(rapport),
      (erreur) => this.alerte.set(messageErreurApi(erreur))
    );
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');
    try {
      const r = await firstValueFrom(
        this.api.getRapportsAdmin(this.page(), 20, {
          periode_id: this.filtres().periode_id,
          statut: this.filtres().statut,
          search: this.filtres().search,
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

  private async chargerPeriodes(): Promise<void> {
    try {
      const r = await firstValueFrom(this.api.getPeriodes(1, 100));
      this.periodes.set(r.data);
    } catch {
      // Filtre par période indisponible au pire ; la liste reste consultable.
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

  protected ouvrirDetail(r: RapportMensuel): void {
    // Depuis la liste, le détail complet (avec `sections`) n'est pas chargé :
    // on le relit pour afficher le modèle de rapport renseigné.
    if (r.sections?.length) {
      this.detail.set(r);
      return;
    }

    void firstValueFrom(this.api.getRapportAdmin(r.id))
      .then((complet) => this.detail.set(complet))
      .catch((e) => this.alerte.set(messageErreurApi(e)));
  }

  protected fermerDetail(): void {
    this.detail.set(null);
  }

  protected ouvrirRejet(r: RapportMensuel): void {
    this.rejet.set(r);
    this.motif = '';
    this.erreurMotif.set('');
  }

  protected fermerRejet(): void {
    this.rejet.set(null);
  }

  protected async valider(r: RapportMensuel): Promise<void> {
    if (!confirm('Valider ce rapport ? Ses lignes engageront la facture et la paie.')) return;
    this.soumission.set(true);
    this.alerte.set('');
    try {
      await firstValueFrom(this.api.validerRapport(r.id));
      this.fermerDetail();
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async confirmerRejet(): Promise<void> {
    const cible = this.rejet();
    if (!cible) return;

    const motif = this.motif.trim();
    if (motif.length < 10) {
      this.erreurMotif.set('Le motif doit comporter au moins 10 caractères.');
      return;
    }

    this.soumission.set(true);
    this.erreurMotif.set('');
    this.alerte.set('');
    try {
      await firstValueFrom(this.api.rejeterRapport(cible.id, motif));
      this.fermerRejet();
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected siFond(ev: MouseEvent, quelle: 'detail' | 'rejet'): void {
    if (ev.target !== ev.currentTarget) return;
    if (quelle === 'detail') this.fermerDetail();
    else this.fermerRejet();
  }

  protected libelleStatut(s: string): string {
    return { soumis: 'Soumis', valide: 'Validé', rejete: 'Rejeté' }[s] ?? s;
  }

  protected chiffre(v: number | null | undefined): string {
    return Number(v ?? 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
  }

  protected monnaie(v: number | null | undefined): string {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(v ?? 0)) + ' FCFA';
  }
}