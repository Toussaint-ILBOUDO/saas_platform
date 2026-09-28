import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MODULES_DESCRIPTION } from '../menu';

/** Écran provisoire élégant pour les modules non encore livrés (Finance, Rapports, Pédagogie…). */
@Component({
  selector: 'espace-placeholder-module',
  template: `
    <section class="page-module">
      <div class="bloc">
        <span class="bloc-icone"><i class="bi {{ module.icone }}"></i></span>
        <span class="surtitre">Module à venir</span>
        <h1>{{ module.titre }}</h1>
        <p>{{ module.phrase }}</p>
        <div class="genre"><i class="bi bi-hourglass-split"></i> En construction</div>
      </div>
    </section>
  `,
  styles: [
    `
      .page-module {
        display: grid;
        place-items: center;
        min-height: calc(100dvh - 220px);
      }
      .bloc {
        max-width: 460px;
        text-align: center;
        padding: 2.2rem 1.6rem;
        border-radius: 1.25rem;
        background: var(--mpc-surface);
        border: 1px dashed var(--mpc-separateur);
        box-shadow: var(--mpc-ombre);
      }
      .bloc-icone {
        display: inline-grid;
        place-items: center;
        width: 68px;
        height: 68px;
        margin-bottom: 1rem;
        border-radius: 1.1rem;
        font-size: 1.7rem;
        color: var(--mpc-primaire);
        background: var(--mpc-primaire-tint);
      }
      .bloc .surtitre {
        display: flex;
        justify-content: center;
      }
      .bloc .surtitre::before {
        display: none;
      }
      h1 {
        margin: 0.3rem 0 0.5rem;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--mpc-bleu);
      }
      p {
        margin: 0 0 1rem;
        color: var(--mpc-texte-doux);
        font-size: 0.9rem;
        line-height: 1.7;
      }
      .genre {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.85rem;
        border-radius: 999px;
        background: var(--mpc-attention-tint);
        color: var(--mpc-attention);
        font-size: 0.78rem;
        font-weight: 700;
      }
    `,
  ],
})
export class PlaceholderModuleComponent {
  private readonly route = inject(ActivatedRoute);

  readonly module = MODULES_DESCRIPTION[this.route.snapshot.data['module'] ?? this.route.snapshot.paramMap.get('slug') ?? '']
    ?? {
      titre: 'Module',
      icone: 'bi-box',
      phrase: 'Ce module arrive bientôt sur l’espace du Magis Plus Center.',
    };
}