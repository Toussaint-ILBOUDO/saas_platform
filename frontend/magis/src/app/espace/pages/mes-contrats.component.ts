import { Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import { ContratCours, MetaPage, STATUTS_CONTRAT, StatutContrat } from '../../models';
import { ouvrirDetailDepuisRoute } from '../detail-de-route';

/**
 * Écran « Mes contrats » du parent (T7A.3).
 *
 * Strictement en lecture : un contrat est établi par l'administration, et sa
 * politique d'accès (`ContratCoursPolicy::view`) n'autorise déjà que l'admin,
 * le parent du contrat, l'enseignant affecté et l'élève concerné.
 *
 * C'est la destination des notifications de type `contrat` / `affectation`
 * : sans cet écran, le parent qui cliquait « Voir le contrat » atterrissait
 * sur le site public (la route `pedagogie/contrats/{id}` n'existe pas).
 */
@Component({
  imports: [RouterLink],
  selector: 'espace-mes-contrats',
  styles: [
    `
      :host { display: block; }
      .entete { margin-bottom: 1.1rem; }
      .entete h1 { margin: 0 0 0.2rem; font-size: 1.25rem; font-weight: 800; color: var(--mpc-bleu); }
      .entete p { margin: 0; color: var(--mpc-texte-doux); font-size: 0.86rem; max-width: 68ch; }
      .table-clair { width: 100%; font-size: 0.86rem; }
      .table-clair th { font-size: 0.72rem; letter-spacing: 0.04em; text-transform: uppercase; color: var(--mpc-texte-doux); white-space: nowrap; }
      .table-clair tbody tr { cursor: pointer; }
      .badge-statut { font-size: 0.74rem; font-weight: 700; padding: 0.28rem 0.55rem; border-radius: 99px; white-space: nowrap; }
      .badge-actif { background: var(--mpc-vert-pale); color: #0a5d35; }
      .badge-suspendu { background: var(--mpc-jaune-doux); color: #6b4f00; }
      .badge-termine { background: #e9ecef; color: #495057; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .lignes-mini { font-size: 0.85rem; }
      .lignes-mini tr:last-child td { border-bottom: none; }
      .vide { text-align: center; padding: 2.2rem 1rem; color: var(--mpc-texte-doux); }
      .pagination { display: flex; gap: 0.4rem; justify-content: flex-end; align-items: center; margin-top: 0.8rem; font-size: 0.85rem; }
      .pagination button:disabled { opacity: 0.45; }
      .totaux { display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem; margin-top: 0.9rem; font-size: 0.86rem; }
    `,
  ],
  template: `
    <section class="entete">
      <h1>Mes contrats de cours</h1>
      <p>
        Les contrats souscrits pour vos enfants : matières, enseignants et
        volumes horaires prévus. Cliquez sur une ligne pour le détail.
      </p>
    </section>

    @if (alerte()) {
      <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div>
    }

    @if (chargement()) {
      <div class="d-flex justify-content-center py-5"><div class="spinner-border text-mpc" role="status"></div></div>
    } @else if (contrats().length === 0) {
      <div class="bg-white rounded-4 border vide">
        <i class="bi bi-file-earmark-text fs-1"></i>
        <div class="mt-2">Aucun contrat de cours pour le moment.</div>
      </div>
    } @else {
      <div class="table-responsive bg-white rounded-4 border">
        <table class="table table-hover align-middle table-clair mb-0">
          <thead>
            <tr>
              <th>Contrat</th><th>Élève</th><th>Type de cours</th>
              <th>Début</th><th>Fin</th>
              <th class="text-end">Cours</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @for (c of contrats(); track c.id) {
              <tr (click)="ouvrirDetail(c)">
                <td class="num-contrat">#{{ c.id }}</td>
                <td>{{ c.eleve?.prenom }} {{ c.eleve?.nom }}</td>
                <td>{{ c.type_cours?.libelle ?? '—' }}</td>
                <td>{{ dateCourte(c.date_debut) }}</td>
                <td>{{ dateCourte(c.date_fin) }}</td>
                <td class="text-end">{{ c.affectations.length }}</td>
                <td><span class="badge-statut badge-{{ c.statut }}">{{ libelleStatut(c.statut) }}</span></td>
              </tr>
            }
          </tbody>
        </table>
      </div>

      @if (pages() > 1) {
        <div class="pagination">
          <button type="button" class="btn btn-sm btn-outline-secondary" [disabled]="page() <= 1" (click)="changerPage(page() - 1)">← Précédent</button>
          <span class="text-doux-tiny">Page {{ page() }} / {{ pages() }}</span>
          <button type="button" class="btn btn-sm btn-outline-secondary" [disabled]="page() >= pages()" (click)="changerPage(page() + 1)">Suivant →</button>
        </div>
      }
    }

    @if (detail(); as c) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event)">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                Contrat #{{ c.id }}
                <span class="badge-statut badge-{{ c.statut }} ms-2">{{ libelleStatut(c.statut) }}</span>
              </h5>
              <button type="button" class="btn-close" (click)="fermerDetail()" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
              <p class="text-doux-tiny mb-3">{{ resume(c) }}</p>

              <table class="table table-sm lignes-mini align-middle">
                <thead>
                  <tr>
                    <th>Matière</th><th>Enseignant</th>
                    <th class="text-end">Heures</th><th class="text-end">Taux/h</th>
                  </tr>
                </thead>
                <tbody>
                  @for (a of c.affectations; track a.id) {
                    <tr>
                      <td>{{ a.matiere?.nom ?? '—' }}</td>
                      <td>{{ a.enseignant ? a.enseignant.prenom + ' ' + a.enseignant.nom : '—' }}</td>
                      <td class="text-end">{{ chiffre(a.nombre_heures_prevues) }} h</td>
                      <td class="text-end">{{ monnaie(a.taux_horaire_enseignant) }}</td>
                    </tr>
                  }
                </tbody>
              </table>

              <div class="totaux">
                <div>Total heures — <b>{{ chiffre(totalHeures(c)) }} h</b></div>
              </div>
            </div>
            <div class="modal-footer">
              <a class="btn btn-outline-secondary btn-sm" routerLink="/espace/notifications">
                <i class="bi bi-bell me-1"></i>Notifications
              </a>
              <button type="button" class="btn btn-mpc" (click)="fermerDetail()">Fermer</button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class MesContratsComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected contrats = signal<ContratCours[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected page = signal(1);
  protected chargement = signal(false);
  protected alerte = signal('');
  protected detail = signal<ContratCours | null>(null);

  async ngOnInit(): Promise<void> {
    await this.charger();

    // Cible d'une notification : `mes-contrats/{id}` ouvre directement la fiche.
    ouvrirDetailDepuisRoute(
      this.route,
      (id) => this.api.getMesContrat(id),
      (contrat) => this.ouvrirDetail(contrat),
      (erreur) => this.alerte.set(messageErreurApi(erreur))
    );
  }

  protected pages(): number {
    return this.meta()?.last_page ?? 1;
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');
    try {
      const r = await firstValueFrom(this.api.getMesContrats(this.page(), 20));
      this.contrats.set(r.data);
      this.meta.set(r.meta ?? null);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.chargement.set(false);
    }
  }

  protected changerPage(p: number): void {
    if (p < 1) return;
    this.page.set(p);
    void this.charger();
  }

  protected ouvrirDetail(c: ContratCours): void {
    this.detail.set(c);
  }

  protected fermerDetail(): void {
    this.detail.set(null);
  }

  protected siFond(ev: MouseEvent): void {
    if (ev.target === ev.currentTarget) this.fermerDetail();
  }

  protected libelleStatut(statut: StatutContrat): string {
    return STATUTS_CONTRAT.find((s) => s.valeur === statut)?.libelle ?? statut;
  }

  protected totalHeures(c: ContratCours): number {
    return c.affectations.reduce((total, a) => total + (a.nombre_heures_prevues || 0), 0);
  }

  /** Une ligne « Élève · Type de cours · du … au … », sans `null` ni « undefined ». */
  protected resume(c: ContratCours): string {
    const enfant = [c.eleve?.prenom, c.eleve?.nom].filter(Boolean).join(' ');
    const periode = `du ${this.dateCourte(c.date_debut)}${
      c.date_fin ? ' au ' + this.dateCourte(c.date_fin) : ''
    }`;

    return [enfant, c.type_cours?.libelle, periode].filter(Boolean).join(' · ');
  }

  protected dateCourte(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  protected chiffre(valeur: number | null | undefined): string {
    return Number(valeur ?? 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
  }

  protected monnaie(valeur: number | null | undefined): string {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(valeur ?? 0)) + ' FCFA';
  }
}
