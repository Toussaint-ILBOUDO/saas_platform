<?php

use App\Modules\Auth\Http\Controllers\Api\AuthApiController;
use App\Modules\Auth\Http\Controllers\Api\ProfilApiController;
use App\Modules\Audit\Http\Controllers\Api\AuditApiController;
use App\Modules\Bibliotheque\Http\Controllers\Api\AdminBibliothequeApiController;
use App\Modules\Bibliotheque\Http\Controllers\Api\BibliothequeApiController;
use App\Modules\Bibliotheque\Http\Controllers\Api\PublicDocumentApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminActualiteApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminFaqQuestionApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminFaqSectionApiController;
use App\Modules\Communication\Http\Controllers\Api\PublicActualiteApiController;
use App\Modules\Communication\Http\Controllers\Api\PublicFaqApiController;
use App\Modules\Librairie\Http\Controllers\Api\AdminLibrairieApiController;
use App\Modules\Librairie\Http\Controllers\Api\MesCommandeApiController;
use App\Modules\Librairie\Http\Controllers\Api\PublicCommandeApiController;
use App\Modules\Librairie\Http\Controllers\Api\PublicPanierApiController;
use App\Modules\Librairie\Http\Controllers\Api\PublicProduitApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\PublicDemandeCoursApiController;
use App\Modules\Public\Http\Controllers\Api\AdminContenuPublicApiController;
use App\Modules\Public\Http\Controllers\Api\CabinetPublicApiController;
use App\Modules\Public\Http\Controllers\Api\PublicEnseignantApiController;
use App\Modules\Public\Http\Controllers\Api\PublicReferencesApiController;
use App\Modules\Public\Http\Controllers\Api\PublicStatsApiController;
use App\Modules\Systeme\Http\Controllers\Api\AdminUtilisateurApiController;
use App\Modules\Systeme\Http\Controllers\Api\NotificationApiController;
use App\Modules\Systeme\Http\Controllers\Api\PushSubscriptionApiController;
use App\Modules\Temoignages\Http\Controllers\Api\MesTemoignageApiController;
use App\Modules\Temoignages\Http\Controllers\Api\PublicTemoignageApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\AffectationApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\CahierTexteApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\ClasseApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\ContratCoursApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\DemandeCoursAdminApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\MatiereApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\MesPlanningApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\ObjectifPedagogiqueApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\PlanningApiController;
use App\Modules\Finance\Http\Controllers\Api\FactureApiController;
use App\Modules\Finance\Http\Controllers\Api\BulletinPaieApiController;
use App\Modules\Finance\Http\Controllers\Api\Enseignant\BulletinPaieApiController as EnseignantBulletinPaieApiController;
use App\Modules\Plateforme\Http\Controllers\Api\CabinetContratApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\RapportMensuelApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\RapportSectionApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\TypeCoursApiController;
use App\Modules\Users\Http\Controllers\Api\EnseignantApiController;
use App\Modules\Users\Http\Controllers\Api\EleveApiController;
use App\Modules\Users\Http\Controllers\Api\MesCoursApiController;
use App\Modules\Users\Http\Controllers\Api\MesContratsApiController;
use App\Modules\Users\Http\Controllers\Api\MesEnfantsApiController;
use App\Modules\Users\Http\Controllers\Api\ParentApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API cabinets (P3)
|--------------------------------------------------------------------------
| Préfixe /api, JSON, tenancy par domaine (appliqué au groupe api dans
| bootstrap/app.php) : chaque <slug>.localhost fait sa propre base.
| Conventions T3.1 : {message, code, erreurs}, 422/401/403/404/429, français.
*/

// ---------- Public (aucune connexion) — T3.3 ----------
Route::prefix('public')->group(function () {
    Route::get('cabinet', [CabinetPublicApiController::class, 'index']);
    Route::get('logo', [CabinetPublicApiController::class, 'logo']);

    Route::get('actualites', [PublicActualiteApiController::class, 'index']);
    // Image en ligne : route contrôlée (disque public tenant non exposé).
    Route::get('actualites/{slug}/image', [PublicActualiteApiController::class, 'image']);
    Route::get('actualites/{slug}', [PublicActualiteApiController::class, 'show']);

    Route::get('faq', [PublicFaqApiController::class, 'index']);

    Route::get('documents', [PublicDocumentApiController::class, 'index']);
    // Avant `documents/{slug}` : sinon « referentiels » serait avalé par le slug.
    Route::get('documents/referentiels', [PublicDocumentApiController::class, 'referentiels']);
    Route::get('documents/{slug}', [PublicDocumentApiController::class, 'show']);
    Route::get('documents/{slug}/fichier', [PublicDocumentApiController::class, 'fichier']);
    Route::get('documents/{slug}/telecharger', [PublicDocumentApiController::class, 'telecharger']);
    Route::post('documents/{slug}/reactions', [PublicDocumentApiController::class, 'reactions'])
        ->middleware('throttle:30,1');
    // Signalement ouvert (anonyme ou membre) — plusieurs signalements possibles,
    // throttles anti-abus (10/min/IP).
    Route::post('documents/{slug}/signalements', [PublicDocumentApiController::class, 'signaler'])
        ->middleware('throttle:10,1');
    Route::post('documents/{slug}/favori', [PublicDocumentApiController::class, 'favori'])
        ->middleware('auth:web');

    Route::get('produits', [PublicProduitApiController::class, 'index']);
    // Avant `produits/{slug}` : sinon « referentiels » serait avalé par le slug.
    Route::get('produits/referentiels', [PublicProduitApiController::class, 'categories']);
    // Image en ligne : route contrôlée, le disque public tenant n'est pas
    // exposé via `/storage` (voir SertMediaPublic).
    Route::get('produits/{slug}/image', [PublicProduitApiController::class, 'image']);
    Route::get('produits/{slug}', [PublicProduitApiController::class, 'show']);

    // Devis serveur du panier (prix officiels recalculés en base).
    Route::post('panier/devis', [PublicPanierApiController::class, 'devis'])
        ->middleware('throttle:60,1');

    // Statistiques, enseignants, témoignages — P2 (page publique frontend).
    Route::get('stats', [PublicStatsApiController::class, 'index']);
    Route::get('enseignants', [PublicEnseignantApiController::class, 'index']);
    Route::get('enseignants/{id}/photo', [PublicEnseignantApiController::class, 'photo']);
    Route::get('temoignages', [PublicTemoignageApiController::class, 'index']);
    Route::get('temoignages/{slug}', [PublicTemoignageApiController::class, 'show']);
    Route::get('references', [PublicReferencesApiController::class, 'index']);

    Route::post('demandes-cours', [PublicDemandeCoursApiController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::post('commandes', [PublicCommandeApiController::class, 'store'])
        ->middleware('throttle:10,1');
    // Suivi public par code de commande (sans compte, sans donnée interne).
    Route::get('commandes/{reference}', [PublicCommandeApiController::class, 'suivre'])
        ->middleware('throttle:30,1');
    Route::get('commandes/{reference}/recapitulatif', [PublicCommandeApiController::class, 'recapitulatif'])
        ->middleware('throttle:10,1');
    Route::post('commandes/{reference}/annuler', [PublicCommandeApiController::class, 'annuler'])
        ->middleware('throttle:10,1');
});

// ---------- Authentification par session (guard web) — T3.2 ----------
Route::prefix('auth')->group(function () {
    Route::post('connexion', [AuthApiController::class, 'connexion'])
        ->middleware('throttle:5,1');
    Route::post('mot-de-passe-oublie', [AuthApiController::class, 'motDePasseOublie'])
        ->middleware('throttle:5,1');
    Route::post('reinitialiser-mot-de-passe', [AuthApiController::class, 'reinitialiserMotDePasse'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:web')->group(function () {
        Route::post('deconnexion', [AuthApiController::class, 'deconnexion']);
        Route::get('moi', [AuthApiController::class, 'moi']);
        // Mon profil : identité du compte, photo et blocs par rôle actif.
        Route::put('moi', [ProfilApiController::class, 'updateMoi']);
        Route::get('moi/photo', [ProfilApiController::class, 'photo']);
        Route::post('moi/photo', [ProfilApiController::class, 'updatePhoto']);
        Route::delete('moi/photo', [ProfilApiController::class, 'destroyPhoto']);
        Route::get('moi/profils', [ProfilApiController::class, 'profils']);
        Route::put('profil-enseignant', [ProfilApiController::class, 'updateEnseignant']);
        Route::put('profil-parent', [ProfilApiController::class, 'updateParent']);
        // Fiche élève : élève lui-même, parent, enseignant affecté ou admin.
        Route::get('eleves/{eleve}', [ProfilApiController::class, 'showEleve']);
        Route::put('eleves/{eleve}', [ProfilApiController::class, 'updateEleve']);
        Route::post('changer-mot-de-passe', [AuthApiController::class, 'changerMotDePasse']);
        Route::post('role-actif', [AuthApiController::class, 'roleActif']);
        // Témoignage d'un membre connecté (publication immédiate, modération a posteriori).
        Route::post('temoignages', [MesTemoignageApiController::class, 'store'])
            ->middleware('throttle:10,1');
    });
});

// ---------- Notifications (base) + abonnement push — T3.6 ----------
Route::middleware('auth:web')->group(function () {
    Route::get('notifications', [NotificationApiController::class, 'index']);
    Route::post('notifications/lire-toutes', [NotificationApiController::class, 'lireToutes']);
    Route::post('notifications/{notification}/lue', [NotificationApiController::class, 'marquerLue']);
    Route::delete('notifications/{notification}', [NotificationApiController::class, 'destroy']);

    Route::get('abonnement-push', [PushSubscriptionApiController::class, 'index']);
    Route::post('abonnement-push', [PushSubscriptionApiController::class, 'store']);
    Route::delete('abonnement-push', [PushSubscriptionApiController::class, 'destroy']);
});

// ---------- Finance (cabinet) — T7A.1 ----------
Route::middleware(['auth:web', 'role:admin_cabinet'])
    ->prefix('finance')
    ->group(function () {
        // Pas de `destroy` : `apiResource` l'enregistrerait par défaut alors que
        // le contrôleur n'a pas d'action `delete` — l'appel échouerait en 500
        // au lieu de 405. La suppression est de toute façon interdite (D-051) :
        // une période porte les écritures de tout un exercice.
        Route::apiResource('periodes', App\Modules\Finance\Http\Controllers\Api\PeriodeComptableApiController::class)
            ->parameters(['periodes' => 'periode'])
            ->except(['destroy']);
        Route::patch('periodes/{periode}/close', [App\Modules\Finance\Http\Controllers\Api\PeriodeComptableApiController::class, 'close']);
        Route::patch('periodes/{periode}/reopen', [App\Modules\Finance\Http\Controllers\Api\PeriodeComptableApiController::class, 'reopen']);
});

// ---------- Référentiels pédagogiques (cabinet) — T7A.2 ----------
Route::middleware(['auth:web', 'role:admin_cabinet'])
    ->prefix('pedagogie')
    ->group(function () {
        Route::apiResource('classes', ClasseApiController::class)
            ->parameters(['classes' => 'classe']);

        Route::apiResource('matieres', MatiereApiController::class)
            ->parameters(['matieres' => 'matiere']);
        // Une matière utilisée ne se supprime pas (D-053) : elle se désactive.
        Route::patch('matieres/{matiere}/activer', [MatiereApiController::class, 'activer']);
        Route::patch('matieres/{matiere}/desactiver', [MatiereApiController::class, 'desactiver']);

        // Pas de delete : un type de cours se désactive (contrats de cours le référencent).
        Route::apiResource('type-cours', TypeCoursApiController::class)
            ->parameters(['type-cours' => 'type_cours'])
            ->except(['destroy']);
        Route::patch('type-cours/{type_cours}/activer', [TypeCoursApiController::class, 'activer']);
        Route::patch('type-cours/{type_cours}/desactiver', [TypeCoursApiController::class, 'desactiver']);

        // Pas de delete : un enseignant porte rapports, bulletins et contrats.
        // `import` et la photo sont déclarés AVANT l'apiResource : le segment
        // `{enseignant}` de la ressource ne doit pas avaler `import`.
        Route::post('enseignants/import', [EnseignantApiController::class, 'import']);
        Route::get('enseignants/{enseignant}/photo', [EnseignantApiController::class, 'photo']);
        Route::post('enseignants/{enseignant}/photo', [EnseignantApiController::class, 'majPhoto']);
        Route::delete('enseignants/{enseignant}/photo', [EnseignantApiController::class, 'supprimerPhoto']);
        Route::apiResource('enseignants', EnseignantApiController::class)
            ->parameters(['enseignants' => 'enseignant'])
            ->except(['create', 'edit', 'destroy']);

        // Création directe — parent / élève (sans demande de cours préalable).
        // Le contrat direct existe déjà (`POST /contrats` porte `eleve_id`) ;
        // ce qui manquait, c'était de fabriquer les identités hors du parcours
        // demande. Pas de delete : un parent porte des factures, un élève porte
        // des contrats, cahiers de texte et factures.
        Route::get('parents', [ParentApiController::class, 'index']);
        Route::post('parents', [ParentApiController::class, 'store']);
        Route::get('eleves', [EleveApiController::class, 'index']);
        Route::post('eleves', [EleveApiController::class, 'store']);

        // M3 — scolarité : détail/édition admin des fiches + activation du
        // compte élève. L'admin est le seul à toucher aux rattachements
        // (`parent_id`/`classe_id`) : la fiche partagée
        // (`PUT /auth/eleves/{eleve}`) les exclut. Pas de DELETE (cf. ci-dessus).
        Route::get('parents/{parent}', [ParentApiController::class, 'show']);
        Route::put('parents/{parent}', [ParentApiController::class, 'update']);
        Route::get('eleves/{eleve}', [EleveApiController::class, 'show']);
        Route::put('eleves/{eleve}', [EleveApiController::class, 'update']);
        // Double flag `eleves.statut` + `users.statut` (AuthService) : les deux
        // doivent bouger ensemble, sinon l'état du compte dépend de qui l'a
        // touché en dernier.
        Route::patch('eleves/{eleve}/activer', [EleveApiController::class, 'activer']);
        Route::patch('eleves/{eleve}/suspendre', [EleveApiController::class, 'suspendre']);

        // Contrats de cours. Pas de DELETE : la suppression en cascade
        // contrat -> affectations -> lignes de facture / de bulletin effacerait
        // des heures déjà facturées et payées. Un contrat se suspend/termine.
        Route::apiResource('contrats', ContratCoursApiController::class)
            ->parameters(['contrats' => 'contrat'])
            ->except(['destroy']);
        Route::patch('contrats/{contrat}/statut', [ContratCoursApiController::class, 'statut']);

        // Affectations enseignant d'un contrat (D-049 / D-048 en dépendent).
        Route::post('contrats/{contrat}/affectations', [AffectationApiController::class, 'store']);
        Route::patch('contrats/{contrat}/affectations/{affectation}', [AffectationApiController::class, 'update']);
        Route::patch('contrats/{contrat}/affectations/{affectation}/statut', [AffectationApiController::class, 'statut']);

        // Demandes de cours reçues depuis le site public. Pas de DELETE : une
        // demande est une trace de contact commercial, l'admin la « traite ».
        //
        // Les trois actions `creer-*` constituent le dossier (parent → élève →
        // contrat). Elles sont idempotentes : `demande_cours.parent_id` /
        // `eleve_id` / `contrat_cours_id` mémorisent ce qui existe déjà, donc un
        // double-clic ne fabrique pas de doublon.
        Route::get('demandes-cours', [DemandeCoursAdminApiController::class, 'index']);
        Route::get('demandes-cours/{demandeCours}', [DemandeCoursAdminApiController::class, 'show']);
        Route::patch('demandes-cours/{demandeCours}/valider', [DemandeCoursAdminApiController::class, 'valider']);
        Route::patch('demandes-cours/{demandeCours}/refuser', [DemandeCoursAdminApiController::class, 'refuser']);
        Route::post('demandes-cours/{demandeCours}/parent', [DemandeCoursAdminApiController::class, 'creerParent']);
        Route::post('demandes-cours/{demandeCours}/eleve', [DemandeCoursAdminApiController::class, 'creerEleve']);
        Route::post('demandes-cours/{demandeCours}/contrat', [DemandeCoursAdminApiController::class, 'creerContrat']);
    });

// ---------- « Mes cours » — enseignant / élève (T7A.3) ----------
Route::middleware(['auth:web', 'role:enseignant|eleve'])
    ->prefix('mes-cours')
    ->group(function () {
        Route::get('/', [MesCoursApiController::class, 'index']);
    });

// ---------- « Mes contrats » — parent (T7A.3) ----------
// Pendant « famille » de `/pedagogie/contrats` (réservé admin_cabinet) : le
// parent consulte les contrats de ses enfants, en lecture seule. La fiche
// est contrôlée par ContratCoursPolicy::view dans le contrôleur.
Route::middleware(['auth:web', 'role:parent'])
    ->prefix('mes-contrats')
    ->group(function () {
        Route::get('/', [MesContratsApiController::class, 'index']);
        Route::get('{contrat}', [MesContratsApiController::class, 'show']);
    });

// ---------- Planning enseignant (T7A.4) ----------
Route::middleware(['auth:web', 'role:enseignant'])
    ->prefix('pedagogie/planning')
    ->group(function () {
        Route::get('/', [PlanningApiController::class, 'index']);
        Route::post('/', [PlanningApiController::class, 'store']);
        // Le nom du paramètre doit correspondre à celui de la méthode, sinon
        // la résolution implicite de modèle ne trouve rien (404).
        Route::patch('{planningCours}', [PlanningApiController::class, 'update']);
        Route::delete('{planningCours}', [PlanningApiController::class, 'destroy']);
    });

// ---------- Planning — consultation élève / parent (T7A.4) ----------
// L'élève ne voit son planning que si son compte est activé ; le parent voit
// celui de ses enfants. Le périmètre est déduit du profil du connecté.
Route::middleware(['auth:web', 'role:eleve|parent'])
    ->get('mes-planning', [MesPlanningApiController::class, 'index']);

// ---------- Cahier de texte — saisie enseignant (T7A.5) ----------
Route::middleware(['auth:web', 'role:enseignant'])
    ->prefix('enseignant/cahiers-textes')
    ->group(function () {
        Route::get('/', [CahierTexteApiController::class, 'index']);
        Route::post('/', [CahierTexteApiController::class, 'store']);

        // Doit précéder `{cahier}` : sinon « affectations » serait résolu comme
        // un identifiant de séance et la liste des cours deviendrait un 404.
        Route::get('affectations', [CahierTexteApiController::class, 'affectations']);

        Route::get('{cahier}', [CahierTexteApiController::class, 'show']);
        Route::put('{cahier}', [CahierTexteApiController::class, 'update']);
        Route::delete('{cahier}', [CahierTexteApiController::class, 'destroy']);
        Route::get('{cahier}/pdf', [CahierTexteApiController::class, 'pdf']);
    });

// ---------- Enfants du parent connecté (T7A.5) ----------
// Sélecteur d'enfant pour les écrans de consultation pédagogique. L'élève y
// figure comme son propre enfant, ce qui permet au frontend de traiter les
// deux rôles avec une seule liste.
Route::middleware(['auth:web', 'role:parent|eleve'])
    ->get('mes-enfants', [MesEnfantsApiController::class, 'index']);

// ---------- Objectifs pédagogiques — enseignant (T7A.6, D-050) ----------
// Une ligne par (élève, période comptable, enseignant). Les routes statiques
// alimentent le formulaire de saisie ; `{objectif}` porte la property, tranchée
// par la policy.
Route::middleware(['auth:web', 'role:enseignant'])
    ->prefix('enseignant/objectifs')
    ->group(function () {
        Route::get('/', [ObjectifPedagogiqueApiController::class, 'index']);
        Route::post('/', [ObjectifPedagogiqueApiController::class, 'store']);
        Route::get('eleves', [ObjectifPedagogiqueApiController::class, 'eleves']);
        Route::get('periodes', [ObjectifPedagogiqueApiController::class, 'periodes']);
        Route::get('matieres', [ObjectifPedagogiqueApiController::class, 'matieres']);
        Route::put('{objectif}', [ObjectifPedagogiqueApiController::class, 'update']);
        Route::delete('{objectif}', [ObjectifPedagogiqueApiController::class, 'destroy']);
    });

// ---------- Objectifs pédagogiques — administration (T7A.6) ----------
// L'admin supervise et corrige tous les objectifs ; la création reste
// réservée à l'enseignant (porteur de la relation, `policy::create`).
Route::middleware(['auth:web', 'role:admin_cabinet'])
    ->prefix('admin/pedagogie/objectifs')
    ->group(function () {
        Route::get('/', [ObjectifPedagogiqueApiController::class, 'index']);
        Route::get('matieres', [ObjectifPedagogiqueApiController::class, 'matieres']);
        Route::put('{objectif}', [ObjectifPedagogiqueApiController::class, 'update']);
        Route::delete('{objectif}', [ObjectifPedagogiqueApiController::class, 'destroy']);
    });

// ---------- Rapport mensuel — enseignant (T7A.7) ----------
// L'enseignant dépose/corrige/re-soumet/supprime ses seuls rapports, et les
// transitions sont des routes explicites (`corriger`, `resoumettre`) plutôt
// qu'un PATCH générique sur `statut` : la machine à états vit dans le
// service, pas dans le frontend.
Route::middleware(['auth:web', 'role:enseignant'])
    ->prefix('pedagogie/rapports-mensuels')
    ->group(function () {
        Route::get('/', [RapportMensuelApiController::class, 'index']);
        Route::post('/', [RapportMensuelApiController::class, 'store']);
        // Doit précéder `{rapport}` : sinon « periodes » serait résolu comme un
        // identifiant de rapport et la liste des périodes deviendrait un 404.
        Route::get('periodes', [RapportMensuelApiController::class, 'periodes']);
        // Modèle de rapport (sections/éléments actifs) et aperçu des heures
        // avant dépôt : également avant `{rapport}` pour les mêmes raisons.
        Route::get('modele', [RapportMensuelApiController::class, 'modele']);
        Route::post('apercu', [RapportMensuelApiController::class, 'apercu']);
        Route::get('{rapport}', [RapportMensuelApiController::class, 'show']);
        Route::get('{rapport}/pdf', [RapportMensuelApiController::class, 'pdf']);
        Route::post('{rapport}/corriger', [RapportMensuelApiController::class, 'update']);
        Route::post('{rapport}/resoumettre', [RapportMensuelApiController::class, 'resoumettre']);
        Route::delete('{rapport}', [RapportMensuelApiController::class, 'destroy']);
    });

// ---------- Modèle de rapport mensuel — administration ----------
// L'admin construit le canevas : sections et éléments que l'enseignant remplira
// au dépôt. Les informations générales et le bilan des activités sont
// automatiques et ne sont pas configurables ici.
Route::middleware(['auth:web', 'role:admin_cabinet'])
    ->prefix('admin/pedagogie/rapport-sections')
    ->group(function () {
        Route::get('/', [RapportSectionApiController::class, 'index']);
        Route::post('/', [RapportSectionApiController::class, 'store']);
        Route::post('reordonner', [RapportSectionApiController::class, 'reordonnerSections']);
        Route::put('{section}', [RapportSectionApiController::class, 'update']);
        Route::delete('{section}', [RapportSectionApiController::class, 'destroy']);
        // Éléments d'une section : CRUD + réordonnancement.
        Route::post('elements', [RapportSectionApiController::class, 'creerElement']);
        Route::post('elements/reordonner', [RapportSectionApiController::class, 'reordonnerElements']);
        Route::put('elements/{element}', [RapportSectionApiController::class, 'modifierElement']);
        Route::delete('elements/{element}', [RapportSectionApiController::class, 'supprimerElement']);
    });

// ---------- Rapport mensuel — administration (T7A.7) ----------
// La validation fige les heures (D-051) : elles deviennent la source de la
// facture parent et du bulletin de paie. Réservé au rôle `admin_cabinet`.
Route::middleware(['auth:web', 'role:admin_cabinet'])
    ->prefix('admin/pedagogie/rapports-mensuels')
    ->group(function () {
        Route::get('/', [RapportMensuelApiController::class, 'indexAdmin']);
        Route::get('{rapport}', [RapportMensuelApiController::class, 'show']);
        Route::post('{rapport}/valider', [RapportMensuelApiController::class, 'valider']);
        Route::post('{rapport}/rejeter', [RapportMensuelApiController::class, 'rejeter']);
    });

// ---------- Facture parent — parent (T7A.8) ----------
// Lecture seule : le parent consulte ses factures et ses PDF. La facture est
// générée par l'administration, jamais par le débiteur — le règlement est un
// acte administratif (`admin/factures/{facture}/paiement`).
Route::middleware(['auth:web', 'role:parent'])
    ->prefix('mes-factures')
    ->group(function () {
        Route::get('/', [FactureApiController::class, 'indexMesFactures']);
        Route::get('{facture}', [FactureApiController::class, 'showMesFacture']);
        Route::get('{facture}/pdf', [FactureApiController::class, 'pdfMesFacture']);
    });

// ---------- Facture parent — administration (T7A.8) ----------
// Les lignes viennent des RAPPORTS VALIDÉS du contrat (D-051) : le preview
// montre ce qui sera facturé avant génération, et la génération refuse un
// contrat dont un enseignant n'a pas de rapport validé.
Route::middleware(['auth:web', 'role:admin_cabinet'])
    ->prefix('admin/factures')
    ->group(function () {
        // `preview` POST précède `{facture}` : un GET `{facture}` ne peut pas
        // être confondu avec un POST sur une route REST n'existant pas ici.
        Route::get('/', [FactureApiController::class, 'indexAdmin']);
        Route::post('preview', [FactureApiController::class, 'preview']);
        Route::post('/', [FactureApiController::class, 'store']);
        // Doit précéder `{facture}` : sinon « types-ajustement » serait résolu
        // comme un identifiant de facture.
        Route::get('types-ajustement', [FactureApiController::class, 'typesAjustement']);
        Route::get('{facture}', [FactureApiController::class, 'show']);
        Route::get('{facture}/pdf', [FactureApiController::class, 'pdf']);
        Route::post('{facture}/paiement', [FactureApiController::class, 'payer']);
        Route::post('{facture}/ajustements', [FactureApiController::class, 'storeAjustement']);
        Route::delete('{facture}/ajustements/{ajustement}', [FactureApiController::class, 'destroyAjustement']);
    });

// ---------- Bulletin de paie — enseignant (T7A.9) ----------
// L'enseignant déroule le cycle de son bulletin : consulter (genere→consulte),
// valider (consulte→valide) ou contester (consulte→conteste, D-052), puis
// confirmer la réception de son paiement (verse→reçu). Rien ici de la paie de
// quelqu'un d'autre : le périmètre est scopé à son profil (404 sinon).
Route::middleware(['auth:web', 'role:enseignant'])
    ->prefix('mes-bulletins')
    ->group(function () {
        Route::get('/', [EnseignantBulletinPaieApiController::class, 'index']);
        Route::get('{bulletin}', [EnseignantBulletinPaieApiController::class, 'show']);
        Route::get('{bulletin}/pdf', [EnseignantBulletinPaieApiController::class, 'pdf']);
        Route::post('{bulletin}/consulter', [EnseignantBulletinPaieApiController::class, 'consulter']);
        Route::post('{bulletin}/valider', [EnseignantBulletinPaieApiController::class, 'valider']);
        // D-052 — motif structuré (catégorie de la liste fermée + détail).
        Route::post('{bulletin}/contester', [EnseignantBulletinPaieApiController::class, 'contester']);
        Route::post('{bulletin}/confirmer-reception', [EnseignantBulletinPaieApiController::class, 'confirmerReception']);
    });

// ---------- Bulletin de paie — administration (T7A.9) ----------
// L'admin aperçoit la paie d'une période depuis les RAPPORTS VALIDÉS (D-051),
// la génère, corrige les contestations, ajuste les primes/retenues et
// enregistre les versements. `preview` et `types-ajustement` précèdent
// `{bulletin}` : sinon le mot « preview » serait résolu comme un bulletin.
Route::middleware(['auth:web', 'role:admin_cabinet'])
    ->prefix('admin/bulletins-paie')
    ->group(function () {
        Route::get('/', [BulletinPaieApiController::class, 'index']);
        Route::post('preview', [BulletinPaieApiController::class, 'preview']);
        Route::post('/', [BulletinPaieApiController::class, 'generer']);
        Route::get('types-ajustement', [BulletinPaieApiController::class, 'typesAjustement']);
        Route::get('{bulletin}', [BulletinPaieApiController::class, 'show']);
        Route::get('{bulletin}/pdf', [BulletinPaieApiController::class, 'pdf']);
        Route::post('{bulletin}/corriger', [BulletinPaieApiController::class, 'corriger']);
        Route::post('{bulletin}/paiement', [BulletinPaieApiController::class, 'payer']);
        Route::post('{bulletin}/ajustements', [BulletinPaieApiController::class, 'storeAjustement']);
        Route::delete('{bulletin}/ajustements/{ajustement}', [BulletinPaieApiController::class, 'destroyAjustement']);
    });

// ---------- Cahier de texte — consultation parent / élève (T7A.5) ----------
// Lecture seule : le parent voit les enfants, l'élève lui-même. Un parent n'a
// aucune écriture, même sur son propre enfant — la saisie est l'acte
// professionnel de l'enseignant.
Route::middleware(['auth:web', 'role:parent|eleve'])
    ->prefix('mes-enfants/{eleve}/cahiers-textes')
    ->group(function () {
        Route::get('/', [CahierTexteApiController::class, 'indexEleve']);
        Route::get('historique-pdf', [CahierTexteApiController::class, 'historiquePdf']);
    });

// ---------- Librairie — commandes du compte connecté (espace personnel) ----------
// Consultation PAR RÉFÉRENCE publique (pas d'id interne) : impossible de
// consulter la commande d'un autre client en modifiant l'URL.
Route::middleware('auth:web')->prefix('mes-commandes')->group(function () {
    Route::get('/', [MesCommandeApiController::class, 'index']);
    Route::get('{reference}', [MesCommandeApiController::class, 'show']);
    Route::get('{reference}/pdf', [MesCommandeApiController::class, 'pdf']);
    Route::post('{reference}/annuler', [MesCommandeApiController::class, 'annuler']);
});

// ---------- Librairie — console du libraire / de l'administrateur ----------
// `role:admin_cabinet|gestionnaire_librairie` : les rôles réels des bases
// cabinet (D-007). La délégation commerciale (clés `parametres_librairie`)
// est appliquée dans `AdminLibrairieApiController` — restriction serveur
// insoupçonnable depuis le frontend. Les clés de délégation ne sont
// modifiables QUE par le libraire : l'admin ne peut pas se réautoriser.
Route::middleware(['auth:web', 'role:admin_cabinet|gestionnaire_librairie'])
    ->prefix('admin/librairie')
    ->group(function () {
        Route::get('parametres', [AdminLibrairieApiController::class, 'parametres']);
        Route::patch('parametres', [AdminLibrairieApiController::class, 'majParametres'])
            ->middleware('role:gestionnaire_librairie');
        Route::get('dashboard', [AdminLibrairieApiController::class, 'dashboard']);

        Route::get('categories', [AdminLibrairieApiController::class, 'categories']);
        Route::post('categories', [AdminLibrairieApiController::class, 'storeCategorie']);

        Route::get('produits', [AdminLibrairieApiController::class, 'produits']);
        Route::post('produits', [AdminLibrairieApiController::class, 'storeProduit']);
        Route::get('produits/{produit}/image', [AdminLibrairieApiController::class, 'imageProduit']);
        // PUT multipart n'est pas peuplé par PHP : POST accepté en complément
        // (convention déjà employée pour les documents de la bibliothèque).
        Route::match(['put', 'post'], 'produits/{produit}', [AdminLibrairieApiController::class, 'updateProduit']);
        Route::patch('produits/{produit}/basculer', [AdminLibrairieApiController::class, 'basculerProduit']);
        Route::delete('produits/{produit}', [AdminLibrairieApiController::class, 'destroyProduit']);

        Route::get('commandes', [AdminLibrairieApiController::class, 'commandes']);
        Route::get('commandes/{reference}', [AdminLibrairieApiController::class, 'commande']);
        Route::get('commandes/{reference}/pdf', [AdminLibrairieApiController::class, 'pdf']);
        Route::patch('commandes/{reference}/statut', [AdminLibrairieApiController::class, 'majStatut']);
        Route::patch('commandes/{reference}/note-interne', [AdminLibrairieApiController::class, 'majNoteInterne']);
    });

// ---------- Bibliothèque — espace membre (Angular) ----------
// Toute la logique vit dans `DocumentBibliothequeService`, déjà partagée avec
// le parcours Blade. Le fichier étant sur un disque privé, la lecture et le
// téléchargement passent par des routes contrôlées (policy + compteurs).
Route::middleware('auth:web')->prefix('bibliotheque')->group(function () {
    Route::get('dashboard', [BibliothequeApiController::class, 'dashboard']);
    Route::get('mes-documents', [BibliothequeApiController::class, 'mesDocuments']);
    Route::get('favoris', [BibliothequeApiController::class, 'favoris']);
    Route::get('referentiels', [BibliothequeApiController::class, 'referentiels']);
    Route::post('documents', [BibliothequeApiController::class, 'store']);
    Route::get('documents/{documentBibliotheque}', [BibliothequeApiController::class, 'show']);
    // POST en plus de PUT : un multipart en PUT n'est pas peuplé par PHP < 8.4
    // (même raison que le logo du cabinet).
    Route::match(['put', 'post'], 'documents/{documentBibliotheque}', [BibliothequeApiController::class, 'update']);
    Route::delete('documents/{documentBibliotheque}', [BibliothequeApiController::class, 'destroy']);
    Route::post('documents/{documentBibliotheque}/favori', [BibliothequeApiController::class, 'toggleFavori']);
    Route::post('documents/{documentBibliotheque}/commentaires', [BibliothequeApiController::class, 'commenter']);
    Route::post('documents/{documentBibliotheque}/notes', [BibliothequeApiController::class, 'noter']);
    Route::post('documents/{documentBibliotheque}/signalements', [BibliothequeApiController::class, 'signaler'])
        ->middleware('throttle:10,1');
    Route::get('fichier/{documentBibliotheque}', [BibliothequeApiController::class, 'fichier']);
    Route::get('telecharger/{documentBibliotheque}', [BibliothequeApiController::class, 'telecharger']);
});

// ---------- Backoffice MVP (authentifié, staff cabinet) — T3.5 ----------
Route::middleware(['auth:web', 'role:admin_cabinet'])->prefix('admin')->name('admin.')->group(function () {
    // Contenu public (thème, pied de page, données)
    Route::get('contenu-public', [AdminContenuPublicApiController::class, 'show']);
    Route::put('contenu-public', [AdminContenuPublicApiController::class, 'update']);
    // POST en plus de PUT : PHP < 8.4 ne peuple pas $_POST/$_FILES pour un
    // multipart en PUT — le navigateur envoie donc l'upload en POST.
    Route::match(['put', 'post'], 'contenu-public/logo', [AdminContenuPublicApiController::class, 'mettreAJourLogo']);

    // Actualités
    Route::get('actualites', [AdminActualiteApiController::class, 'index']);
    Route::post('actualites', [AdminActualiteApiController::class, 'store']);
    Route::get('actualites/{actualite}/image', [AdminActualiteApiController::class, 'image']);
    Route::get('actualites/{actualite}', [AdminActualiteApiController::class, 'show']);
    Route::match(['put', 'post'], 'actualites/{actualite}', [AdminActualiteApiController::class, 'update']);
    Route::delete('actualites/{actualite}', [AdminActualiteApiController::class, 'destroy']);

    // FAQ
    Route::get('faq/sections', [AdminFaqSectionApiController::class, 'index']);
    Route::post('faq/sections', [AdminFaqSectionApiController::class, 'store']);
    Route::get('faq/sections/{faqSection}', [AdminFaqSectionApiController::class, 'show']);
    Route::put('faq/sections/{faqSection}', [AdminFaqSectionApiController::class, 'update']);
    Route::delete('faq/sections/{faqSection}', [AdminFaqSectionApiController::class, 'destroy']);

    Route::get('faq/sections/{faqSection}/questions', [AdminFaqQuestionApiController::class, 'index']);
    Route::post('faq/questions', [AdminFaqQuestionApiController::class, 'store']);
    Route::get('faq/questions/{faqQuestion}', [AdminFaqQuestionApiController::class, 'show']);
    Route::put('faq/questions/{faqQuestion}', [AdminFaqQuestionApiController::class, 'update']);
    Route::delete('faq/questions/{faqQuestion}', [AdminFaqQuestionApiController::class, 'destroy']);

    // Utilisateurs
    Route::get('utilisateurs', [AdminUtilisateurApiController::class, 'index']);
    Route::post('utilisateurs', [AdminUtilisateurApiController::class, 'store']);
    Route::get('utilisateurs/{utilisateur}', [AdminUtilisateurApiController::class, 'show']);
    Route::put('utilisateurs/{utilisateur}', [AdminUtilisateurApiController::class, 'update']);
    Route::patch('utilisateurs/{utilisateur}/activer', [AdminUtilisateurApiController::class, 'activer']);
    Route::patch('utilisateurs/{utilisateur}/suspendre', [AdminUtilisateurApiController::class, 'suspendre']);

    // Bibliothèque — modération (Angular). Pas de DELETE sur les
    // signalements : un signalement est une trace, il est « traité ».
    Route::get('bibliotheque/signalements', [AdminBibliothequeApiController::class, 'signalements']);
    Route::get('bibliotheque/signalements/{signalement}', [AdminBibliothequeApiController::class, 'showSignalement']);
    Route::patch('bibliotheque/signalements/{signalement}', [AdminBibliothequeApiController::class, 'majSignalement']);
    Route::patch('bibliotheque/signalements/{signalement}/traiter', [AdminBibliothequeApiController::class, 'traiterSignalement']);
    Route::get('bibliotheque', [AdminBibliothequeApiController::class, 'index']);
    Route::get('bibliotheque/{documentBibliotheque}', [AdminBibliothequeApiController::class, 'show']);
    Route::patch('bibliotheque/{documentBibliotheque}/valider', [AdminBibliothequeApiController::class, 'valider']);
    Route::patch('bibliotheque/{documentBibliotheque}/refuser', [AdminBibliothequeApiController::class, 'refuser']);
    Route::patch('bibliotheque/{documentBibliotheque}/archiver', [AdminBibliothequeApiController::class, 'archiver']);
    Route::delete('bibliotheque/{documentBibliotheque}', [AdminBibliothequeApiController::class, 'destroy']);

    // Journal d'audit (M13) — lecture seule, entrées créées par les services
    // métier via `AuditService::enregistrer()`.
    Route::get('audit', [AuditApiController::class, 'index']);
    Route::get('audit/actions', [AuditApiController::class, 'actions']);

    // Contrat commercial et factures de la PLATEFORME (P8) —
    // consultation SEULE pour l'admin du cabinet (read-only).
    Route::get('mon-contrat', [CabinetContratApiController::class, 'index']);
    Route::get('mes-factures-plateforme', [CabinetContratApiController::class, 'factures']);
    Route::get('mes-factures-plateforme/{facture}', [CabinetContratApiController::class, 'facture']);
    Route::get('mes-factures-plateforme/{facture}/pdf', [CabinetContratApiController::class, 'facturePdf']);
});
