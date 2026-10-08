<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\Cabinet;
use App\Models\CahierTexte;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\ParametrePublic;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Models\TypeCours;
use App\Models\User;
use App\Modules\Pedagogie\Services\RapportMensuelPdfService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API rapport mensuel (T7A.7).
 *
 * Le rapport validé est la source de vérité de la chaîne facture parent et
 * bulletin de paie (D-051) : les tests verrouillent donc en priorité la
 * machine à états, le périmètre (un seul chiffre mal affecté gonflerait une
 * facture comme un bulletin), et le gel des périodes.
 *
 * On vérifie aussi que la ventilation vient du cahier de texte (jamais du
 * client) et que les transitions sont bornées par la policy.
 */
class RapportMensuelApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecte(string $slug, string $email): void
    {
        tenancy()->initialize($slug);
        User::where('email', $email)->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function api($slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/pedagogie/rapports-mensuels{$chemin}";
    }

    private function admin($slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/admin/pedagogie/rapports-mensuels{$chemin}";
    }

    /**
     * Cabinet : une période ouverte, un élève, sa mère, un enseignant affecté
     * (prof), un collègue non affecté, et une séance de cahier de texte de 2 h.
     * L'admin reste distinct du prof — pour pouvoir tester les permissions.
     *
     * @return array<string, mixed>
     */
    private function socle(string $slug): array
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);

        PeriodeComptable::create([
            'label' => 'Octobre 2026',
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
            'type' => 'mensuel',
        ]);

        $eleveUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Ibrahim', "ibrahim@{$slug}.local");
        $eleveUser->assignRole('eleve');
        $mereUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Mère', "mere@{$slug}.local");
        $mereUser->assignRole('parent');
        $mereUser->parentProfil()->firstOrCreate([], []);

        $classe = \App\Models\Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $mereUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $matiere = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);

        $profUser = $this->creeUtilisateur($slug, 'Kaboré', 'Aïcha', "prof@{$slug}.local");
        $profUser->assignRole('enseignant');
        $prof = \App\Models\EnseignantProfil::create(['user_id' => $profUser->id]);
        $prof->matieres()->sync([$matiere->id]);

        $autreUser = $this->creeUtilisateur($slug, 'Sawadogo', 'Abdoulaye', "abdoulaye@{$slug}.local");
        $autreUser->assignRole('enseignant');
        $autre = \App\Models\EnseignantProfil::create(['user_id' => $autreUser->id]);

        $typeCours = TypeCours::create([
            'code' => 'DOM',
            'libelle' => 'À domicile',
            'actif' => true,
        ]);

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
            'autres_frais_suivi' => 0,
            'statut' => 'actif',
        ]);

        $affectation = AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $prof->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => 2500,
            'nombre_heures_prevues' => 4,
            'date_affectation' => '2026-10-01',
            'statut' => 'actif',
        ]);

        // Une séance de 2 h : c'est elle qui produit la ventilation du rapport.
        CahierTexte::create([
            'affectation_enseignant_id' => $affectation->id,
            'date_seance' => '2026-10-05',
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
            'duree_heures' => '2.00',
            'contenu_cours' => 'Dérivation.',
        ]);

        /*
         * Réponses minimales au modèle de rapport (inséré en défaut par la
         * migration) : on renseigne les éléments marqués obligatoires pour
         * que les dépôts passent la validation.
         */
        $reponsesParDefaut = \App\Models\RapportElement::query()
            ->where('obligatoire', true)
            ->get()
            ->mapWithKeys(function ($element) {
                $texte = match ($element->libelle) {
                    'Points notables sur la matière' => 'Très investi.',
                    'Difficultés rencontrées' => 'Aucune.',
                    'Solutions proposées' => 'Exercices de renforcement en prévision.',
                    default => 'RAS.',
                };

                return [(string) $element->id => $texte];
            })
            ->all();

        $resultat = [
            'periode_id' => (int) PeriodeComptable::first()->id,
            'affectation_id' => (int) $affectation->id,
            'contrat_id' => (int) $contrat->id,
            'matiere_id' => (int) $matiere->id,
            'prof_id' => (int) $prof->id,
            'autre_id' => (int) $autre->id,
            'enseignant_email' => "prof@{$slug}.local",
            'admin_email' => "admin@{$slug}.local",
            'autre_email' => "abdoulaye@{$slug}.local",
            'mere_email' => "mere@{$slug}.local",
            'reponses_par_defaut' => $reponsesParDefaut,
            'element_appreciation_id' => (int) \App\Models\RapportElement::query()
                ->where('libelle', 'Appréciation générale')
                ->value('id'),
        ];

        tenancy()->end();

        return $resultat;
    }

    private function creeUtilisateur(string $slug, string $nom, string $prenom, string $email): User
    {
        return User::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
    }

    private function payload(array $socle, array $surcharge = []): array
    {
        return array_merge([
            'contrat_cours_id' => $socle['contrat_id'],
            'periode_id' => $socle['periode_id'],
            'reponses' => $socle['reponses_par_defaut'],
        ], $surcharge);
    }

    private function cloturePeriode(string $slug): void
    {
        tenancy()->initialize($slug);
        PeriodeComptable::query()->update(['statut' => PeriodeComptable::CLOTUREE]);
        tenancy()->end();
    }

    private function assertErreur(TestResponse $reponse, string $champ): void
    {
        $reponse->assertStatus(422);
        $this->assertSame('VALIDATION', $reponse->json('code'));
        $this->assertNotEmpty(
            $reponse->json("erreurs.{$champ}"),
            "Attendu une erreur de validation sur « {$champ} », reçu : "
                .json_encode($reponse->json(), JSON_UNESCAPED_UNICODE)
        );
    }

    private function dernierRapport(string $slug): ?RapportMensuelEnseignant
    {
        tenancy()->initialize($slug);
        $rapport = RapportMensuelEnseignant::latest('id')->first();
        tenancy()->end();

        return $rapport;
    }

    // ------------------------------------------------------- dépôt (POST)

    public function test_depot_cree_un_rapport_soumis_avec_ventilation(): void
    {
        $socle = $this->socle('moncab1');
        $this->connecte('moncab1', $socle['enseignant_email']);

        $reponse = $this->postJson($this->api('moncab1'), $this->payload($socle));
        $reponse
            ->assertStatus(201)
            ->assertJsonPath('data.statut', 'soumis')
            ->assertJsonPath('data.volume_horaire_cumule', 2)
            ->assertJsonCount(1, 'data.lignes')
            ->assertJsonPath('data.lignes.0.matiere_nom', 'Mathématiques')
            ->assertJsonPath('data.lignes.0.nombre_heures', 2)
            ->assertJsonPath('data.lignes.0.taux_horaire', 2500)
            ->assertJsonPath('data.lignes.0.montant_estime', 5000);

        // La ventilation est bien persistée pour la facture et le bulletin.
        tenancy()->initialize('moncab1');
        $this->assertSame(1, RapportMensuelEnseignant::first()->lignes()->count());
        tenancy()->end();

        // L'enseignant ne reçoit ni « valider » ni « rejeter » : ce sont des
        // actions d'administration réservées à la route `admin`.
        $actions = $reponse->json('data.actions');
        $this->assertNotContains('valider', $actions);
        $this->assertNotContains('rejeter', $actions);
    }

    public function test_depot_refuse_un_doublon_sur_le_meme_couple_contrat_periode(): void
    {
        $socle = $this->socle('moncab2');
        $this->connecte('moncab2', $socle['enseignant_email']);

        $this->postJson($this->api('moncab2'), $this->payload($socle))->assertStatus(201);

        $this->postJson($this->api('moncab2'), $this->payload($socle))
            ->assertStatus(422)
            ->assertJsonPath('erreurs.rapport.0', 'Un rapport existe déjà pour cette période.');

        tenancy()->initialize('moncab2');
        $this->assertSame(1, RapportMensuelEnseignant::count());
        tenancy()->end();
    }

    public function test_depot_refuse_un_contrat_dont_l_enseignant_n_est_pas_affecte(): void
    {
        $socle = $this->socle('moncab3');
        $this->connecte('moncab3', $socle['autre_email']);

        $this->postJson($this->api('moncab3'), $this->payload($socle))
            ->assertStatus(422)
            ->assertJsonPath('erreurs.contrat_cours_id.0', 'Ce cours ne fait pas partie de vos affectations.');
    }

    public function test_depot_refuse_rapport_quand_periode_cloturee(): void
    {
        $socle = $this->socle('moncab4');
        $this->cloturePeriode('moncab4');
        $this->connecte('moncab4', $socle['enseignant_email']);

        $this->postJson($this->api('moncab4'), $this->payload($socle))
            ->assertStatus(422)
            ->assertJsonPath('erreurs.periode_id.0', 'La période est clôturée : le dépôt du rapport est impossible.');
    }

    public function test_depot_est_refuse_a_un_parent(): void
    {
        $socle = $this->socle('moncab4b');

        $this->connecte('moncab4b', $socle['mere_email']);

        $this->postJson($this->api('moncab4b'), $this->payload($socle))
            ->assertStatus(403);
    }

    // ------------------------------------------------------- validation admin

    public function test_admin_valide_un_rapport_soumis(): void
    {
        $socle = $this->socle('moncab5');
        $this->connecte('moncab5', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab5'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data');

        $this->connecte('moncab5', $socle['admin_email']);
        $this->postJson($this->admin('moncab5', '/' . $rapport['id'] . '/valider'))
            ->assertOk()
            ->assertJsonPath('data.statut', 'valide');
    }

    public function test_enseignant_ne_peut_pas_valider_ni_rejeter(): void
    {
        $socle = $this->socle('moncab6');
        $this->connecte('moncab6', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab6'), $this->payload($socle))->json('data');

        $this->postJson($this->admin('moncab6', '/' . $rapport['id'] . '/valider'))
            ->assertStatus(403);
        $this->postJson($this->admin('moncab6', '/' . $rapport['id'] . '/rejeter'), [
            'motif_rejet' => 'Motif de test suffisamment long.',
        ])->assertStatus(403);
    }

    public function test_rejeter_puis_corriger_puis_resoumettre(): void
    {
        $socle = $this->socle('moncab7');
        $this->connecte('moncab7', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab7'), $this->payload($socle))->json('data');

        // Rejet motivé par l'admin.
        $this->connecte('moncab7', $socle['admin_email']);
        $this->postJson($this->admin('moncab7', '/' . $rapport['id'] . '/rejeter'), [
            'motif_rejet' => 'Le nombre de séances ne correspond pas au cahier de texte.',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'rejete')
            ->assertJsonPath('data.motif_rejet', 'Le nombre de séances ne correspond pas au cahier de texte.');

        // L'enseignant corrige le rapport rejeté (réponses modifiées) et le
        // re-soumet : `resoumettre` recalcule la ventilation depuis le cahier
        // de texte et repasse le rapport en `soumis`.
        $this->connecte('moncab7', $socle['enseignant_email']);
        $this->postJson($this->api('moncab7', "/{$rapport['id']}/resoumettre"), [
            'reponses' => $socle['reponses_par_defaut'] + [
                (string) $socle['element_appreciation_id'] => 'Séance du 12 corrigée.',
            ],
        ])->assertOk()
            ->assertJsonPath('data.statut', 'soumis');

        tenancy()->initialize('moncab7');
        $this->assertSame(
            'Séance du 12 corrigée.',
            RapportMensuelEnseignant::find($rapport['id'])
                ?->reponses[(string) $socle['element_appreciation_id']]
        );
        tenancy()->end();
    }

    public function test_rejeter_est_refuse_sans_motif(): void
    {
        $socle = $this->socle('moncab8');
        $this->connecte('moncab8', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab8'), $this->payload($socle))->json('data');

        $this->connecte('moncab8', $socle['admin_email']);
        $this->postJson($this->admin('moncab8', "/{$rapport['id']}/rejeter"))
            ->assertStatus(422)
            ->assertJsonPath('erreurs.motif_rejet.0', 'Le motif du rejet est obligatoire.');
    }

    public function test_rapport_valide_est_intouchable_par_l_enseignant(): void
    {
        $socle = $this->socle('moncab9');
        $this->connecte('moncab9', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab9'), $this->payload($socle))->json('data');

        $this->connecte('moncab9', $socle['admin_email']);
        $this->postJson($this->admin('moncab9', "/{$rapport['id']}/valider"))->assertOk();

        $this->connecte('moncab9', $socle['enseignant_email']);
        $this->postJson($this->api('moncab9', "/{$rapport['id']}/corriger"), [
            'reponses' => $socle['reponses_par_defaut'],
        ])->assertStatus(422);

        $this->deleteJson($this->api('moncab9', "/{$rapport['id']}"))
            ->assertStatus(422);
    }

    // ------------------------------------------------------- listes

    public function test_index_enseignant_ne_voit_que_ses_rapports(): void
    {
        $socle = $this->socle('moncab10');
        $this->connecte('moncab10', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab10'), $this->payload($socle))->json('data');

        $this->getJson($this->api('moncab10'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $rapport['id'])
            ->assertJsonPath('data.0.periode.label', 'Octobre 2026')
            ->assertJsonPath('data.0.total_heures_lignes', 2);
    }

    public function test_index_admin_liste_tous_les_rapports_du_cabinet(): void
    {
        $socle = $this->socle('moncab11');
        $this->connecte('moncab11', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab11'), $this->payload($socle))->json('data');

        $this->connecte('moncab11', $socle['admin_email']);
        $this->getJson($this->admin('moncab11'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $rapport['id']);
    }

    public function test_admin_liste_filtre_par_statut_inconnu_renvoie_422(): void
    {
        $this->socle('moncab12');
        $this->connecte('moncab12', 'admin@moncab12.local');

        $this->getJson($this->admin('moncab12') . '?statut=archivé')
            ->assertStatus(422);
    }

    // ------------------------------------------------------- modèle & aperçu

    public function test_enseignant_consulte_le_modele_de_rapport_avec_les_reponses(): void
    {
        $socle = $this->socle('moncab12b');
        $this->connecte('moncab12b', $socle['enseignant_email']);

        $rapport = $this->postJson($this->api('moncab12b'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data');

        // Le détail porte les sections avec la réponse de l'enseignant.
        $this->assertNotEmpty($rapport['sections']);
        $this->assertSame('Très investi.', $rapport['sections'][0]['elements'][0]['reponse']);

        // Le modele ne renvoie QUE les sections actives, sans réponse.
        $this->getJson($this->api('moncab12b', '/modele'))
            ->assertOk()
            ->assertJsonPath('data.0.libelle', 'Évaluation pédagogique')
            ->assertJsonPath('data.0.elements.0.libelle', 'Points notables sur la matière')
            ->assertJsonMissing(['reponse' => 'Très investi.']);
    }

    public function test_apercu_renvoie_le_calcul_avant_depot(): void
    {
        $socle = $this->socle('moncab12c');
        $this->connecte('moncab12c', $socle['enseignant_email']);

        $this->postJson($this->api('moncab12c', '/apercu'), [
            'contrat_cours_id' => $socle['contrat_id'],
            'periode_id' => $socle['periode_id'],
        ])->assertOk()
            ->assertJsonPath('data.volume_horaire', 2)
            ->assertJsonPath('data.nombre_seances', 1)
            ->assertJsonPath('data.ventilation.0.matiere', 'Mathématiques')
            ->assertJsonPath('data.ventilation.0.nombre_heures', 2);
    }

    /**
     * Le sélecteur de dépôt de l'écran enseignant se remplit depuis cette
     * route : le client a besoin du `statut` (pas seulement de `est_ouverte`)
     * pour l'historique, et le serveur ne filtre PAS les périodes closes ici
     * (D-051 n'est tranchée qu'à l'écriture).
     */
    public function test_periodes_du_depot_listent_les_periodes_avec_leur_statut(): void
    {
        $socle = $this->socle('moncab12f');
        $this->connecte('moncab12f', $socle['enseignant_email']);

        $this->getJson($this->api('moncab12f', '/periodes'))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Octobre 2026')
            ->assertJsonPath('data.0.statut', 'ouverte')
            ->assertJsonPath('data.0.est_ouverte', true)
            ->assertJsonStructure([
                'data' => [['id', 'label', 'date_debut', 'date_fin', 'statut', 'est_ouverte']],
            ]);

        $this->cloturePeriode('moncab12f');

        $this->getJson($this->api('moncab12f', '/periodes'))
            ->assertOk()
            ->assertJsonPath('data.0.statut', 'cloturee')
            ->assertJsonPath('data.0.est_ouverte', false);
    }

    public function test_periodes_du_depot_refusees_hors_profil_enseignant(): void
    {
        $socle = $this->socle('moncab12g');
        $this->connecte('moncab12g', $socle['mere_email']);

        $this->getJson($this->api('moncab12g', '/periodes'))->assertStatus(403);
    }

    public function test_depot_refuse_sans_reponses_obligatoires(): void
    {
        $socle = $this->socle('moncab12d');
        $this->connecte('moncab12d', $socle['enseignant_email']);

        $reponse = $this->postJson($this->api('moncab12d'), [
            'contrat_cours_id' => $socle['contrat_id'],
            'periode_id' => $socle['periode_id'],
            'reponses' => [],
        ])->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION');

        $this->assertNotEmpty(
            $reponse->json('erreurs.reponses'),
            'Attendu une erreur de validation sur les réponses obligatoires.'
        );
    }

    public function test_depot_refuse_une_reponse_sur_un_element_inconnu(): void
    {
        $socle = $this->socle('moncab12e');
        $this->connecte('moncab12e', $socle['enseignant_email']);

        $reponse = $this->postJson($this->api('moncab12e'), [
            'contrat_cours_id' => $socle['contrat_id'],
            'periode_id' => $socle['periode_id'],
            'reponses' => $socle['reponses_par_defaut'] + ['999999' => 'Intrus'],
        ])->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION');

        $this->assertNotEmpty(
            $reponse->json('erreurs.reponses'),
            'Attendu une erreur de validation sur l\'élément inconnu.'
        );
    }

    // ------------------------------------------------------- PDF

    public function test_pdf_du_rapport_accesssible_au_proprietaire(): void
    {
        $socle = $this->socle('moncab13');
        $this->connecte('moncab13', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab13'), $this->payload($socle))->json('data');

        $this->get($this->api('moncab13', "/{$rapport['id']}/pdf"))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Un contrat peut être partagé entre professeurs : le PDF ne présente
     * que les matières dont l'ENSEIGNANT du rapport a été chargé.
     */
    public function test_pdf_ne_liste_que_les_matieres_de_l_enseignant(): void
    {
        $socle = $this->socle('moncab13m');

        // Le collègue partage le même contrat sur une AUTRE matière.
        tenancy()->initialize('moncab13m');
        $matiereColl = Matiere::create(['nom' => 'Sciences Physiques', 'sigle' => 'SP']);
        AffectationEnseignant::create([
            'contrat_cours_id' => $socle['contrat_id'],
            'enseignant_id' => $socle['autre_id'],
            'matiere_id' => $matiereColl->id,
            'taux_horaire_enseignant' => 3000,
            'nombre_heures_prevues' => 3,
            'date_affectation' => '2026-10-01',
            'statut' => 'actif',
        ]);
        tenancy()->end();

        $this->connecte('moncab13m', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab13m'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data');

        tenancy()->initialize('moncab13m');
        $model = RapportMensuelEnseignant::findOrFail($rapport['id']);
        $donnees = app(RapportMensuelPdfService::class)
            ->prepareData($model);

        $noms = $donnees['matieres']->pluck('nom')->all();

        $this->assertSame(
            ['Mathématiques'],
            $noms,
            'Seule la matière de l\'enseignant doit figurer dans le PDF, reçu : '
                .json_encode($noms)
        );
        $this->assertNotContains('Sciences Physiques', $noms);

        // L'identité complète du cabinet et la référence nourrissent le
        // nouveau gabarit (en-tête, pied de page « Page X / Y »).
        $this->assertArrayHasKey('cabinet', $donnees);
        $this->assertNotEmpty($donnees['cabinet_nom']);
        $this->assertMatchesRegularExpression('/^RM-\d{4}\/\d{4}$/', $donnees['numero']);

        tenancy()->end();
    }

    /**
     * L'en-tête du PDF porte l'identité du cabinet CONCERNÉ (celui du tenant
     * courant), la même donnée que le front — jamais celle d'un autre cabinet
     * ni la config legacy `keduc.cabinet`.
     */
    public function test_pdf_affiche_lidentite_du_cabinet_concerné(): void
    {
        $socle = $this->socle('moncab13c');

        tenancy()->initialize('moncab13c');
        $public = ParametrePublic::query()->firstOrNew();
        $donnees = $public->data ?? [];
        $donnees['fiche'] = [
            'identite' => [
                'slogan' => 'Se former pour mieux servir',
                'directeur' => 'Mme Exemple',
            ],
            'contact' => [
                'telephone' => '11223344',
                'whatsapp' => '55667788',
                'email' => 'contact@cabinet-exemple.bf',
                'adresse' => 'Bobo-Dioulasso - Burkina Faso',
            ],
        ];
        $public->data = $donnees;
        $public->save();
        tenancy()->end();

        $this->connecte('moncab13c', $socle['enseignant_email']);
        $rapport = $this->postJson($this->api('moncab13c'), $this->payload($socle))
            ->assertStatus(201)
            ->json('data');

        tenancy()->initialize('moncab13c');
        $model = RapportMensuelEnseignant::findOrFail($rapport['id']);
        $donnees = app(RapportMensuelPdfService::class)->prepareData($model);

        $this->assertSame('Cabinet moncab13c', $donnees['cabinet_nom']);
        $this->assertSame('Cabinet moncab13c', $donnees['cabinet']['nom']);
        $this->assertSame('Se former pour mieux servir', $donnees['cabinet']['slogan']);
        $this->assertSame('11223344', $donnees['cabinet']['telephone']);
        $this->assertSame('55667788', $donnees['cabinet']['whatsapp']);
        $this->assertSame('contact@cabinet-exemple.bf', $donnees['cabinet']['email']);
        $this->assertSame('Bobo-Dioulasso - Burkina Faso', $donnees['cabinet']['adresse']);

        // En aucun cas l'identité d'un autre cabinet (legacy KEDUC).
        $this->assertNotSame('Cabinet KEDUC', $donnees['cabinet_nom']);
        $this->assertNotSame('contact@keduc.bf', $donnees['cabinet']['email']);

        // Aucun logo configuré pour ce cabinet : repli monogramme.
        $this->assertNull($donnees['cabinet_logo']);

        tenancy()->end();
    }
}