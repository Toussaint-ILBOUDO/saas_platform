import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { FaqQuestionBackoffice, FaqSectionBackoffice } from '../../models';
import { messageErreurApi } from '../../services/messages';

interface ModeleSection {
  title: string;
  description: string;
  order_index: number;
}

interface ModeleQuestion {
  question: string;
  answer: string;
  order_index: number;
}

const SECTION_VIDE = (): ModeleSection => ({ title: '', description: '', order_index: 0 });
const QUESTION_VIDE = (): ModeleQuestion => ({ question: '', answer: '', order_index: 0 });

/** Écran admin « FAQ » : sections et questions (CRUD via l'API admin). */
@Component({
  imports: [FormsModule],
  selector: 'espace-faq',
  styles: [
    `
      :host {
        display: block;
        max-width: 860px;
      }
      .entete {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.1rem;
      }
      .entete h1 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .entete p {
        margin: 0.2rem 0 0;
        color: var(--mpc-texte-doux);
        font-size: 0.85rem;
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
      }
      .btn-primaire:hover:not(:disabled) {
        background: var(--mpc-primaire-fonce);
      }
      .btn-primaire:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .toast {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: 0.8rem;
        font-size: 0.86rem;
        font-weight: 600;
        background: var(--mpc-danger-tint);
        color: var(--mpc-danger);
      }
      .cartes {
        display: grid;
        gap: 0.7rem;
      }
      .carte {
        background: var(--mpc-surface);
        border: 1px solid var(--mpc-separateur);
        border-radius: 1rem;
        overflow: hidden;
      }
      .carte-entete {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.95rem 1.1rem;
        cursor: pointer;
      }
      .carte-texte {
        flex: 1;
        min-width: 0;
      }
      .carte-texte b {
        display: block;
        font-size: 0.95rem;
        color: var(--mpc-bleu);
      }
      .carte-texte small {
        color: var(--mpc-texte-doux);
        font-size: 0.8rem;
      }
      .compteur {
        flex: 0 0 auto;
        font-size: 0.74rem;
        font-weight: 700;
        padding: 0.25rem 0.6rem;
        border-radius: 999px;
        background: var(--mpc-bleu-tint);
        color: var(--mpc-bleu-clair);
      }
      .carte-cheo {
        flex: 0 0 auto;
        color: var(--mpc-texte-doux);
      }
      .badge-active {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
      }
      .badge-active._oui {
        background: var(--mpc-succes-tint);
        color: var(--mpc-succes);
      }
      .badge-active._non {
        background: var(--mpc-abandon);
        color: var(--mpc-texte-doux);
      }
      .carte-corps {
        border-top: 1px solid var(--mpc-separateur);
        padding: 1rem 1.1rem;
      }
      .question {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        padding: 0.7rem 0;
        border-bottom: 1px solid var(--mpc-separateur);
      }
      .question:last-child {
        border-bottom: none;
      }
      .question-texte {
        flex: 1;
        min-width: 0;
      }
      .question-texte b {
        display: block;
        font-size: 0.88rem;
      }
      .question-texte p {
        margin: 0.2rem 0 0;
        color: var(--mpc-texte-doux);
        font-size: 0.82rem;
      }
      .question-actions {
        display: flex;
        gap: 0.3rem;
      }
      .mini {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        border-radius: 0.6rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte-doux);
        cursor: pointer;
      }
      .mini:hover {
        border-color: var(--mpc-primaire);
        color: var(--mpc-primaire);
      }
      .mini._danger:hover {
        border-color: var(--mpc-danger);
        color: var(--mpc-danger);
      }
      .vide {
        margin: 0;
        padding: 0.9rem 0;
        text-align: center;
        color: var(--mpc-texte-doux);
        font-size: 0.85rem;
      }
      .formulaire {
        display: grid;
        gap: 0.7rem;
        margin-top: 0.7rem;
        padding: 1rem;
        border-radius: 0.85rem;
        background: var(--mpc-abandon);
      }
      .formulaire h3 {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      .grille {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.7rem;
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
      .champ textarea {
        padding: 0.6rem 0.75rem;
        border-radius: 0.7rem;
        border: 1px solid var(--mpc-separateur);
        background: var(--mpc-fond);
        color: var(--mpc-texte);
        font: inherit;
        font-size: 0.88rem;
        outline: none;
      }
      .champ textarea {
        min-height: 80px;
        resize: vertical;
      }
      .champ input:focus,
      .champ textarea:focus {
        border-color: var(--mpc-primaire);
        box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
      }
      .actions-form {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
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
      .btn-valider {
        padding: 0.55rem 0.95rem;
        border: none;
        border-radius: 0.75rem;
        background: var(--mpc-primaire);
        color: #fff;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
      }
      .spinner {
        text-align: center;
        padding: 2rem;
        color: var(--mpc-texte-doux);
      }
      @media (max-width: 520px) {
        .grille {
          grid-template-columns: 1fr;
        }
      }
    `,
  ],
  template: `
    <div class="entete">
      <div>
        <h1>FAQ du site</h1>
        <p>Sections et questions fréquentes affichées sur la page publique.</p>
      </div>
    </div>

    @if (toast()) {
      <div class="toast"><i class="bi bi-exclamation-triangle"></i>{{ toast() }}</div>
    }

    @if (charge()) {
      <div class="spinner"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
    } @else {
      <div class="cartes">
        @for (section of sections(); track section.id) {
          <div class="carte">
            <div class="carte-entete" (click)="basculer(section)">
              <span class="carte-texte">
                <b>{{ section.title }}</b>
                @if (section.description) {
                  <small>{{ section.description }}</small>
                }
              </span>
              <span class="badge-active" [class._oui]="section.is_active" [class._non]="!section.is_active">
                {{ section.is_active ? 'Visible' : 'Masquée' }}
              </span>
              <span class="compteur">{{ section.questions_count }} question(s)</span>
              <button class="mini _danger" type="button" title="Supprimer la section" (click)="supprimerSection(section)">
                <i class="bi bi-trash"></i>
              </button>
              <i class="bi bi-chevron-down carte-cheo"></i>
            </div>

            @if (sectionOuverte() === section.id) {
              <div class="carte-corps">
                @if (questions().length === 0) {
                  <p class="vide">Aucune question dans cette section.</p>
                }
                @for (q of questions(); track q.id) {
                  <div class="question">
                    <div class="question-texte">
                      <b>{{ q.question }}</b>
                      <p>{{ q.answer }}</p>
                    </div>
                    <div class="question-actions">
                      <button class="mini" type="button" (click)="editerQuestion(section, q)" title="Modifier"><i class="bi bi-pencil"></i></button>
                      <button class="mini _danger" type="button" (click)="supprimerQuestion(section, q)" title="Supprimer"><i class="bi bi-trash"></i></button>
                    </div>
                  </div>
                }

                @if (formQuestionOuvert() && questionSection() === section.id) {
                  <div class="formulaire">
                    <h3>{{ questionModele().id ? 'Modifier la question' : 'Nouvelle question' }}</h3>
                    <label class="champ">
                      <span>Question</span>
                      <input [(ngModel)]="questionModele().question" name="question" />
                    </label>
                    <label class="champ">
                      <span>Réponse</span>
                      <textarea [(ngModel)]="questionModele().answer" name="answer"></textarea>
                    </label>
                    <label class="champ">
                      <span>Ordre d'affichage</span>
                      <input type="number" [(ngModel)]="questionModele().order_index" name="odq" />
                    </label>
                    <div class="actions-form">
                      <button class="btn-neutral" type="button" (click)="annulerQuestion()">Annuler</button>
                      <button class="btn-valider" type="button" (click)="sauverQuestion(section)">Enregistrer</button>
                    </div>
                  </div>
                } @else {
                  <div class="actions-form" style="justify-content:flex-start;padding-top:0.4rem">
                    <button class="btn-valider" type="button" (click)="ouvrirQuestion(section)"><i class="bi bi-plus-lg"></i> Ajouter une question</button>
                  </div>
                }
              </div>
            }
          </div>
        }
      </div>

      @if (!formSectionOuvert()) {
        <div class="actions-form" style="justify-content:flex-start;margin-top:1rem">
          <button class="btn-primaire" type="button" (click)="ouvrirSection()"><i class="bi bi-folder-plus"></i> Nouvelle section</button>
        </div>
      } @else {
        <div class="formulaire" style="margin-top:1rem">
          <h3>{{ sectionModele().id ? 'Modifier la section' : 'Nouvelle section' }}</h3>
          <div class="grille">
            <label class="champ">
              <span>Titre *</span>
              <input [(ngModel)]="sectionModele().title" name="stitle" />
            </label>
            <label class="champ">
              <span>Ordre d'affichage</span>
              <input type="number" [(ngModel)]="sectionModele().order_index" name="sordre" />
            </label>
          </div>
          <label class="champ">
            <span>Description</span>
            <textarea [(ngModel)]="sectionModele().description" name="sdesc" style="min-height:60px"></textarea>
          </label>
          <div class="actions-form">
            <button class="btn-neutral" type="button" (click)="annulerSection()">Annuler</button>
            <button class="btn-valider" type="button" (click)="sauverSection()">Enregistrer</button>
          </div>
        </div>
      }

      @if (sections().length) {
        <div class="actions-form" style="justify-content:flex-start;margin-top:1rem">
          <button class="btn-neutral" type="button" (click)="editerSection(sections()[0])"><i class="bi bi-pencil"></i> Modifier la première section</button>
        </div>
      }
    }
  `,
})
export class FaqGestionComponent implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly sections = signal<FaqSectionBackoffice[]>([]);
  protected readonly questions = signal<FaqQuestionBackoffice[]>([]);
  protected readonly sectionOuverte = signal<number | null>(null);
  protected readonly charge = signal(true);
  protected readonly toast = signal('');

  protected readonly formSectionOuvert = signal(false);
  protected readonly formQuestionOuvert = signal(false);
  protected readonly questionSection = signal<number | null>(null);
  protected readonly sectionModele = signal<{ id: number | null } & ModeleSection>({ id: null, ...SECTION_VIDE() });
  protected readonly questionModele = signal<{ id: number | null; faq_section_id: number | null } & ModeleQuestion>({
    id: null,
    faq_section_id: null,
    ...QUESTION_VIDE(),
  });

  ngOnInit(): void {
    this.chargerSections();
  }

  protected basculer(section: FaqSectionBackoffice): void {
    if (this.sectionOuverte() === section.id) {
      this.sectionOuverte.set(null);
      this.questions.set([]);
      return;
    }
    this.sectionOuverte.set(section.id);
    this.chargerQuestions(section.id);
  }

  protected ouvrirSection(): void {
    this.sectionModele.set({ id: null, ...SECTION_VIDE() });
    this.formSectionOuvert.set(true);
  }

  protected editerSection(section: FaqSectionBackoffice): void {
    this.sectionModele.set({
      id: section.id,
      title: section.title,
      description: section.description ?? '',
      order_index: section.order_index,
    });
    this.formSectionOuvert.set(true);
  }

  protected annulerSection(): void {
    this.formSectionOuvert.set(false);
  }

  protected async sauverSection(): Promise<void> {
    const m = this.sectionModele();
    if (!m.title.trim()) {
      this.toast.set('Le titre de la section est obligatoire.');
      return;
    }
    const payload = { title: m.title.trim(), description: m.description.trim(), order_index: Number(m.order_index) || 0 };
    try {
      if (m.id) {
        await this.api.majFaqSection(m.id, payload).toPromise();
      } else {
        await this.api.creerFaqSection(payload).toPromise();
      }
      this.formSectionOuvert.set(false);
      this.chargerSections();
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  protected async supprimerSection(section: FaqSectionBackoffice): Promise<void> {
    if (!confirm(`Supprimer la section « ${section.title} » et ses questions ?`)) return;
    try {
      await this.api.supprimerFaqSection(section.id).toPromise();
      if (this.sectionOuverte() === section.id) {
        this.sectionOuverte.set(null);
        this.questions.set([]);
      }
      this.chargerSections();
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  protected ouvrirQuestion(section: FaqSectionBackoffice): void {
    this.questionModele.set({ id: null, faq_section_id: section.id, ...QUESTION_VIDE() });
    this.questionSection.set(section.id);
    this.formQuestionOuvert.set(true);
  }

  protected editerQuestion(section: FaqSectionBackoffice, q: FaqQuestionBackoffice): void {
    this.questionModele.set({
      id: q.id,
      faq_section_id: section.id,
      question: q.question,
      answer: q.answer,
      order_index: q.order_index,
    });
    this.questionSection.set(section.id);
    this.formQuestionOuvert.set(true);
  }

  protected annulerQuestion(): void {
    this.formQuestionOuvert.set(false);
    this.questionSection.set(null);
  }

  protected async sauverQuestion(section: FaqSectionBackoffice): Promise<void> {
    const m = this.questionModele();
    if (!m.question.trim() || !m.answer.trim()) {
      this.toast.set('La question et la réponse sont obligatoires.');
      return;
    }
    const payload = { question: m.question.trim(), answer: m.answer.trim(), order_index: Number(m.order_index) || 0 };
    try {
      if (m.id) {
        await this.api.majFaqQuestion(m.id, payload).toPromise();
      } else {
        await this.api.creerFaqQuestion({ ...payload, faq_section_id: section.id }).toPromise();
      }
      this.formQuestionOuvert.set(false);
      this.questionSection.set(null);
      await this.chargerQuestions(section.id);
      await this.chargerSections();
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  protected async supprimerQuestion(section: FaqSectionBackoffice, q: FaqQuestionBackoffice): Promise<void> {
    if (!confirm(`Supprimer « ${q.question} » ?`)) return;
    try {
      await this.api.supprimerFaqQuestion(q.id).toPromise();
      await this.chargerQuestions(section.id);
      await this.chargerSections();
    } catch (e) {
      this.toast.set(messageErreurApi(e));
    }
  }

  private chargerSections(): void {
    this.api.getFaqSections().subscribe({
      next: (r) => this.sections.set(r.data),
      error: (e) => this.toast.set(messageErreurApi(e)),
      complete: () => this.charge.set(false),
    });
  }

  private chargerQuestions(sectionId: number): Promise<void> {
    return new Promise((resolve) => {
      this.api.getFaqQuestions(sectionId).subscribe({
        next: (r) => this.questions.set(r.data),
        error: (e) => this.toast.set(messageErreurApi(e)),
        complete: () => resolve(),
      });
    });
  }
}