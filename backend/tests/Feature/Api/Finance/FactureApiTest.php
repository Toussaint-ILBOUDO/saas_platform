<?php

namespace Tests\Feature\Api\Finance;

use App\Models\AffectationEnseignant;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Facture;
use App\Models\Matiere;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Models\RapportMensuelEnseignantLigne;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API facture parent (T7A.8).
 *
 * La facture consomme la ventilation des rapports VALIDÉS (D-049/D-051) : les
 * tests verrouillent la source de vérité (2 h × 2 500 F = 5 000 F), la règle
 * des prérequis (pas de facture avant validation de tous les rapports), le
 * gel des périodes closes, le doublon contrat/période, et le périmètre parent
 * (on ne voit que SES factures, on ne règle jamais soi-même).
 */
class FactureApiTest extends TenantTestCase
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

    private function parentApi(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/mes-factures{$chemin}";
    }

    private function adminApi(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/admin/factures{$chemin}";
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

    /**
     * Cabinet minimal : période ouverte, un élève attaché à sa mère, un
     * enseignant affecté sur une matière au taux 2 500 F/h. Pas de cahier de
     * texte ici : le rapport validé est injecté directement, car c'est le
     * rapport — pas sa provenance — que la facturation consomme.
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
        $autreParentUser = $this->creeUtilisateur($slug, 'Diallo', 'Autre', "autreparent@{$slug}.local");
        $autreParentUser->assignRole('parent');

        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $mereUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $matiere = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);

        $profUser = $this->creeUtilisateur($slug, 'Kaboré', 'Aïcha', "prof@{$slug}.local");
        $profUser->assignRole('enseignant');
        $prof = EnseignantProfil::create(['user_id' => $profUser->id]);
        $prof->matieres()->sync([$matiere->id]);

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

        $resultat = [
            'periode_id' => (int) PeriodeComptable::first()->id,
            'affectation_id' => (int) $affectation->id,
            'contrat_id' => (int) $contrat->id,
            'matiere_id' => (int) $matiere->id,
            'prof_id' => (int) $prof->id,
            'enseignant_email' => "prof@{$slug}.local",
            'admin_email' => "admin@{$slug}.local",
            'mere_email' => "mere@{$slug}.local",
            'autre_parent_email' => "autreparent@{$slug}.local",
        ];

        tenancy()->end();

        return $resultat;
    }

    /**
     * Injecte un rapport menuel validé (2 h) pour le contrat/période : c'est
     * lui que la facturation va consommer.
     */
    private function injRapportValide(array $socle): void
    {
        tenancy()->initialize('c1');
        $rapport = RapportMensuelEnseignant::create([
            'contrat_cours_id' => $socle['contrat_id'],
            'enseignant_id' => $socle['prof_id'],
            'periode_id' => $socle['periode_id'],
            'volume_horaire_cumule' => '2.00',
            'statut' => 'valide',
            'date_validation' => now(),
            'valide_par' => 1,
        ]);
        RapportMensuelEnseignantLigne::create([
            'rapport_mensuel_enseignant_id' => $rapport->id,
            'affectation_enseignant_id' => $socle['affectation_id'],
            'matiere_id' => $socle['matiere_id'],
            'nombre_seances' => 1,
            'nombre_heures' => '2.00',
        ]);
        tenancy()->end();
    }

    private function payloadFacture(array $socle, array $surcharge = []): array
    {
        return array_merge([
            'contrat_cours_id' => $socle['contrat_id'],
            'periode_id' => $socle['periode_id'],
        ], $surcharge);
    }

    // ---------------------------------------------------------------- parent

    public function test_parent_ne_voit_aucune_facture_avant_generation(): void
    {
        $socle = $this->socle('c1');
        $this->connecte('c1', $socle['mere_email']);

        $this->getJson($this->parentApi('c1'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_parent_ne_peut_pas_generer_ni_payer(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);
        $this->connecte('c1', $socle['mere_email']);

        // La génération est une route d'administration : le rôle suffit à 403.
        $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))
            ->assertForbidden();
    }

    // ------------------------------------------------------- génération admin

    public function test_admin_generer(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);
        $this->connecte('c1', $socle['admin_email']);

        $reponse = $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle));
        $reponse
            ->assertCreated()
            ->assertJsonPath('data.statut_paiement', 'en_attente')
            ->assertJsonPath('data.montant_total', 5000)
            ->assertJsonPath('data.volume_horaire_total', 2)
            ->assertJsonPath('data.lignes.0.nombre_heures', 2)
            ->assertJsonPath('data.lignes.0.montant', 5000)
            ->assertJsonPath('data.lignes.0.matiere', 'Mathématiques')
            ->assertJsonPath('data.actions', ['pdf', 'payer'])
            ->assertJsonStructure(['data' => ['numero_facture', 'eleve', 'parent', 'periode', 'lignes']]);

        // Le numéro séquentiel existe et commence par le préfixe FAC.
        $this->assertTrue(str_starts_with(
            $reponse->json('data.numero_facture'),
            'FAC-'
        ));
    }

    public function test_generer_avec_frais_remise_et_commentaire(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);
        $this->connecte('c1', $socle['admin_email']);

        $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle, [
            'frais_suivi' => 1000,
            'autres_frais' => 500,
            'remise' => 2000,
            'commentaire' => 'Rappel de l\'accord commercial.',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.frais_suivi', 1000)
            ->assertJsonPath('data.autres_frais', 500)
            ->assertJsonPath('data.remise', 2000)
            // 5000 + 1000 + 500 − 2000 = 4500
            ->assertJsonPath('data.montant_total', 4500);
    }

    public function test_generer_refuse_sans_rapport_valide(): void
    {
        $socle = $this->socle('c1');
        // Aucun rapport : la génération doit refuser.
        $this->connecte('c1', $socle['admin_email']);

        $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['rapports']]);
    }

    public function test_generer_refuse_le_doublon_contrat_periode(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);
        $this->connecte('c1', $socle['admin_email']);

        $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))->assertCreated();
        $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['facture']]);
    }

    public function test_generer_refuse_sur_periode_cloturee(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);

        tenancy()->initialize('c1');
        PeriodeComptable::query()->update(['statut' => PeriodeComptable::CLOTUREE]);
        tenancy()->end();

        $this->connecte('c1', $socle['admin_email']);

        $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['periode']]);
    }

    public function test_preview_calcule_sans_ecrire(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);
        $this->connecte('c1', $socle['admin_email']);

        $this->postJson($this->adminApi('c1', '/preview'), $this->payloadFacture($socle, [
            'frais_suivi' => 1000,
        ]))
            ->assertOk()
            ->assertJsonPath('data.montant_cours', 5000)
            ->assertJsonPath('data.frais_suivi', 1000)
            ->assertJsonPath('data.montant_total', 6000)
            ->assertJsonPath('data.volume_horaire_total', 2)
            ->assertJsonPath('data.lignes.0.matiere', 'Mathématiques');

        // L'aperçu ne crée aucune facture.
        $this->assertSame(0, Facture::count());
    }

    // ------------------------------------------------------------- paiement

    public function test_admin_marque_payee(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = data_get(
            $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))->json(),
            'data.id'
        );

        $this->postJson($this->adminApi('c1', "/{$id}/paiement"), [
            'date_paiement' => '2026-10-20',
            'mode_paiement' => 'orange_money',
            'reference_paiement' => 'OM-2026-00042',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut_paiement', 'payee')
            ->assertJsonPath('data.est_payee', true)
            ->assertJsonPath('data.mode_paiement', 'orange_money')
            ->assertJsonPath('data.reference_paiement', 'OM-2026-00042')
            // Une facture payée est figée : plus de règlement à proposer.
            ->assertJsonPath('data.actions', ['pdf']);
    }

    public function test_paiement_refuse_une_seconde_fois(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = data_get(
            $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))->json(),
            'data.id'
        );

        $this->postJson($this->adminApi('c1', "/{$id}/paiement"), [
            'date_paiement' => '2026-10-20',
            'mode_paiement' => 'especes',
        ])->assertOk();

        $this->postJson($this->adminApi('c1', "/{$id}/paiement"), [
            'date_paiement' => '2026-10-21',
            'mode_paiement' => 'virement',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['facture']]);
    }

    public function test_paiement_refuse_sans_mode(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = data_get(
            $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))->json(),
            'data.id'
        );

        $this->postJson($this->adminApi('c1', "/{$id}/paiement"), [
            'date_paiement' => '2026-10-20',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['mode_paiement']]);
    }

    // ------------------------------------------------------------- périmètre

    public function test_index_admin_et_parent_apres_generation(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);

        $this->connecte('c1', $socle['admin_email']);
        $id = data_get(
            $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))->json(),
            'data.id'
        );

        // Admin : la facture apparaît, encore en attente.
        $this->getJson($this->adminApi('c1'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.montant_total', 5000)
            ->assertJsonPath('data.0.statut_paiement', 'en_attente');

        // Parent : sa facture apparaît aussi, mais en lecture seule.
        $this->connecte('c1', $socle['mere_email']);
        $this->getJson($this->parentApi('c1'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.actions', ['pdf']);

        // Le détail parent marche et le PDF se télécharge.
        $this->getJson($this->parentApi('c1', "/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.numero_facture', data_get(
                $this->getJson($this->parentApi('c1'))->json(),
                'data.0.numero_facture'
            ));

        $this->get($this->parentApi('c1', "/{$id}/pdf"))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_parent_ne_voit_pas_la_facture_d_un_autre_parent(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportValide($socle);

        $this->connecte('c1', $socle['admin_email']);
        $id = data_get(
            $this->postJson($this->adminApi('c1'), $this->payloadFacture($socle))->json(),
            'data.id'
        );

        // Un autre parent du même cabinet n'a aucune facture : le détail doit
        // donner un 404, jamais un 403 (la facture « n'existe pas » pour lui).
        $this->connecte('c1', $socle['autre_parent_email']);
        $this->getJson($this->parentApi('c1', "/{$id}"))
            ->assertNotFound();

        $this->getJson($this->parentApi('c1'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_filtre_statut_inconnu_rejete(): void
    {
        $socle = $this->socle('c1');
        $this->connecte('c1', $socle['admin_email']);

        $this->getJson($this->adminApi('c1') . '?statut=partiel')
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['statut']]);
    }
}