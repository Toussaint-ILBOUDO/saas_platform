# PLAN_CABINET_1 — Lancement du premier cabinet réel

> Règle de livraison d'un cabinet : **D-043** — créer le cabinet + son admin (backend
> provisionné), **puis** coder son frontend sur mesure (Core Angular + API JSON), et
> **publier seulement après validation**. Pas de gabarit d'apparence imposé.
> Source unique de vérité des données publiques : `parametres_publics.data` (D-044).

---

## 1. Principe retenu : création minimale + complétion progressive

- **À la création (super-admin)** : uniquement l'**essentiel** (identité + contact minimal).
  Les champs sont saisis **dans la page de création du cabinet** (Landlord), pour que la
  procédure soit comprise de bout en bout.
- **Générés par défaut** : thème (couleurs/polices), référentiels (types de cours/documents),
  rôles/permissions, et tout ce qui n'est pas renseigné → valeur par défaut.
- **Complétés ensuite par l'admin** dans son backoffice → écran « Fiche cabinet »
  (contact complet, paiements, zones, horaires, réseaux, SEO, contenus).
- **Indicateur de complétude** sur le tableau de bord admin : liste ce qui manque
  avant mise en ligne (évite un site vide).

## 2. Fiche de données (checklist)

| Bloc | Champs | Saisi à la création | Modifiable par |
|---|---|---|---|
| A. Identité | nom officiel, slogan/tagline, directeur, logo | essentielles | admin + super-admin |
| B. Contact | téléphone(s), whatsapp, email public, adresse, horaires | essentielles | admin |
| C. Paiements | Orange Money, Moov Money, (Wave ?) | optionnel | admin |
| D. Zones | villes/communes couvertes + pays | optionnel | admin |
| E. Réseaux | Facebook, TikTok, WhatsApp Business, LinkedIn | optionnel | admin |
| F. SEO | meta description, mots-clés, slug/domaine | essentielle (slug) | super-admin |
| G. Contenus | texts hero, à propos, services, stats, témoignages, enseignants, FAQ | après | admin |

> **Texte de la page publique : UN SEUL fichier.** Tous les textes « modèle »
> (créé en 2026, à propos, descriptions de services, mentions, footer…) vivent dans
> un fichier unique `content.ts` du frontend `cabinet-<slug>` — corrections en un lieu.
> Seules les données réellement variables (contact, zones, paiements, actualités, FAQ)
> restent en base (modifiables par l'admin).

## 3. Chemin d'implémentation

1. **P0 — Dossier** : l'utilisateur fournit au minimum A + B (le reste plus tard).
2. **P1 — Backend « fiche cabinet » [FAIT]** :
   - Formulaire de création du super-admin enrichi (identité, contact, horaires,
     paiements Orange/Moov/Wave/Cash, pays/devise/localités, réseaux) → stocké sur le
     tenant (virtual column `fiche` → `tenants.data`), **injecté par le pipeline**
     (`CreerAdminCabinetEtNotifier`, après seed) dans `parametres_publics.data.fiche`
     (source unique D-044) — les `seed_parameters` stancl n'étaient pas retenables.
   - `GET/PUT /api/admin/contenu-public` étendus : schéma structuré `data.fiche`
     (identite/contact/paiements/zones/reseaux) validé + tests.
   - Squelette par défaut dans `TenantDatabaseSeeder` + `App\Support\FicheCabinet`.
   - Correction : renommer `sous_domaine` resynchronise la table `domains`.
   - Tests : pipeline (injection fiche), domaines, API fiche — suite complète 84 verts.
3. **P2 — Core Angular (P4 du plan), puis P3 — Frontend du cabinet 1** :
   - App **Angular 22** dans `frontend/magis` (workspace dédié « cabinet 1 ») : signal-based,
     `provideZonelessChangeDetection`, lazy-loading par route, SEO dynamique.
   - **Palette « Magis Plus Center »** (D-047) : orange `#e8610c` + bleu `#12305e`,
     **aucun dégradé, aucun émoji**, icônes **bootstrap-icons uniquement**, mobile-first.
   - **Textes modèle = `frontend/magis/src/content.ts`** (D-044) : hero, about, services,
     zones, solutions, FAQ, bibliothèque, boutique, footer, nav. Données variables via API
     publique (fiche, actualités, FAQ, documents, produits, stats, enseignants, témoignages).
   - **Endpoints publics ajoutés (P2)** : `GET /api/public/stats`, `GET /api/public/enseignants`,
     `GET /api/public/temoignages`(+slug), `GET /api/public/references` (options formulaires).
   - **Pages publiques livrées** : accueil (hero, about, stats API, services, zones, solutions
     en onglets, enseignants API, témoignages API, FAQ API, demande bandeau, contact), actualités
     (+détail), bibliothèque, boutique, FAQ, demande de cours (formulaire → POST demandes-cours),
     contact. Header collant + tiroir mobile, footer 4 colonnes, bouton remontée.
   - **Build vérifié** (`npm run build`) et suite backend complète verte (**88 tests / 397 assertions**).
   - Backoffice : fiche cabinet, actualités, FAQ, utilisateurs, notifications, push (déploiement).
4. **P4 — Mobile / SEO / IA** : mobile-first, Core Web Vitals, SSR/prerender des routes
   publiques, métadonnées + JSON-LD (`Organization`, `LocalBusiness` avec
   `areaServed`/`OpeningHours`/`PaymentAccepted`=Orange/Moov, `FAQPage`, `Service`,
   `Review`, `BreadcrumbList`), `sitemap.xml`, `robots.txt`, lisibilité agents IA.
5. **P5 — Validation & publication** : Lighthouse ≥ 90, PWA hors-ligne, push, parcours
   complet de bout en bout, publication sur `cabinet-1` réel.

## 4. En attente de l'utilisateur (dossier A + B + choix)

> **Données reçues (26/09) — cabinet « Magis Plus Center » :** nom = **Magis Plus Center** ;
> fonctionnalités actives = **toutes** ; pays = **Burkina Faso** (indicatif +226) ; devise = **CFA (FCFA)** ;
> paiements = **Orange Money, Moov Money, Wave, Cash** ; logo = **générique** (modifié ensuite par l'admin) ;
> horaires = **24h/24, 7j/7**. Reste : slug/domaine définitif, slogan, directeur, téléphone(s), email public,
> adresse, whatsapp, zones précises, réseaux sociaux, contenus éditoriaux.

- [x] Nom officiel exact du cabinet (→ slug/sous-domaine). — *Magis Plus Center*
- [x] Fonctionnalités actives — *toutes (pédagogie, planning, finance, bibliothèque, actualités, boutique, témoignages, CMS/FAQ)*
- [x] Paiements (Orange Money, Moov Money, Wave, Cash) et devise (FCFA). — *Burkina Faso / FCFA*
- [x] Horaires — *24h/24, 7j/7* — *saisissables au formulaire (P1 fait)*
- [x] Logo — *générique au départ, modifiable par l'admin*
- [x] **Formulaire P1** — identité, contact, horaires, paiements, pays/devise/localités, réseaux, saisis à la création
- [ ] Domaine de production quand connu (dev : `magis-plus-center.localhost` — `sous_domaine` modifiable).
- [ ] **ID technique du cabinet** (immutable, ex. `magis-plus-center`) — à choix/confirmation à la création.
- [ ] Slogan/tagline, directeur, logo spécifique (si voulus dès la création).
- [ ] Téléphone principal (+226), WhatsApp, email public, adresse (si voulus dès la création).
- [ ] Zones d'intervention précises (villes/communes).
- [ ] Réseaux sociaux (Facebook, TikTok, WhatsApp Business, LinkedIn).
- [x] Contenus éditoriaux (hero, à propos, services, stats, témoignages, enseignants, FAQ). — *contenu modèle `content.ts` (27/09)*

## 5. Lancement en local (frontend cabinet 1)

```bash
# 1) Dépendances du frontend (une fois)
cd frontend/magis && npm install

# 2) Démarrage dev (Angular 22, node côté Windows : cmd.exe /c "npm start")
npm start   # http://localhost:4200 — /api relayé par proxy.conf.json vers
            # http://magis-plus-center.localhost (domaine tenant déjà créé)
```

- Production : SPA servie depuis le domaine tenant, API même origine (`/api`), pas de proxy.
- Le backoffice admin supervise le cabinet à `admin.localhost/admin/login` (D-006).