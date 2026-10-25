<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\HistoriqueActivite;
use App\Models\Matiere;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API Référentiels — import CSV d'enseignants + photo de profil (M1, IAM).
 *
 * Routes `api/pedagogie/enseignants/*` murées par « auth:web » +
 * « role:admin_cabinet ». L'import crée `User` + `EnseignantProfil` + rôle,
 * rapporte ligne par ligne et journalise dans l'audit (M13).
 */
class EnseignantImportApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecteAdmin(string $slug): void
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);
        User::where('email', "admin@{$slug}.local")
            ->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => "admin@{$slug}.local",
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function urlImport(string $slug): string
    {
        return "http://{$slug}.localhost/api/pedagogie/enseignants/import";
    }

    private function csv(string $contenu): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('enseignants.csv', $contenu);
    }

    /**
     * Erreur de validation selon la convention T3.1 : `{message, code,
     * erreurs}` — pas l'enveloppe `errors` que `assertJsonValidationErrors()`
     * cherche (voir `bootstrap/app.php`).
     */
    private function assertErreur(\Illuminate\Testing\TestResponse $reponse, string $champ): void
    {
        $reponse->assertStatus(422);
        $this->assertSame('VALIDATION', $reponse->json('code'));
        $this->assertNotEmpty(
            $reponse->json("erreurs.{$champ}"),
            "Attendu une erreur de validation sur « {$champ} », reçu : "
                . json_encode($reponse->json(), JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_import_exige_connexion_et_role_admin_cabinet(): void
    {
        $this->makeCabinet('imp1');

        $this->post($this->urlImport('imp1'), [
            'fichier' => $this->csv("nom;prenom\nKoffi;Sam"),
            'password_defaut' => 'Secret1234',
        ], ['Accept' => 'application/json'])->assertStatus(401);

        tenancy()->initialize('imp1');
        $enseignant = User::create([
            'nom' => 'Enseignant',
            'prenom' => 'Paul',
            'email' => 'paul@imp1.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $enseignant->assignRole('enseignant');
        tenancy()->end();

        $this->postJson('http://imp1.localhost/api/auth/connexion', [
            'email' => 'paul@imp1.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->post($this->urlImport('imp1'), [
            'fichier' => $this->csv("nom;prenom\nKoffi;Sam"),
            'password_defaut' => 'Secret1234',
        ], ['Accept' => 'application/json'])->assertStatus(403);
    }

    public function test_import_valide_cree_comptes_profils_matieres_et_audit(): void
    {
        $this->connecteAdmin('imp2');

        tenancy()->initialize('imp2');
        Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);
        tenancy()->end();

        // BOM UTF-8 + point-virgule (Excel francophone), matières séparées
        // par « | » dont une introuvable → avertissement, ligne importée.
        $csv = "\xEF\xBB\xBF"
            . "Nom;Prénom;Email;Téléphone WhatsApp;Diplôme max;Frais annuel réglé;Matières\n"
            . "Koffi;Samuel;sam@exemple.test;0707070707;Licence;oui;Mathématiques|Philosophie\n"
            . "Diallo;Awa;;0606060606;Bac;non;Mathématiques\n";

        $this->post($this->urlImport('imp2'), [
            'fichier' => $this->csv($csv),
            'password_defaut' => 'MotDePasse1',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.importees', 2)
            ->assertJsonPath('data.ignorees', 0)
            ->assertJsonPath('data.erreurs', [])
            ->assertJsonCount(1, 'data.avertissements')
            ->assertJsonPath('data.avertissements.0.ligne', 2);

        tenancy()->initialize('imp2');
        $this->assertDatabaseHas('users', ['email' => 'sam@exemple.test', 'statut' => true]);
        $this->assertDatabaseHas('users', ['nom' => 'Diallo', 'prenom' => 'Awa', 'email' => null]);

        $sam = User::where('email', 'sam@exemple.test')->firstOrFail();
        $this->assertTrue($sam->hasRole('enseignant'));
        $this->assertNotNull($sam->enseignantProfil);
        $this->assertSame('Licence', $sam->enseignantProfil->diplome_max);
        $this->assertTrue($sam->enseignantProfil->frais_annuel_regle);
        // Le mot de passe par défaut a bien été haché (jamais en clair).
        $this->assertTrue(Hash::check('MotDePasse1', $sam->password));
        $this->assertContains(
            Matiere::where('nom', 'Mathématiques')->firstOrFail()->id,
            $sam->enseignantProfil->matieres->pluck('id')->all()
        );

        // Journal d'audit (M13).
        $audit = HistoriqueActivite::where('action', 'enseignant.importe')->first();
        $this->assertNotNull($audit);
        $this->assertSame(2, $audit->contexte['importees']);

        // Photo publique : encore absente → 404 documenté.
        tenancy()->end();
        $this->getJson("http://imp2.localhost/api/public/enseignants/{$sam->id}/photo")
            ->assertNotFound();
    }

    public function test_import_partiel_signale_les_lignes_en_erreur(): void
    {
        $this->connecteAdmin('imp3');

        $csv = "nom;prenom;email\n"
            . "Konan;Yves;yves@exemple.test\n"   // ligne 2 : valide
            . "Bad;Email;pas-un-email\n"          // ligne 3 : email invalide
            . ";SansNom;;\n"                      // ligne 4 : nom/prénom absents
            . "Doublon;Mail;yves@exemple.test\n"; // ligne 5 : doublon du fichier

        $this->post($this->urlImport('imp3'), [
            'fichier' => $this->csv($csv),
            'password_defaut' => 'Secret1234',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.importees', 1)
            ->assertJsonPath('data.ignorees', 3)
            ->assertJsonCount(3, 'data.erreurs')
            ->assertJsonPath('data.erreurs.0.ligne', 3)
            ->assertJsonPath('data.erreurs.1.ligne', 4)
            ->assertJsonPath('data.erreurs.2.ligne', 5);

        tenancy()->initialize('imp3');
        $this->assertSame(1, User::role('enseignant')->count());
        tenancy()->end();
    }

    public function test_import_refuse_en_tete_incomplet_et_password_defaut(): void
    {
        $this->connecteAdmin('imp4');

        // En-tête sans prénom → 422 explicite.
        $this->assertErreur(
            $this->post($this->urlImport('imp4'), [
                'fichier' => $this->csv("nom;email\nKoffi;k@exemple.test"),
                'password_defaut' => 'Secret1234',
            ], ['Accept' => 'application/json']),
            'fichier'
        );

        // password_defaut absent → 422.
        $this->assertErreur(
            $this->post($this->urlImport('imp4'), [
                'fichier' => $this->csv("nom;prenom\nKoffi;Sam"),
            ], ['Accept' => 'application/json']),
            'password_defaut'
        );

        // Fichier non CSV → 422.
        $this->assertErreur(
            $this->post($this->urlImport('imp4'), [
                'fichier' => UploadedFile::fake()->create('photo.png', 10, 'image/png'),
                'password_defaut' => 'Secret1234',
            ], ['Accept' => 'application/json']),
            'fichier'
        );

        tenancy()->initialize('imp4');
        $this->assertSame(0, User::role('enseignant')->count());
        tenancy()->end();
    }

    public function test_import_ignore_email_deja_presente_en_base(): void
    {
        $this->connecteAdmin('imp5');

        tenancy()->initialize('imp5');
        User::create([
            'nom' => 'Existant',
            'prenom' => 'Lucie',
            'email' => 'lucie@exemple.test',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        tenancy()->end();

        $this->post($this->urlImport('imp5'), [
            'fichier' => $this->csv(
                "nom;prenom;email\n"
                . "Neuf;Compte;neuf@exemple.test\n"
                . "Dujardin;Lucie;lucie@exemple.test\n"
            ),
            'password_defaut' => 'Secret1234',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.importees', 1)
            ->assertJsonPath('data.ignorees', 1)
            ->assertJsonPath('data.erreurs.0.ligne', 3)
            ->assertJsonPath('data.erreurs.0.message', "Un compte existe déjà avec l'adresse lucie@exemple.test.");
    }

    public function test_photo_admin_upload_affichage_et_suppression(): void
    {
        $this->connecteAdmin('imp6');

        tenancy()->initialize('imp6');
        $enseignant = app(\App\Modules\Users\Services\EnseignantService::class)->create([
            'nom' => 'Photo',
            'prenom' => 'Test',
            'email' => 'photo@imp6.local',
            'password' => 'Secret1234',
        ]);
        tenancy()->end();

        $base = "http://imp6.localhost/api/pedagogie/enseignants/{$enseignant->id}";

        // Aucune photo au départ.
        $this->getJson("{$base}/photo")->assertNotFound()
            ->assertJsonPath('code', 'PHOTO_ABSENTE');

        // Upload.
        $this->post("{$base}/photo", [
            'photo' => UploadedFile::fake()->image('profil.jpg', 200, 200),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'Photo mise à jour.');

        // Lecture admin (200 + type image) puis lecture publique (même media).
        $this->get("{$base}/photo")->assertOk()->assertHeader('content-type', 'image/jpeg');
        $this->getJson("http://imp6.localhost/api/public/enseignants/{$enseignant->id}/photo")
            ->assertOk();

        // Suppression.
        $this->deleteJson("{$base}/photo")->assertOk();
        $this->getJson("{$base}/photo")->assertNotFound();
    }

    public function test_photo_admin_refusee_sans_role_admin_cabinet(): void
    {
        $this->connecteAdmin('imp7');

        tenancy()->initialize('imp7');
        $enseignant = app(\App\Modules\Users\Services\EnseignantService::class)->create([
            'nom' => 'Cible',
            'prenom' => 'Eleve',
            'email' => 'cible@imp7.local',
            'password' => 'Secret1234',
        ]);
        $autre = User::create([
            'nom' => 'Collègue',
            'prenom' => 'Paul',
            'email' => 'paul2@imp7.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $autre->assignRole('enseignant');
        tenancy()->end();

        $this->postJson('http://imp7.localhost/api/auth/connexion', [
            'email' => 'paul2@imp7.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->postJson("http://imp7.localhost/api/pedagogie/enseignants/{$enseignant->id}/photo", [])
            ->assertStatus(403);
    }
}
