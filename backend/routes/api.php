<?php

use App\Modules\Auth\Http\Controllers\Api\AuthApiController;
use App\Modules\Bibliotheque\Http\Controllers\Api\PublicDocumentApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminActualiteApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminFaqQuestionApiController;
use App\Modules\Communication\Http\Controllers\Api\AdminFaqSectionApiController;
use App\Modules\Communication\Http\Controllers\Api\PublicActualiteApiController;
use App\Modules\Communication\Http\Controllers\Api\PublicFaqApiController;
use App\Modules\Librairie\Http\Controllers\Api\PublicCommandeApiController;
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
use App\Modules\Temoignages\Http\Controllers\Api\PublicTemoignageApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\AffectationApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\CahierTexteApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\ClasseApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\ContratCoursApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\DemandeCoursAdminApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\MatiereApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\MesPlanningApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\PlanningApiController;
use App\Modules\Finance\Http\Controllers\Api\FactureApiController;
use App\Modules\Finance\Http\Controllers\Api\BulletinPaieApiController;
use App\Modules\Finance\Http\Controllers\Api\Enseignant\BulletinPaieApiController as EnseignantBulletinPaieApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\RapportMensuelApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\RapportSectionApiController;
use App\Modules\Pedagogie\Http\Controllers\Api\TypeCoursApiController;
use App\Modules\Users\Http\Controllers\Api\EnseignantApiController;
use App\Modules\Users\Http\Controllers\Api\MesCoursApiController;
use App\Modules\Users\Http\Controllers\Api\MesContratsApiController;
use App\Modules\Users\Http\Controllers\Api\MesEnfantsApiController;
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
    Route::get('actualites/{slug}', [PublicActualiteApiController::class, 'show']);

    Route::get('faq', [PublicFaqApiController::class, 'index']);

    Route::get('documents', [PublicDocumentApiController::class, 'index']);
    Route::get('documents/{slug}', [PublicDocumentApiController::class, 'show']);

    Route::get('produits', [PublicProduitApiController::class, 'index']);

    // Statistiques, enseignants, témoignages — P2 (page publique frontend).
    Route::get('stats', [PublicStatsApiController::class, 'index']);
    Route::get('enseignants', [PublicEnseignantApiController::class, 'index']);
    Route::get('temoignages', [PublicTemoignageApiController::class, 'index']);
    Route::get('temoignages/{slug}', [PublicTemoignageApiController::class, 'show']);
    Route::get('references', [PublicReferencesApiController::class, 'index']);

    Route::post('demandes-cours', [PublicDemandeCoursApiController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::post('commandes', [PublicCommandeApiController::class, 'store'])
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
        Route::post('changer-mot-de-passe', [AuthApiController::class, 'changerMotDePasse']);
        Route::post('role-actif', [AuthApiController::class, 'roleActif']);
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
        Route::apiResource('enseignants', EnseignantApiController::class)
            ->parameters(['enseignants' => 'enseignant'])
            ->except(['create', 'edit', 'destroy']);

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
        Route::get('{facture}', [FactureApiController::class, 'show']);
        Route::get('{facture}/pdf', [FactureApiController::class, 'pdf']);
        Route::post('{facture}/paiement', [FactureApiController::class, 'payer']);
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
});
