import { Component, OnInit, inject, signal } from '@angular/core';
import { KeyValuePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { EntreeAudit, FiltresAudit, MetaPage } from '../../models';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';

/** Libellés lisibles des actions déjà journalisées (repli : la clé brute). */
const LIBELLES_ACTIONS: Record<string, string> = {
  'facture.emise': 'Facture émise',
  'facture.reglee': 'Facture réglée',
  'bulletin.genere': 'Bulletin généré',
  'bulletin.valide': 'Bulletin validé',
  'bulletin.verse': 'Bulletin versé',
  'periode.cloturee': 'Période clôturée',
  'periode.reouverte': 'Période réouverte',
  'rapport.valide': 'Rapport validé',
  'rapport.rejete': 'Rapport rejeté',
  'contrat.statut': 'Changement de statut de contrat',
  'utilisateur.suspendu': 'Utilisateur suspendu',
  'utilisateur.active': 'Utilisateur réactivé',
  'document.valide': 'Document validé',
  'document.refuse': 'Document refusé',
  'document.archive': 'Document archivé',
};

/**
 * Écran admin « Journal d'audit » (M13).
 *
 * Lecture seule : chaque action sensible du cabinet (factures, bulletins,
 * périodes, contrats, utilisateurs, modération) y laisse une trace horodatée
 * avec son auteur. Les filtres (action, recherche, fenêtre de dates) remontent
 * vers le serveur — la table peut grandir, on ne télécharge pas tout.
 */
@Component({
  imports: [FormsModule, KeyValuePipe],
  selector: 'espace-audit',
  styles: [
    `
      :host {
        display: block;
      }

      .entete {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.1rem;
      }
      .entete h1 {
        margin: 0 0 0.2rem;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .entete p {
        margin: 0;
        color: var(--mpc-texte-doux);
        font-size: 0.86rem;
        max-width: 70ch;
      }

      .filtres {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        align-items: flex-end;
        padding: 0.85rem 1rem;
        border: 1px solid var(--mpc-bordure, #e3e8f0);
        border-radius: 1rem;
        background: var(--mpc-fond, #f8fafc);
        margin-bottom: 1rem;
      }
      .filtres label {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--mpc-texte-doux);
        margin-bottom: 0.25rem;
      }
      .filtres input,
      .filtres select {
        min-width: 11rem;
        padding: 0.5rem 0.65rem;
        border: 1px solid var(--mpc-bordure, #d6deea);
        border-radius: 0.6rem;
        font-size: 0.86rem;
        background: #fff;
      }
      .filtres .actions-filtres {
        display: flex;
        gap: 0.5rem;
      }
      .btn-appliquer {
        padding: 0.55rem 1rem;
        border: none;
        border-radius: 0.65rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.84rem;
        cursor: pointer;
      }
      .btn-appliquer:hover {
        background: var(--mpc-primaire-fonce);
      }
      .btn-reinit {
        padding: 0.55rem 0.9rem;
        border: 1px solid var(--mpc-bordure, #d6deea);
        border-radius: 0.65rem;
        background: #fff;
        font-size: 0.84rem;
        cursor: pointer;
      }

      .cadre {
        border: 1px solid var(--mpc-bordure, #e3e8f0);
        border-radius: 1rem;
        overflow: hidden;
        background: #fff;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
      }
      th {
        text-align: left;
        padding: 0.7rem 0.85rem;
        background: var(--mpc-fond, #f8fafc);
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--mpc-texte-doux);
        border-bottom: 1px solid var(--mpc-bordure, #e3e8f0);
      }
      td {
        padding: 0.65rem 0.85rem;
        border-bottom: 1px solid var(--mpc-bordure, #eef2f7);
        vertical-align: top;
      }
      tr:last-child td {
        border-bottom: none;
      }
      .date {
        white-space: nowrap;
        color: var(--mpc-texte-doux);
        font-size: 0.8rem;
      }
      .code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
      }
      .badge-info,
      .badge-warning {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
      }
      .badge-info {
        background: #e0f2fe;
        color: #0369a1;
      }
      .badge-warning {
        background: #fef3c7;
        color: #b45309;
      }
      .vide,
      .chargement {
        padding: 1.6rem 1rem;
        text-align: center;
        color: var(--mpc-texte-doux);
        font-size: 0.88rem;
      }
      .alerte {
        margin-bottom: 0.9rem;
        padding: 0.7rem 0.95rem;
        border-radius: 0.75rem;
        background: #fee2e2;
        color: #b91c1c;
        font-size: 0.85rem;
      }
      details.contexte summary {
        cursor: pointer;
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
      }
      details.contexte pre {
        margin: 0.4rem 0 0;
        padding: 0.5rem 0.65rem;
        background: var(--mpc-fond, #f8fafc);
        border-radius: 0.5rem;
        font-size: 0.75rem;
        overflow-x: auto;
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>Journal d'audit</h1>
        <p>
          Trace horodatée des actions sensibles du cabinet : factures, bulletins, périodes,
          contrats, comptes et modération. Lecture seule — les entrées sont écrites par le
          moteur métier, jamais à la main.
        </p>
      </div>
    </div>

    @if (message()) {
      <div class="alerte">{{ message() }}</div>
    }

    <div class="filtres">
      <div>
        <label for="filtre-action">Action</label>
        <select
          id="filtre-action"
          [ngModel]="actionFiltre()"
          (ngModelChange)="actionFiltre.set($event)"
        >
          <option value="">Toutes</option>
          @for (a of actions(); track a) {
            <option [value]="a">{{ libelleAction(a) }}</option>
          }
        </select>
      </div>
      <div>
        <label for="filtre-q">Recherche</label>
        <input
          id="filtre-q"
          type="search"
          placeholder="Facture, contrat, email…"
          [ngModel]="qFiltre()"
          (ngModelChange)="qFiltre.set($event)"
          (keyup.enter)="rechercher()"
        />
      </div>
      <div>
        <label for="filtre-du">Du</label>
        <input
          id="filtre-du"
          type="date"
          [ngModel]="duFiltre()"
          (ngModelChange)="duFiltre.set($event)"
        />
      </div>
      <div>
        <label for="filtre-au">Au</label>
        <input
          id="filtre-au"
          type="date"
          [ngModel]="auFiltre()"
          (ngModelChange)="auFiltre.set($event)"
        />
      </div>
      <div class="actions-filtres">
        <button type="button" class="btn-appliquer" (click)="rechercher()">Rechercher</button>
        <button type="button" class="btn-reinit" (click)="reinitialiser()">Réinitialiser</button>
      </div>
    </div>

    <div class="cadre">
      @if (charge()) {
        <div class="chargement">Chargement…</div>
      } @else if (entrees().length === 0) {
        <div class="vide">Aucune activité pour ces critères.</div>
      } @else {
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Niveau</th>
              <th>Action</th>
              <th>Description</th>
              <th>Auteur</th>
              <th>Entité</th>
              <th>Contexte</th>
            </tr>
          </thead>
          <tbody>
            @for (e of entrees(); track e.id) {
              <tr>
                <td class="date">{{ dateCourte(e.date) }}</td>
                <td>
                  <span [class]="e.level === 'warning' ? 'badge-warning' : 'badge-info'">
                    {{ e.level === 'warning' ? 'Alerte' : 'Info' }}
                  </span>
                </td>
                <td>
                  <div>{{ libelleAction(e.action) }}</div>
                  <div class="code">{{ e.action }}</div>
                </td>
                <td>{{ e.description }}</td>
                <td>
                  @if (e.utilisateur?.nom_complet) {
                    <div>{{ e.utilisateur!.nom_complet }}</div>
                    <div class="code">{{ e.utilisateur!.email }}</div>
                  } @else {
                    <span class="code">Système</span>
                  }
                </td>
                <td>
                  @if (e.entite) {
                    <span class="code">{{ e.entite.type }} #{{ e.entite.id }}</span>
                  } @else {
                    <span class="code">—</span>
                  }
                </td>
                <td>
                  @if (e.contexte && (e.contexte | keyvalue).length > 0) {
                    <details class="contexte">
                      <summary>voir</summary>
                      <pre>{{ serialiserContexte(e.contexte) }}</pre>
                    </details>
                  } @else {
                    <span class="code">—</span>
                  }
                </td>
              </tr>
            }
          </tbody>
        </table>
      }
    </div>

    @if (meta() && meta()!.last_page > 1) {
      <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
          <li class="page-item" [class.disabled]="page() <= 1">
            <button class="page-link" (click)="changerPage(page() - 1)">Précédent</button>
          </li>
          <li class="page-item disabled">
            <span class="page-link">Page {{ page() }} / {{ meta()!.last_page }}</span>
          </li>
          <li class="page-item" [class.disabled]="page() >= meta()!.last_page">
            <button class="page-link" (click)="changerPage(page() + 1)">Suivant</button>
          </li>
        </ul>
      </nav>
    }
  `,
})
export class AuditComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly entrees = signal<EntreeAudit[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly page = signal(1);
  protected readonly charge = signal(true);
  protected readonly message = signal('');
  protected readonly actions = signal<string[]>([]);

  protected readonly actionFiltre = signal('');
  protected readonly qFiltre = signal('');
  protected readonly duFiltre = signal('');
  protected readonly auFiltre = signal('');

  ngOnInit(): void {
    void this.charger();
    void this.chargerActions();
  }

  protected libelleAction(action: string): string {
    return LIBELLES_ACTIONS[action] ?? action;
  }

  protected dateCourte(iso: string): string {
    return new Date(iso).toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  protected serialiserContexte(contexte: Record<string, unknown>): string {
    return JSON.stringify(contexte, null, 2);
  }

  protected rechercher(): void {
    this.page.set(1);
    void this.charger();
  }

  protected reinitialiser(): void {
    this.actionFiltre.set('');
    this.qFiltre.set('');
    this.duFiltre.set('');
    this.auFiltre.set('');
    this.rechercher();
  }

  protected changerPage(p: number): void {
    this.page.set(p);
    void this.charger();
  }

  private filtres(): FiltresAudit {
    return {
      action: this.actionFiltre(),
      q: this.qFiltre(),
      du: this.duFiltre(),
      au: this.auFiltre(),
    };
  }

  private async charger(): Promise<void> {
    this.charge.set(true);
    this.message.set('');
    try {
      const r = await firstValueFrom(this.api.getAudit(this.page(), 25, this.filtres()));
      this.entrees.set(r.data);
      this.meta.set(r.meta ?? null);
    } catch (e) {
      this.message.set(messageErreurApi(e));
      this.entrees.set([]);
      this.meta.set(null);
    } finally {
      this.charge.set(false);
    }
  }

  private async chargerActions(): Promise<void> {
    try {
      this.actions.set(await firstValueFrom(this.api.getAuditActions()));
    } catch {
      /* le sélecteur reste vide : le filtre par action est un confort */
    }
  }
}
