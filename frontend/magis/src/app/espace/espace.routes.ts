import { Routes } from '@angular/router';
import { roleAdminGuard, roleGuard, rolePedagogieGuard } from '../services/guard.auth';
import { EspaceComponent } from './espace.component';

/**
 * Coquille /espace : frame responsive (sidebar / rail / barre mobile) commune,
 * écrans chargés paresseusement. Les écrans admin sont gardés par rôle.
 */
export const ESPACE_ROUTES: Routes = [
  {
    path: '',
    component: EspaceComponent,
    children: [
      { path: '', pathMatch: 'full', loadComponent: () => import('./pages/dashboard.component').then((m) => m.DashboardComponent) },

      // ---------------------------------------------------------------------
      // Écrans réels — déclarés AVANT les placeholders génériques.
      //
      // Une route `path: 'finance'` matche par préfixe : si elle précède
      // `finance/periodes`, le placeholder avale le chemin et l'écran n'est
      // jamais atteint. L'ordre ci-dessous n'est donc pas cosmétique, il est
      // fonctionnel : du plus spécifique au plus générique.
      // ---------------------------------------------------------------------

      {
        // T7A.1 — périodes comptables (admin).
        path: 'finance/periodes',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/periodes.component').then((m) => m.PeriodesComponent),
      },
      {
        // T7A.2 — référentiels pédagogiques (admin).
        path: 'pedagogie/referentiels',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/referentiels.component').then((m) => m.ReferentielsComponent),
      },
      {
        // Demandes de cours reçues du site public (admin).
        // Le segment `:id` ouvre la fiche d'une demande : c'est la cible de
        // `route_angular` sur la notification correspondante.
        path: 'pedagogie/demandes-cours',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/demandes-cours-admin.component').then((m) => m.DemandesCoursAdminComponent),
      },
      {
        path: 'pedagogie/demandes-cours/:id',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/demandes-cours-admin.component').then((m) => m.DemandesCoursAdminComponent),
      },
      {
        // T7A.3 — contrats de cours & affectations (admin).
        path: 'pedagogie/contrats',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/contrats.component').then((m) => m.ContratsComponent),
      },
      {
        // Fiche d'un contrat ouverte depuis une notification admin : sans ce
        // segment `:id`, `/espace/pedagogie/contrats/12` ne matchait aucun
        // enfant et le `**` racine renvoyait sur le site public.
        path: 'pedagogie/contrats/:id',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/contrats.component').then((m) => m.ContratsComponent),
      },
      {
        // T7A.3 — « mes cours » : l'enseignant voit ce qu'il donne, l'élève ce
        // qu'il suit. Le parent en est volontairement exclu (T7A.3).
        path: 'modules/mes-cours',
        canActivate: [roleGuard('enseignant', 'eleve')],
        loadComponent: () => import('./pages/mes-cours.component').then((m) => m.MesCoursComponent),
      },
      {
        // T7A.8 — factures : la gestion financière vive en administration.
        path: 'finance/factures',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/factures-admin.component').then((m) => m.FacturesAdminComponent),
      },
      {
        // Fiche ciblée par la notification : ouvre directement la facture.
        path: 'finance/factures/:id',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/factures-admin.component').then((m) => m.FacturesAdminComponent),
      },
      {
        // T7A.9 — bulletins de paie : établis depuis les rapports VALIDÉS
        // (D-051), puis corrigés/payés/ajustés par l'administration.
        path: 'finance/bulletins-paie',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/bulletins-admin.component').then((m) => m.BulletinsAdminComponent),
      },
      {
        // Fiche ciblée par la notification : ouvre directement le bulletin.
        path: 'finance/bulletins-paie/:id',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/bulletins-admin.component').then((m) => m.BulletinsAdminComponent),
      },
      {
        // T7A.8 — « mes factures » : lecture seule pour le parent.
        path: 'modules/mes-factures',
        canActivate: [roleGuard('parent')],
        loadComponent: () => import('./pages/mes-factures.component').then((m) => m.MesFacturesComponent),
      },
      {
        // Fiche ciblée par la notification « facture » : ouvre directement
        // la facture du parent.
        path: 'modules/mes-factures/:id',
        canActivate: [roleGuard('parent')],
        loadComponent: () => import('./pages/mes-factures.component').then((m) => m.MesFacturesComponent),
      },
      {
        // T7A.3 — « mes contrats » : lecture seule du parent (contrats de ses
        // enfants). Cible des notifications `contrat` / `affectation`, qui
        // pointaient auparavant vers la route admin inexistante.
        path: 'modules/mes-contrats',
        canActivate: [roleGuard('parent')],
        loadComponent: () => import('./pages/mes-contrats.component').then((m) => m.MesContratsComponent),
      },
      {
        // Fiche ciblée par la notification : ouvre directement le contrat.
        path: 'modules/mes-contrats/:id',
        canActivate: [roleGuard('parent')],
        loadComponent: () => import('./pages/mes-contrats.component').then((m) => m.MesContratsComponent),
      },
      {
        // T7A.9 — « mes bulletins de paie » : cycle scellé de l'enseignant
        // (consulter, valider/contester, confirmer la réception).
        path: 'modules/mes-bulletins',
        canActivate: [roleGuard('enseignant')],
        loadComponent: () => import('./pages/mes-bulletins.component').then((m) => m.MesBulletinsComponent),
      },
      {
        // Fiche ciblée par la notification « bulletin_paie » : ouvre
        // directement le bulletin de l'enseignant.
        path: 'modules/mes-bulletins/:id',
        canActivate: [roleGuard('enseignant')],
        loadComponent: () => import('./pages/mes-bulletins.component').then((m) => m.MesBulletinsComponent),
      },
      {
        // T7A.7 — rapports mensuels : validation en administration.
        path: 'pedagogie/rapports-mensuels',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/rapports-admin.component').then((m) => m.RapportsAdminComponent),
      },
      {
        // Fiche ciblée par la notification « rapport » (reportSubmitted) :
        // ouvre directement le rapport à valider.
        path: 'pedagogie/rapports-mensuels/:id',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/rapports-admin.component').then((m) => m.RapportsAdminComponent),
      },
      {
        // T7A.7 — modèle de rapport mensuel : l'administration compose les
        // sections et les éléments que l'enseignant remplit au dépôt. Les
        // informations générales et le bilan des activités ne sont pas ici.
        path: 'pedagogie/modele-rapport',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/modele-rapport.component').then((m) => m.ModeleRapportComponent),
      },
      {
        // T7A.5 — cahier de texte partagé enseignant (écrit) / parent / élève
        // (lecture). Même trio de rôles que le planning : l'enseignant saisit,
        // la famille consulte et exporte en PDF.
        path: 'modules/cahier-de-texte',
        canActivate: [rolePedagogieGuard],
        loadComponent: () => import('./pages/cahier-de-texte.component').then((m) => m.CahierDeTexteComponent),
      },
      {
        // T7A.7 — rapports mensuels de l'enseignant : dépôt, correction,
        // re-soumission, consultation. Lecture réservée à l'enseignant
        // lui-même (ses chiffres et ses commentaires).
        path: 'modules/rapports-mensuels',
        canActivate: [roleGuard('enseignant')],
        loadComponent: () => import('./pages/rapports-enseignant.component').then((m) => m.RapportsEnseignantComponent),
      },
      {
        // Fiche ciblée par la notification « rapport » (reportValidated /
        // reportRejected) : rouvre le rapport renvoyé au dépôt.
        path: 'modules/rapports-mensuels/:id',
        canActivate: [roleGuard('enseignant')],
        loadComponent: () => import('./pages/rapports-enseignant.component').then((m) => m.RapportsEnseignantComponent),
      },
      {
        // T7A.4 — planning partagé enseignant / parent / élève.
        path: 'modules/planning',
        canActivate: [rolePedagogieGuard],
        loadComponent: () => import('./pages/planning.component').then((m) => m.PlanningComponent),
      },

      // ---------------------------------------------------------------------
      // Placeholders génériques — doivent rester en dernier.
      // ---------------------------------------------------------------------
      { path: 'finance', loadComponent: () => import('./pages/placeholder-module.component').then((m) => m.PlaceholderModuleComponent), data: { module: 'finance' } },
      { path: 'rapports', loadComponent: () => import('./pages/placeholder-module.component').then((m) => m.PlaceholderModuleComponent), data: { module: 'rapports' } },
      { path: 'modules/:slug', loadComponent: () => import('./pages/placeholder-module.component').then((m) => m.PlaceholderModuleComponent) },
      {
        path: 'actualites',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/actualites.component').then((m) => m.ActualitesBackofficeComponent),
      },
      {
        path: 'actualites/nouvelle',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/actualite-edition.component').then((m) => m.ActualiteEditionComponent),
      },
      {
        path: 'actualites/:id/editer',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/actualite-edition.component').then((m) => m.ActualiteEditionComponent),
      },
      {
        path: 'faq',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/faq-gestion.component').then((m) => m.FaqGestionComponent),
      },
      {
        path: 'fiche-cabinet',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/fiche-cabinet.component').then((m) => m.FicheCabinetComponent),
      },
      {
        path: 'utilisateurs',
        canActivate: [roleAdminGuard],
        loadComponent: () => import('./pages/utilisateurs.component').then((m) => m.UtilisateursComponent),
      },
      { path: 'notifications', loadComponent: () => import('./pages/notifications.component').then((m) => m.NotificationsComponent) },
      { path: 'profil', loadComponent: () => import('./pages/profil.component').then((m) => m.ProfilComponent) },
      { path: 'choix-role', loadComponent: () => import('./pages/choix-role.component').then((m) => m.ChoixRoleComponent) },
    ],
  },
];