import { Routes } from '@angular/router';
import { espaceGuard } from './services/guard.auth';

export const routes: Routes = [
  { path: '', pathMatch: 'full', loadComponent: () => import('./pages/accueil.component').then((m) => m.AccueilComponent) },
  { path: 'actualites', loadComponent: () => import('./pages/actualites.component').then((m) => m.ActualitesComponent) },
  { path: 'actualites/:slug', loadComponent: () => import('./pages/actualite-detail.component').then((m) => m.ActualiteDetailComponent) },
  { path: 'bibliotheque', loadComponent: () => import('./pages/bibliotheque.component').then((m) => m.BibliothequeComponent) },
  { path: 'boutique', loadComponent: () => import('./pages/boutique.component').then((m) => m.BoutiqueComponent) },
  { path: 'faq', loadComponent: () => import('./pages/faq.component').then((m) => m.FaqComponent) },
  { path: 'demande-cours', loadComponent: () => import('./pages/demande-cours.component').then((m) => m.DemandeCoursComponent) },
  { path: 'contact', loadComponent: () => import('./pages/contact.component').then((m) => m.ContactComponent) },
  { path: 'connexion', loadComponent: () => import('./pages/auth/connexion.component').then((m) => m.ConnexionComponent) },
  { path: 'mot-de-passe-oublie', loadComponent: () => import('./pages/auth/mot-de-passe-oublie.component').then((m) => m.MotDePasseOublieComponent) },
  { path: 'reinitialiser-mot-de-passe', loadComponent: () => import('./pages/auth/reinitialiser-mot-de-passe.component').then((m) => m.ReinitialiserMotDePasseComponent) },
  {
    path: 'espace',
    canActivate: [espaceGuard],
    loadChildren: () => import('./espace/espace.routes').then((m) => m.ESPACE_ROUTES),
  },
  { path: '**', pathMatch: 'full', redirectTo: '' },
];