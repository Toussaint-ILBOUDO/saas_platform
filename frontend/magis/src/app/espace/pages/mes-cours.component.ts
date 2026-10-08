import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { messageErreurApi } from '../../services/messages';
import { ContratCours, MetaPage, StatutAffectation, StatutContrat } from '../../models';

/**
 * Écran « Mes cours » (T7A.3) — enseignant et élève.
 *
 * Un seul écran, deux lectures. L'enseignant voit ce qu'il **donne** : ses
 * contrats, ses élèves, les heures prévues. L'élève voit ce qu'il **suit** : le
 * même contrat, la même matière, l'enseignant en face. Même donnée, deux
 * questions différentes — d'où un composant unique qui décide du libellé, pas
 * deux écrans qui divergeraient.
 *
 * Aucun filtre n'est proposé ici : le serveur déduit déjà le périmètre du
 * profil connecté (un enseignant ne voit que ses contrats). Afficher un filtre
 * « par enseignant » sur cette page donnerait l'illusion d'un choix qui n'en
 * est pas un.
 */
@Component({
  imports: [FormsModule],
  selector: 'espace-mes-cours',
  styles: [
    `
      :host {
        display: block;
      }
      .entete {
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
        max-width: 64ch;
      }

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

      .liste {
        display: grid;
        gap: 0.85rem;
      }
      .carte {
        border-radius: 1.05rem;
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        overflow: hidden;
      }
      .carte.termine,
      .carte.suspendu {
        border-style: dashed;
        background: var(--mpc-fond);
      }
      .carte-entete {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-wrap: wrap;
        padding: 0.95rem 1rem;
      }
      .pastille {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 0.85rem;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
        font-size: 1rem;
      }
      .titre {
        flex: 1;
        min-width: 180px;
      }
      .titre b {
        display: block;
        font-size: 0.97rem;
        color: var(--mpc-bleu);
      }
      .titre small {
        display: block;
        font-size: 0.78rem;
        color: var(--mpc-texte-doux);
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
      .badge.actif {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .badge.suspendu {
        background: var(--mpc-attention-tint);
        color: var(--mpc-attention);
      }
      .badge.termine {
        background: var(--mpc-ordre-tint);
        color: var(--mpc-ordre);
      }
      .badge.info {
        background: var(--mpc-info-tint);
        color: var(--mpc-info);
      }

      .cours {
        border-top: 1px solid var(--mpc-separateur);
        padding: 0.6rem 1rem 0.85rem;
        background: var(--mpc-surface-teintee);
        display: grid;
        gap: 0.45rem;
      }
      .ligne-cours {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        flex-wrap: wrap;
        padding: 0.5rem 0;
        font-size: 0.84rem;
      }
      .ligne-cours + .ligne-cours {
        border-top: 1px dashed var(--mpc-separateur);
      }
      .ligne-cours .qui {
        flex: 1;
        min-width: 150px;
        color: var(--mpc-bleu);
        font-weight: 700;
      }
      .ligne-cours .qui small {
        display: block;
        font-weight: 400;
        font-size: 0.74rem;
        color: var(--mpc-texte-doux);
      }
      .ligne-cours .droite {
        color: var(--mpc-texte-doux);
        font-size: 0.78rem;
        white-space: nowrap;
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

      .spin {
        display: inline-block;
        animation: tourner 0.9s linear infinite;
      }
      @keyframes tourner {
        to {
          transform: rotate(360deg);
        }
      }
    `,
  ],
  template: `
    <div class="entete">
      <h1>Mes cours</h1>
      <p>
        @if (estEleve()) {
          Les cours auxquels vous êtes inscrit, avec l’enseignant et la matière de chaque séance.
        } @else {
          Les contrats qui vous sont affectés, la matière enseignée et le volume d’heures prévu.
        }
      </p>
    </div>

    @if (message()) {
      <div class="alerte"><i class="bi bi-exclamation-triangle"></i><span>{{ message() }}</span></div>
    }

    @if (charge()) {
      <div class="vide"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else if (contrats().length === 0) {
      <div class="vide">
        <i class="bi bi-journal-x"></i>
        {{ estEleve() ? 'Aucun cours inscrit pour le moment.' : 'Aucun cours ne vous est affecté pour le moment.' }}
      </div>
    } @else {
      <div class="liste">
        @for (c of contrats(); track c.id) {
          <article class="carte" [class.suspendu]="c.statut === 'suspendu'" [class.termine]="c.statut === 'termine'">
            <div class="carte-entete">
              <span class="pastille"><i class="bi bi-journal-bookmark"></i></span>
              <div class="titre">
                <b>{{ c.type_cours?.libelle }}</b>
                <small>
                  du {{ dateCourte(c.date_debut) }}
                  {{ c.date_fin ? 'au ' + dateCourte(c.date_fin) : '(sans terme)' }}
                  @if (estEnseignant()) {
                    · {{ c.eleve?.prenom }} {{ c.eleve?.nom }}
                  }
                </small>
              </div>
              <div class="badges">
                <span class="badge" [class.actif]="c.statut === 'actif'" [class.suspendu]="c.statut === 'suspendu'" [class.termine]="c.statut === 'termine'">
                  {{ libelleStatut(c.statut) }}
                </span>
                @if (estEnseignant()) {
                  <span class="badge info">{{ totalHeures(c) }} h prévues</span>
                }
              </div>
            </div>

            <div class="cours">
              @for (a of c.affectations; track a.id) {
                <div class="ligne-cours">
                  <div class="qui">
                    {{ a.matiere?.nom }}
                    <small>
                      {{ estEleve() ? 'enseigné par' : 'élève' }} :
                      {{ estEleve() ? a.enseignant?.prenom + ' ' + a.enseignant?.nom : c.eleve?.prenom + ' ' + c.eleve?.nom }}
                    </small>
                  </div>
                  <span class="badge" [class.actif]="a.statut === 'actif'" [class.suspendu]="a.statut === 'suspendu'" [class.termine]="a.statut === 'termine'">
                    {{ libelleStatutAffectation(a.statut) }}
                  </span>
                  @if (estEnseignant()) {
                    <span class="droite">
                      {{ a.nombre_heures_prevues }} h prévues
                      @if (a.date_fin) {
                        · jusqu’au {{ dateCourte(a.date_fin) }}
                      }
                    </span>
                  }
                </div>
              }
            </div>
          </article>
        }
      </div>

      @if ((meta()?.last_page ?? 1) > 1) {
        <nav class="pagination" aria-label="Pagination">
          <button class="page" type="button" [disabled]="(meta()?.current_page ?? 1) <= 1" (click)="aller((meta()?.current_page ?? 1) - 1)">
            <i class="bi bi-chevron-left"></i>
          </button>
          <span class="page _courante">{{ meta()?.current_page }} / {{ meta()?.last_page }}</span>
          <button
            class="page"
            type="button"
            [disabled]="(meta()?.current_page ?? 1) >= (meta()?.last_page ?? 1)"
            (click)="aller((meta()?.current_page ?? 1) + 1)"
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </nav>
      }
    }
  `,
})
export class MesCoursComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly auth = inject(AuthService);

  protected readonly contrats = signal<ContratCours[]>([]);
  protected readonly meta = signal<MetaPage | null>(null);
  protected readonly charge = signal(true);
  protected readonly message = signal('');

  protected readonly estEnseignant = computed(() => this.auth.roleActif() === 'enseignant');
  protected readonly estEleve = computed(() => this.auth.roleActif() === 'eleve');

  ngOnInit(): void {
    void this.charger(1);
  }

  protected libelleStatut(statut: StatutContrat): string {
    return statut === 'actif' ? 'Actif' : statut === 'suspendu' ? 'Suspendu' : 'Terminé';
  }

  protected libelleStatutAffectation(statut: StatutAffectation): string {
    return statut === 'actif' ? 'En cours' : statut === 'suspendu' ? 'Suspendue' : 'Terminée';
  }

  protected dateCourte(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  protected totalHeures(c: ContratCours): string {
    return String(c.affectations.reduce((t, a) => t + (a.nombre_heures_prevues || 0), 0));
  }

  private async charger(page: number): Promise<void> {
    this.charge.set(true);
    try {
      const r = await firstValueFrom(this.api.getMesCours(page, 20));
      this.contrats.set(r.data ?? []);
      this.meta.set(r.meta ?? null);
    } catch (e) {
      this.message.set(messageErreurApi(e));
    } finally {
      this.charge.set(false);
    }
  }

  protected aller(page: number): void {
    void this.charger(page);
  }
}