<?php

namespace Tests\Feature;

use App\Models\JournalPlateforme;
use App\Models\SuperAdmin;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Écran de consultation du journal de la plateforme (T2.7).
 */
class LandlordJournalTest extends TenantTestCase
{
    use InteractsWithCabinets;

    protected function connecte(): void
    {
        $this->actingAs(
            SuperAdmin::create([
                'nom' => 'Super Admin',
                'email' => 'admin@saascd.test',
                'password' => 'motdepasse',
            ]),
            'landlord'
        );
    }

    public function test_l_acc_liste_et_affiche_les_entrees(): void
    {
        $c1 = $this->makeCabinet('c1');

        JournalPlateforme::ecrire('test.unitaire', 'warning', $c1, ['cle' => 'valeur']);

        $this->connecte();
        $this->get('http://admin.localhost/admin/journal')
            ->assertOk()
            ->assertSee('test.unitaire')
            ->assertSee('badge text-bg-warning')
            ->assertSee($c1->nom);
    }

    public function test_filtre_par_action(): void
    {
        $c1 = $this->makeCabinet('c1');
        JournalPlateforme::ecrire('a.alpha', 'info', $c1);
        JournalPlateforme::ecrire('b.beta', 'info', $c1);

        $this->connecte();
        $reponse = $this->get('http://admin.localhost/admin/journal?action=a.')
            ->assertOk();

        $reponse->assertSee('a.alpha');
        $reponse->assertDontSee('b.beta');
    }

    public function test_filtre_par_niveau(): void
    {
        $c1 = $this->makeCabinet('c1');
        JournalPlateforme::ecrire('n.info', 'info', $c1);
        JournalPlateforme::ecrire('n.warning', 'warning', $c1);

        $this->connecte();
        $reponse = $this->get('http://admin.localhost/admin/journal?niveau=warning')
            ->assertOk();

        $reponse->assertSee('n.warning');
        $reponse->assertDontSee('n.info');
    }

    public function test_le_contexte_json_est_visible(): void
    {
        $c1 = $this->makeCabinet('c1');
        JournalPlateforme::ecrire('test.contexte', 'info', $c1, ['identifiants' => ['email' => 'a@b.test']]);

        $this->connecte();
        $this->get('http://admin.localhost/admin/journal')
            ->assertOk()
            ->assertSee('a@b.test');
    }

    public function test_acces_authentifie_requis(): void
    {
        $this->get('http://admin.localhost/admin/journal')->assertRedirect(route('landlord.login'));
    }
}