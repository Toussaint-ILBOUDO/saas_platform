import { Component, OnInit, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { DetailFacturePlateforme, FacturePlateforme, MonContratPlateforme } from '../../models';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';

/** Code de tarif → libellé lisible pour le cabinet. */
const LIBELLES_TARIFS: Record<keyof MonContratPlateforme['tarifs'], string> = {
  frais_creation_contrat: 'Frais de création du contrat',
  frais_contrat_actif: 'Frais de contrat actif',
  abonnement_mensuel_enseignant: 'Abonnement mensuel — enseignant',
  abonnement_mensuel_parent: 'Abonnement mensuel — parent',
  abonnement_mensuel_eleve: 'Abonnement mensuel — élève',
  frais_rapport_mensuel: 'Frais par rapport mensuel validé',
  abonnement_annuel_admin: 'Abonnement annuel — admin du cabinet',
  maintenance_annuelle_site: 'Maintenance annuelle du site',
  taux_commission_librairie: 'Commission librairie (débit sur ventes)',
  taux_commission_autres: 'Commission autres services (%)',
};

const CODES_TAUX: Array<keyof MonContratPlateforme['tarifs']> = [
  'taux_commission_librairie',
  'taux_commission_autres',
];

const CATEGORIES_DEFAUT: Record<string, string> = {
  ouverture: "Frais d'ouverture du compte",
  abonnement: 'Abonnements mensuels',
  annuel: 'Services annuels',
  commission: 'Commissions',
  ponctuel: 'Services ponctuels',
};

const LIBELLES_STATUT: Record<string, string> = {
  emise: 'Émise',
  partiellement_payee: 'Partiellement réglée',
  payee: 'Réglée',
  brouillon: 'Brouillon',
  annulee: 'Annulée',
};

/**
 * Écran admin « Mon contrat » (P8) — lecture seule du contrat commercial
 * négocié avec la plateforme et des factures de service. Le super-admin
 * de la plateforme crée et valide le contrat ; le cabinet ne consulte pas
 * plus. null (non couvert) ≠ 0 (gratuit négocié) dans les montants (Q-A17).
 */
@Component({
  selector: 'espace-contrat-plateforme',
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

      .alerte {
        padding: 0.7rem 1rem;
        border-radius: 0.8rem;
        background: var(--mpc-rouge-doux, #feeff0);
        color: var(--mpc-rouge, #b3261e);
        font-size: 0.86rem;
        margin-bottom: 1.1rem;
      }

      .cadre {
        border: 1px solid var(--mpc-bordure, #e3e8f0);
        border-radius: 1rem;
        background: var(--mpc-fond, #f8fafc);
        padding: 0.95rem 1.05rem;
        margin-bottom: 1.1rem;
      }
      .titre-cadre {
        font-weight: 800;
        font-size: 0.95rem;
        color: var(--mpc-bleu);
        margin-bottom: 0.7rem;
      }
      .sous-titre {
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
        margin-bottom: 0.5rem;
      }

      .meta-liste {
        display: grid;
        gap: 0.4rem;
        font-size: 0.88rem;
      }
      .meta-liste .champ {
        color: var(--mpc-texte-doux);
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }

      .tableau {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
      }
      .tableau th,
      .tableau td {
        padding: 0.45rem 0.5rem;
        border-bottom: 1px solid var(--mpc-bordure, #eef2f8);
        text-align: left;
      }
      .tableau thead th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--mpc-texte-doux);
      }
      .tableau td.num {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
      }
      .non-couvert {
        color: var(--mpc-texte-doux);
        font-style: italic;
      }

      .badge {
        display: inline-block;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
      }
      .badge-info {
        background: #dbeafe;
        color: #1e40af;
      }
      .badge-ok {
        background: #dcfce7;
        color: #166534;
      }
      .badge-warn {
        background: #fef3c7;
        color: #92400e;
      }

      .titre-partie {
        margin: 1.1rem 0 0.4rem;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--mpc-bleu);
      }
      .description {
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
        margin-top: 0.15rem;
        max-width: 60ch;
      }

      .cond {
        margin-top: 0.8rem;
        font-size: 0.84rem;
        color: var(--mpc-texte-doux);
        white-space: pre-line;
      }

      .detail {
        margin-top: 0.9rem;
        border-top: 1px dashed var(--mpc-bordure, #cbd5e1);
        padding-top: 0.9rem;
      }

      .actions a,
      .btn-lien {
        color: var(--mpc-bleu);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.8rem;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
      }

      .vide {
        color: var(--mpc-texte-doux);
        font-size: 0.88rem;
      }

      .paiements {
        margin-top: 0.4rem;
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>Mon contrat</h1>
        <p>
          Contrat commercial négocié entre le cabinet et la plateforme, et factures de
          service correspondantes. Lecture seule : le contrat est créé et validé par la
          plateforme ; « — » signifie absence de convention (service jamais facturé),
          « 0 » une gratuité négociée.
        </p>
      </div>
    </div>

    @if (message()) {
      <div class="alerte">{{ message() }}</div>
    }

    @if (charge()) {
      <div class="cadre"><div class="vide">Chargement…</div></div>
    } @else if (contrat() === null) {
      <div class="cadre">
        <div class="vide">
          Aucun contrat commercial validé pour ce cabinet à ce jour. Contactez la plateforme :
          le contrat est établi et validé de son côté, puis apparaît ici.
        </div>
      </div>
    } @else {
      <div class="cadre">
        <div class="titre-cadre">
          Contrat {{ contrat()!.numero }} — version {{ contrat()!.version }}
        </div>

        <div class="meta-liste">
          <div>
            <span class="champ">Date d'effet</span>
            {{ dateCourte(contrat()!.date_effet) }}
            @if (contrat()!.date_fin_validite) {
              → {{ dateCourte(contrat()!.date_fin_validite) }}
            }
          </div>
          <div>
            <span class="champ">Validé par la plateforme</span>
            {{ dateCourte(contrat()!.valide_le) }}
          </div>
          @if (contrat()!.responsable_nom) {
            <div>
              <span class="champ">Responsable côté cabinet</span>
              {{ contrat()!.responsable_nom }}
            </div>
          }
          @if (contrat()!.modalites_paiement) {
            <div>
              <span class="champ">Modalités de paiement</span>
              {{ contrat()!.modalites_paiement }}
            </div>
          }
          @if (exonerations().length > 0) {
            <div>
              <span class="champ">Exonérations négociées</span>
              {{ exonerationLibelles().join(' · ') }}
            </div>
          }
          @if (contrat()!.conditions_particulieres) {
            <div class="cond">
              <span class="champ">Conditions particulières</span>
              {{ contrat()!.conditions_particulieres }}
            </div>
          }
        </div>

        @for (groupe of groupesTarifs(); track groupe.categorie) {
          <h3 class="titre-partie">
            {{ groupe.label }}
          </h3>
          <table class="tableau">
            <thead>
              <tr>
                <th>Service</th>
                <th class="num">Convention</th>
              </tr>
            </thead>
            <tbody>
              @for (ligne of groupe.lignes; track ligne.code) {
                <tr>
                  <td>
                    <div>{{ ligne.libelle }}</div>
                    @if (ligne.description) {
                      <div class="description">{{ ligne.description }}</div>
                    }
                  </td>
                  <td class="num" [class.non-couvert]="ligne.defaut == null">
                    {{ ligne.montant }}
                  </td>
                </tr>
              }
            </tbody>
          </table>
        }
      </div>

      <div class="cadre">
        <div class="titre-cadre">Factures de service</div>
        @if (factures().length === 0) {
          <div class="vide">Aucune facture émise par la plateforme à ce jour.</div>
        } @else {
          <table class="tableau">
            <thead>
              <tr>
                <th>N° facture</th>
                <th>Période facturée</th>
                <th>Statut</th>
                <th class="num">Total (FCFA)</th>
                <th class="num">Réglé</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @for (f of factures(); track f.id) {
                <tr>
                  <td>{{ f.numero ?? '—' }}</td>
                  <td>{{ periode(f) }}</td>
                  <td>
                    <span [class]="badgeClasse(f.statut)">{{ statutFacture(f.statut) }}</span>
                  </td>
                  <td class="num">{{ montantFcfa(f.montant_total) }}</td>
                  <td class="num">{{ montantFcfa(f.montant_paye) }}</td>
                  <td class="num actions">
                    <button type="button" class="btn-lien" (click)="ouvrirDetail(f)">
                      @if (detailFacture()?.id === f.id) { ▲ } @else { ▼ }
                    </button>
                    <a [href]="urlPdf(f.id)" target="_blank">PDF</a>
                  </td>
                </tr>
                @if (detailFacture()?.id === f.id) {
                  <tr>
                    <td colspan="6" style="padding: 0 0.5rem 0.6rem;">
                      <div class="detail">
                        <div class="sous-titre">{{ detailFacture()!.conditions_paiement }}</div>
                        <strong>Détail facturé</strong>
                        @if (detailFacture()!.lignes.length === 0) {
                          <div class="vide">Aucune ligne.</div>
                        } @else {
                          <table class="tableau">
                            <thead>
                              <tr>
                                <th>Libellé</th>
                                <th class="num">Qté</th>
                                <th class="num">Montant (FCFA)</th>
                              </tr>
                            </thead>
                            <tbody>
                              @for (l of detailFacture()!.lignes; track $index) {
                                <tr>
                                  <td>
                                    <div>{{ l.libelle }}{{ l.source === 'manuelle' ? ' (à la main)' : '' }}</div>
                                    @if (l.motif) {
                                      <div class="description">{{ l.motif }}</div>
                                    }
                                  </td>
                                  <td class="num">{{ l.quantite }}</td>
                                  <td class="num" [class.non-couvert]="l.sens === 'credit'">
                                    {{ l.sens === 'credit' ? '−' : '+' }} {{ montantFcfa(l.montant) }}
                                  </td>
                                </tr>
                              }
                            </tbody>
                          </table>
                        }

                        <div class="paiements">
                          <strong>Règlements</strong>
                          @if (detailFacture()!.paiements.length === 0) {
                            <div class="vide">Aucun règlement enregistré.</div>
                          }
                          @for (p of detailFacture()!.paiements; track $index) {
                            <div>
                              {{ dateCourte(p.date) }} · {{ montantFcfa(p.montant) }} FCFA ·
                              {{ p.mode }}{{ p.reference ? ' — réf. ' + p.reference : '' }}
                            </div>
                          }
                        </div>
                      </div>
                    </td>
                  </tr>
                }
              }
            </tbody>
          </table>
        }
      </div>
    }
  `,
})
export class ContratPlateformeComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly contrat = signal<MonContratPlateforme | null>(null);
  protected readonly factures = signal<FacturePlateforme[]>([]);
  protected readonly detailFacture = signal<DetailFacturePlateforme | null>(null);
  protected readonly charge = signal(true);
  protected readonly message = signal('');

  ngOnInit(): void {
    void this.charger();
  }

  private async charger(): Promise<void> {
    this.charge.set(true);
    this.message.set('');
    try {
      const contrat = await firstValueFrom(this.api.getMonContratPlateforme());
      this.contrat.set(contrat);
      this.factures.set(await firstValueFrom(this.api.getMesFacturesPlateforme()));
    } catch (e) {
      this.message.set(messageErreurApi(e));
      this.contrat.set(null);
      this.factures.set([]);
    } finally {
      this.charge.set(false);
    }
  }

  protected groupesTarifs(): Array<{
    categorie: string;
    label: string;
    lignes: Array<{ code: string; libelle: string; description: string | null; montant: string; defaut: number | null }>;
  }> {
    const contrat = this.contrat();
    if (!contrat) return [];

    const services = contrat.services ?? {};
    const categories = contrat.categories ?? {};
    const ordre = ['ouverture', 'abonnement', 'annuel', 'commission', 'ponctuel'];
    const lignes = (
      Object.entries(contrat.tarifs) as Array<[keyof MonContratPlateforme['tarifs'], number | null]>
    ).map(([code, valeur]) => ({
      code,
      libelle: services[code]?.libelle ?? LIBELLES_TARIFS[code] ?? code,
      description: services[code]?.description ?? null,
      categorie: services[code]?.categorie ?? 'ponctuel',
      montant: this.valeurTarif(code, valeur),
      defaut: valeur,
    }));

    const autres = new Set(
      lignes.map((l) => l.categorie).filter((c) => !ordre.includes(c) && !(c in categories)),
    );
    const catsConnues = Object.keys(categories ?? {}).filter((c) => !ordre.includes(c));
    const categoriesOrdonnees = [...ordre, ...catsConnues, ...autres];

    const groupes = categoriesOrdonnees
      .map((categorie) => ({
        categorie,
        label: categories[categorie] ?? CATEGORIES_DEFAUT[categorie] ?? categorie,
        lignes: lignes.filter((l) => l.categorie === categorie),
      }))
      .filter((g) => g.lignes.length > 0);

    return groupes;
  }

  private valeurTarif(code: keyof MonContratPlateforme['tarifs'], valeur: number | null): string {
    if (valeur === null) return '—';
    if (CODES_TAUX.includes(code)) return `${this.montantFcfa(valeur)} %`;
    return `${this.montantFcfa(valeur)}${valeur === 0 ? ' (gratuit)' : ' FCFA'}`;
  }

  protected exonerationLibelles(): string[] {
    const services = this.contrat()?.services ?? {};
    return this.exonerations().map((code) => services[code]?.libelle ?? LIBELLES_TARIFS[code as keyof MonContratPlateforme['tarifs']] ?? code);
  }

  private exonerations(): string[] {
    return this.contrat()?.exonerations_codes ?? [];
  }

  protected async ouvrirDetail(facture: FacturePlateforme): Promise<void> {
    if (this.detailFacture()?.id === facture.id) {
      this.detailFacture.set(null);
      return;
    }
    try {
      this.detailFacture.set(await firstValueFrom(this.api.getFacturePlateforme(facture.id)));
    } catch (e) {
      this.message.set(messageErreurApi(e));
    }
  }

  protected montantFcfa(montant: number): string {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(montant);
  }

  protected statutFacture(statut: string): string {
    return LIBELLES_STATUT[statut] ?? statut;
  }

  protected badgeClasse(statut: string): string {
    if (statut === 'payee') return 'badge badge-ok';
    if (statut === 'partiellement_payee') return 'badge badge-warn';
    return 'badge badge-info';
  }

  protected periode(facture: FacturePlateforme): string {
    return `${this.dateCourte(facture.periode_debut)} → ${this.dateCourte(facture.periode_fin)}`;
  }

  protected dateCourte(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  }

  protected urlPdf(id: number): string {
    return this.api.urlPdfFacturePlateforme(id);
  }
}
