import { Routes } from '@angular/router';
import { roleAdminGuard } from '../services/guard.auth';
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