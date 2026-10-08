<?php

namespace Tests\Feature\Api\Pedagogie;

use App\Models\RapportElement;
use App\Models\RapportSection;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API modèle de rapport mensuel — configuration admin (sections + éléments).
 *
 * Le canevas que l'enseignant remplit au dépôt est administré ici. Les
 * informations générales et le bilan des activités ne s'y trouvent pas : ils
 * sont automatiques (contrat, période, cahier de texte).
 */
class RapportSectionApiTest extends TenantTestCase
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

    private function sectionsApi(string $slug): string
    {
        return "http://{$slug}.localhost/api/admin/pedagogie/rapport-sections";
    }

    private function modele(string $slug): string
    {
        return "http://{$slug}.localhost/api/pedagogie/rapports-mensuels/modele";
    }

    private function creeEnseignant(string $slug): User
    {
        tenancy()->initialize($slug);
        $user = User::create([
            'nom' => 'Kaboré',
            'prenom' => 'Aïcha',
            'email' => "prof-rapmod@{$slug}.local",
            'password' => Hash::make('Secret1234'),
        ]);
        $user->assignRole('enseignant');
        tenancy()->end();

        return $user;
    }

    public function test_admin_cree_une_section(): void
    {
        $this->makeCabinet('sec1');
        $this->connecte('sec1', 'admin@sec1.local');

        $this->postJson($this->sectionsApi('sec1'), [
            'libelle' => 'Soutien périscolaire',
            'description' => 'Ce qui se passe hors des cours.',
        ])->assertStatus(201)
            ->assertJsonPath('data.libelle', 'Soutien périscolaire');

        tenancy()->initialize('sec1');
        $this->assertSame(6, RapportSection::count());
        tenancy()->end();
    }

    public function test_admin_ajoute_un_element_obligatoire(): void
    {
        $this->makeCabinet('sec2');
        $this->connecte('sec2', 'admin@sec2.local');

        $section = $this->getJson($this->sectionsApi('sec2'))
            ->assertOk()
            ->json('data.0');

        $this->postJson($this->sectionsApi('sec2') . '/elements', [
            'section_id' => $section['id'],
            'libelle' => 'Projet d\'accompagnement',
            'type' => 'textarea',
            'obligatoire' => true,
            'aide' => 'Décrivez brièvement.',
        ])->assertStatus(201)
            ->assertJsonPath('data.0.elements.2.libelle', 'Projet d\'accompagnement')
            ->assertJsonPath('data.0.elements.2.obligatoire', true);

        tenancy()->initialize('sec2');
        $this->assertTrue(RapportElement::where('libelle', 'Projet d\'accompagnement')->first()->obligatoire);
        tenancy()->end();
    }

    public function test_admin_desactive_une_section_invisible_pour_l_enseignant(): void
    {
        $this->makeCabinet('sec3');
        $this->connecte('sec3', 'admin@sec3.local');

        $sections = $this->getJson($this->sectionsApi('sec3'))
            ->assertOk()
            ->json('data');

        // On coupe « Observations » (dernière section par défaut).
        $derniere = $sections[count($sections) - 1];
        $this->assertSame('Observations', $derniere['libelle']);

        $this->putJson($this->sectionsApi('sec3') . '/' . $derniere['id'], [
            'actif' => false,
        ])->assertOk();

        // L'enseignant ne voit plus la section inactive dans son formulaire.
        $this->creeEnseignant('sec3');
        $this->connecte('sec3', 'prof-rapmod@sec3.local');
        $this->getJson($this->modele('sec3'))
            ->assertOk()
            // La section « Observations » (désactivée) a disparu de l'arbre.
            ->assertJsonMissing(['libelle' => 'Observations'])
            // L'ordre reste celui du modèle (ordonné, toutes actives sauf coupées).
            ->assertJsonPath('data.0.libelle', 'Évaluation pédagogique');
    }

    public function test_admin_reordonne_les_sections(): void
    {
        $this->makeCabinet('sec4');
        $this->connecte('sec4', 'admin@sec4.local');

        $sections = $this->getJson($this->sectionsApi('sec4'))
            ->assertOk()
            ->json('data');

        $idsDansDesordre = array_column($sections, 'id');

        // Premier = dernier : l'ordre est strictement inversé.
        [$premier, $dernier] = [$idsDansDesordre[0], $idsDansDesordre[count($idsDansDesordre) - 1]];
        $idsDansDesordre[count($idsDansDesordre) - 1] = $premier;
        $idsDansDesordre[0] = $dernier;
        $idsDansDesordre = array_values($idsDansDesordre);

        $this->postJson($this->sectionsApi('sec4') . '/reordonner', [
            'ids' => $idsDansDesordre,
        ])->assertOk()
            ->assertJsonPath('data.0.id', $dernier);

        tenancy()->initialize('sec4');
        $this->assertSame(
            array_map('intval', $idsDansDesordre),
            RapportSection::query()->orderBy('ordre')->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        tenancy()->end();
    }

    public function test_reordonner_refuse_un_id_etranger(): void
    {
        $this->makeCabinet('sec5');
        $this->connecte('sec5', 'admin@sec5.local');

        $ids = $this->getJson($this->sectionsApi('sec5'))->json('data');
        $ids = array_column($ids, 'id');
        $ids[] = 999999;

        $this->postJson($this->sectionsApi('sec5') . '/reordonner', ['ids' => $ids])
            ->assertStatus(422);
    }

    public function test_seul_l_admin_configure_le_modele(): void
    {
        $this->makeCabinet('sec6');
        $this->creeEnseignant('sec6');
        $this->connecte('sec6', 'prof-rapmod@sec6.local');

        $this->postJson($this->sectionsApi('sec6'), [
            'libelle' => 'Intrus',
        ])->assertStatus(403);
    }
}