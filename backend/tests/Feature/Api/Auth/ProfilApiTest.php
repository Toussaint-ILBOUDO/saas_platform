<?php

namespace Tests\Feature\Api\Auth;

use App\Models\AffectationEnseignant;
use App\Models\Cabinet;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Matiere;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API « mon profil » (Angular) : identité du compte, photo, blocs de
 * profil par rôle actif, et droits partagés d'édition d'une fiche élève
 * (élève lui-même, parent, enseignant affecté, admin).
 */
class ProfilApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function preparerCabinet(string $slug): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        if (! Cabinet::find($slug)) {
            $this->makeCabinet($slug);
        }
        tenancy()->initialize($slug);
    }

    private function creerCompte(string $slug, string $email, string $role): User
    {
        $this->preparerCabinet($slug);

        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'Compte',
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
        $user->assignRole($role);

        if ($role === 'parent') {
            $user->parentProfil()->firstOrCreate([], []);
        }
        if ($role === 'enseignant') {
            $user->enseignantProfil()->firstOrCreate([], []);
        }

        tenancy()->end();

        return $user;
    }

    private function connecter(string $slug, string $email): void
    {
        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    /* ---------------------------------------------------------------
       Compte (User) + photo
       --------------------------------------------------------------- */

    public function test_update_moi_met_a_jour_identite_et_coordonnees(): void
    {
        $this->creerCompte('c1', 'moi@c1.local', 'enseignant');
        $this->connecter('c1', 'moi@c1.local');

        $this->putJson('http://c1.localhost/api/auth/moi', [
            'nom' => 'OUEDRAOGO',
            'prenom' => 'Amine',
            'telephone_whatsapp' => '70000001',
            'telephone_appel' => '70000002',
            'email' => 'moi@c1.local',
        ])
            ->assertOk()
            ->assertJsonPath('user.nom', 'OUEDRAOGO')
            ->assertJsonPath('user.prenom', 'Amine')
            ->assertJsonPath('user.telephone_whatsapp', '70000001');

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertOk()
            ->assertJsonPath('user.nom', 'OUEDRAOGO');
    }

    public function test_update_moi_refuse_email_deja_utilise(): void
    {
        $this->creerCompte('c1', 'premier@c1.local', 'enseignant');
        $this->creerCompte('c1', 'deuxieme@c1.local', 'parent');
        $this->connecter('c1', 'deuxieme@c1.local');

        $this->putJson('http://c1.localhost/api/auth/moi', [
            'nom' => 'Test',
            'prenom' => 'Deux',
            'email' => 'premier@c1.local',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['email']]);
    }

    public function test_photo_profil_upload_affichage_et_suppression(): void
    {
        $this->creerCompte('c1', 'photo@c1.local', 'parent');
        $this->connecter('c1', 'photo@c1.local');

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertOk()
            ->assertJsonPath('user.photo_url', null);

        $this->post('http://c1.localhost/api/auth/moi/photo', [
            'photo' => UploadedFile::fake()->image('moi.jpg', 200, 200),
        ])
            ->assertOk()
            ->assertJsonStructure(['message', 'photo_url']);

        // Le SessionGuard mémoïse l'utilisateur (et sa relation `media`
        // chargée à vide pendant l'upload) entre les requêtes du test :
        // on force un rechargement propre avant de relire le profil.
        $this->app['auth']->forgetGuards();

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertOk()
            ->assertJsonPath('user.photo_url', fn ($url) => str_contains((string) $url, '/api/auth/moi/photo'));

        // Le binaire passe bien par la route contrôlée.
        $this->get('http://c1.localhost/api/auth/moi/photo')
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->deleteJson('http://c1.localhost/api/auth/moi/photo')->assertOk();
        $this->app['auth']->forgetGuards();

        $this->getJson('http://c1.localhost/api/auth/moi')
            ->assertOk()
            ->assertJsonPath('user.photo_url', null);
    }

    /* ---------------------------------------------------------------
       Blocs de profil par rôle
       --------------------------------------------------------------- */

    public function test_profil_enseignant_lisible_et_modifiable(): void
    {
        $this->creerCompte('c1', 'prof@c1.local', 'enseignant');
        $this->preparerCabinet('c1');
        $matiere = Matiere::firstOrCreate(['nom' => 'Algèbre'], ['sigle' => 'ALG', 'actif' => true]);
        tenancy()->end();

        $this->connecter('c1', 'prof@c1.local');

        $this->putJson('http://c1.localhost/api/auth/profil-enseignant', [
            'numero_orange_money' => '70112233',
            'diplome_max' => 'Licence Mathématiques',
            'lieu_de_service' => 'Lycée Municipal',
            'domicile' => 'Nioko 2',
            'matieres' => [$matiere->id],
        ])->assertOk();

        $this->getJson('http://c1.localhost/api/auth/moi/profils')
            ->assertOk()
            ->assertJsonPath('data.role', 'enseignant')
            ->assertJsonPath('data.enseignant.diplome_max', 'Licence Mathématiques')
            ->assertJsonPath('data.enseignant.lieu_de_service', 'Lycée Municipal');
    }

    public function test_profil_enseignant_refuse_un_autre_role(): void
    {
        $this->creerCompte('c1', 'parentx@c1.local', 'parent');
        $this->connecter('c1', 'parentx@c1.local');

        $this->putJson('http://c1.localhost/api/auth/profil-enseignant', [
            'diplome_max' => 'Bidon',
        ])->assertStatus(403);
    }

    public function test_profil_parent_lisible_avec_enfants(): void
    {
        $this->creerCompte('c1', 'famille@c1.local', 'parent');
        $this->preparerCabinet('c1');

        $parent = User::where('email', 'famille@c1.local')->first();
        $classe = Classe::firstOrCreate(['nom' => '5e B'], ['sigle' => '5B']);
        Eleve::create([
            'parent_id' => $parent->id,
            'classe_id' => $classe->id,
            'ecole' => 'CPE Kossodo',
            'statut' => true,
        ]);
        tenancy()->end();

        $this->connecter('c1', 'famille@c1.local');

        $this->putJson('http://c1.localhost/api/auth/profil-parent', [
            'adresse_domicile' => 'Secteur 41',
            'profession' => 'Commerçant',
            'nombre_enfants' => 2,
        ])->assertOk();

        $this->getJson('http://c1.localhost/api/auth/moi/profils')
            ->assertOk()
            ->assertJsonPath('data.role', 'parent')
            ->assertJsonPath('data.parent.profession', 'Commerçant')
            ->assertJsonPath('data.parent.nombre_enfants', 2)
            ->assertJsonPath('data.enfants.0.ecole', 'CPE Kossodo');
    }

    /* ---------------------------------------------------------------
       Fiche élève — droits partagés
       --------------------------------------------------------------- */

    /** Crée un élève rattaché au parent donné (et optionnellement à un compte élève). */
    private function creerEleve(User $parent, ?User $compteEleve = null): Eleve
    {
        $classe = Classe::firstOrCreate(['nom' => '6e A'], ['sigle' => '6A']);

        return Eleve::create([
            'user_id' => $compteEleve?->id,
            'parent_id' => $parent->id,
            'classe_id' => $classe->id,
            'ecole' => 'École Témoin',
            'statut' => true,
        ]);
    }

    public function test_parent_peut_modifier_la_fiche_de_son_enfant(): void
    {
        $this->creerCompte('c1', 'papa@c1.local', 'parent');
        $this->preparerCabinet('c1');
        $parent = User::where('email', 'papa@c1.local')->first();
        $eleve = $this->creerEleve($parent);
        tenancy()->end();

        $this->connecter('c1', 'papa@c1.local');

        $this->putJson("http://c1.localhost/api/auth/eleves/{$eleve->id}", [
            'ecole' => 'Nouvelle École',
            'regime_etude' => 'externe',
            'boisson_preferee' => 'Bissap',
            'maladies_allergies' => 'Aucune',
        ])
            ->assertOk()
            ->assertJsonPath('eleve.ecole', 'Nouvelle École')
            ->assertJsonPath('eleve.regime_etude', 'externe')
            ->assertJsonPath('eleve.boisson_preferee', 'Bissap');
    }

    public function test_eleve_lui_meme_peut_modifier_sa_fiche(): void
    {
        $this->creerCompte('c1', 'paperc1@c1.local', 'parent');
        $this->creerCompte('c1', 'gamin@c1.local', 'eleve');
        $this->preparerCabinet('c1');
        $parent = User::where('email', 'paperc1@c1.local')->first();
        $compteEleve = User::where('email', 'gamin@c1.local')->first();
        $eleve = $this->creerEleve($parent, $compteEleve);
        tenancy()->end();

        $this->connecter('c1', 'gamin@c1.local');

        $this->putJson("http://c1.localhost/api/auth/eleves/{$eleve->id}", [
            'loisirs_sport' => 'Football',
        ])
            ->assertOk()
            ->assertJsonPath('eleve.loisirs_sport', 'Football');
    }

    public function test_enseignant_affecte_peut_modifier_la_fiche_eleve(): void
    {
        $this->creerCompte('c1', 'papa2@c1.local', 'parent');
        $this->creerCompte('c1', 'prof2@c1.local', 'enseignant');
        $this->preparerCabinet('c1');

        $parent = User::where('email', 'papa2@c1.local')->first();
        $enseignant = User::where('email', 'prof2@c1.local')->first();
        $eleve = $this->creerEleve($parent);

        $profil = EnseignantProfil::firstOrCreate(['user_id' => $enseignant->id]);
        $matiere = Matiere::firstOrCreate(['nom' => 'Français'], ['sigle' => 'FRA', 'actif' => true]);
        $type = TypeCours::firstOrCreate(['libelle' => 'Soutien'], ['code' => 'SOUT', 'actif' => true]);
        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $type->id,
            'date_debut' => now()->toDateString(),
            'statut' => 'actif',
        ]);
        AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $profil->id,
            'matiere_id' => $matiere->id,
            'date_affectation' => now()->toDateString(),
        ]);
        tenancy()->end();

        $this->connecter('c1', 'prof2@c1.local');

        $this->putJson("http://c1.localhost/api/auth/eleves/{$eleve->id}", [
            'profession_pere' => 'Ingénieur',
        ])
            ->assertOk()
            ->assertJsonPath('eleve.profession_pere', 'Ingénieur');
    }

    public function test_enseignant_non_affecte_est_refuse(): void
    {
        $this->creerCompte('c1', 'papa3@c1.local', 'parent');
        $this->creerCompte('c1', 'prof3@c1.local', 'enseignant');
        $this->preparerCabinet('c1');
        $parent = User::where('email', 'papa3@c1.local')->first();
        $eleve = $this->creerEleve($parent);
        tenancy()->end();

        $this->connecter('c1', 'prof3@c1.local');

        $this->putJson("http://c1.localhost/api/auth/eleves/{$eleve->id}", [
            'profession_pere' => 'Interdit',
        ])->assertStatus(403)
            ->assertJsonPath('code', 'ACCES_REFUSE');
    }

    public function test_parent_peut_consulter_la_fiche_complete_de_son_enfant(): void
    {
        $this->creerCompte('c1', 'lecteur@c1.local', 'parent');
        $this->preparerCabinet('c1');
        $parent = User::where('email', 'lecteur@c1.local')->first();
        $eleve = $this->creerEleve($parent);
        $eleve->update(['boisson_preferee' => 'Bissap', 'regime_etude' => 'externe']);
        tenancy()->end();

        $this->connecter('c1', 'lecteur@c1.local');

        $this->getJson("http://c1.localhost/api/auth/eleves/{$eleve->id}")
            ->assertOk()
            ->assertJsonPath('eleve.id', $eleve->id)
            ->assertJsonPath('eleve.boisson_preferee', 'Bissap')
            ->assertJsonPath('eleve.regime_etude', 'externe');
    }

    public function test_enseignant_non_affecte_ne_peut_pas_consulter_la_fiche(): void
    {
        $this->creerCompte('c1', 'curieux@c1.local', 'parent');
        $this->creerCompte('c1', 'profcurieux@c1.local', 'enseignant');
        $this->preparerCabinet('c1');
        $parent = User::where('email', 'curieux@c1.local')->first();
        $eleve = $this->creerEleve($parent);
        tenancy()->end();

        $this->connecter('c1', 'profcurieux@c1.local');

        $this->getJson("http://c1.localhost/api/auth/eleves/{$eleve->id}")
            ->assertStatus(403)
            ->assertJsonPath('code', 'ACCES_REFUSE');
    }

    public function test_validation_regime_etude_invalide(): void
    {
        $this->creerCompte('c1', 'papa4@c1.local', 'parent');
        $this->preparerCabinet('c1');
        $parent = User::where('email', 'papa4@c1.local')->first();
        $eleve = $this->creerEleve($parent);
        tenancy()->end();

        $this->connecter('c1', 'papa4@c1.local');

        $this->putJson("http://c1.localhost/api/auth/eleves/{$eleve->id}", [
            'regime_etude' => 'bidon',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'code', 'erreurs' => ['regime_etude']]);
    }

    public function test_visiteur_non_connecte_recoit_401(): void
    {
        $this->preparerCabinet('c1');

        $this->putJson('http://c1.localhost/api/auth/moi', [
            'nom' => 'X',
            'prenom' => 'Y',
        ])->assertStatus(401);
    }
}
