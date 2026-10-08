<?php

namespace Tests\Feature\Api\Finance;

use App\Models\AffectationEnseignant;
use App\Models\BulletinPaie;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Matiere;
use App\Models\PeriodeComptable;
use App\Models\RapportMensuelEnseignant;
use App\Models\RapportMensuelEnseignantLigne;
use App\Models\TypeAjustement;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API bulletin de paie (T7A.9).
 *
 * Le bulletin consomme les rapports VALIDÉS (D-049/D-051) : 2 h × 2 500 F pour
 * le professeur A, 2 h × 2 000 F pour le professeur B. Les tests verrouillent
 * la source de vérité (les heures validées seules paient), le gel des
 * périodes closes, le cycle enseignant (consulter → valider/contester → recevoir
 * après versement, D-052), les ajustements, et le périmètre (un enseignant ne
 * voit jamais le bulletin d'un autre).
 */
class BulletinPaieApiTest extends TenantTestCase
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

    private function profApi(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/mes-bulletins{$chemin}";
    }

    private function adminApi(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/admin/bulletins-paie{$chemin}";
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
     * Cabinet minimal : période ouverte, un élève, deux enseignants (2 h à
     * 2 500 F et 2 h à 2 000 F) affectés sur le même contrat. Les rapports
     * validés sont injectés directement : c'est le rapport — pas sa
     * provenance — que la paie consomme.
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
        $parentUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Mère', "mere@{$slug}.local");
        $parentUser->assignRole('parent');

        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $parentUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $math = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);
        $svt = Matiere::create(['nom' => 'SVT', 'sigle' => 'SVT']);

        $profAUser = $this->creeUtilisateur($slug, 'Kaboré', 'Aïcha', "profa@{$slug}.local");
        $profAUser->assignRole('enseignant');
        $profA = EnseignantProfil::create(['user_id' => $profAUser->id]);
        $profA->matieres()->sync([$math->id]);

        $profBUser = $this->creeUtilisateur($slug, 'Zongo', 'Marie', "profb@{$slug}.local");
        $profBUser->assignRole('enseignant');
        $profB = EnseignantProfil::create(['user_id' => $profBUser->id]);
        $profB->matieres()->sync([$svt->id]);

        // Deux types d'ajustement actifs : un crédit (prime) et un débit (retenue).
        TypeAjustement::create([
            'libelle' => 'Prime de transport',
            'direction' => 'credit',
            'is_active' => true,
        ]);
        TypeAjustement::create([
            'libelle' => 'Retenue d\'assurance',
            'direction' => 'debit',
            'is_active' => true,
        ]);

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

        $affectationA = AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $profA->id,
            'matiere_id' => $math->id,
            'taux_horaire_enseignant' => 2500,
            'nombre_heures_prevues' => 4,
            'date_affectation' => '2026-10-01',
            'statut' => 'actif',
        ]);

        $affectationB = AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $profB->id,
            'matiere_id' => $svt->id,
            'taux_horaire_enseignant' => 2000,
            'nombre_heures_prevues' => 4,
            'date_affectation' => '2026-10-01',
            'statut' => 'actif',
        ]);

        $resultat = [
            'periode_id' => (int) PeriodeComptable::first()->id,
            'contrat_id' => (int) $contrat->id,
            'affectation_a' => (int) $affectationA->id,
            'affectation_b' => (int) $affectationB->id,
            'matiere_math' => (int) $math->id,
            'matiere_svt' => (int) $svt->id,
            'prof_a' => (int) $profA->id,
            'prof_b' => (int) $profB->id,
            'prof_a_email' => "profa@{$slug}.local",
            'prof_b_email' => "profb@{$slug}.local",
            'admin_email' => "admin@{$slug}.local",
        ];

        tenancy()->end();

        return $resultat;
    }

    /**
     * Injecte les deux rapports mensuels validés (2 h chacun).
     */
    private function injRapportsValides(array $socle): void
    {
        tenancy()->initialize('c1');

        foreach ([['id' => $socle['prof_a'], 'aff' => $socle['affectation_a'], 'mat' => $socle['matiere_math']],
                  ['id' => $socle['prof_b'], 'aff' => $socle['affectation_b'], 'mat' => $socle['matiere_svt']]] as $data) {
            $rapport = RapportMensuelEnseignant::create([
                'contrat_cours_id' => $socle['contrat_id'],
                'enseignant_id' => $data['id'],
                'periode_id' => $socle['periode_id'],
                'volume_horaire_cumule' => '2.00',
                'statut' => 'valide',
                'date_validation' => now(),
                'valide_par' => 1,
            ]);
            RapportMensuelEnseignantLigne::create([
                'rapport_mensuel_enseignant_id' => $rapport->id,
                'affectation_enseignant_id' => $data['aff'],
                'matiere_id' => $data['mat'],
                'nombre_seances' => 1,
                'nombre_heures' => '2.00',
            ]);
        }

        tenancy()->end();
    }

    private function payload(array $socle, array $surcharge = []): array
    {
        return array_merge([
            'periode_id' => $socle['periode_id'],
            // Aucun frais de suivi : net = brut (lisibilité des montants).
            'frais_suivi' => [
                (string) $socle['prof_a'] => 0,
                (string) $socle['prof_b'] => 0,
            ],
        ], $surcharge);
    }

    private function idBulletinA(array $json): int
    {
        return (int) $json['data'][0]['id'];
    }

    // ------------------------------------------------------------- périmètre

    public function test_enseignant_ne_voit_aucun_bulletin_avant_generation(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['prof_a_email']);

        $this->getJson($this->profApi('c1'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_enseignant_ne_peut_pas_generer(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['prof_a_email']);

        $this->postJson($this->adminApi('c1'), $this->payload($socle))
            ->assertForbidden();
    }

    // ----------------------------------------------------- preview + génération

    public function test_admin_preview_calcule_sans_ecrire(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $this->postJson($this->adminApi('c1', '/preview'), $this->payload($socle))
            ->assertOk()
            ->assertJsonPath('total_enseignants', 2)
            ->assertJsonPath('total_heures', 4)
            ->assertJsonPath('total_montant', 9000)
            ->assertJsonPath('data.0.enseignant.id', $socle['prof_a'])
            ->assertJsonPath('data.0.montant_brut', 5000)
            ->assertJsonPath('data.0.lignes.0.nombre_heures', 2)
            ->assertJsonCount(2, 'types_ajustement')
            ->assertJsonPath('types_ajustement.0.direction', 'credit')
            ->assertJsonStructure(['data' => [['enseignant', 'lignes', 'montant_net']]]);

        // L'aperçu ne crée aucun bulletin.
        $this->assertSame(0, BulletinPaie::count());
    }

    public function test_admin_generer_les_bulletins(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $reponse = $this->postJson($this->adminApi('c1'), $this->payload($socle));

        $reponse
            ->assertCreated()
            ->assertJsonCount(2, 'data')
            // Professeur A : 2 h × 2 500 F = 5 000 F, B : 2 h × 2 000 F = 4 000 F.
            ->assertJsonPath('data.0.montant_brut', 5000)
            ->assertJsonPath('data.0.montant_net', 5000)
            ->assertJsonPath('data.0.total_heures', 2)
            ->assertJsonPath('data.0.statut', 'genere')
            ->assertJsonPath('data.1.montant_brut', 4000)
            ->assertJsonPath('data.1.total_heures', 2)
            ->assertJsonStructure(['data' => [['numero', 'enseignant', 'periode', 'lignes']]]);

        // Le numéro séquentiel existe et commence par le préfixe BP.
        $this->assertTrue(str_starts_with(
            $reponse->json('data.0.numero'),
            'BP-'
        ));
    }

    public function test_generation_refuse_sur_periode_cloturee(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);

        tenancy()->initialize('c1');
        PeriodeComptable::query()->update(['statut' => PeriodeComptable::CLOTUREE]);
        tenancy()->end();

        $this->connecte('c1', $socle['admin_email']);

        $this->postJson($this->adminApi('c1'), $this->payload($socle))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['periode']]);
    }

    // ---------------------------------------------------- cycle enseignant

    public function test_enseignant_consulte_puis_valide(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        $this->connecte('c1', $socle['prof_a_email']);

        // L'enseignant ne voit que son bulletin, avec l'action « consulter ».
        $this->getJson($this->profApi('c1'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.actions', ['pdf', 'consulter']);

        $this->postJson($this->profApi('c1', "/{$id}/consulter"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'consulte')
            ->assertJsonPath('data.actions', ['pdf', 'valider', 'contester']);

        $this->postJson($this->profApi('c1', "/{$id}/valider"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'valide')
            ->assertJsonPath('data.actions', ['pdf']);
    }

    public function test_enseignant_conteste_puis_admin_corrige(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        $this->connecte('c1', $socle['prof_a_email']);
        $this->postJson($this->profApi('c1', "/{$id}/consulter"))->assertOk();

        $this->postJson($this->profApi('c1', "/{$id}/contester"), [
            'motif_contestation' => 'heures',
            'commentaire_enseignant' => 'La séance du 14 octobre n\'a pas été comptée dans mes heures.',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'conteste')
            ->assertJsonPath('data.motif_contestation', 'heures')
            ->assertJsonPath('data.libelle_motif_contestation', 'Heures retenues incorrectes')
            ->assertJsonPath('data.actions', ['pdf']);

        // L'administration corrige, puis l'enseignant peut re-consulter.
        $this->connecte('c1', $socle['admin_email']);

        $this->getJson($this->adminApi('c1', "/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.actions', ['pdf', 'corriger', 'ajuster']);

        $this->postJson($this->adminApi('c1', "/{$id}/corriger"), [
            'commentaire_admin' => 'Heures rectifiées après vérification du cahier de texte.',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'corrige');

        $this->connecte('c1', $socle['prof_a_email']);

        $this->postJson($this->profApi('c1', "/{$id}/consulter"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'consulte');
    }

    public function test_contester_exige_motif_et_detail(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        $this->connecte('c1', $socle['prof_a_email']);
        $this->postJson($this->profApi('c1', "/{$id}/consulter"))->assertOk();

        $this->postJson($this->profApi('c1', "/{$id}/contester"), [
            'motif_contestation' => 'inconnu',
            'commentaire_enseignant' => 'Détail d\'au moins vingt caractères.',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['motif_contestation']]);

        $this->postJson($this->profApi('c1', "/{$id}/contester"), [
            'motif_contestation' => 'taux',
            'commentaire_enseignant' => 'trop court',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['commentaire_enseignant']]);
    }

    // ---------------------------------------------------------------- paiement

    public function test_paiement_refuse_sans_validation(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        $this->postJson($this->adminApi('c1', "/{$id}/paiement"), [
            'date_paiement' => '2026-10-20',
            'mode_paiement' => 'orange_money',
            'reference_paiement' => 'OM-2026-00042',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['statut']]);
    }

    public function test_versement_puis_confirmation_de_reception(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        $this->connecte('c1', $socle['prof_a_email']);
        $this->postJson($this->profApi('c1', "/{$id}/consulter"))->assertOk();
        $this->postJson($this->profApi('c1', "/{$id}/valider"))->assertOk();

        // L'administration verse (valide → verse).
        $this->connecte('c1', $socle['admin_email']);

        $this->getJson($this->adminApi('c1', "/{$id}"))
            ->assertJsonPath('data.actions', ['pdf', 'payer', 'ajuster']);

        $this->postJson($this->adminApi('c1', "/{$id}/paiement"), [
            'date_paiement' => '2026-10-20',
            'mode_paiement' => 'virement',
            'reference_paiement' => 'VIR-2026-0133',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'verse')
            ->assertJsonPath('data.en_attente_reception', true)
            ->assertJsonPath('data.mode_paiement', 'virement')
            // Un bulletin versé est figé pour l'admin.
            ->assertJsonPath('data.actions', ['pdf']);

        // L'enseignant confirme avoir reçu (verse → reçu, D-052).
        $this->connecte('c1', $socle['prof_a_email']);

        $this->getJson($this->profApi('c1', "/{$id}"))
            ->assertJsonPath('data.actions', ['pdf', 'confirmer-reception']);

        $this->postJson($this->profApi('c1', "/{$id}/confirmer-reception"))
            ->assertOk()
            ->assertJsonPath('data.statut', 'verse')
            ->assertJsonPath('data.est_recu', true)
            ->assertJsonPath('data.en_attente_reception', false)
            ->assertJsonPath('data.actions', ['pdf']);

        // Un second appel ne doit pas attendre : déjà reçu.
        $this->postJson($this->profApi('c1', "/{$id}/confirmer-reception"))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['bulletin']]);
    }

    // ------------------------------------------------------------ ajustements

    public function test_ajustement_ajoute_puis_supprime(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);

        tenancy()->initialize('c1');
        $prime = TypeAjustement::create([
            'libelle' => 'Prime de transport',
            'direction' => 'credit',
            'is_active' => true,
        ]);
        tenancy()->end();

        $this->connecte('c1', $socle['admin_email']);

        $id = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        // Prime de 1 000 F : net 5 000 → 6 000.
        $ajustement = $this->postJson($this->adminApi('c1', "/{$id}/ajustements"), [
            'type_ajustement_id' => $prime->id,
            'libelle' => 'Prime de transport',
            'montant' => 1000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.libelle', 'Prime de transport')
            ->assertJsonPath('data.type', 'prime')
            ->json();

        $this->getJson($this->adminApi('c1', "/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.montant_brut', 5000)
            ->assertJsonPath('data.total_primes', 1000)
            ->assertJsonPath('data.montant_net_final', 6000)
            ->assertJsonCount(1, 'data.ajustements');

        // Suppression : net de retour à 5 000.
        $this->deleteJson($this->adminApi('c1', "/{$id}/ajustements/{$ajustement['data']['id']}"))
            ->assertNoContent();

        $this->getJson($this->adminApi('c1', "/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.total_primes', 0)
            ->assertJsonPath('data.montant_net_final', 5000)
            ->assertJsonCount(0, 'data.ajustements');
    }

    public function test_ajustement_refuse_sur_bulletin_verse(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);

        tenancy()->initialize('c1');
        $prime = TypeAjustement::create([
            'libelle' => 'Prime de transport',
            'direction' => 'credit',
            'is_active' => true,
        ]);
        tenancy()->end();

        $this->connecte('c1', $socle['admin_email']);

        $id = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        // Cycle complet jusqu'au versement.
        $this->connecte('c1', $socle['prof_a_email']);
        $this->postJson($this->profApi('c1', "/{$id}/consulter"))->assertOk();
        $this->postJson($this->profApi('c1', "/{$id}/valider"))->assertOk();

        $this->connecte('c1', $socle['admin_email']);
        $this->postJson($this->adminApi('c1', "/{$id}/paiement"), [
            'date_paiement' => '2026-10-20',
            'mode_paiement' => 'especes',
        ])->assertOk();

        $this->postJson($this->adminApi('c1', "/{$id}/ajustements"), [
            'type_ajustement_id' => $prime->id,
            'libelle' => 'Prime de transport',
            'montant' => 1000,
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['bulletin']]);
    }

    // ------------------------------------------------------------- périmètre

    public function test_enseignant_ne_voit_pas_le_bulletin_d_un_autre(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $idA = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        // Le professeur B ne voit ni le détail ni le PDF de A (404).
        $this->connecte('c1', $socle['prof_b_email']);

        $this->getJson($this->profApi('c1', "/{$idA}"))
            ->assertNotFound();

        $this->getJson($this->profApi('c1'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.montant_brut', 4000);
    }

    public function test_index_filtre_par_statut(): void
    {
        $socle = $this->socle('c1');
        $this->injRapportsValides($socle);
        $this->connecte('c1', $socle['admin_email']);

        $id = $this->idBulletinA(
            $this->postJson($this->adminApi('c1'), $this->payload($socle))->json()
        );

        $this->connecte('c1', $socle['prof_a_email']);
        $this->postJson($this->profApi('c1', "/{$id}/consulter"))->assertOk();

        $this->getJson($this->profApi('c1') . '?statut=consulte')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson($this->profApi('c1') . '?statut=valide')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_filtre_statut_inconnu_rejete(): void
    {
        $socle = $this->socle('c1');
        $this->connecte('c1', $socle['admin_email']);

        $this->getJson($this->adminApi('c1') . '?statut=paye')
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['statut']]);
    }
}