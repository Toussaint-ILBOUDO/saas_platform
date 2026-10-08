<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Identité du parent : `eleves.parent_id` référence **`users.id`**, pas
 * `parent_profils.id`.
 *
 * Les deux séquences sont indépendantes (`parent_profils` a sa propre clé
 * primaire), donc comparer `parent_id` à `parentProfil->id` n'est correct que
 * par un hasard d'alignement. Le scénario ci-dessous force volontairement
 * l'écart : l'identité du parent ne doit pas dépendre du nombre de parents déjà
 * créés dans le cabinet.
 */
class IdentiteParentTest extends TenantTestCase
{
    use InteractsWithCabinets;

    public function test_planning_du_parent_ignore_son_profil_et_suit_son_compte(): void
    {
        $s = $this->deuxFamilles('ip1');

        // Garde-fou du test lui-même : la collision doit exister, sinon le
        // test ne prouverait rien. `parent_profils.id` de la famille B tombe
        // pile sur `users.id` de la famille A — c'est la configuration réelle en
        // production, et c'est elle qui fait fuiter le planning de A vers B.
        $this->assertSame(
            $s['parentA']['users_id'],
            $s['parentB']['profil_id'],
            'Ce test perd son intérêt si les identifiants ne se recouvrent pas.'
        );

        // Parent B ouvre son planning : il ne doit voir que Beta.
        $this->connecte('ip1', $s['parentB']['email']);

        $reponse = $this->getJson('http://ip1.localhost/api/mes-planning')
            ->assertOk()
            ->assertJsonCount(1, 'eleves');

        // `array_column` ne gère pas la notation pointée : on parcourt.
        $vus = array_map(
            static fn (array $ligne): int => (int) $ligne['eleve']['id'],
            $reponse->json('eleves')
        );

        $this->assertSame(
            [$s['eleveB']],
            $vus,
            'Le parent B ne doit voir que son propre enfant.'
        );
    }

    public function test_cahier_de_texte_suit_le_compte_parent_et_non_son_profil(): void
    {
        $s = $this->deuxFamilles('ip2');

        $this->connecte('ip2', $s['enseignant_email']);
        $this->postJson('http://ip2.localhost/api/enseignant/cahiers-textes', [
            'affectation_enseignant_id' => $s['affectationA'],
            'date_seance' => now()->format('Y-m-d'),
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
            'contenu_cours' => 'Cours d\'alphabétisation.',
        ])->assertCreated();

        // Parent A voit l'historique de Alpha…
        $this->connecte('ip2', $s['parentA']['email']);
        $this->getJson("http://ip2.localhost/api/mes-enfants/{$s['eleveA']}/cahiers-textes")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // …et surtout pas celui de Beta : c'est le contre-test qui manquait.
        $this->getJson("http://ip2.localhost/api/mes-enfants/{$s['eleveB']}/cahiers-textes")
            ->assertForbidden();
    }

    /**
     * Le sélecteur d'enfant doit refléter la même identité que le reste : un
     * parent ne voit que ses enfants, l'élève se voit lui-même. C'est cet
     * endpoint qui fournit les `eleve.id` consommés par les URL d'historique.
     */
    public function test_selecteur_enfants_respecte_la_perimetre(): void
    {
        $s = $this->deuxFamilles('ip3');

        $this->connecte('ip3', $s['parentB']['email']);

        $enfantsB = $this->getJson('http://ip3.localhost/api/mes-enfants')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $enfantsB);
        $this->assertSame($s['eleveB'], $enfantsB[0]['id']);
        $this->assertSame('beta', $enfantsB[0]['nom']);

        // Un parent n'a pas de fiche élève : `/auth/moi` ne doit pas en inventer.
        $this->connecte('ip3', $s['parentB']['email']);
        $this->assertNull(
            $this->getJson('http://ip3.localhost/api/auth/moi')->json('user.eleve'),
            'Un compte parent n\'a pas de fiche élève.'
        );

        // L'élève se voit lui-même : le frontend n'a ainsi qu'une seule liste à
        // traiter, que le rôle soit parent ou élève.
        $this->connecte('ip3', $s['eleveBEmail']);

        $soi = $this->getJson('http://ip3.localhost/api/mes-enfants')
            ->assertOk()
            ->json('data');

        // Toujours une **liste**, comme pour le parent : sinon le frontend
        // devrait distinguer deux formes de réponse selon le rôle.
        $this->assertIsArray($soi);
        $this->assertCount(1, $soi);
        $this->assertSame($s['eleveB'], $soi[0]['id']);

        // Un enseignant n'a pas d'enfant : le sélecteur ne s'adresse pas à lui.
        $this->connecte('ip3', $s['enseignant_email']);
        $this->getJson('http://ip3.localhost/api/mes-enfants')->assertForbidden();
    }

    /**
     * Construit deux familles avec des liens parent/enfant **corrects**
     * (`eleves.parent_id = users.id`) et des `parent_profils` volontairement
     * décalés : c'est exactement la situation réelle en production.
     */
    private function deuxFamilles(string $slug): array
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);

        $admin = User::where('email', "admin@{$slug}.local")->first();
        $admin->assignRole('enseignant');

        \App\Models\PeriodeComptable::create([
            'label' => 'Année 2026',
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
            'type' => 'annuel',
        ]);

        $matiere = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);
        $prof = $admin->enseignantProfil()->firstOrCreate([], []);
        $prof->matieres()->sync([$matiere->id]);

        $typeCours = TypeCours::create(['code' => 'DOM', 'libelle' => 'Domicile', 'actif' => true]);
        $classe = \App\Models\Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);

        $famille = function (string $nom) use ($classe, $typeCours, $prof, $matiere) {
            $parentUser = User::create([
                'nom' => $nom,
                'prenom' => 'Parent',
                'email' => "parent-{$nom}@local.test",
                'password' => Hash::make('Secret1234'),
            ]);
            $parentUser->assignRole('parent');
            $profil = $parentUser->parentProfil()->firstOrCreate([], []);

            $eleveUser = User::create([
                'nom' => $nom,
                'prenom' => 'Enfant',
                'email' => "eleve-{$nom}@local.test",
                'password' => Hash::make('Secret1234'),
            ]);

            // Lien correct : l'identifiant du **compte** du parent.
            $eleve = Eleve::create([
                'user_id' => $eleveUser->id,
                'parent_id' => $parentUser->id,
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

            $affectation = AffectationEnseignant::create([
                'contrat_cours_id' => $contrat->id,
                'enseignant_id' => $prof->id,
                'matiere_id' => $matiere->id,
                'taux_horaire_enseignant' => 2500,
                'nombre_heures_prevues' => 4,
                'date_affectation' => '2026-01-01',
                'statut' => 'actif',
            ]);

            $eleveUser->assignRole('eleve');

            return [
                'email' => $parentUser->email,
                'eleveEmail' => $eleveUser->email,
                'users_id' => (int) $parentUser->id,
                'profil_id' => (int) $profil->id,
                'eleve_id' => (int) $eleve->id,
                'affectation_id' => (int) $affectation->id,
            ];
        };

        $a = $famille('alpha');
        $b = $famille('beta');

        $resultat = [
            'parentA' => $a,
            'parentB' => $b,
            'eleveA' => $a['eleve_id'],
            'eleveB' => $b['eleve_id'],
            'eleveBEmail' => $b['eleveEmail'],
            'affectationA' => $a['affectation_id'],
            'enseignant_email' => "admin@{$slug}.local",
        ];

        tenancy()->end();

        return $resultat;
    }

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
}