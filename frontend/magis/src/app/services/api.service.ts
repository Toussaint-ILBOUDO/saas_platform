import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, filter, map } from 'rxjs';
import {
  ApiReponse,
  CabinetPublic,
  DemandeCours,
  Enseignant,
  Actualite,
  FaqSection,
  DocumentBibliotheque,
  MetaPage,
  Produit,
  StatsCabinet,
  Temoignage,
  ReferencesPubliques,
  SessionUtilisateur,
  UtilisateurAdmin,
  ActualiteBackoffice,
  FaqSectionBackoffice,
  FaqQuestionBackoffice,
  ContenuPublicBackoffice,
  NotificationEspace,
} from '../models';

/** Chemin de base de l'API (même origine en prod ; proxy /api en dev). */
export const API_BASE = '/api';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);

  /** Cache court terme des informations publiques du cabinet (D-044). */
  private readonly cabinetCache = new BehaviorSubject<CabinetPublic | null>(null);
  private cabinetCherche = false;

  getCabinetPublic(): Observable<CabinetPublic> {
    if (!this.cabinetCherche) {
      this.cabinetCherche = true;
      this.rafraichirCabinetPublic();
    }
    return this.cabinetCache.pipe(filter((d): d is CabinetPublic => d !== null));
  }

  /** Recharge les informations publiques (fiche, logo…) après une sauvegarde. */
  rafraichirCabinetPublic(): void {
    this.http.get<CabinetPublic>(`${API_BASE}/public/cabinet`).subscribe({
      next: (donnees) => this.cabinetCache.next(donnees),
      error: () => {
        /* conserve le cache existant si le rechargement échoue */
      },
    });
  }

  getStats(): Observable<StatsCabinet> {
    return this.http
      .get<{ data: StatsCabinet }>(`${API_BASE}/public/stats`)
      .pipe(map((r) => r.data));
  }

  getReferences(): Observable<ReferencesPubliques> {
    return this.http
      .get<{ data: ReferencesPubliques }>(`${API_BASE}/public/references`)
      .pipe(map((r) => r.data));
  }

  getEnseignants(page = 1): Observable<{ data: Enseignant[]; meta?: MetaPage }> {
    return this.http.get<{ data: Enseignant[]; meta?: MetaPage }>(
      `${API_BASE}/public/enseignants?page=${page}`
    );
  }

  getTemoignages(page = 1): Observable<{ data: Temoignage[]; meta?: MetaPage }> {
    return this.http.get<{ data: Temoignage[]; meta?: MetaPage }>(
      `${API_BASE}/public/temoignages?page=${page}`
    );
  }

  getActualites(page = 1, perPage = 9): Observable<{ data: Actualite[]; meta: MetaPage }> {
    return this.http.get<{ data: Actualite[]; meta: MetaPage }>(
      `${API_BASE}/public/actualites?page=${page}&per_page=${perPage}`
    );
  }

  getActualite(slug: string): Observable<Actualite> {
    return this.http.get<Actualite>(`${API_BASE}/public/actualites/${slug}`);
  }

  getFaq(): Observable<FaqSection[]> {
    return this.http
      .get<{ data: FaqSection[] }>(`${API_BASE}/public/faq`)
      .pipe(map((r) => r.data));
  }

  getDocuments(page = 1, perPage = 12, recherche = ''): Observable<{
    data: DocumentBibliotheque[];
    meta: MetaPage;
  }> {
    const q = recherche ? `&search=${encodeURIComponent(recherche)}` : '';
    return this.http.get<{ data: DocumentBibliotheque[]; meta: MetaPage }>(
      `${API_BASE}/public/documents?page=${page}&per_page=${perPage}${q}`
    );
  }

  getProduits(page = 1, perPage = 12, recherche = ''): Observable<{
    data: Produit[];
    meta: MetaPage;
  }> {
    const q = recherche ? `&search=${encodeURIComponent(recherche)}` : '';
    return this.http.get<{ data: Produit[]; meta: MetaPage }>(
      `${API_BASE}/public/produits?page=${page}&per_page=${perPage}${q}`
    );
  }

  envoyerDemandeCours(payload: DemandeCours): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/public/demandes-cours`, payload);
  }

  /* ============================================================
     Authentification / session (/api/auth)
     ============================================================ */

  connexion(email: string, motDePasse: string): Observable<{ message: string; user: SessionUtilisateur }> {
    return this.http.post<{ message: string; user: SessionUtilisateur }>(`${API_BASE}/auth/connexion`, {
      email,
      password: motDePasse,
    });
  }

  moi(): Observable<{ user: SessionUtilisateur }> {
    return this.http.get<{ user: SessionUtilisateur }>(`${API_BASE}/auth/moi`);
  }

  deconnexion(): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/auth/deconnexion`, {});
  }

  roleActif(role: string): Observable<{ message: string; active_role: string }> {
    return this.http.post<{ message: string; active_role: string }>(`${API_BASE}/auth/role-actif`, { role });
  }

  motDePasseOublie(email: string): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/auth/mot-de-passe-oublie`, { email });
  }

  reinitialiserMotDePasse(email: string, token: string, motDePasse: string): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/auth/reinitialiser-mot-de-passe`, {
      email,
      token,
      password: motDePasse,
      password_confirmation: motDePasse,
    });
  }

  changerMotDePasse(motDePasseActuel: string, nouveauMotDePasse: string): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/auth/changer-mot-de-passe`, {
      mot_de_passe_actuel: motDePasseActuel,
      nouveau_mot_de_passe: nouveauMotDePasse,
    });
  }

  /* ============================================================
     Backoffice — contenu public (fiche cabinet)
     ============================================================ */

  getContenuPublic(): Observable<ContenuPublicBackoffice> {
    return this.http
      .get<{ data: ContenuPublicBackoffice }>(`${API_BASE}/admin/contenu-public`)
      .pipe(map((r) => r.data));
  }

  putContenuPublic(payload: ContenuPublicBackoffice): Observable<{ message: string; data: ContenuPublicBackoffice }> {
    return this.http.put<{ message: string; data: ContenuPublicBackoffice }>(
      `${API_BASE}/admin/contenu-public`,
      payload
    );
  }

  mettreAJourLogo(form: FormData): Observable<{ message: string; data: ContenuPublicBackoffice }> {
    return this.http.post<{ message: string; data: ContenuPublicBackoffice }>(
      `${API_BASE}/admin/contenu-public/logo`,
      form
    );
  }

  /* ============================================================
     Backoffice — actualités
     ============================================================ */

  getActualitesAdmin(page = 1, perPage = 12, recherche = '', statut = ''): Observable<{
    data: ActualiteBackoffice[];
    meta: MetaPage;
  }> {
    const q = recherche ? `&search=${encodeURIComponent(recherche)}` : '';
    const s = statut ? `&statut=${encodeURIComponent(statut)}` : '';
    return this.http.get<{ data: ActualiteBackoffice[]; meta: MetaPage }>(
      `${API_BASE}/admin/actualites?page=${page}&per_page=${perPage}${q}${s}`
    );
  }

  getActualiteAdmin(id: number): Observable<ActualiteBackoffice> {
    return this.http
      .get<{ data: ActualiteBackoffice }>(`${API_BASE}/admin/actualites/${id}`)
      .pipe(map((r) => r.data));
  }

  creerActualite(form: FormData): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/admin/actualites`, form);
  }

  majActualite(id: number, form: FormData): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/admin/actualites/${id}`, form);
  }

  supprimerActualite(id: number): Observable<ApiReponse> {
    return this.http.delete<ApiReponse>(`${API_BASE}/admin/actualites/${id}`);
  }

  /* ============================================================
     Backoffice — FAQ
     ============================================================ */

  getFaqSections(page = 1, perPage = 50): Observable<{ data: FaqSectionBackoffice[]; meta: MetaPage }> {
    return this.http.get<{ data: FaqSectionBackoffice[]; meta: MetaPage }>(
      `${API_BASE}/admin/faq/sections?page=${page}&per_page=${perPage}`
    );
  }

  creerFaqSection(payload: Partial<FaqSectionBackoffice>): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/admin/faq/sections`, payload);
  }

  majFaqSection(id: number, payload: Partial<FaqSectionBackoffice>): Observable<ApiReponse> {
    return this.http.put<ApiReponse>(`${API_BASE}/admin/faq/sections/${id}`, payload);
  }

  supprimerFaqSection(id: number): Observable<ApiReponse> {
    return this.http.delete<ApiReponse>(`${API_BASE}/admin/faq/sections/${id}`);
  }

  getFaqQuestions(sectionId: number): Observable<{ data: FaqQuestionBackoffice[]; meta: MetaPage }> {
    return this.http.get<{ data: FaqQuestionBackoffice[]; meta: MetaPage }>(
      `${API_BASE}/admin/faq/sections/${sectionId}/questions`
    );
  }

  creerFaqQuestion(payload: Partial<FaqQuestionBackoffice>): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/admin/faq/questions`, payload);
  }

  majFaqQuestion(id: number, payload: Partial<FaqQuestionBackoffice>): Observable<ApiReponse> {
    return this.http.put<ApiReponse>(`${API_BASE}/admin/faq/questions/${id}`, payload);
  }

  supprimerFaqQuestion(id: number): Observable<ApiReponse> {
    return this.http.delete<ApiReponse>(`${API_BASE}/admin/faq/questions/${id}`);
  }

  /* ============================================================
     Backoffice — utilisateurs
     ============================================================ */

  getUtilisateurs(page = 1, perPage = 15, recherche = '', role = '', statut?: boolean): Observable<{
    data: UtilisateurAdmin[];
    meta: MetaPage;
  }> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('per_page', String(perPage));
    if (recherche) params.set('search', recherche);
    if (role) params.set('role', role);
    if (statut !== undefined) params.set('statut', statut ? '1' : '0');
    return this.http.get<{ data: UtilisateurAdmin[]; meta: MetaPage }>(
      `${API_BASE}/admin/utilisateurs?${params.toString()}`
    );
  }

  creerUtilisateur(payload: Partial<UtilisateurAdmin> & { password?: string }): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/admin/utilisateurs`, payload);
  }

  majUtilisateur(id: number, payload: Partial<UtilisateurAdmin> & { password?: string }): Observable<ApiReponse> {
    return this.http.put<ApiReponse>(`${API_BASE}/admin/utilisateurs/${id}`, payload);
  }

  activerUtilisateur(id: number): Observable<ApiReponse> {
    return this.http.patch<ApiReponse>(`${API_BASE}/admin/utilisateurs/${id}/activer`, {});
  }

  suspendreUtilisateur(id: number): Observable<ApiReponse> {
    return this.http.patch<ApiReponse>(`${API_BASE}/admin/utilisateurs/${id}/suspendre`, {});
  }

  /* ============================================================
     Notifications
     ============================================================ */

  getNotifications(page = 1, perPage = 20): Observable<{
    data: NotificationEspace[];
    meta: MetaPage & { non_lues: number };
  }> {
    return this.http.get<{ data: NotificationEspace[]; meta: MetaPage & { non_lues: number } }>(
      `${API_BASE}/notifications?page=${page}&per_page=${perPage}`
    );
  }

  lireToutesNotifications(): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/notifications/lire-toutes`, {});
  }

  marquerNotificationLue(id: number): Observable<ApiReponse> {
    return this.http.post<ApiReponse>(`${API_BASE}/notifications/${id}/lue`, {});
  }

  supprimerNotification(id: number): Observable<ApiReponse> {
    return this.http.delete<ApiReponse>(`${API_BASE}/notifications/${id}`);
  }
}