import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import { RapportElement, RapportSection } from '../../models';

/**
 * Écran « Modèle de rapport » — administration (T7A.7).
 *
 * L'admin compose le canevas que l'enseignant remplit au dépôt : des sections
 * (blocs) et, dans chaque section, des éléments (questions). Les informations
 * générales et le bilan des activités ne sont pas configurables : ils sont
 * automatiques (contrat, période, cahier de texte).
 *
 * Une section désactivée disparaît immédiatement du formulaire enseignant
 * (elle n'est pas carrément supprimée : ses réponses restent lisibles sur les
 * rapports déjà déposés). Un élément marqué « obligatoire » bloque le dépôt
 * tant qu'il n'est pas renseigné.
 */
@Component({
  imports: [FormsModule],
  selector: 'espace-modele-rapport',
  styles: [
    `
      :host { display: block; }
      .entete h1 { margin: 0 0 0.2rem; font-size: 1.25rem; font-weight: 800; color: var(--mpc-bleu); }
      .entete p { margin: 0; color: var(--mpc-texte-doux); font-size: 0.86rem; max-width: 76ch; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin: 0.9rem 0; }
      .succes { display: flex; align-items: center; gap: 0.5rem; padding: 0.7rem 0.95rem; border-radius: 0.9rem; background: #e7f6ec; color: #14523a; font-size: 0.85rem; margin: 0.9rem 0; }
      .carte-section { border: 1px solid #e5e7eb; border-radius: 1rem; }
      .en-tete-section { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
      .pastille-ordre { display: inline-flex; align-items: center; justify-content: center; width: 1.6rem; height: 1.6rem; border-radius: 0.5rem; background: var(--mpc-bleu-doux, #e8efff); color: var(--mpc-bleu); font-weight: 800; font-size: 0.78rem; }
      .chip-type { font-size: 0.7rem; padding: 0.15rem 0.5rem; border-radius: 99px; background: #eef2f7; color: #55606e; }
      .obligatoire { color: #b3261e; font-weight: 700; }
      .texte-inactif { opacity: 0.55; }
      .rangee-element { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
    `,
  ],
  template: `
    <section class="entete">
      <h1>Modèle de rapport mensuel</h1>
      <p>
        Composez les sections et les questions que l'enseignant remplira à chaque dépôt.
        Les informations générales et le bilan des activités sont automatiques — ils ne
        se configurent pas ici.
      </p>
    </section>

    <!-- Ajout d'une section -->
    <section class="bg-white rounded-4 border p-3">
      <div class="row g-2 align-items-center">
        <div class="col-md-4">
          <label class="form-label mb-0 small fw-semibold" for="ns_libelle">Nouvelle section</label>
          <input class="form-control form-control-sm" id="ns_libelle" placeholder="Libellé (ex. « Projet d'accompagnement »)" [(ngModel)]="nouvelleSection.libelle" name="ns_libelle" />
        </div>
        <div class="col-md-5">
          <label class="form-label mb-0 small fw-semibold" for="ns_description">Description (facultatif)</label>
          <input class="form-control form-control-sm" id="ns_description" placeholder="Ce que l'enseignant doit renseigner ici…" [(ngModel)]="nouvelleSection.description" name="ns_description" />
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="button" class="btn btn-mpc btn-sm w-100" (click)="ajouterSection()">
            <i class="bi bi-plus-lg"></i> Ajouter la section
          </button>
        </div>
      </div>
    </section>

    @if (alerte()) { <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div> }
    @if (succes()) { <div class="succes"><i class="bi bi-check-circle"></i>{{ succes() }}</div> }

    @if (chargement()) {
      <div class="d-flex justify-content-center py-5"><div class="spinner-border text-mpc" role="status"></div></div>
    } @else if (sections().length === 0) {
      <div class="alert alert-light border text-center py-5">
        <i class="bi bi-ui-checks fs-1"></i>
        <div class="mt-2">Aucune section pour l'instant. Créez-en une ci-dessus.</div>
      </div>
    } @else {
      <div class="d-flex flex-column gap-3 mt-3">
        @for (section of sections(); track section.id; let sindex = $index) {
          <div class="carte-section bg-white p-3" [class.texte-inactif]="!section.actif">
            <div class="en-tete-section">
              <span class="pastille-ordre">{{ sindex + 1 }}</span>
              @if (enEditionSection()?.id === section.id) {
                <input class="form-control form-control-sm" style="max-width: 320px" [(ngModel)]="editionSectionLibelle" name="edit_libelle" />
                <input class="form-control form-control-sm" style="max-width: 320px" placeholder="Description" [(ngModel)]="editionSectionDescription" name="edit_description" />
                <button type="button" class="btn btn-sm btn-outline-mpc" (click)="validerEditionSection()" [disabled]="soumission()"><i class="bi bi-check-lg"></i> OK</button>
                <button type="button" class="btn btn-sm btn-light" (click)="enEditionSection.set(null)"><i class="bi bi-x-lg"></i></button>
              } @else {
                <strong>{{ section.libelle }}</strong>
                <span class="chip-type">{{ (section.elements ?? []).length }} question(s)</span>
                <span class="chip-type">{{ section.actif ? 'Active' : 'Désactivée' }}</span>
                <div class="ms-auto btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-mpc" title="Monter" (click)="monterSection(sindex)" [disabled]="sindex === 0 || soumission()"><i class="bi bi-arrow-up"></i></button>
                  <button type="button" class="btn btn-outline-mpc" title="Descendre" (click)="descendreSection(sindex)" [disabled]="sindex === sections().length - 1 || soumission()"><i class="bi bi-arrow-down"></i></button>
                  <button type="button" class="btn btn-outline-mpc" title="Modifier" (click)="editerSection(section)"><i class="bi bi-pencil"></i></button>
                  <button type="button" class="btn btn-outline-mpc text-danger" title="Supprimer (les questions partent avec)" (click)="supprimerSection(section)"><i class="bi bi-trash"></i></button>
                </div>
              }
            </div>

            @if (section.description) {
              <p class="text-doux-tiny mb-2 mt-2">{{ section.description }}</p>
            }

            <div class="form-check form-switch ms-auto" style="width: max-content;">
              <input class="form-check-input" type="checkbox" [checked]="section.actif"
                     id="sec_actif_{{ section.id }}" name="actif_{{ section.id }}"
                     (change)="basculerActifSection(section, $event)" />
              <label class="form-check-label small" for="sec_actif_{{ section.id }}">Visible du formulaire enseignant</label>
            </div>

            @for (element of (section.elements ?? []); track element.id; let eindex = $index) {
              <div class="rangee-element border-top py-2" [class.texte-inactif]="!element.actif">
                <span class="text-doux-tiny">{{ eindex + 1 }}.</span>
                <span>
                  {{ element.libelle }}
                  @if (element.obligatoire) { <span class="obligatoire">* obligatoire</span> }
                </span>
                <span class="chip-type">{{ element.type === 'text' ? 'Texte court' : 'Paragraphe' }}</span>
                <div class="ms-auto btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-mpc" title="Monter" (click)="monterElement(section, eindex)" [disabled]="eindex === 0 || soumission()"><i class="bi bi-arrow-up"></i></button>
                  <button type="button" class="btn btn-outline-mpc" title="Descendre" (click)="descendreElement(section, eindex)" [disabled]="eindex === (section.elements ?? []).length - 1 || soumission()"><i class="bi bi-arrow-down"></i></button>
                  <button type="button" class="btn btn-outline-mpc" title="Modifier" (click)="editerElement(section, element)"><i class="bi bi-pencil"></i></button>
                  <button type="button" class="btn btn-outline-mpc text-danger" title="Supprimer" (click)="supprimerElement(element)"><i class="bi bi-trash"></i></button>
                </div>
              </div>
            }

            <!-- Ajout / édition d'élément -->
            @if (curseurElement() === section.id) {
              <div class="border-top pt-3 mt-2">
                @if (enEditionElement()?.id) {
                  <h6 class="fw-semibold">Modifier la question</h6>
                } @else {
                  <h6 class="fw-semibold">Ajouter une question à « {{ section.libelle }} »</h6>
                }
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label small" for="el_libelle">Libellé</label>
                    <input class="form-control form-control-sm" id="el_libelle" [(ngModel)]="formElement.libelle" name="el_libelle" />
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small" for="el_type">Type</label>
                    <select class="form-select form-select-sm" id="el_type" [(ngModel)]="formElement.type" name="el_type">
                      <option value="textarea">Paragraphe</option>
                      <option value="text">Texte court</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small" for="el_aide">Aide (facultatif)</label>
                    <input class="form-control form-control-sm" id="el_aide" placeholder="Ex. « Décrivez brièvement. »" [(ngModel)]="formElement.aide" name="el_aide" />
                  </div>
                  <div class="col-md-3 d-flex align-items-center gap-2">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" [(ngModel)]="formElement.obligatoire" name="el_obligatoire" id="el_obligatoire" />
                      <label class="form-check-label small" for="el_obligatoire">Obligatoire</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-mpc" (click)="validerElement(section)">
                      {{ enEditionElement()?.id ? 'Enregistrer' : 'Ajouter' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-light" (click)="annulerElement()"><i class="bi bi-x-lg"></i></button>
                  </div>
                </div>
              </div>
            } @else {
              <button type="button" class="btn btn-sm btn-outline-mpc mt-2" (click)="ouvrirAjoutElement(section)"><i class="bi bi-plus-lg"></i> Ajouter une question</button>
            }
          </div>
        }
      </div>
    }
  `,
})
export class ModeleRapportComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected sections = signal<RapportSection[]>([]);
  protected chargement = signal(false);
  protected soumission = signal(false);
  protected alerte = signal('');
  protected succes = signal('');

  protected nouvelleSection = { libelle: '', description: '' };
  protected enEditionSection = signal<{ id: number; libelle: string; description: string } | null>(null);
  protected editionSectionLibelle = '';
  protected editionSectionDescription = '';

  /** id de la section dont le formulaire d'élément est ouvert. */
  protected curseurElement = signal<number | null>(null);
  protected enEditionElement = signal<RapportElement | null>(null);
  protected formElement = { libelle: '', type: 'textarea', obligatoire: false, aide: '' };

  async ngOnInit(): Promise<void> {
    await this.charger();
  }

  /**
   * Recharge l'arbre. `silencieux = true` après une action : la liste n'est
   * pas remplacée par le spinner (pas de saut de scroll, le retour de
   * l'action — bandeau vert — reste lisible).
   */
  private async charger(silencieux = false): Promise<void> {
    if (!silencieux) {
      this.chargement.set(true);
    }
    this.alerte.set('');
    try {
      const donnees = await firstValueFrom(this.api.getSectionsRapport());
      this.sections.set(Array.isArray(donnees) ? donnees : []);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.chargement.set(false);
    }
  }

  protected async ajouterSection(): Promise<void> {
    if (this.soumission()) return;

    const libelle = this.nouvelleSection.libelle.trim();
    if (!libelle) {
      this.succes.set('');
      this.alerte.set('Saisissez le libellé de la section avant de l’ajouter.');
      return;
    }

    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      await firstValueFrom(this.api.creerSectionRapport({ libelle, description: this.nouvelleSection.description.trim() || undefined }));
      this.nouvelleSection = { libelle: '', description: '' };
      this.succes.set('Section ajoutée.');
      await this.charger(true);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected editerSection(section: RapportSection): void {
    this.enEditionSection.set({ id: section.id, libelle: section.libelle, description: section.description ?? '' });
    this.editionSectionLibelle = section.libelle;
    this.editionSectionDescription = section.description ?? '';
  }

  protected async validerEditionSection(): Promise<void> {
    const cible = this.enEditionSection();
    if (!cible) return;

    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      await firstValueFrom(
        this.api.modifierSectionRapport(cible.id, {
          libelle: this.editionSectionLibelle.trim() || cible.libelle,
          description: this.editionSectionDescription.trim() || null,
        })
      );
      this.enEditionSection.set(null);
      this.succes.set('Section mise à jour.');
      await this.charger(true);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async supprimerSection(section: RapportSection): Promise<void> {
    const reponse = confirm(
      `Supprimer la section « ${section.libelle} » et ses ${(section.elements ?? []).length} question(s) ?`
    );
    if (!reponse) return;

    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      await firstValueFrom(this.api.supprimerSectionRapport(section.id));
      this.succes.set('Section supprimée.');
      await this.charger(true);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async basculerActifSection(section: RapportSection, ev: Event): Promise<void> {
    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      await firstValueFrom(this.api.modifierSectionRapport(section.id, { actif: (ev.target as HTMLInputElement).checked }));
      this.succes.set('Visibilité de la section mise à jour.');
      await this.charger(true);
    } catch (e) {
      // Échec : la case reprend l'état réel, elle ne doit pas mentir.
      (ev.target as HTMLInputElement).checked = section.actif;
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async monterSection(index: number): Promise<void> {
    await this.reordonnerSections(index, -1);
  }

  protected async descendreSection(index: number): Promise<void> {
    await this.reordonnerSections(index, 1);
  }

  private async reordonnerSections(index: number, delta: -1 | 1): Promise<void> {
    const ids = this.sections().map((s) => s.id);
    const cible = ids[index];
    ids[index] = ids[index + delta];
    ids[index + delta] = cible;

    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      await firstValueFrom(this.api.reordonnerSectionsRapport(ids));
      this.succes.set('Ordre des sections enregistré.');
      await this.charger(true);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected ouvrirAjoutElement(section: RapportSection): void {
    this.curseurElement.set(section.id);
    this.enEditionElement.set(null);
    this.formElement = { libelle: '', type: 'textarea', obligatoire: false, aide: '' };
  }

  protected editerElement(section: RapportSection, element: RapportElement): void {
    this.curseurElement.set(section.id);
    this.enEditionElement.set(element);
    this.formElement = {
      libelle: element.libelle,
      type: element.type,
      obligatoire: element.obligatoire,
      aide: element.aide ?? '',
    };
  }

  protected annulerElement(): void {
    this.curseurElement.set(null);
    this.enEditionElement.set(null);
  }

  protected async validerElement(section: RapportSection): Promise<void> {
    if (this.soumission()) return;

    const libelle = this.formElement.libelle.trim();
    if (!libelle) {
      this.succes.set('');
      this.alerte.set('Saisissez le libellé de la question avant de l’enregistrer.');
      return;
    }

    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      const cible = this.enEditionElement();
      if (cible?.id) {
        await firstValueFrom(
          this.api.modifierElementRapport(cible.id, {
            libelle,
            type: this.formElement.type,
            obligatoire: this.formElement.obligatoire,
            aide: this.formElement.aide.trim() || null,
          })
        );
      } else {
        await firstValueFrom(
          this.api.creerElementRapport({
            section_id: section.id,
            libelle,
            type: this.formElement.type,
            obligatoire: this.formElement.obligatoire,
            aide: this.formElement.aide.trim() || undefined,
          })
        );
      }
      this.annulerElement();
      this.succes.set('Question enregistrée.');
      await this.charger(true);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async supprimerElement(element: RapportElement): Promise<void> {
    if (!confirm(`Supprimer la question « ${element.libelle} » ?`)) return;

    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      await firstValueFrom(this.api.supprimerElementRapport(element.id));
      this.succes.set('Question supprimée.');
      await this.charger(true);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected async monterElement(section: RapportSection, index: number): Promise<void> {
    await this.reordonnerElements(section, index, -1);
  }

  protected async descendreElement(section: RapportSection, index: number): Promise<void> {
    await this.reordonnerElements(section, index, 1);
  }

  private async reordonnerElements(section: RapportSection, index: number, delta: -1 | 1): Promise<void> {
    const liste = section.elements ?? [];
    const ids = liste.map((e) => e.id);
    const cible = ids[index];
    ids[index] = ids[index + delta];
    ids[index + delta] = cible;

    this.soumission.set(true);
    this.alerte.set('');
    this.succes.set('');
    try {
      await firstValueFrom(this.api.reordonnerElementsRapport(ids));
      this.succes.set('Ordre des questions enregistré.');
      await this.charger(true);
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }
}