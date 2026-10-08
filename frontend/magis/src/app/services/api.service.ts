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
  PeriodeComptable,
  CreneauPeriode,
  PlanningEnseignant,
  MesPlanningParent,
  MesPlanningEleve,
  CreneauFormulaire,
  CreneauPlanning,
  ClasseReferentiel,
  CreneauClasse,
  MatiereReferentiel,
  CreneauMatiere,
  TypeCoursReferentiel,
  CreneauTypeCours,
  EnseignantReferentiel,
  CreneauEnseignant,
  ContratCours,
  Affectation,
  ContratFormulaire,
  AffectationFormulaire,
  EnfantConsulter,
  CahierTexte,
  CahierTexteCorrection,
  AffectationCahierTexte,
  CahierTexteFormulaire,
  ReponseCahierTexte,
  RapportMensuel,
  RapportMensuelFormulaire,
  RapportMensuelApercu,
  RapportSection,
  Facture,
  FactureApercu,
  FactureFormulaire,
  FacturePaiement,
  LigneFacture,
  BulletinApercu,
  BulletinContestation,
  BulletinGenererFormulaire,
  BulletinPaie,
  BulletinPaieAjustement,
  BulletinVersement,
  TypeAjustement,
  DemandeCoursAdmin,
  StatsDemandesCours,
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

  getNotifications(
    page = 1,
    perPage = 20,
    filtres: { type?: string; lu?: boolean } = {}
  ): Observable<{
    data: NotificationEspace[];
    meta: MetaPage & { non_lues: number };
  }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    if (filtres.type) q.set('type', filtres.type);
    if (filtres.lu !== undefined) q.set('lu', filtres.lu ? '1' : '0');

    return this.http.get<{ data: NotificationEspace[]; meta: MetaPage & { non_lues: number } }>(
      `${API_BASE}/notifications?${q.toString()}`
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

  /* ============================================================
     T7A.1 — Périodes comptables
     ============================================================ */

  getPeriodes(page = 1, perPage = 15): Observable<{ data: PeriodeComptable[]; meta: MetaPage }> {
    return this.http.get<{ data: PeriodeComptable[]; meta: MetaPage }>(
      `${API_BASE}/finance/periodes?page=${page}&per_page=${perPage}`
    );
  }

  getPeriode(id: number): Observable<PeriodeComptable> {
    return this.http
      .get<{ data: PeriodeComptable }>(`${API_BASE}/finance/periodes/${id}`)
      .pipe(map((r) => r.data));
  }

  creerPeriode(payload: CreneauPeriode): Observable<{ message: string; data: PeriodeComptable }> {
    return this.http.post<{ message: string; data: PeriodeComptable }>(
      `${API_BASE}/finance/periodes`,
      payload
    );
  }

  majPeriode(id: number, payload: CreneauPeriode): Observable<{ message: string; data: PeriodeComptable }> {
    return this.http.put<{ message: string; data: PeriodeComptable }>(
      `${API_BASE}/finance/periodes/${id}`,
      payload
    );
  }

  cloturerPeriode(id: number): Observable<{ message: string; data: PeriodeComptable }> {
    return this.http.patch<{ message: string; data: PeriodeComptable }>(
      `${API_BASE}/finance/periodes/${id}/close`,
      {}
    );
  }

  rouvrirPeriode(id: number): Observable<{ message: string; data: PeriodeComptable }> {
    return this.http.patch<{ message: string; data: PeriodeComptable }>(
      `${API_BASE}/finance/periodes/${id}/reopen`,
      {}
    );
  }

  /* ============================================================
     T7A.4 — Planning des cours
     ============================================================ */

  getPlanningEnseignant(): Observable<PlanningEnseignant> {
    return this.http.get<PlanningEnseignant>(`${API_BASE}/pedagogie/planning`);
  }

  creerCreneau(payload: CreneauFormulaire): Observable<{ message: string; data: CreneauPlanning }> {
    return this.http.post<{ message: string; data: CreneauPlanning }>(
      `${API_BASE}/pedagogie/planning`,
      payload
    );
  }

  majCreneau(id: number, payload: CreneauFormulaire): Observable<{ message: string; data: CreneauPlanning }> {
    return this.http.patch<{ message: string; data: CreneauPlanning }>(
      `${API_BASE}/pedagogie/planning/${id}`,
      payload
    );
  }

  supprimerCreneau(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${API_BASE}/pedagogie/planning/${id}`);
  }

  /**
   * `GET /api/mes-planning` — union et non intersection : le contrôleur renvoie
   * `{ eleves }` au parent et `{ compte_actif, creneaux }` à l'élève. Une
   * intersection serait fausse : chaque réponse contiendrait alors les deux
   * clés, ce que le serveur ne fait jamais.
   */
  getMesPlanning(): Observable<MesPlanningParent | MesPlanningEleve> {
    return this.http.get<MesPlanningParent | MesPlanningEleve>(`${API_BASE}/mes-planning`);
  }

  /* ============================================================
     T7A.2 — Référentiels pédagogiques (admin cabinet)
     ============================================================ */

  getClasses(page = 1, perPage = 15, search = ''): Observable<{ data: ClasseReferentiel[]; meta: MetaPage }> {
    const q = search.trim() ? `&search=${encodeURIComponent(search.trim())}` : '';

    return this.http.get<{ data: ClasseReferentiel[]; meta: MetaPage }>(
      `${API_BASE}/pedagogie/classes?page=${page}&per_page=${perPage}${q}`
    );
  }

  creerClasse(payload: CreneauClasse): Observable<ClasseReferentiel> {
    return this.http
      .post<{ data: ClasseReferentiel }>(`${API_BASE}/pedagogie/classes`, payload)
      .pipe(map((r) => r.data));
  }

  majClasse(id: number, payload: CreneauClasse): Observable<ClasseReferentiel> {
    return this.http
      .put<{ data: ClasseReferentiel }>(`${API_BASE}/pedagogie/classes/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  supprimerClasse(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/pedagogie/classes/${id}`);
  }

  /**
   * `actif` est une chaîne volontairement : « '' » = pas de filtre,
   * « 1 » = actives seules, « 0 » = inactives seules. L'envoyer en booléen
   * ferait perdre la différence entre « aucun filtre » et « filtre à faux ».
   */
  getMatieres(
    page = 1,
    perPage = 15,
    search = '',
    actif = ''
  ): Observable<{ data: MatiereReferentiel[]; meta: MetaPage }> {
    const q = search.trim() ? `&search=${encodeURIComponent(search.trim())}` : '';
    const f = actif === '' ? '' : `&actif=${actif}`;

    return this.http.get<{ data: MatiereReferentiel[]; meta: MetaPage }>(
      `${API_BASE}/pedagogie/matieres?page=${page}&per_page=${perPage}${q}${f}`
    );
  }

  creerMatiere(payload: CreneauMatiere): Observable<MatiereReferentiel> {
    return this.http
      .post<{ data: MatiereReferentiel }>(`${API_BASE}/pedagogie/matieres`, payload)
      .pipe(map((r) => r.data));
  }

  majMatiere(id: number, payload: CreneauMatiere): Observable<MatiereReferentiel> {
    return this.http
      .put<{ data: MatiereReferentiel }>(`${API_BASE}/pedagogie/matieres/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  /**
   * Matière utilisée : la suppression est refusée en 409, la désactivation est
   * le chemin prévu. `activer` / `desactiver` sont des actions dédiées et non
   * une propriété d'édition — sinon un simple renommage pourrait la couper.
   */
  supprimerMatiere(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/pedagogie/matieres/${id}`);
  }

  activerMatiere(id: number): Observable<MatiereReferentiel> {
    return this.http
      .patch<{ data: MatiereReferentiel }>(`${API_BASE}/pedagogie/matieres/${id}/activer`, {})
      .pipe(map((r) => r.data));
  }

  desactiverMatiere(id: number): Observable<MatiereReferentiel> {
    return this.http
      .patch<{ data: MatiereReferentiel }>(`${API_BASE}/pedagogie/matieres/${id}/desactiver`, {})
      .pipe(map((r) => r.data));
  }

  getTypesCours(page = 1, perPage = 15, search = ''): Observable<{ data: TypeCoursReferentiel[]; meta: MetaPage }> {
    const q = search.trim() ? `&search=${encodeURIComponent(search.trim())}` : '';

    return this.http.get<{ data: TypeCoursReferentiel[]; meta: MetaPage }>(
      `${API_BASE}/pedagogie/type-cours?page=${page}&per_page=${perPage}${q}`
    );
  }

  creerTypeCours(payload: CreneauTypeCours): Observable<TypeCoursReferentiel> {
    return this.http
      .post<{ data: TypeCoursReferentiel }>(`${API_BASE}/pedagogie/type-cours`, payload)
      .pipe(map((r) => r.data));
  }

  majTypeCours(id: number, payload: CreneauTypeCours): Observable<TypeCoursReferentiel> {
    return this.http
      .put<{ data: TypeCoursReferentiel }>(`${API_BASE}/pedagogie/type-cours/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  activerTypeCours(id: number): Observable<TypeCoursReferentiel> {
    return this.http
      .patch<{ data: TypeCoursReferentiel }>(`${API_BASE}/pedagogie/type-cours/${id}/activer`, {})
      .pipe(map((r) => r.data));
  }

  desactiverTypeCours(id: number): Observable<TypeCoursReferentiel> {
    return this.http
      .patch<{ data: TypeCoursReferentiel }>(`${API_BASE}/pedagogie/type-cours/${id}/desactiver`, {})
      .pipe(map((r) => r.data));
  }

  /**
   * Référentiel backoffice — à ne pas confondre avec `getEnseignants()` qui
   * lit la vitrine publique (`/public/enseignants`) et renvoie un autre type.
   */
  /**
   * Liste les enseignants du cabinet.
   *
   * Pour une affectation de contrat il faut le `profil.id`
   * (`enseignant_profils.id`), pas le `users.id` : ce sont deux séquences
   * indépendantes. `profil` est donc exigé par l'écran qui construit un contrat.
   */
  getEnseignantsReferentiel(
    page = 1,
    perPage = 15,
    search = ''
  ): Observable<{ data: EnseignantReferentiel[]; meta: MetaPage }> {
    const q = search.trim() ? `&search=${encodeURIComponent(search.trim())}` : '';

    return this.http.get<{ data: EnseignantReferentiel[]; meta: MetaPage }>(
      `${API_BASE}/pedagogie/enseignants?page=${page}&per_page=${perPage}${q}`
    );
  }

  creerEnseignant(payload: CreneauEnseignant): Observable<EnseignantReferentiel> {
    return this.http
      .post<{ data: EnseignantReferentiel }>(`${API_BASE}/pedagogie/enseignants`, payload)
      .pipe(map((r) => r.data));
  }

  majEnseignant(id: number, payload: CreneauEnseignant): Observable<EnseignantReferentiel> {
    return this.http
      .put<{ data: EnseignantReferentiel }>(`${API_BASE}/pedagogie/enseignants/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  /* ============================================================
     T7A.3 — Contrats de cours & affectations (admin cabinet)
     ============================================================ */

  /**
   * `statut` est une chaîne volontairement : « '' » = pas de filtre. L'envoyer
   * en booléen ferait perdre la différence entre « aucun filtre » et « actif ».
   */
  getContrats(
    page = 1,
    perPage = 15,
    search = '',
    statut = '',
    typeCoursId = ''
  ): Observable<{ data: ContratCours[]; meta: MetaPage }> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('par_page', String(perPage));
    if (search.trim()) params.set('search', search.trim());
    if (statut !== '') params.set('statut', statut);
    if (typeCoursId !== '') params.set('type_cours_id', String(typeCoursId));

    return this.http.get<{ data: ContratCours[]; meta: MetaPage }>(`${API_BASE}/pedagogie/contrats?${params}`);
  }

  getContrat(id: number): Observable<ContratCours> {
    return this.http
      .get<{ data: ContratCours }>(`${API_BASE}/pedagogie/contrats/${id}`)
      .pipe(map((r) => r.data));
  }

  creerContrat(payload: ContratFormulaire): Observable<ContratCours> {
    return this.http
      .post<{ data: ContratCours }>(`${API_BASE}/pedagogie/contrats`, payload)
      .pipe(map((r) => r.data));
  }

  majContrat(id: number, payload: Omit<ContratFormulaire, 'affectations' | 'eleve_id' | 'type_cours_id'>): Observable<ContratCours> {
    return this.http
      .put<{ data: ContratCours }>(`${API_BASE}/pedagogie/contrats/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  changerStatutContrat(id: number, statut: string): Observable<ContratCours> {
    return this.http
      .patch<{ data: ContratCours }>(`${API_BASE}/pedagogie/contrats/${id}/statut`, { statut })
      .pipe(map((r) => r.data));
  }

  creerAffectation(contratId: number, payload: AffectationFormulaire): Observable<Affectation> {
    return this.http
      .post<{ data: Affectation }>(`${API_BASE}/pedagogie/contrats/${contratId}/affectations`, payload)
      .pipe(map((r) => r.data));
  }

  majAffectation(
    contratId: number,
    affectationId: number,
    payload: Partial<AffectationFormulaire>
  ): Observable<Affectation> {
    return this.http
      .patch<{ data: Affectation }>(
        `${API_BASE}/pedagogie/contrats/${contratId}/affectations/${affectationId}`,
        payload
      )
      .pipe(map((r) => r.data));
  }

  changerStatutAffectation(contratId: number, affectationId: number, statut: string): Observable<Affectation> {
    return this.http
      .patch<{ data: Affectation }>(
        `${API_BASE}/pedagogie/contrats/${contratId}/affectations/${affectationId}/statut`,
        { statut }
      )
      .pipe(map((r) => r.data));
  }

  /* ---------- « Mes cours » — enseignant / élève (T7A.3) ---------- */

  getMesCours(page = 1, perPage = 20): Observable<{ data: ContratCours[]; meta: MetaPage }> {
    return this.http.get<{ data: ContratCours[]; meta: MetaPage }>(
      `${API_BASE}/mes-cours?page=${page}&per_page=${perPage}`
    );
  }

  /* ---------- Sélecteur d'enfant (parent / élève) ---------- */

  /**
   * Pour un compte élève, la liste ne contient que lui-même : le frontend n'a
   * donc pas à distinguer deux formes de réponse selon le rôle.
   */
  getMesEnfants(): Observable<EnfantConsulter[]> {
    return this.http
      .get<{ data: EnfantConsulter[] }>(`${API_BASE}/mes-enfants`)
      .pipe(map((r) => r.data));
  }

  /* ============================================================
     T7A.7 — Rapport mensuel enseignant
     ============================================================ */

  /** Index enseignant : ses seuls rapports. */
  getRapports(
    page = 1,
    perPage = 20,
    filtres: { periode_id?: number | null; statut?: string } = {}
  ): Observable<{ data: RapportMensuel[]; meta: MetaPage }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    if (filtres.periode_id) q.set('periode_id', String(filtres.periode_id));
    if (filtres.statut) q.set('statut', filtres.statut);

    return this.http.get<{ data: RapportMensuel[]; meta: MetaPage }>(
      `${API_BASE}/pedagogie/rapports-mensuels?${q.toString()}`
    );
  }

  /** Fiche d'un rapport — cible de la notification « rapport » (enseignant). */
  getRapport(id: number): Observable<RapportMensuel> {
    return this.http
      .get<{ data: RapportMensuel }>(`${API_BASE}/pedagogie/rapports-mensuels/${id}`)
      .pipe(map((r) => r.data));
  }

  /** Index administration : tous les rapports du cabinet. */
  getRapportsAdmin(
    page = 1,
    perPage = 20,
    filtres: { periode_id?: number | null; statut?: string; search?: string } = {}
  ): Observable<{ data: RapportMensuel[]; meta: MetaPage }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    if (filtres.periode_id) q.set('periode_id', String(filtres.periode_id));
    if (filtres.statut) q.set('statut', filtres.statut);
    if (filtres.search?.trim()) q.set('search', filtres.search.trim());

    return this.http.get<{ data: RapportMensuel[]; meta: MetaPage }>(
      `${API_BASE}/admin/pedagogie/rapports-mensuels?${q.toString()}`
    );
  }

  /** Fiche côté administration — cible de la notification « rapport » déposé. */
  getRapportAdmin(id: number): Observable<RapportMensuel> {
    return this.http
      .get<{ data: RapportMensuel }>(`${API_BASE}/admin/pedagogie/rapports-mensuels/${id}`)
      .pipe(map((r) => r.data));
  }

  /** Périodes pour l'écran rapport : liste complète (filtre d'historique compris). Le formulaire de dépôt ne proposera que les ouvertes — la période close est refusée au dépôt par le serveur (D-051). */
  getPeriodesRapport(): Observable<PeriodeComptable[]> {
    return this.http
      .get<{ data: PeriodeComptable[] }>(`${API_BASE}/pedagogie/rapports-mensuels/periodes`)
      .pipe(map((r) => r.data));
  }

  creerRapport(payload: RapportMensuelFormulaire): Observable<RapportMensuel> {
    return this.http
      .post<{ data: RapportMensuel }>(`${API_BASE}/pedagogie/rapports-mensuels`, payload)
      .pipe(map((r) => r.data));
  }

  /**
   * Correction d'un rapport soumis (ou re-soumission d'un rapport rejeté).
   * Même payload : la ventilation est toujours recalculée côté serveur.
   */
  corrigerRapport(id: number, payload: Partial<RapportMensuelFormulaire>): Observable<RapportMensuel> {
    return this.http
      .post<{ data: RapportMensuel }>(`${API_BASE}/pedagogie/rapports-mensuels/${id}/corriger`, payload)
      .pipe(map((r) => r.data));
  }

  resoumettreRapport(id: number, payload: Partial<RapportMensuelFormulaire>): Observable<RapportMensuel> {
    return this.http
      .post<{ data: RapportMensuel }>(`${API_BASE}/pedagogie/rapports-mensuels/${id}/resoumettre`, payload)
      .pipe(map((r) => r.data));
  }

  supprimerRapport(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${API_BASE}/pedagogie/rapports-mensuels/${id}`);
  }

  validerRapport(id: number): Observable<RapportMensuel> {
    return this.http
      .post<{ data: RapportMensuel }>(`${API_BASE}/admin/pedagogie/rapports-mensuels/${id}/valider`, {})
      .pipe(map((r) => r.data));
  }

  rejeterRapport(id: number, motif_rejet: string): Observable<RapportMensuel> {
    return this.http
      .post<{ data: RapportMensuel }>(`${API_BASE}/admin/pedagogie/rapports-mensuels/${id}/rejeter`, { motif_rejet })
      .pipe(map((r) => r.data));
  }

  /** Comme pour le cahier de texte, le PDF est ouvert via un lien : le
   *  navigateur rejoue la session et gère le `Content-Disposition`. */
  urlPdfRapport(id: number): string {
    return `${API_BASE}/pedagogie/rapports-mensuels/${id}/pdf`;
  }

  /* ------------------------------------------------------------
     Modèle de rapport — lecteur enseignant
     ------------------------------------------------------------ */

  /** Sections et éléments ACTIFS du modèle, pour le formulaire de dépôt. */
  getModeleRapport(): Observable<RapportSection[]> {
    return this.http
      .get<{ data: RapportSection[] }>(`${API_BASE}/pedagogie/rapports-mensuels/modele`)
      .pipe(map((r) => r.data));
  }

  /** Aperçu avant dépôt : heures, séances, ventilation et bilan relus du
   *  cahier de texte. Aucune écriture — l'enseignant vérifie avant de figer. */
  apercuRapport(payload: {
    contrat_cours_id: number;
    periode_id: number;
  }): Observable<RapportMensuelApercu> {
    return this.http
      .post<{ data: RapportMensuelApercu }>(`${API_BASE}/pedagogie/rapports-mensuels/apercu`, payload)
      .pipe(map((r) => r.data));
  }

  /* ------------------------------------------------------------
     Modèle de rapport — configuration administration
     ------------------------------------------------------------ */

  /** Arbre complet (actifs et inactifs) pour l'écran de configuration. */
  getSectionsRapport(): Observable<RapportSection[]> {
    return this.http
      .get<{ data: RapportSection[] }>(`${API_BASE}/admin/pedagogie/rapport-sections`)
      .pipe(map((r) => r.data));
  }

  creerSectionRapport(payload: { libelle: string; description?: string }): Observable<RapportSection> {
    return this.http
      .post<{ data: RapportSection }>(`${API_BASE}/admin/pedagogie/rapport-sections`, payload)
      .pipe(map((r) => r.data));
  }

  modifierSectionRapport(
    id: number,
    payload: { libelle?: string; description?: string | null; actif?: boolean }
  ): Observable<RapportSection[]> {
    return this.http
      .put<{ data: RapportSection[] }>(`${API_BASE}/admin/pedagogie/rapport-sections/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  supprimerSectionRapport(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${API_BASE}/admin/pedagogie/rapport-sections/${id}`);
  }

  reordonnerSectionsRapport(ids: number[]): Observable<RapportSection[]> {
    return this.http
      .post<{ data: RapportSection[] }>(`${API_BASE}/admin/pedagogie/rapport-sections/reordonner`, { ids })
      .pipe(map((r) => r.data));
  }

  creerElementRapport(payload: {
    section_id: number;
    libelle: string;
    type?: string;
    obligatoire?: boolean;
    aide?: string;
  }): Observable<RapportSection[]> {
    return this.http
      .post<{ data: RapportSection[] }>(`${API_BASE}/admin/pedagogie/rapport-sections/elements`, payload)
      .pipe(map((r) => r.data));
  }

  modifierElementRapport(
    id: number,
    payload: { libelle?: string; type?: string; obligatoire?: boolean; aide?: string | null; actif?: boolean }
  ): Observable<RapportSection[]> {
    return this.http
      .put<{ data: RapportSection[] }>(`${API_BASE}/admin/pedagogie/rapport-sections/elements/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  supprimerElementRapport(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${API_BASE}/admin/pedagogie/rapport-sections/elements/${id}`);
  }

  reordonnerElementsRapport(ids: number[]): Observable<RapportSection[]> {
    return this.http
      .post<{ data: RapportSection[] }>(`${API_BASE}/admin/pedagogie/rapport-sections/elements/reordonner`, { ids })
      .pipe(map((r) => r.data));
  }

  /* ============================================================
     T7A.8 — Factures parent
     ============================================================ */

  /** Index parent : ses SEULES factures (lecture seule, aucun écrit). */
  getMesFactures(
    page = 1,
    perPage = 20,
    filtres: { periode_id?: number | null; statut?: string } = {}
  ): Observable<{ data: Facture[]; meta: MetaPage }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    if (filtres.periode_id) q.set('periode_id', String(filtres.periode_id));
    if (filtres.statut) q.set('statut', filtres.statut);

    return this.http.get<{ data: Facture[]; meta: MetaPage }>(
      `${API_BASE}/mes-factures?${q.toString()}`
    );
  }

  getMesFacture(id: number): Observable<Facture> {
    return this.http
      .get<{ data: Facture }>(`${API_BASE}/mes-factures/${id}`)
      .pipe(map((r) => r.data));
  }

  urlPdfFacture(id: number): string {
    return `${API_BASE}/mes-factures/${id}/pdf`;
  }

  /**
   * Contrats des enfants du parent connecté, en lecture seule (T7A.3).
   * Le pendant « famille » de `getContrats`, réservé côté serveur à
   * `role:parent` — un parent sur `/pedagogie/contrats` reçoit 403.
   */
  getMesContrats(page = 1, perPage = 20): Observable<{ data: ContratCours[]; meta: MetaPage }> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('per_page', String(perPage));

    return this.http.get<{ data: ContratCours[]; meta: MetaPage }>(`${API_BASE}/mes-contrats?${params}`);
  }

  /** Fiche d'un contrat du parent — autorisée par ContratCoursPolicy::view. */
  getMesContrat(id: number): Observable<ContratCours> {
    return this.http
      .get<{ data: ContratCours }>(`${API_BASE}/mes-contrats/${id}`)
      .pipe(map((r) => r.data));
  }

  /** Index administration : toutes les factures du cabinet. */
  getFacturesAdmin(
    page = 1,
    perPage = 20,
    filtres: { periode_id?: number | null; statut?: string; search?: string } = {}
  ): Observable<{ data: Facture[]; meta: MetaPage }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    if (filtres.periode_id) q.set('periode_id', String(filtres.periode_id));
    if (filtres.statut) q.set('statut', filtres.statut);
    if (filtres.search?.trim()) q.set('search', filtres.search.trim());

    return this.http.get<{ data: Facture[]; meta: MetaPage }>(
      `${API_BASE}/admin/factures?${q.toString()}`
    );
  }

  getFacture(id: number): Observable<Facture> {
    return this.http
      .get<{ data: Facture }>(`${API_BASE}/admin/factures/${id}`)
      .pipe(map((r) => r.data));
  }

  urlPdfFactureAdmin(id: number): string {
    return `${API_BASE}/admin/factures/${id}/pdf`;
  }

  /** Aperçu sans écriture : lignes des rapports validés + totaux. */
  apercuFacture(payload: FactureFormulaire): Observable<FactureApercu> {
    return this.http
      .post<{ data: FactureApercu }>(`${API_BASE}/admin/factures/preview`, payload)
      .pipe(map((r) => r.data));
  }

  creerFacture(payload: FactureFormulaire): Observable<Facture> {
    return this.http
      .post<{ data: Facture }>(`${API_BASE}/admin/factures`, payload)
      .pipe(map((r) => r.data));
  }

  /** Règlement d'une facture — réservé à l'administration. */
  marquerFacturePayee(id: number, payload: FacturePaiement): Observable<Facture> {
    return this.http
      .post<{ data: Facture }>(`${API_BASE}/admin/factures/${id}/paiement`, payload)
      .pipe(map((r) => r.data));
  }

  /* ============================================================
     T7A.9 — Bulletin de paie : enseignant
     ============================================================ */

  /** Index enseignant : SES SEULS bulletins, cycle scellé par la machine à états. */
  getMesBulletins(
    page = 1,
    perPage = 20,
    filtres: { periode_id?: number | null; statut?: string } = {}
  ): Observable<{ data: BulletinPaie[]; meta: MetaPage }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    if (filtres.periode_id) q.set('periode_id', String(filtres.periode_id));
    if (filtres.statut) q.set('statut', filtres.statut);

    return this.http.get<{ data: BulletinPaie[]; meta: MetaPage }>(
      `${API_BASE}/mes-bulletins?${q.toString()}`
    );
  }

  getMesBulletin(id: number): Observable<BulletinPaie> {
    return this.http
      .get<{ data: BulletinPaie }>(`${API_BASE}/mes-bulletins/${id}`)
      .pipe(map((r) => r.data));
  }

  urlPdfBulletin(id: number): string {
    return `${API_BASE}/mes-bulletins/${id}/pdf`;
  }

  /** Transitions enseignant : chaque POST est un acte d'état, jamais un PATCH. */
  consulterBulletin(id: number): Observable<BulletinPaie> {
    return this.http
      .post<{ data: BulletinPaie }>(`${API_BASE}/mes-bulletins/${id}/consulter`, null)
      .pipe(map((r) => r.data));
  }

  validerBulletin(id: number): Observable<BulletinPaie> {
    return this.http
      .post<{ data: BulletinPaie }>(`${API_BASE}/mes-bulletins/${id}/valider`, null)
      .pipe(map((r) => r.data));
  }

  contesterBulletin(
    id: number,
    payload: BulletinContestation
  ): Observable<BulletinPaie> {
    return this.http
      .post<{ data: BulletinPaie }>(
        `${API_BASE}/mes-bulletins/${id}/contester`,
        payload
      )
      .pipe(map((r) => r.data));
  }

  confirmerReceptionBulletin(id: number): Observable<BulletinPaie> {
    return this.http
      .post<{ data: BulletinPaie }>(
        `${API_BASE}/mes-bulletins/${id}/confirmer-reception`,
        null
      )
      .pipe(map((r) => r.data));
  }

  /* ============================================================
     T7A.9 — Bulletin de paie : administration
     ============================================================ */

  /** Index administration : tous les bulletins du cabinet. */
  getBulletinsAdmin(
    page = 1,
    perPage = 20,
    filtres: { periode_id?: number | null; statut?: string; search?: string } = {}
  ): Observable<{ data: BulletinPaie[]; meta: MetaPage }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    if (filtres.periode_id) q.set('periode_id', String(filtres.periode_id));
    if (filtres.statut) q.set('statut', filtres.statut);
    if (filtres.search?.trim()) q.set('search', filtres.search.trim());

    return this.http.get<{ data: BulletinPaie[]; meta: MetaPage }>(
      `${API_BASE}/admin/bulletins-paie?${q.toString()}`
    );
  }

  getBulletin(id: number): Observable<BulletinPaie> {
    return this.http
      .get<{ data: BulletinPaie }>(`${API_BASE}/admin/bulletins-paie/${id}`)
      .pipe(map((r) => r.data));
  }

  urlPdfBulletinAdmin(id: number): string {
    return `${API_BASE}/admin/bulletins-paie/${id}/pdf`;
  }

  typesAjustement(): Observable<TypeAjustement[]> {
    return this.http
      .get<{ data: TypeAjustement[] }>(
        `${API_BASE}/admin/bulletins-paie/types-ajustement`
      )
      .pipe(map((r) => r.data));
  }

  /** Aperçu de la paie d'une période : ne crée AUCUN bulletin. */
  apercuBulletins(payload: BulletinGenererFormulaire): Observable<BulletinApercu> {
    return this.http.post<BulletinApercu>(
      `${API_BASE}/admin/bulletins-paie/preview`,
      payload
    );
  }

  genererBulletins(payload: BulletinGenererFormulaire): Observable<BulletinPaie[]> {
    return this.http
      .post<{ data: BulletinPaie[] }>(
        `${API_BASE}/admin/bulletins-paie`,
        payload
      )
      .pipe(map((r) => r.data));
  }

  payerBulletin(id: number, payload: BulletinVersement): Observable<BulletinPaie> {
    return this.http
      .post<{ data: BulletinPaie }>(
        `${API_BASE}/admin/bulletins-paie/${id}/paiement`,
        payload
      )
      .pipe(map((r) => r.data));
  }

  corrigerBulletin(
    id: number,
    payload: { commentaire_admin?: string | null }
  ): Observable<BulletinPaie> {
    return this.http
      .post<{ data: BulletinPaie }>(
        `${API_BASE}/admin/bulletins-paie/${id}/corriger`,
        payload
      )
      .pipe(map((r) => r.data));
  }

  ajouterAjustement(
    id: number,
    payload: { type_ajustement_id: number; libelle: string; montant: number }
  ): Observable<BulletinPaieAjustement> {
    return this.http
      .post<{ data: BulletinPaieAjustement }>(
        `${API_BASE}/admin/bulletins-paie/${id}/ajustements`,
        payload
      )
      .pipe(map((r) => r.data));
  }

  supprimerAjustement(
    id: number,
    ajustementId: number
  ): Observable<void> {
    return this.http.delete<void>(
      `${API_BASE}/admin/bulletins-paie/${id}/ajustements/${ajustementId}`
    );
  }

  /* ============================================================
     T7A.5 — Cahier de texte
     ============================================================ */

  /**
   * Séances de l'enseignant connecté. Les filtres sont reconstruits ici plutôt
   * que laissé au template : une valeur vide doit **omettre** le paramètre,
   * l'envoyer reviendrait à demander « égal à la chaîne vide ».
   */
  getCahiersTexte(
    page = 1,
    perPage = 20,
    filtres: { search?: string; date_debut?: string; date_fin?: string } = {}
  ): Observable<{ data: CahierTexte[]; meta: MetaPage }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    const search = filtres.search?.trim();
    if (search) q.set('search', search);
    if (filtres.date_debut) q.set('date_debut', filtres.date_debut);
    if (filtres.date_fin) q.set('date_fin', filtres.date_fin);

    return this.http.get<{ data: CahierTexte[]; meta: MetaPage }>(
      `${API_BASE}/enseignant/cahiers-textes?${q.toString()}`
    );
  }

  /** Cours navigables pour la saisie : uniquement ceux de l'enseignant. */
  getAffectationsCahierTexte(): Observable<AffectationCahierTexte[]> {
    return this.http
      .get<{ data: AffectationCahierTexte[] }>(`${API_BASE}/enseignant/cahiers-textes/affectations`)
      .pipe(map((r) => r.data));
  }

  /**
   * Saisie d'une séance.
   *
   * Le même payload produit deux issues **sans erreur** : **201** ligne créée,
   * **200** reprise idempotente (même `uuid_client` déjà enregistrée). Le
   * message de retour ne les distingue pas — seul le statut HTTP le fait, d'où
   * `observe: 'response'`. S'attribuer `cree: true` par défaut ferait
   * supprimer la copie locale d'une séance qu'un client hors ligne vient
   * précisément de réémettre.
   */
  creerCahierTexte(payload: CahierTexteFormulaire): Observable<ReponseCahierTexte> {
    return this.http
      .post<ReponseCahierTexte>(
        `${API_BASE}/enseignant/cahiers-textes`,
        payload,
        { observe: 'response' }
      )
      // Le corps est typé nullable par Angular (une 204 n'en aurait pas) ;
      // l'endpoint renvoie toujours un JsonResource, donc on l'affirme ici
      // plutôt que de propager un `| undefined` dans tout l'écran.
      .pipe(map((r): ReponseCahierTexte => ({ ...(r.body as ReponseCahierTexte), cree: r.status === 201 })));
  }

  majCahierTexte(id: number, payload: CahierTexteCorrection): Observable<CahierTexte> {
    return this.http
      .put<{ data: CahierTexte }>(`${API_BASE}/enseignant/cahiers-textes/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  supprimerCahierTexte(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/enseignant/cahiers-textes/${id}`);
  }

  /**
   * Historique d'un élève — périmètre parent **et** élève. `eleveId` est un
   * `eleves.id`, jamais un `users.id` : la confusion renverrait 403.
   */
  getHistoriqueCahierTexte(
    eleveId: number,
    page = 1,
    perPage = 20,
    filtres: { search?: string; date_debut?: string; date_fin?: string } = {}
  ): Observable<{ data: CahierTexte[]; meta: MetaPage }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    const search = filtres.search?.trim();
    if (search) q.set('search', search);
    if (filtres.date_debut) q.set('date_debut', filtres.date_debut);
    if (filtres.date_fin) q.set('date_fin', filtres.date_fin);

    return this.http.get<{ data: CahierTexte[]; meta: MetaPage }>(
      `${API_BASE}/mes-enfants/${eleveId}/cahiers-textes?${q.toString()}`
    );
  }

  /* ---------- Téléchargement PDF (navigation, pas XHR) ---------- */

  /**
   * Le PDF est produit par DomPDF, pas par l'API JSON : il faut donc ouvrir
   * l'URL dans le navigateur. `fetch` + blob paraît plus « propre », mais il
   * faudrait alors rejouer l'authentification par cookie et gérer le nom de
   * fichier extrait de l'en-tête `Content-Disposition` : le navigateur le fait
   * déjà très bien pour un lien.
   */
  urlPdfCahierTexte(id: number): string {
    return `${API_BASE}/enseignant/cahiers-textes/${id}/pdf`;
  }

  urlHistoriquePdf(eleveId: number, dateDebut?: string | null, dateFin?: string | null): string {
    const q = new URLSearchParams();

    if (dateDebut) q.set('date_debut', dateDebut);
    if (dateFin) q.set('date_fin', dateFin);

    const suffixe = q.toString();

    return `${API_BASE}/mes-enfants/${eleveId}/cahiers-textes/historique-pdf${suffixe ? `?${suffixe}` : ''}`;
  }

  /* ============================================================
     Demandes de cours (administration)
     ============================================================ */

  /**
   * Demandes reçues depuis le site public. Les compteurs de tête sont
   * globaux (calculés sur tout le cabinet) et non sur la page : le serveur
   * les place dans `meta.stats`.
   */
  getDemandesCours(
    page = 1,
    perPage = 20,
    filtres: { search?: string; statut?: string; classe_id?: number | null } = {}
  ): Observable<{ data: DemandeCoursAdmin[]; meta: MetaPage & { stats: StatsDemandesCours } }> {
    const q = new URLSearchParams();

    q.set('page', String(page));
    q.set('per_page', String(perPage));

    const search = filtres.search?.trim();
    if (search) q.set('search', search);
    if (filtres.statut) q.set('statut', filtres.statut);
    if (filtres.classe_id) q.set('classe_id', String(filtres.classe_id));

    return this.http.get<{ data: DemandeCoursAdmin[]; meta: MetaPage & { stats: StatsDemandesCours } }>(
      `${API_BASE}/pedagogie/demandes-cours?${q.toString()}`
    );
  }

  getDemandeCours(id: number): Observable<DemandeCoursAdmin> {
    return this.http
      .get<{ data: DemandeCoursAdmin }>(`${API_BASE}/pedagogie/demandes-cours/${id}`)
      .pipe(map((r) => r.data));
  }

  /** Marque la demande comme traitée. Idempotent côté serveur. */
  validerDemandeCours(id: number): Observable<DemandeCoursAdmin> {
    return this.http
      .patch<{ data: DemandeCoursAdmin }>(`${API_BASE}/pedagogie/demandes-cours/${id}/valider`, {})
      .pipe(map((r) => r.data));
  }

  /** Refuse la demande (« pas de suite »). Idempotent côté serveur. */
  refuserDemandeCours(id: number): Observable<DemandeCoursAdmin> {
    return this.http
      .patch<{ data: DemandeCoursAdmin }>(`${API_BASE}/pedagogie/demandes-cours/${id}/refuser`, {})
      .pipe(map((r) => r.data));
  }

  /**
   * Les trois actions de constitution du dossier renvoient la **demande** mise à
   * jour : l'écran a besoin de savoir ce qui existe désormais pour proposer
   * l'étape suivante. Le serveur est idempotent, un double-clic est sans effet.
   */
  creerParentDemandeCours(id: number, payload: unknown): Observable<DemandeCoursAdmin> {
    return this.http
      .post<{ data: DemandeCoursAdmin }>(`${API_BASE}/pedagogie/demandes-cours/${id}/parent`, payload)
      .pipe(map((r) => r.data));
  }

  creerEleveDemandeCours(id: number, payload: unknown): Observable<DemandeCoursAdmin> {
    return this.http
      .post<{ data: DemandeCoursAdmin }>(`${API_BASE}/pedagogie/demandes-cours/${id}/eleve`, payload)
      .pipe(map((r) => r.data));
  }

  creerContratDemandeCours(id: number, payload: unknown): Observable<DemandeCoursAdmin> {
    return this.http
      .post<{ data: DemandeCoursAdmin }>(`${API_BASE}/pedagogie/demandes-cours/${id}/contrat`, payload)
      .pipe(map((r) => r.data));
  }
}
