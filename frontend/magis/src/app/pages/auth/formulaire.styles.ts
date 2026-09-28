/** Styles partagés des formulaires d'authentification (connexion, réinitialisation). */
export const STYLES_FORMULAIRE_AUTH = [
  `
    h1 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--mpc-texte);
      letter-spacing: -0.01em;
    }
    .sous-titre {
      margin: -0.4rem 0 0;
      color: var(--mpc-texte-doux);
      font-size: 0.9rem;
    }
    .alerte {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      padding: 0.7rem 0.85rem;
      border-radius: 0.75rem;
      background: var(--mpc-danger-tint);
      border: 1px solid transparent;
      color: var(--mpc-danger);
      font-size: 0.85rem;
      font-weight: 600;
    }
    .alerte i {
      margin-top: 2px;
    }
    .info {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      padding: 0.7rem 0.85rem;
      border-radius: 0.75rem;
      background: var(--mpc-succes-tint);
      color: var(--mpc-succes);
      font-size: 0.85rem;
      font-weight: 600;
    }
    .form-auth {
      display: grid;
      gap: 0.9rem;
    }
    .champ {
      display: grid;
      gap: 0.35rem;
    }
    .champ > span {
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--mpc-texte-doux);
    }
    .controle {
      display: flex;
      align-items: center;
      gap: 0.55rem;
      padding: 0 0.8rem;
      height: 48px;
      border-radius: 0.8rem;
      border: 1px solid var(--mpc-separateur);
      background: var(--mpc-fond);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .controle:focus-within {
      border-color: var(--mpc-primaire);
      box-shadow: 0 0 0 3px var(--mpc-primaire-tint);
    }
    .controle i {
      color: var(--mpc-texte-doux);
      font-size: 1.05rem;
    }
    .controle input {
      flex: 1;
      min-width: 0;
      border: none;
      outline: none;
      background: transparent;
      color: var(--mpc-texte);
      font-size: 0.95rem;
    }
    .controle input::placeholder {
      color: var(--mpc-texte-doux);
    }
    .liens {
      display: flex;
      justify-content: flex-end;
      margin-top: -0.2rem;
    }
    .liens a {
      font-size: 0.82rem;
      font-weight: 600;
      text-decoration: none;
    }
    .bouton {
      width: 100%;
      height: 48px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      border: none;
      border-radius: 0.8rem;
      background: var(--mpc-primaire);
      color: #fff;
      font-weight: 700;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background 0.15s ease, transform 0.1s ease;
    }
    .bouton:hover:not(:disabled) {
      background: var(--mpc-primaire-fonce);
    }
    .bouton:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    .secours {
      text-align: center;
      margin: 0;
      font-size: 0.85rem;
      color: var(--mpc-texte-doux);
    }
    .secours a {
      font-weight: 700;
      text-decoration: none;
    }
  `,
];