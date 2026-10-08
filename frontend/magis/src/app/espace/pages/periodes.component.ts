import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import { CreneauPeriode, MetaPage, PeriodeComptable, TypePeriode } from '../../models';

const TYPES: { valeur: TypePeriode; libelle: string; aide: string }[] = [
  { valeur: 'mensuel', libelle: 'Mensuel', aide: 'Une période par mois — le plus courant pour un cabinet de cours.' },
  { valeur: 'trimestriel', libelle: 'Trimestriel', aide: 'Une période par trimestre (période scolaire).' },
  { valeur: 'annuel', libelle: 'Annuel', aide: 'Une période par année civile.' },
];

const MODEL_VIDE: CreneauPeriode = {
  label: '',
  date_debut: '',
  date_fin: '',
  type: 'mensuel',
};

/** Mois en toutes lettres pour l'affichage des bornes (format ISO en base). */
const MOIS = [
  'janvier',
  'février',
  'mars',
  'avril',
  'mai',
  'juin',
  'juillet',
  'août',
  'septembre',
  'octobre',
  'novembre',
  'décembre',
];

/**
 * Écran admin « Périodes comptables » (T7A.1).
 *
 * Une période est un **cadre d'écriture** : tout ce qui est saisi dedans
 * (séances, rapports, factures, bulletins) gèle à la clôture. L'écran est donc
 * construit autour de cette idée — l'état de la période est l'information
 * principale, et les actions disponibles en découlent plutôt que l'inverse.
 */
@Component({
  imports: [FormsModule],
  selector: 'espace-periodes',
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
        max-width: 62ch;
      }
      .btn-primaire {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1.05rem;
        border: none;
        border-radius: 0.8rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        white-space: nowrap;
      }
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-primaire:disabled {
        opacity: 0.55;
        cursor: not-allowed;
      }

      /* ---------- Bandeau : la période ouverte en cours ---------- */
      .ouverte {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-wrap: wrap;
        padding: 0.95rem 1.05rem;
        border-radius: 1rem;
        margin-bottom: 1.1rem;
        background: var(--mpc-succes-tint);
        border: 1px solid var(--mpc-succes);
      }
      .ouverte i {
        font-size: 1.3rem;
        color: var(--mpc-succes);
      }
      .ouverte b {
        display: block;
        color: var(--mpc-bleu);
        font-size: 0.98rem;
      }
      .ouverte span {
        font-size: 0.8rem;
        color: var(--mpc-texte-doux);
      }
      .aucune-ouverte {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-wrap: wrap;
        padding: 0.95rem 1.05rem;
        border-radius: 1rem;
        margin-bottom: 1.1rem;
        background: var(--mpc-attention-tint);
        border: 1px solid var(--mpc-attention);
      }
      .aucune-ouverte i {
        font-size: 1.3rem;
        color: var(--mpc-attention);
      }
      .aucune-ouverte b {
        display: block;
        color: var(--mpc-bleu);
        font-size: 0.98rem;
      }
      .aucune-ouverte span {
        font-size: 0.8rem;
        color: var(--mpc-texte-doux);
      }

      /* ---------- Liste ---------- */
      .liste {
        display: grid;
        gap: 0.7rem;
      }
      .ligne {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        flex-wrap: wrap;
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
      }
      .ligne._ouverte {
        border-color: var(--mpc-succes);
      }
      .pastille-type {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 0.85rem;
        font-weight: 800;
        font-size: 0.7rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
      }
      .detail {
        flex: 1;
        min-width: 190px;
      }
      .detail b {
        display: block;
        font-size: 0.95rem;
        color: var(--mpc-bleu);
      }
      .detail small {
        display: block;
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
      }
      .detail .cloture {
        font-size: 0.74rem;
        color: var(--mpc-texte-doux);
        margin-top: 0.2rem;
        display: block;
      }
      .badges {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
      }
      .badge {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: var(--mpc-abandon);
        color: var(--mpc-texte-doux);
        white-space: nowrap;
      }
      .badge._ouverte {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .badge._cloturee {
        background: var(--mpc-ordre-tint);
        color: var(--mpc-ordre);
      }
      .actions {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
      }
      .mini {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.42rem 0.7rem;
        border-radius: 0.65rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
      }
      .mini:hover:not(:disabled) {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .mini:disabled {
        opacity: 0.45;
        cursor: not-allowed;
      }
      .mini._cloture {
        border-color: var(--mpc-danger);
        color: var(--mpc-danger);
      }
      .mini._cloture:hover:not(:disabled) {
        background: var(--mpc-danger);
        border-color: var(--mpc-danger);
        color: #fff;
      }

      /* ---------- États ---------- */
      .alerte {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.8rem 0.95rem;
        border-radius: 0.9rem;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .succes {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.8rem 0.95rem;
        border-radius: 0.9rem;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .vide {
        padding: 2.6rem 1rem;
        text-align: center;
        color: var(--mpc-texte-doux);
        border: 1px dashed var(--mpc-separateur);
        border-radius: 1rem;
        background: var(--mpc-surface);
      }
      .vide i {
        font-size: 1.5rem;
        display: block;
        margin-bottom: 0.5rem;
        opacity: 0.55;
      }

      .pagination {
        display: flex;
        justify-content: center;
        gap: 0.4rem;
        margin-top: 1.3rem;
      }
      .page {
        min-width: 40px;
        height: 40px;
        padding: 0 0.6rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-size: 0.86rem;
        font-weight: 700;
        cursor: pointer;
      }
      .page._courante {
        background: var(--mpc-primaire);
        border-color: var(--mpc-primaire);
        color: #fff;
      }
      .page:disabled {
        opacity: 0.4;
        cursor: not-allowed;
      }

      /* ---------- Modale ---------- */
      .fenetre {
        position: fixed;
        inset: 0;
        z-index: 90;
        display: grid;
        place-items: center;
        padding: 1rem;
        background: rgba(10, 14, 26, 0.55);
        overflow-y: auto;
      }
      .panneau {
        width: 100%;
        max-width: 540px;
        border-radius: 1.1rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
        padding: 1.3rem 1.4rem;
      }
      .panneau-entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
      }
      .panneau-entete h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .fermer {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border: none;
        border-radius: 0.7rem;
        background: var(--mpc-abandon);
        color: var(--mpc-texte);
        cursor: pointer;
      }
      .formulaire {
        display: grid;
        gap: 0.85rem;
      }
      .champ {
        display: grid;
        gap: 0.3rem;
      }
      .champ > span {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--mpc-texte-doux);
      }
      .champ input,
      .champ select {
        padding: 0.6rem 0.75rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.88rem;
        outline: none;
      }
      .champ input:focus,
      .champ select:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .champ._erreur input,
      .champ._erreur select {
        border-color: var(--mpc-danger);
      }
      .erreur {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--mpc-danger);
      }
      .aide {
        font-size: 0.75rem;
        color: var(--mpc-texte-doux);
      }
      .grille {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
      }
      .avertissement {
        display: flex;
        gap: 0.5rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.7rem;
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
        font-size: 0.78rem;
        font-weight: 600;
      }
      .actions-form {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 0.35rem;
      }
      .btn-neutral {
        padding: 0.55rem 0.95rem;
        border-radius: 0.75rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
      }

      .spin {
        display: inline-block;
        animation: tourner 0.9s linear infinite;
      }
      @keyframes tourner {
        to {
          transform: rotate(360deg);
        }
      }

      @media (max-width: 560px) {
        .grille {
          grid-template-columns: 1fr;
        }
        .actions {
          width: 100%;
        }
        .mini {
          flex: 1;
          justify-content: center;
        }
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>Périodes comptables</h1>
        <p>
          Chaque période délimite ce qui peut être saisi : séances, rapports mensuels, factures et bulletins de
          paie. La clôture gèle les écritures de la période.
        </p>
      </div>
      <button class="btn-primaire" type="button" (click)="ouvrirCreation()">
        <i class="bi bi-plus-lg"></i> Nouvelle période
      </button>
    </div>

    @if (message()) {
      <div class="alerte"><i class="bi bi-exclamation-triangle"></i><span>{{ message() }}</span></div>
    }
    @if (succes()) {
      <div class="succes"><i class="bi bi-check-circle"></i><span>{{ succes() }}</span></div>
    }

    @if (!charge()) {
      @if (periodeOuverte(); as ouverte) {
        <div class="ouverte">
          <i class="bi bi-unlock"></i>
          <div>
            <b>{{ ouverte.label }}</b>
            <span>Période ouverte · {{ libelleBornes(ouverte.date_debut, ouverte.date_fin) }}</span>
          </div>
        </div>
      } @else {
        <div class="aucune-ouverte">
          <i class="bi bi-lock"></i>
          <div>
            <b>Aucune période ouverte</b>
            <span>Les rapports mensuels ne peuvent être déposés que sur une période ouverte. Créez-en une.</span>
          </div>
        </div>
      }
    }

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else if (periodes().length === 0) {
      <div class="vide"><i class="bi bi-calendar-range"></i> Aucune période comptable créée.</div>
    } @else {
      <div class="liste">
        @for (p of periodes(); track p.id) {
          <article class="ligne" [class._ouverte]="p.est_ouverte">
            <span class="pastille-type">{{ sigleType(p.type) }}</span>
            <div class="detail">
              <b>{{ p.label }}</b>
              <small>{{ libelleBornes(p.date_debut, p.date_fin) }}</small>
              @if (p.est_cloturee) {
                <span class="cloture">
                  <i class="bi bi-lock"></i>
                  Clôturée{{ p.cloturee_par_nom ? ' par ' + p.cloturee_par_nom : '' }}{{ p.cloturee_at ? ' le ' + dateCourte(p.cloturee_at) : '' }}
                </span>
              }
            </div>
            <div class="badges">
              <span class="badge" [class._ouverte]="p.est_ouverte" [class._cloturee]="p.est_cloturee">
                {{ p.est_ouverte ? 'Ouverte' : 'Clôturée' }}
              </span>
              <span class="badge">{{ libelleType(p.type) }}</span>
            </div>
            <div class="actions">
              <button
                class="mini"
                type="button"
                [disabled]="p.est_cloturee"
                [title]="p.est_cloturee ? 'Une période clôturée est gelée' : ''"
                (click)="ouvrirEdition(p)"
              >
                <i class="bi bi-pencil"></i> Modifier
              </button>
              @if (p.est_ouverte) {
                <button class="mini _cloture" type="button" [disabled]="actionEnCours()" (click)="cloturer(p)">
                  <i class="bi bi-lock"></i> Clôturer
                </button>
              } @else {
                <button class="mini" type="button" [disabled]="actionEnCours()" (click)="rouvrir(p)">
                  <i class="bi bi-unlock"></i> Rouvrir
                </button>
              }
            </div>
          </article>
        }
      </div>

      @if ((meta()?.last_page ?? 1) > 1) {
        <nav class="pagination" aria-label="Pagination des périodes">
          <button class="page" type="button" [disabled]="(meta()?.current_page ?? 1) <= 1" (click)="aller((meta()?.current_page ?? 1) - 1)">
            <i class="bi bi-chevron-left"></i>
          </button>
          <span class="page _courante">{{ meta()?.current_page }} / {{ meta()?.last_page }}</span>
          <button class="page" type="button" [disabled]="(meta()?.current_page ?? 1) >= (meta()?.last_page ?? 1)" (click)="aller((meta()?.current_page ?? 1) + 1)">
            <i class="bi bi-chevron-right"></i>
          </button>
        </nav>
      }
    }

    @if (formulaireOuvert()) {
      <div class="fenetre" (click)="fermerFormulaire()">
        <div class="panneau" (click)="$event.stopPropagation()">
          <div class="panneau-entete">
            <h2>{{ modele().id ? 'Modifier la période' : 'Nouvelle période comptable' }}</h2>
            <button class="fermer" type="button" (click)="fermerFormulaire()" aria-label="Fermer">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="formulaire">
            <label class="champ" [class._erreur]="!!erreurs()['label']">
              <span>Intitulé *</span>
              <input [ngModel]="modele().label" (ngModelChange)="setLabel($event)" placeholder="Octobre 2026" />
              @if (erreurs()['label']; as e) {
                <span class="erreur">{{ e }}</span>
              }
            </label>

            <div class="grille">
              <label class="champ" [class._erreur]="!!erreurs()['date_debut']">
                <span>Du *</span>
                <input type="date" [ngModel]="modele().date_debut" (ngModelChange)="setDebut($event)" />
                @if (erreurs()['date_debut']; as e) {
                  <span class="erreur">{{ e }}</span>
                }
              </label>
              <label class="champ" [class._erreur]="!!erreurs()['date_fin']">
                <span>Au *</span>
                <input type="date" [ngModel]="modele().date_fin" (ngModelChange)="setFin($event)" />
                @if (erreurs()['date_fin']; as e) {
                  <span class="erreur">{{ e }}</span>
                }
              </label>
            </div>

            <label class="champ">
              <span>Type de période</span>
              <select [ngModel]="modele().type" (ngModelChange)="setType($event)">
                @for (t of types; track t.valeur) {
                  <option [value]="t.valeur">{{ t.libelle }}</option>
                }
              </select>
              <span class="aide">{{ aideType() }}</span>
            </label>

            <div class="avertissement">
              <i class="bi bi-info-circle"></i>
              <span>
                Une période naît <b>ouverte</b>. Sa clôture est une action séparée, volontaire, et elle gèle toutes
                les écritures qu’elle contient.
              </span>
            </div>

            <div class="actions-form">
              <button class="btn-neutral" type="button" (click)="fermerFormulaire()">Annuler</button>
              <button class="btn-primaire" type="button" (click)="enregistrer()" [disabled]="sauvegarde()">
                @if (sauvegarde()) {
                  <i class="bi bi-arrow-repeat spin"></i> Enregistrement…
                } @else {
                  <i class="bi bi-check2"></i> Enregistrer
                }
              </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class PeriodesComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly types = TYPES;

  protected readonly periodes = signal<PeriodeComptable[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly charge = signal(true);
  protected readonly message = signal('');
  protected readonly succes = signal('');
  protected readonly actionEnCours = signal(false);

  protected readonly formulaireOuvert = signal(false);
  protected readonly sauvegarde = signal(false);
  protected readonly modele = signal<CreneauPeriode & { id: number | null }>({ id: null, ...MODEL_VIDE });
  protected readonly erreurs = signal<Record<string, string>>({});

  /** La période dans laquelle les enseignants peuvent actuellement déposer. */
  protected readonly periodeOuverte = computed(() => this.periodes().find((p) => p.est_ouverte) ?? null);

  protected readonly aideType = computed(
    () => TYPES.find((t) => t.valeur === this.modele().type)?.aide ?? '',
  );

  ngOnInit(): void {
    this.charger(1);
  }

  /* ---------- Aides d'affichage ---------- */

  protected libelleType(type: TypePeriode): string {
    return TYPES.find((t) => t.valeur === type)?.libelle ?? type;
  }

  protected sigleType(type: TypePeriode): string {
    switch (type) {
      case 'trimestriel':
        return 'Trim';
      case 'annuel':
        return 'An';
      default:
        return 'Mois';
    }
  }

  /** « 1er octobre 2026 au 31 octobre 2026 » — lisible, là où l'ISO ne l'est pas. */
  protected libelleBornes(debut: string, fin: string): string {
    const format = (iso: string): string => {
      if (!iso) return '';
      const [a, m, j] = iso.slice(0, 10).split('-').map(Number);
      if (!a || !m || !j) return iso;
      return `${j === 1 ? '1er' : j} ${MOIS[m - 1]} ${a}`;
    };
    const d = format(debut);
    const f = format(fin);
    if (!d || !f) return '';
    return d === f ? d : `${d} au ${f}`;
  }

  protected dateCourte(iso: string): string {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  /* ---------- Données ---------- */

  private async charger(page: number): Promise<void> {
    this.charge.set(true);
    try {
      const reponse = await firstValueFrom(this.api.getPeriodes(page, 15));
      this.periodes.set(reponse.data ?? []);
      this.meta.set(reponse.meta ?? null);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.charge.set(false);
    }
  }

  protected aller(page: number): void {
    this.charger(page);
  }

  /* ---------- Formulaire ---------- */

  protected ouvrirCreation(): void {
    this.modele.set({ id: null, ...MODEL_VIDE });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected ouvrirEdition(p: PeriodeComptable): void {
    this.modele.set({
      id: p.id,
      label: p.label,
      date_debut: p.date_debut,
      date_fin: p.date_fin,
      type: p.type,
    });
    this.erreurs.set({});
    this.formulaireOuvert.set(true);
  }

  protected fermerFormulaire(): void {
    this.formulaireOuvert.set(false);
  }

  protected setLabel(valeur: string): void {
    this.modele.set({ ...this.modele(), label: valeur });
    this.effacerErreur('label');
  }

  protected setDebut(valeur: string): void {
    this.modele.set({ ...this.modele(), date_debut: valeur });
    this.effacerErreur('date_debut');
  }

  protected setFin(valeur: string): void {
    this.modele.set({ ...this.modele(), date_fin: valeur });
    this.effacerErreur('date_fin');
  }

  protected setType(valeur: TypePeriode): void {
    this.modele.set({ ...this.modele(), type: valeur });
  }

  protected async enregistrer(): Promise<void> {
    this.message.set('');
    this.succes.set('');
    this.erreurs.set({});

    const m = this.modele();
    if (!m.label.trim()) {
      this.erreurs.set({ label: 'L’intitulé est obligatoire.' });
      return;
    }
    if (!m.date_debut || !m.date_fin) {
      this.erreurs.set({
        [m.date_debut ? 'date_fin' : 'date_debut']: 'Les deux dates sont obligatoires.',
      });
      return;
    }
    if (m.date_fin < m.date_debut) {
      this.erreurs.set({ date_fin: 'La date de fin doit être postérieure à la date de début.' });
      return;
    }

    this.sauvegarde.set(true);
    try {
      const payload: CreneauPeriode = {
        label: m.label.trim(),
        date_debut: m.date_debut,
        date_fin: m.date_fin,
        type: m.type,
      };
      if (m.id) {
        await firstValueFrom(this.api.majPeriode(m.id, payload));
      } else {
        await firstValueFrom(this.api.creerPeriode(payload));
      }
      this.formulaireOuvert.set(false);
      this.succes.set(m.id ? 'Période mise à jour.' : 'Période créée, elle est ouverte.');
      await this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      const champs = (e as { error?: { erreurs?: Record<string, string[]> } })?.error?.erreurs;
      if (champs) {
        const aplat: Record<string, string> = {};
        for (const champ of Object.keys(champs)) {
          if (champs[champ]?.[0]) aplat[champ] = champs[champ][0];
        }
        this.erreurs.set(aplat);
        if (!Object.keys(aplat).length) this.message.set(messageErreurApi(e));
      } else {
        this.message.set(messageErreurApi(e));
      }
    } finally {
      this.sauvegarde.set(false);
    }
  }

  /* ---------- Clôture / réouverture (D-051) ---------- */

  protected async cloturer(p: PeriodeComptable): Promise<void> {
    if (
      !confirm(
        `Clôturer « ${p.label} » ?\n\nToutes les écritures de cette période (séances, rapports, factures, bulletins) seront gelées. Vous pourrez la rouvrir tant qu'aucune écriture n'y aura été produite.`,
      )
    ) {
      return;
    }
    await this.executerAction(
      () => firstValueFrom(this.api.cloturerPeriode(p.id)),
      `« ${p.label} » est clôturée.`,
    );
  }

  protected async rouvrir(p: PeriodeComptable): Promise<void> {
    if (
      !confirm(
        `Rouvrir « ${p.label} » ?\n\nLa période redevient modifiable. Cette action est refusée si des écritures financières y ont déjà été produites.`,
      )
    ) {
      return;
    }
    await this.executerAction(
      () => firstValueFrom(this.api.rouvrirPeriode(p.id)),
      `« ${p.label} » est rouverte.`,
    );
  }

  private async executerAction(appel: () => Promise<unknown>, succes: string): Promise<void> {
    this.message.set('');
    this.succes.set('');
    this.actionEnCours.set(true);
    try {
      await appel();
      this.succes.set(succes);
      await this.charger(this.meta()?.current_page ?? 1);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.actionEnCours.set(false);
    }
  }

  private effacerErreur(champ: string): void {
    const courantes = this.erreurs();
    if (!courantes[champ]) return;
    const suivante = { ...courantes };
    delete suivante[champ];
    this.erreurs.set(suivante);
  }
}