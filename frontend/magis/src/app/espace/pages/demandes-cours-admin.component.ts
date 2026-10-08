import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { messageErreurApi } from '../../services/messages';
import {
  ClasseReferentiel,
  DemandeCoursAdmin,
  EnseignantReferentiel,
  MatiereReferentiel,
  MetaPage,
  StatsDemandesCours,
} from '../../models';

/**
 * Écran « Demandes de cours » de l'administration.
 *
 * Remplace le backoffice Blade (`DemandeCoursAdminController`). Les demandes
 * arrivent du formulaire public : ce sont des contacts commerciaux, la seule
 * action est donc « traiter » ou « refuser » — jamais la suppression, une demande
 * reste une trace. Les compteurs de tête viennent du serveur et sont globaux :
 * filtrer la liste ne doit pas faire disparaître le nombre de demandes en
 * attente.
 *
 * Le vrai travail est la constitution du dossier, en trois étapes enchaînées
 * parent → élève → contrat. Chaque étape est une action serveur distincte, et
 * le serveur refuse d'avancer (422) si l'étape précédente manque : l'écran
 * n'affiche donc le bouton d'une étape que lorsque le maillon précédent existe.
 * L'état affiché vient de la réponse serveur, pas d'un calcul local — c'est ce
 * qui permet à l'admin de voir « déjà créé » après avoir quitté la page.
 */
@Component({
  imports: [FormsModule],
  selector: 'espace-demandes-cours-admin',
  styles: [
    `
      :host { display: block; }
      .entete { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
      .entete h1 { margin: 0 0 0.2rem; font-size: 1.25rem; font-weight: 800; color: var(--mpc-bleu); }
      .entete p { margin: 0; color: var(--mpc-texte-doux); font-size: 0.86rem; max-width: 68ch; }
      .cartes { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.8rem; margin-bottom: 1.1rem; }
      .carte { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.95rem 1.05rem; border-radius: 1rem; border: 1px solid var(--mpc-separateur); background: var(--mpc-surface); }
      .carte-label { font-size: 0.74rem; letter-spacing: 0.03em; text-transform: uppercase; color: var(--mpc-texte-doux); }
      .carte-valeur { font-size: 1.5rem; font-weight: 800; font-variant-numeric: tabular-nums; }
      .carte-icone { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 0.85rem; font-size: 1.1rem; }
      .carte-total .carte-icone { background: var(--mpc-primaire-tint); color: var(--mpc-primaire); }
      .carte-attente .carte-icone { background: var(--mpc-jaune-doux); color: #6b4f00; }
      .carte-traitees .carte-icone { background: var(--mpc-vert-pale); color: #0a5d35; }
      .carte-annulees .carte-icone { background: var(--mpc-rouge-clair); color: #7a1d1d; }
      .carte-contrats .carte-icone { background: var(--mpc-primaire-tint); color: var(--mpc-primaire); }
      .filtres { display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center; margin-bottom: 1rem; }
      .table-clair { width: 100%; font-size: 0.86rem; }
      .table-clair th { font-size: 0.72rem; letter-spacing: 0.04em; text-transform: uppercase; color: var(--mpc-texte-doux); white-space: nowrap; }
      .badge-statut { font-size: 0.74rem; font-weight: 700; padding: 0.28rem 0.55rem; border-radius: 99px; white-space: nowrap; }
      .badge-en_attente { background: var(--mpc-jaune-doux); color: #6b4f00; }
      .badge-traitee { background: var(--mpc-vert-pale); color: #0a5d35; }
      .badge-annulee { background: var(--mpc-rouge-clair); color: #7a1d1d; }
      .etapes { display: grid; gap: 0.6rem; margin-bottom: 1.1rem; }
      .etape { display: flex; align-items: flex-start; gap: 0.7rem; padding: 0.7rem 0.85rem; border: 1px solid var(--mpc-separateur); border-radius: 0.9rem; background: var(--mpc-surface); }
      .etape-faite { border-color: var(--mpc-vert-pale); background: var(--mpc-vert-pale); }
      .etape-bloque { opacity: 0.72; }
      .etape-num { flex: 0 0 auto; width: 26px; height: 26px; display: grid; place-items: center; border-radius: 99px; font-size: 0.8rem; font-weight: 800; background: var(--mpc-primaire-tint); color: var(--mpc-primaire); }
      .etape-faite .etape-num { background: #0a5d35; color: #fff; }
      .etape-corps { flex: 1 1 auto; min-width: 0; }
      .etape-titre { font-size: 0.86rem; font-weight: 700; }
      .etape-aide { font-size: 0.78rem; color: var(--mpc-texte-doux); }
      .formulaire { display: grid; gap: 0.85rem; }
      .champ { display: grid; gap: 0.3rem; font-size: 0.82rem; font-weight: 600; }
      .champ input, .champ select { font: inherit; font-weight: 400; padding: 0.5rem 0.6rem; border: 1px solid var(--mpc-separateur); border-radius: 0.6rem; background: #fff; }
      .champ input:disabled, .champ select:disabled { background: #f4f5f7; color: var(--mpc-texte-doux); }
      .ligne-affectation { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.6rem; align-items: end; }
      .whatsapp { display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 700; color: #0a5d35; text-decoration: none; }
      .whatsapp:hover { text-decoration: underline; }
      .alerte { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-rouge-clair); color: #7a1d1d; font-size: 0.85rem; margin-bottom: 0.9rem; }
      .fiche dl { display: grid; grid-template-columns: minmax(120px, auto) 1fr; gap: 0.45rem 1rem; margin: 0 0 1.1rem; }
      .fiche dt { color: var(--mpc-texte-doux); font-size: 0.8rem; font-weight: 600; }
      .fiche dd { margin: 0; font-size: 0.88rem; }
      .message-parent { padding: 0.8rem 0.95rem; border-radius: 0.9rem; background: var(--mpc-primaire-tint); font-size: 0.86rem; }
      .tags { display: flex; flex-wrap: wrap; gap: 0.35rem; }
      .tag { font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.55rem; border-radius: 99px; background: var(--mpc-primaire-tint); color: var(--mpc-primaire); }
    `,
  ],
  template: `
    <section class="entete">
      <div>
        <h1>Demandes de cours</h1>
        <p>
          Contacts venus du formulaire public du site. Une demande n'est jamais
          supprimée : elle est <b>traitée</b> une fois le parent rappelé et le
          contrat construit.
        </p>
      </div>
    </section>

    <section class="cartes">
      <div class="carte carte-total">
        <div>
          <div class="carte-label">Total demandes</div>
          <div class="carte-valeur">{{ stats().total }}</div>
        </div>
        <div class="carte-icone"><i class="bi bi-envelope"></i></div>
      </div>
      <div class="carte carte-attente">
        <div>
          <div class="carte-label">En attente</div>
          <div class="carte-valeur">{{ stats().en_attente }}</div>
        </div>
        <div class="carte-icone"><i class="bi bi-clock-history"></i></div>
      </div>
      <div class="carte carte-traitees">
        <div>
          <div class="carte-label">Traitées</div>
          <div class="carte-valeur">{{ stats().traitees }}</div>
        </div>
        <div class="carte-icone"><i class="bi bi-check-circle"></i></div>
      </div>
      <div class="carte carte-annulees">
        <div>
          <div class="carte-label">Refusées</div>
          <div class="carte-valeur">{{ stats().annulees }}</div>
        </div>
        <div class="carte-icone"><i class="bi bi-x-circle"></i></div>
      </div>
      <div class="carte carte-contrats">
        <div>
          <div class="carte-label">Avec contrat</div>
          <div class="carte-valeur">{{ stats().avec_contrat }}</div>
        </div>
        <div class="carte-icone"><i class="bi bi-file-earmark-text"></i></div>
      </div>
    </section>

    <section class="filtres">
      <select
        class="form-select form-select-sm w-auto"
        [ngModel]="filtres().statut"
        (ngModelChange)="filtres.update(f => ({ ...f, statut: $event })); charger()"
      >
        <option value="">Tous les statuts</option>
        <option value="en_attente">En attente</option>
        <option value="traitee">Traitée</option>
        <option value="annulee">Refusée</option>
      </select>
      <select
        class="form-select form-select-sm w-auto"
        [ngModel]="filtres().classe_id"
        (ngModelChange)="filtres.update(f => ({ ...f, classe_id: $event })); charger()"
      >
        <option [ngValue]="null">Toutes les classes</option>
        @for (c of classes(); track c.id) {
          <option [ngValue]="c.id">{{ c.nom }}</option>
        }
      </select>
      <form class="d-flex gap-2" (ngSubmit)="rechercher()">
        <input
          class="form-control form-control-sm"
          style="min-width: 220px"
          placeholder="Parent ou téléphone…"
          [(ngModel)]="recherche"
          name="recherche"
        />
        <button class="btn btn-outline-mpc btn-sm" type="submit"><i class="bi bi-search"></i></button>
      </form>
    </section>

    @if (alerte()) {
      <div class="alerte"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div>
    }

    @if (chargement()) {
      <div class="d-flex justify-content-center py-5">
        <div class="spinner-border text-mpc" role="status"></div>
      </div>
    } @else if (demandes().length === 0) {
      <div class="alert alert-light border text-center py-5">
        <i class="bi bi-inbox fs-1"></i>
        <div class="mt-2">Aucune demande pour ces critères.</div>
      </div>
    } @else {
      <div class="table-responsive bg-white rounded-4 border">
        <table class="table table-hover align-middle table-clair mb-0">
          <thead>
            <tr>
              <th>Parent</th>
              <th>Téléphone</th>
              <th>Classe</th>
              <th>Type</th>
              <th class="text-end">Volume</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @for (d of demandes(); track d.id) {
              <tr>
                <td class="fw-semibold">{{ d.prenom_parent }} {{ d.nom_parent }}</td>
                <td>{{ d.telephone }}</td>
                <td>{{ d.classe?.nom ?? '—' }}</td>
                <td>{{ d.type_cours?.libelle ?? '—' }}</td>
                <td class="text-end">{{ d.volume_horaire_estime }} h</td>
                <td>
                  <span class="badge-statut badge-{{ d.statut }}">{{ libelleStatut(d.statut) }}</span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-mpc" (click)="ouvrirDetail(d)" title="Consulter">
                      <i class="bi bi-eye"></i>
                    </button>
                    @if (d.statut === 'en_attente') {
                      <button class="btn btn-outline-mpc text-success" (click)="valider(d)" title="Marquer traitée">
                        <i class="bi bi-check-lg"></i>
                      </button>
                      <button class="btn btn-outline-mpc text-danger" (click)="refuser(d)" title="Refuser la demande">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    }
                    <a
                      class="btn btn-outline-mpc text-success"
                      [href]="d.lien_whatsapp"
                      target="_blank"
                      rel="noopener"
                      title="Contacter par WhatsApp"
                    >
                      <i class="bi bi-whatsapp"></i>
                    </a>
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
    }

    <!-- Fiche de la demande -->
    @if (detail()) {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event)">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                Demande de {{ detail()!.prenom_parent }} {{ detail()!.nom_parent }}
                <span class="badge-statut badge-{{ detail()!.statut }} ms-2">
                  {{ libelleStatut(detail()!.statut) }}
                </span>
              </h5>
              <button type="button" class="btn-close" (click)="fermerDetail()"></button>
            </div>
            <div class="modal-body fiche">
              <dl>
                <dt>Téléphone</dt>
                <dd><a [href]="'tel:' + detail()!.telephone">{{ detail()!.telephone }}</a></dd>
                @if (detail()!.telephone_whatsapp && detail()!.telephone_whatsapp !== detail()!.telephone) {
                  <dt>WhatsApp demandé</dt>
                  <dd>{{ detail()!.telephone_whatsapp }}</dd>
                }
                <dt>Classe</dt>
                <dd>{{ detail()!.classe?.nom ?? '—' }}</dd>
                <dt>Type de cours</dt>
                <dd>{{ detail()!.type_cours?.libelle ?? '—' }}</dd>
                <dt>Volume souhaité</dt>
                <dd>{{ detail()!.volume_horaire_estime }} h par semaine</dd>
                <dt>Reçue le</dt>
                <dd>{{ dater(detail()!.created_at) }}</dd>
              </dl>

              @if (detail()!.lien_whatsapp) {
                <a
                  class="whatsapp mb-3"
                  [href]="detail()!.lien_whatsapp"
                  target="_blank"
                  rel="noopener"
                >
                  <i class="bi bi-whatsapp"></i>
                  Écrire à {{ detail()!.prenom_parent }} sur {{ detail()!.numero_whatsapp }}
                </a>
              }

              @if (detail()!.matieres?.length) {
                <div class="carte-label mb-2">Matières demandées</div>
                <div class="tags mb-3">
                  @for (m of detail()!.matieres; track m.id) {
                    <span class="tag">{{ m.nom }}</span>
                  }
                </div>
              }

              <div class="carte-label mb-2">Message</div>
              <div class="message-parent mb-3">
                {{ detail()!.message || 'Aucun message.' }}
              </div>

              <div class="carte-label mb-2">Constituer le dossier</div>
              <div class="etapes">
                <div class="etape" [class.etape-faite]="detail()!.parent_cree" [class.etape-bloque]="refusee()">
                  <span class="etape-num">{{ detail()!.parent_cree ? '✓' : '1' }}</span>
                  <div class="etape-corps">
                    <div class="etape-titre">Compte parent</div>
                    <div class="etape-aide">
                      @if (detail()!.parent_cree) {
                        Déjà créé : {{ detail()!.parent_cree!.prenom }} {{ detail()!.parent_cree!.nom }}
                      } @else if (refusee()) {
                        Demande refusée.
                      } @else {
                        Prérempli avec les coordonnées de la demande.
                      }
                    </div>
                    @if (!detail()!.parent_cree && !refusee()) {
                      <button type="button" class="btn btn-sm btn-mpc mt-1" (click)="ouvrirParent()">
                        <i class="bi bi-person-plus me-1"></i>Créer le parent
                      </button>
                    }
                  </div>
                </div>

                <div
                  class="etape"
                  [class.etape-faite]="detail()!.eleve_cree"
                  [class.etape-bloque]="!detail()!.parent_cree || refusee()"
                >
                  <span class="etape-num">{{ detail()!.eleve_cree ? '✓' : '2' }}</span>
                  <div class="etape-corps">
                    <div class="etape-titre">Fiche élève</div>
                    <div class="etape-aide">
                      @if (detail()!.eleve_cree) {
                        Déjà créé{{ detail()!.eleve_cree!.classe ? ' en ' + detail()!.eleve_cree!.classe!.nom : '' }}
                      } @else if (refusee()) {
                        Demande refusée.
                      } @else if (!detail()!.parent_cree) {
                        Créez d'abord le compte parent.
                      } @else {
                        Rattachée au parent, dans la classe demandée.
                      }
                    </div>
                    @if (!detail()!.eleve_cree && detail()!.parent_cree && !refusee()) {
                      <button type="button" class="btn btn-sm btn-mpc mt-1" (click)="ouvrirEleve()">
                        <i class="bi bi-person-badge mt-1 me-1"></i>Créer l'élève
                      </button>
                    }
                  </div>
                </div>

                <div
                  class="etape"
                  [class.etape-faite]="detail()!.contrat_cree"
                  [class.etape-bloque]="!detail()!.eleve_cree || refusee()"
                >
                  <span class="etape-num">{{ detail()!.contrat_cree ? '✓' : '3' }}</span>
                  <div class="etape-corps">
                    <div class="etape-titre">Contrat de cours</div>
                    <div class="etape-aide">
                      @if (detail()!.contrat_cree) {
                        Contrat #{{ detail()!.contrat_cree!.id }} —
                        {{ detail()!.contrat_cree!.statut }}
                      } @else if (refusee()) {
                        Demande refusée.
                      } @else if (!detail()!.eleve_cree) {
                        Créez d'abord la fiche élève.
                      } @else {
                        Matières et volume préremplis, vous choisissez l'enseignant et son taux.
                      }
                    </div>
                    @if (!detail()!.contrat_cree && detail()!.eleve_cree && !refusee()) {
                      <button type="button" class="btn btn-sm btn-mpc mt-1" (click)="ouvrirContrat()">
                        <i class="bi bi-file-earmark-text me-1"></i>Créer le contrat
                      </button>
                    }
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" (click)="fermerDetail()">Fermer</button>
              @if (!refusee()) {
                <button
                  type="button"
                  class="btn btn-outline-mpc text-danger"
                  (click)="refuser(detail()!)"
                  [disabled]="soumission()"
                >
                  Refuser la demande
                </button>
              }
              @if (detail()!.statut === 'en_attente' && !workflowEnCours()) {
                <button
                  type="button"
                  class="btn btn-outline-mpc text-success"
                  (click)="valider(detail()!)"
                  [disabled]="soumission()"
                >
                  @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                  Marquer comme traitée
                </button>
              }
            </div>
          </div>
        </div>
      </div>
    }

    @if (modale() === 'parent') {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event)">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer le compte parent</h5>
            <button class="btn-close" (click)="fermerModale()"></button>
          </div>
          <div class="modal-body fiche">
            <!-- Le bandeau d'alerte global est masqué par la modale : le message
                 d'erreur doit être visible ici, sinon un 422 passe pour un échec muet. -->
            @if (alerte()) {
              <div class="alerte mb-3"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div>
            }
            <p class="etape-aide mb-3">
              Les coordonnées proviennent de la demande. Un mot de passe provisoire est
              généré : il sera transmis au parent lors de la remise des accès.
            </p>
            <div class="formulaire">
              <label class="champ">
                <span>Nom *</span>
                <input type="text" [(ngModel)]="formParent.nom" />
              </label>
              <label class="champ">
                <span>Prénom *</span>
                <input type="text" [(ngModel)]="formParent.prenom" />
              </label>
              <label class="champ">
                <span>Téléphone</span>
                <input type="tel" [(ngModel)]="formParent.telephone_appel" />
              </label>
              <label class="champ">
                <span>WhatsApp</span>
                <input type="tel" [(ngModel)]="formParent.telephone_whatsapp" />
                <small class="etape-aide">Laissez vide pour réutiliser le téléphone.</small>
              </label>
              <label class="champ">
                <span>Email</span>
                <input type="email" [(ngModel)]="formParent.email" />
              </label>
              <label class="champ">
                <span>Mot de passe * (8 caractères minimum)</span>
                <input type="text" autocomplete="new-password" [(ngModel)]="formParent.password" />
              </label>
              <label class="champ">
                <span>Profession</span>
                <input type="text" [(ngModel)]="formParent.profession" />
              </label>
              <label class="champ">
                <span>Adresse du domicile</span>
                <input type="text" [(ngModel)]="formParent.adresse_domicile" />
              </label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" (click)="fermerModale()">Annuler</button>
            <button
              type="button"
              class="btn btn-mpc"
              (click)="validerParent()"
              [disabled]="soumission() || !formParent.nom.trim() || !formParent.prenom.trim() || formParent.password.trim().length < 8"
            >
              @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
              Créer le parent
            </button>
            </div>
          </div>
        </div>
      </div>
    }

    @if (modale() === 'eleve') {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event)">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer la fiche élève</h5>
            <button class="btn-close" (click)="fermerModale()"></button>
          </div>
          <div class="modal-body fiche">
            <!-- Le bandeau d'alerte global est masqué par la modale : le message
                 d'erreur doit être visible ici, sinon un 422 passe pour un échec muet. -->
            @if (alerte()) {
              <div class="alerte mb-3"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div>
            }
            <div class="formulaire">
              <label class="champ">
                <span>Parent</span>
                <select [ngModel]="formEleve.parent_id" (ngModelChange)="formEleve.parent_id = $event">
                  <option [ngValue]="null">— Parent lié à cette demande —</option>
                  @for (p of parentsExistants(); track p.id) {
                    <option [ngValue]="p.id">{{ p.prenom }} {{ p.nom }}{{ p.email ? ' (' + p.email + ')' : '' }}</option>
                  }
                </select>
                <small class="etape-aide">Laissez vide pour utiliser le parent déjà lié à cette demande.</small>
              </label>
              <label class="champ">
                <span>Prénom de l'enfant *</span>
                <input type="text" [(ngModel)]="formEleve.prenom" />
                <small class="etape-aide">La demande ne précise pas le prénom de l'enfant.</small>
              </label>
              <label class="champ">
                <span>Date de naissance</span>
                <input type="date" [(ngModel)]="formEleve.date_naissance" />
              </label>
              <label class="champ">
                <span>Classe</span>
                <input type="text" [ngModel]="formEleve.libelle_classe" disabled />
                <small class="etape-aide">Reprise de la demande.</small>
              </label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" (click)="fermerModale()">Annuler</button>
            <button
              type="button"
              class="btn btn-mpc"
              (click)="validerEleve()"
              [disabled]="soumission() || !formEleve.prenom.trim()"
            >
              @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
              Créer l'élève
            </button>
            </div>
          </div>
        </div>
      </div>
    }

    @if (modale() === 'contrat') {
      <div class="modal d-block" tabindex="-1" (click)="siFond($event)">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Créer le contrat</h5>
            <button class="btn-close" (click)="fermerModale()"></button>
          </div>
          <div class="modal-body fiche">
            <!-- Le bandeau d'alerte global est masqué par la modale : le message
                 d'erreur doit être visible ici, sinon un 422 passe pour un échec muet. -->
            @if (alerte()) {
              <div class="alerte mb-3"><i class="bi bi-exclamation-triangle"></i>{{ alerte() }}</div>
            }
            <div class="formulaire mb-3">
              <label class="champ">
                <span>Date de début *</span>
                <input type="date" [(ngModel)]="formContrat.date_debut" />
              </label>
              <label class="champ">
                <span>Date de fin</span>
                <input type="date" [(ngModel)]="formContrat.date_fin" />
              </label>
              <label class="champ">
                <span>Notes internes</span>
                <input type="text" [(ngModel)]="formContrat.notes_admin" />
              </label>
            </div>

            <div class="carte-label mb-2">Affectations</div>
            <p class="etape-aide mb-2">
              Une affectation par matière : le serveur vérifie que l'enseignant suit
              cette matière.
            </p>
            @for (a of formContrat.affectations; track $index; let i = $index) {
              <div class="ligne-affectation mb-2">
                <label class="champ">
                  <span>Matière</span>
                  <select [ngModel]="a.matiere_id" (ngModelChange)="majAffectation(i, 'matiere_id', $event)">
                    <option [ngValue]="null">— Choisir —</option>
                    @for (m of matieres(); track m.id) {
                      <option [ngValue]="m.id">{{ m.nom }}</option>
                    }
                  </select>
                </label>
                <label class="champ">
                  <span>Enseignant</span>
                  <select [ngModel]="a.enseignant_id" (ngModelChange)="majAffectation(i, 'enseignant_id', $event)">
                    <option [ngValue]="null">— Choisir —</option>
                    @for (p of enseignantsDisponibles(i); track p.id) {
                      <option [ngValue]="p.profil?.id ?? null">{{ p.prenom }} {{ p.nom }}</option>
                    }
                  </select>
                </label>
                <label class="champ">
                  <span>Heures / semaine</span>
                  <input type="number" min="0" step="0.5" [ngModel]="a.nombre_heures_prevues"
                         (ngModelChange)="majAffectation(i, 'nombre_heures_prevues', $event)" />
                </label>
                <label class="champ">
                  <span>Taux enseignant</span>
                  <input type="number" min="0" step="50" [ngModel]="a.taux_horaire_enseignant"
                         (ngModelChange)="majAffectation(i, 'taux_horaire_enseignant', $event)" />
                </label>
              </div>
            }
            @if (formContrat.affectations.length < matieres().length) {
              <button type="button" class="btn btn-sm btn-light" (click)="ajouterAffectation()">
                <i class="bi bi-plus me-1"></i>Ajouter une matière
              </button>
            }
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" (click)="fermerModale()">Annuler</button>
            <button type="button" class="btn btn-mpc" (click)="validerContrat()" [disabled]="soumission() || !contratValide()">
              @if (soumission()) { <span class="spinner-border spinner-border-sm me-1"></span> }
              Créer le contrat
            </button>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class DemandesCoursAdminComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  /**
   * Volume horaire réparti sur les matières demandées, arrondi à l'unité pour
   * éviter des 1,33 h par ligne. L'admin ajuste ensuite affectation par
   * affectation ; le serveur rejette de toute façon un volume incohérent avec
   * le contrat, il n'y a donc pas de risque de facturer autre chose que ce qui
   * est affiché.
   */
  private static readonly HEURES_PAR_AFFECTATION = 1;

  protected demandes = signal<DemandeCoursAdmin[]>([]);
  protected classes = signal<ClasseReferentiel[]>([]);
  protected matieres = signal<MatiereReferentiel[]>([]);
  protected enseignants = signal<EnseignantReferentiel[]>([]);
  protected meta = signal<MetaPage | null>(null);
  protected stats = signal<StatsDemandesCours>({
    total: 0,
    en_attente: 0,
    traitees: 0,
    annulees: 0,
    avec_contrat: 0,
  });
  protected page = signal(1);
  protected chargement = signal(false);
  protected soumission = signal(false);
  protected alerte = signal('');
  protected detail = signal<DemandeCoursAdmin | null>(null);
  protected recherche = '';

  /** Formulaire parent : prérempli depuis la demande au moment de l'ouverture. */
  protected formParent: {
    nom: string;
    prenom: string;
    telephone_appel: string;
    telephone_whatsapp: string;
    email: string;
    password: string;
    profession: string;
    adresse_domicile: string;
  } = this.formParentVide();

  /** Formulaire élève : seul le prénom de l'enfant est réellement à saisir. */
  protected parentsExistants = signal<Array<{ id: number; nom: string; prenom: string; email: string | null }>>([]);
  protected formEleve: {
    prenom: string;
    nom: string;
    date_naissance: string;
    classe_id: number | null;
    libelle_classe: string;
    parent_id: number | null;
    parent_autre: boolean;
  } = {
    prenom: '',
    nom: '',
    date_naissance: '',
    classe_id: null,
    libelle_classe: '',
    parent_id: null,
    parent_autre: false,
  };

  protected formContrat: {
    type_cours_id: number | null;
    date_debut: string;
    date_fin: string;
    notes_admin: string;
    affectations: {
      matiere_id: number | null;
      /** `enseignant_profils.id` : le contrat référence le profil, pas `users`. */
      enseignant_id: number | null;
      taux_horaire_enseignant: number | null;
      nombre_heures_prevues: number | null;
      date_affectation: string | null;
    }[];
  } = this.formContratVide();

  protected readonly modale = signal<'parent' | 'eleve' | 'contrat' | null>(null);

  /** Une demande annulée est sortie du pipeline : plus aucune action. */
  protected readonly refusee = computed(() => this.detail()?.statut === 'annulee');

  /** Une étape de constitution est ouverte : « traiter » n'a plus de sens. */
  protected readonly workflowEnCours = computed(() => {
    const d = this.detail();
    return !!d && (!!d.parent_cree || !!d.eleve_cree || !!d.contrat_cree);
  });

  /**
   * Un contrat est submissible quand tout ce que le serveur exige est présent.
   *
   * Méthode et non `computed()` : `formContrat` est un objet plain que le
   * template modifie via `ngModel`, sans qu'aucun signal ne change. Un
   * `computed` ne se réévalue que sur signal et resterait figé sur son état
   * initial (aucune affectation) — le bouton n'aurait jamais pu s'activer.
   */
  protected contratValide(): boolean {
    const c = this.formContrat;
    return (
      !!c.type_cours_id &&
      !!c.date_debut &&
      c.affectations.length > 0 &&
      c.affectations.every(
        (a) =>
          !!a.enseignant_id &&
          !!a.matiere_id &&
          a.taux_horaire_enseignant !== null &&
          a.taux_horaire_enseignant >= 0 &&
          a.nombre_heures_prevues !== null &&
          a.nombre_heures_prevues > 0
      )
    );
  }

  protected enseignantsDisponibles(index: number): EnseignantReferentiel[] {
    const matiereId = this.formContrat.affectations[index]?.matiere_id;
    if (!matiereId) return this.enseignants();
    return this.enseignants().filter((p) =>
      p.profil?.matieres?.some((m) => m.id === matiereId)
    );
  }

  private formParentVide(): {
    nom: string;
    prenom: string;
    telephone_appel: string;
    telephone_whatsapp: string;
    email: string;
    password: string;
    profession: string;
    adresse_domicile: string;
  } {
    return {
      nom: '',
      prenom: '',
      telephone_appel: '',
      telephone_whatsapp: '',
      email: '',
      password: '',
      profession: '',
      adresse_domicile: '',
    };
  }

  private formContratVide(): {
    type_cours_id: number | null;
    date_debut: string;
    date_fin: string;
    notes_admin: string;
    affectations: {
      matiere_id: number | null;
      enseignant_id: number | null;
      taux_horaire_enseignant: number | null;
      nombre_heures_prevues: number | null;
      date_affectation: string | null;
    }[];
  } {
    return {
      type_cours_id: null,
      date_debut: new Date().toISOString().slice(0, 10),
      date_fin: '',
      notes_admin: '',
      affectations: [
        {
          matiere_id: null,
          enseignant_id: null,
          taux_horaire_enseignant: null,
          nombre_heures_prevues: null,
          date_affectation: null,
        },
      ],
    };
  }

  protected filtres = signal<{ statut: string; classe_id: number | null; search: string }>({
    statut: '',
    classe_id: null,
    search: '',
  });

  async ngOnInit(): Promise<void> {
    // Une notification peut ouvrir cette page sur une demande précise.
    void this.ouvrirParNotification();

    await Promise.all([this.charger(), this.chargerReferentiels()]);
  }

  /**
   * `/espace/pedagogie/demandes-cours/12` : ouvre la fiche 12.
   *
   * `route_angular` renvoie ce chemin pour que le clic sur une notification
   * mène directement à la demande concernée et non à la simple liste.
   */
  private async ouvrirParNotification(): Promise<void> {
    const url = this.router.url.split('?')[0];
    const segments = url.split('/').filter(Boolean);
    const id = Number(segments[segments.length - 1]);

    if (!Number.isInteger(id) || id <= 0) {
      return;
    }

    try {
      this.detail.set(await firstValueFrom(this.api.getDemandeCours(id)));
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    }
  }

  private async charger(): Promise<void> {
    this.chargement.set(true);
    this.alerte.set('');

    try {
      const r = await firstValueFrom(
        this.api.getDemandesCours(this.page(), 20, {
          statut: this.filtres().statut,
          classe_id: this.filtres().classe_id,
          search: this.filtres().search,
        })
      );

      this.demandes.set(r.data);
      this.meta.set(r.meta ?? null);
      this.stats.set(r.meta?.stats ?? this.stats());
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.chargement.set(false);
    }
  }

  protected async chargerReferentiels(): Promise<void> {
    try {
      const r = await firstValueFrom(this.api.getClasses(1, 100));
      this.classes.set(r.data);
    } catch {
      // Filtre par classe indisponible au pire ; la liste reste consultable.
    }

    // Matières et enseignants ne servent qu'à l'étape « contrat » : on les
    // charge en arrière-plan pour ne pas retarder l'affichage de la liste.
    try {
      const r = await firstValueFrom(this.api.getMatieres(1, 100, '', '1'));
      this.matieres.set(r.data);
    } catch {
      // Le formulaire de contrat signalera qu'il manque des données.
    }

    try {
      const r = await firstValueFrom(this.api.getEnseignantsReferentiel(1, 100));
      this.enseignants.set(r.data);
    } catch {
      // Idem.
    }
    await this.chargerParents();
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

  protected async ouvrirDetail(d: DemandeCoursAdmin): Promise<void> {
    try {
      this.detail.set(await firstValueFrom(this.api.getDemandeCours(d.id)));
    } catch (e) {
      this.detail.set(d);
      this.alerte.set(messageErreurApi(e));
    }
  }

  protected fermerDetail(): void {
    this.detail.set(null);
  }

  protected siFond(evenement: Event): void {
    if (evenement.target === evenement.currentTarget) {
      this.fermerDetail();
    }
  }

  protected async valider(d: DemandeCoursAdmin): Promise<void> {
    if (!confirm(`Marquer la demande de ${d.prenom_parent} ${d.nom_parent} comme traitée ?`)) {
      return;
    }

    this.soumission.set(true);
    this.alerte.set('');

    try {
      const majoutee = await firstValueFrom(this.api.validerDemandeCours(d.id));

      this.appliquer(majoutee);

      // Les compteurs changent : ils sont recalculés par le serveur, pas
      // devinés localement, sinon ils divergeraient de la réalité.
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }


  private async chargerParents(): Promise<void> {
    try {
      const r = await firstValueFrom(this.api.getUtilisateurs(1, 100, '', 'parent'));
      this.parentsExistants.set(
        (r.data || []).map((u) => ({
          id: u.id,
          nom: u.nom,
          prenom: u.prenom,
          email: u.email ?? null,
        }))
      );
    } catch {
      // silencieux
    }
  }
  protected libelleStatut(s: string): string {
    return { en_attente: 'En attente', traitee: 'Traitée', annulee: 'Refusée' }[s] ?? s;
  }

  /**
   * Refuse la demande.
   *
   * Le serveur répond 422 si un contrat existe déjà (on ne laisse pas un
   * engagement de facturation orphelin d'une demande annulée) ; le message
   * d'erreur est renvoyé tel quel.
   */
  protected async refuser(d: DemandeCoursAdmin): Promise<void> {
    if (
      !confirm(
        `Refuser la demande de ${d.prenom_parent} ${d.nom_parent} ?\n` +
          `Le parent sera prévenu par le canal habituel.`
      )
    ) {
      return;
    }

    this.soumission.set(true);
    this.alerte.set('');

    try {
      await this.appliquer(await firstValueFrom(this.api.refuserDemandeCours(d.id)));
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  /* ---------- Étape 1 : parent ---------- */

  protected ouvrirParent(): void {
    const d = this.detail();
    if (!d) return;

    this.formParent = {
      ...this.formParentVide(),
      nom: d.nom_parent ?? '',
      prenom: d.prenom_parent ?? '',
      telephone_appel: d.telephone ?? '',
      // Numéro WhatsApp réellement contactable : si la demande n'en précise pas
      // un, le téléphone est déjà un numéro WhatsApp valide.
      telephone_whatsapp: d.numero_whatsapp ?? d.telephone ?? '',
    };

    this.modale.set('parent');
  }

  protected async validerParent(): Promise<void> {
    const d = this.detail();
    if (!d) return;

    this.soumission.set(true);
    this.alerte.set('');

    try {
      const majoutee = await firstValueFrom(
        this.api.creerParentDemandeCours(d.id, {
          nom: this.formParent.nom.trim(),
          prenom: this.formParent.prenom.trim(),
          telephone_appel: this.formParent.telephone_appel.trim() || undefined,
          telephone_whatsapp: this.formParent.telephone_whatsapp.trim() || undefined,
          email: this.formParent.email.trim() || undefined,
          password: this.formParent.password,
          profession: this.formParent.profession.trim() || undefined,
          adresse_domicile: this.formParent.adresse_domicile.trim() || undefined,
        })
      );

      this.modale.set(null);
      this.appliquer(majoutee);
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  /* ---------- Étape 2 : élève ---------- */

  protected async ouvrirEleve(): Promise<void> {
    const d = this.detail();
    if (!d) return;
    await this.chargerParents();

    this.formEleve = {
      prenom: '',
      // L'enfant porte le nom du parent : une demande ne dit pas « nom de
      // famille » de l'enfant, et il est plus rare en écart dans ce contexte.
      nom: d.nom_parent ?? '',
      date_naissance: '',
      classe_id: d.classe?.id ?? null,
      libelle_classe: d.classe?.nom ?? '',
      parent_id: d.parent_cree?.id ?? null,
      parent_autre: false,
    };

    this.modale.set('eleve');
  }

  protected async validerEleve(): Promise<void> {
    const d = this.detail();
    if (!d) return;

    this.soumission.set(true);
    this.alerte.set('');

    try {
      const majoutee = await firstValueFrom(
        this.api.creerEleveDemandeCours(d.id, {
          parent_id: this.formEleve.parent_id ?? undefined,
          prenom: this.formEleve.prenom.trim(),
          nom: this.formEleve.nom.trim() || undefined,
          date_naissance: this.formEleve.date_naissance || undefined,
          classe_id: this.formEleve.classe_id ?? undefined,
        })
      );

      this.modale.set(null);
      this.appliquer(majoutee);
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  /* ---------- Étape 3 : contrat ---------- */

  protected ouvrirContrat(): void {
    const d = this.detail();
    if (!d) return;

    const modele = this.formContratVide();
    modele.type_cours_id = d.type_cours?.id ?? null;

    // Une ligne préremplie par matière demandée, avec le volume réparti. Sans
    // matière demandée, une ligne vide : le contrat exige au moins une
    // affectation, l'admin choisit alors lui-même.
    const parLigne = Math.max(
      DemandesCoursAdminComponent.HEURES_PAR_AFFECTATION,
      Math.round((d.volume_horaire_estime || 0) / (d.matieres?.length || 1))
    );

    modele.affectations = (d.matieres?.length ? d.matieres : [null]).map((m) => ({
      matiere_id: m?.id ?? null,
      enseignant_id: null,
      taux_horaire_enseignant: null,
      nombre_heures_prevues: d.volume_horaire_estime ? parLigne : null,
      date_affectation: modele.date_debut,
    }));

    this.formContrat = modele;
    this.modale.set('contrat');
  }

  protected ajouterAffectation(): void {
    this.formContrat.affectations = [
      ...this.formContrat.affectations,
      {
        matiere_id: null,
        enseignant_id: null,
        taux_horaire_enseignant: null,
        nombre_heures_prevues: null,
        date_affectation: this.formContrat.date_debut || null,
      },
    ];
  }

  protected majAffectation(
    index: number,
    champ: 'matiere_id' | 'enseignant_id' | 'taux_horaire_enseignant' | 'nombre_heures_prevues',
    valeur: number | null
  ): void {
    const lignes = this.formContrat.affectations.map((a, i) =>
      i === index ? { ...a, [champ]: valeur } : a
    );

    // Choisir une matière propose d'emblée les enseignants qui la couvrent :
    // c'est la compétence que le serveur vérifie, autant ne pas laisser l'admin
    // la deviner.
    if (champ === 'matiere_id') {
      const candidats = this.enseignants().filter((p) =>
        p.profil?.matieres?.some((m) => m.id === valeur)
      );
      const ligne = lignes[index];

      if (candidats.length === 1 && !ligne.enseignant_id) {
        lignes[index] = { ...ligne, enseignant_id: candidats[0].profil?.id ?? null };
      }
    }

    this.formContrat.affectations = lignes;
  }

  protected async validerContrat(): Promise<void> {
    const d = this.detail();
    if (!d) return;

    this.soumission.set(true);
    this.alerte.set('');

    try {
      const majoutee = await firstValueFrom(
        this.api.creerContratDemandeCours(d.id, {
          type_cours_id: this.formContrat.type_cours_id,
          date_debut: this.formContrat.date_debut,
          date_fin: this.formContrat.date_fin || undefined,
          notes_admin: this.formContrat.notes_admin.trim() || undefined,
          affectations: this.formContrat.affectations.map((a) => ({
            matiere_id: a.matiere_id,
            enseignant_id: a.enseignant_id,
            // Le serveur exige un entier ; `Number()` évite d'envoyer « 1500 »
            // en texte si le champ a été rempli à la main.
            taux_horaire_enseignant: Math.round(a.taux_horaire_enseignant ?? 0),
            nombre_heures_prevues: a.nombre_heures_prevues,
            date_affectation: a.date_affectation,
          })),
        })
      );

      this.modale.set(null);
      this.appliquer(majoutee);
      await this.charger();
    } catch (e) {
      this.alerte.set(messageErreurApi(e));
    } finally {
      this.soumission.set(false);
    }
  }

  protected fermerModale(): void {
    this.modale.set(null);
  }

  /**
   * Remplace la demande par sa version renvoyée par le serveur, dans la liste
   * comme dans la fiche ouverte. Sans cela, l'admin garderait à l'écran des
   * étapes déjà franchies jusqu'au rechargement.
   */
  private appliquer(majoutee: DemandeCoursAdmin): void {
    this.demandes.update((liste) => liste.map((x) => (x.id === majoutee.id ? majoutee : x)));

    if (this.detail()?.id === majoutee.id) {
      this.detail.set(majoutee);
    }
  }

  protected dater(iso?: string | null): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(iso));
  }
}