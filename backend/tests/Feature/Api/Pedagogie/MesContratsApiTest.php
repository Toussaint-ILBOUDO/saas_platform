<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\TypeCours;
use App\Models\User;
use App\Modules\Systeme\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * « Mes contrats » du parent (T7A.3) — API `/api/mes-contrats`.
 *
 * Le contrat vit dans `/api/pedagogie/contrats`, réservé `role:admin_cabinet` :
 * la notification « Nouveau contrat de cours » envoyée au parent mène donc à un
 * 403, et côté Angular la route `pedagogie/contrats/{id}` n'existe pas — le
 * router retombait sur le `**` racine et affichait le site public.
 *
 * Les tests verrouillent les trois garde-fous du périmètre :
 *  - le parent ne voit que les contrats de SES enfants ;
 *  - la fiche est contrôlée par `ContratCoursPolicy::view` (403 hors famille) ;
 *  - le pied d'accès reste réservé au rôle `parent` ;
 *  - et la notification cible bien le portail parent, pas l'écran admin.
 */
class MesContratsApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    public function test_le_parent_ne_voit_que_les_contrats_de_ses_enfants(): void
    {
        $s = $this->socle('mc1');
        $this->connecte('mc1', $s['parentA_email']);

        $reponse = $this->getJson("http://mc1.localhost/api/mes-contrats")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $s['contratA'])
            ->assertJsonPath('data.0.eleve.id', $s['eleveA']);

        // Les paramètres financiers sont exposés : c'est ce que la famille
        // lit pour comprendre sa facture.
        $this->assertSame(
            10000,
            (int) $reponse->json('data.0.affectations.0.montant_prevu')
        );
    }

    public function test_la_fiche_expose_la_matiere_et_l_enseignant(): void
    {
        $s = $this->socle('mc2');
        $this->connecte('mc2', $s['parentA_email']);

        $reponse = $this->getJson("http://mc2.localhost/api/mes-contrats/{$s['contratA']}")
            ->assertOk()
            ->assertJsonPath('data.id', $s['contratA'])
            ->assertJsonPath('data.affectations.0.matiere.nom', 'Mathématiques')
            ->assertJsonPath('data.affectations.0.taux_horaire_enseignant', 2500);

        // L'enseignant est résolu via `enseignant_profils.user` : la famille
        // doit lire un nom, jamais un identifiant nu.
        $this->assertNotNull($reponse->json('data.affectations.0.enseignant.nom'));
        $this->assertNotEmpty($reponse->json('data.affectations.0.enseignant.prenom'));
    }

    public function test_la_fiche_d_une_autre_famille_est_refusee(): void
    {
        $s = $this->socle('mc3');
        $this->connecte('mc3', $s['parentA_email']);

        $this->getJson("http://mc3.localhost/api/mes-contrats/{$s['contratB']}")
            ->assertForbidden();
    }

    public function test_le_pied_d_acces_est_reserve_au_role_parent(): void
    {
        $s = $this->socle('mc4');

        // L'admin n'a pas accès au portail famille : il passe par
        // /pedagogie/contrats, qui porte la gestion.
        $this->connecte('mc4', $s['admin_email']);
        $this->getJson('http://mc4.localhost/api/mes-contrats')->assertForbidden();
    }

    public function test_l_endpoint_d_administration_reste_interdit_au_parent(): void
    {
        // La raison d'être de /mes-contrats : sans lui, le parent n'a AUCUNE
        // porte d'entrée sur le contrat qu'on vient de lui annoncer.
        $s = $this->socle('mc5');
        $this->connecte('mc5', $s['parentA_email']);

        $this->getJson("http://mc5.localhost/api/pedagogie/contrats/{$s['contratA']}")
            ->assertForbidden();
    }

    public function test_la_notification_parente_pointe_vers_le_portail_parent(): void
    {
        $s = $this->socle('mc6');

        $this->notifier('mc6', $s['parentA_email'], $s['contratA']);
        $this->connecte('mc6', $s['parentA_email']);

        $this->getJson('http://mc6.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'contrat')
            ->assertJsonPath(
                'data.0.route_angular',
                "/espace/modules/mes-contrats/{$s['contratA']}"
            );
    }

    public function test_la_notification_admine_vise_l_ecran_d_administration(): void
    {
        $s = $this->socle('mc7');

        $this->notifier('mc7', $s['admin_email'], $s['contratA']);
        $this->connecte('mc7', $s['admin_email']);

        $this->getJson('http://mc7.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath(
                'data.0.route_angular',
                "/espace/pedagogie/contrats/{$s['contratA']}"
            );
    }

    public function test_la_notification_enseignante_vise_ses_cours(): void
    {
        $s = $this->socle('mc8');

        $this->notifier('mc8', $s['enseignant_email'], $s['contratA']);
        $this->connecte('mc8', $s['enseignant_email']);

        $this->getJson('http://mc8.localhost/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.route_angular', '/espace/modules/mes-cours');
    }

    /* ------------------------------------------------------------------ */

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

    /**
     * Crée une notification de type `contrat` pour le compte indiqué.
     * `route_angular` est un accesseur résolu à la lecture : il faut donc
     * notifier puis se reconnecter en ce compte pour observer la route.
     */
    private function notifier(string $slug, string $email, int $contratId): void
    {
        tenancy()->initialize($slug);
        $user = User::where('email', $email)->firstOrFail();

        (new NotificationService())->create(
            $user->id,
            'Nouveau contrat de cours',
            'Un contrat a été créé pour votre enfant.',
            'contrat',
            ['contrat_id' => $contratId],
            'bi-file-earmark-text'
        );

        tenancy()->end();
    }

    /**
     * Deux familles indépendantes, un contrat chacune, plus un enseignant
     * « ordinaire » (sans droit admin) pour vérifier la route de sa
     * notification.
     *
     * @return array<string, mixed>
     */
    private function socle(string $slug): array
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);

        $admin = User::where('email', "admin@{$slug}.local")->first();

        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $matiere = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);
        $typeCours = TypeCours::create([
            'code' => 'DOM',
            'libelle' => 'Domicile',
            'actif' => true,
        ]);

        $prof = $admin->enseignantProfil()->firstOrCreate([], []);
        $prof->matieres()->sync([$matiere->id]);

        $enseignant = User::create([
            'nom' => 'Kaboré',
            'prenom' => 'Awa',
            'email' => "awa@{$slug}.local",
            'password' => Hash::make('Secret1234'),
        ]);
        $enseignant->assignRole('enseignant');

        $famille = static function (string $nom) use ($classe, $typeCours, $prof, $matiere) {
            $parent = User::create([
                'nom' => $nom,
                'prenom' => 'Parent',
                'email' => "parent-{$nom}@local.test",
                'password' => Hash::make('Secret1234'),
            ]);
            $parent->assignRole('parent');
            // `ContratCoursPolicy::view` autorise le parent via `parentProfil`
            // + `eleves.parent_id = users.id` : ParentService::create crée
            // toujours les deux, le socle doit donc les faire exister aussi.
            $parent->parentProfil()->firstOrCreate([], []);

            $eleveUser = User::create([
                'nom' => $nom,
                'prenom' => 'Enfant',
                'email' => "eleve-{$nom}@local.test",
                'password' => Hash::make('Secret1234'),
            ]);
            $eleveUser->assignRole('eleve');

            // `eleves.parent_id` référence `users.id`, jamais `parent_profils.id`.
            $eleve = Eleve::create([
                'user_id' => $eleveUser->id,
                'parent_id' => $parent->id,
                'classe_id' => $classe->id,
                'statut' => true,
            ]);

            $contrat = ContratCours::create([
                'eleve_id' => $eleve->id,
                'type_cours_id' => $typeCours->id,
                'date_debut' => '2026-01-01',
                'autres_frais_suivi' => 0,
                'statut' => 'actif',
            ]);

            AffectationEnseignant::create([
                'contrat_cours_id' => $contrat->id,
                'enseignant_id' => $prof->id,
                'matiere_id' => $matiere->id,
                'taux_horaire_enseignant' => 2500,
                'nombre_heures_prevues' => 4,
                'date_affectation' => '2026-01-01',
                'statut' => 'actif',
            ]);

            return [
                'email' => $parent->email,
                'eleve_id' => (int) $eleve->id,
                'contrat_id' => (int) $contrat->id,
            ];
        };

        $a = $famille('alpha');
        $b = $famille('beta');

        tenancy()->end();

        return [
            'parentA_email' => $a['email'],
            'parentB_email' => $b['email'],
            'admin_email' => "admin@{$slug}.local",
            'enseignant_email' => $enseignant->email,
            'eleveA' => $a['eleve_id'],
            'contratA' => $a['contrat_id'],
            'contratB' => $b['contrat_id'],
        ];
    }
}
