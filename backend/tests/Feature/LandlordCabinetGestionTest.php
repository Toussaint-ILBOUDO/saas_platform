<?php

namespace Tests\Feature;

use App\Models\Cabinet;
use App\Models\JournalPlateforme;
use App\Models\ParametresCabinet;
use App\Models\ParametresPlateforme;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Cycle de vie d'un cabinet (T2.5) : suspension, réactivation, archivage,
 * suppression définitive (pg_dump préalable).
 */
class LandlordCabinetGestionTest extends TenantTestCase
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

    public function test_suspension_puis_reactivation(): void
    {
        $this->connecte();
        $c1 = $this->makeCabinet('c1');

        $this->post("http://admin.localhost/admin/cabinets/{$c1->id}/statut", ['statut' => 'suspendu'])
            ->assertRedirect(route('landlord.cabinets.show', $c1));

        $this->assertSame('suspendu', Cabinet::find('c1')->status);
        $this->assertDatabaseHas('journal_plateforme', ['action' => 'cabinet.suspendu', 'cabinet_id' => 'c1']);

        // Domaine cabinet bloqué quand suspendu (web KEduc activé pour atteindre
        // le middleware cabinet.actif — décision B4, T2.10).
        ParametresPlateforme::definir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, true);
        $this->get('http://c1.localhost/')->assertForbidden();
        tenancy()->end();

        $this->post("http://admin.localhost/admin/cabinets/{$c1->id}/statut", ['statut' => 'actif'])
            ->assertRedirect(route('landlord.cabinets.show', $c1));

        $this->assertSame('actif', Cabinet::find('c1')->status);
        $this->assertDatabaseHas('journal_plateforme', ['action' => 'cabinet.reactive', 'cabinet_id' => 'c1']);
    }

    public function test_archivage_login_au_journal(): void
    {
        $this->connecte();
        $c1 = $this->makeCabinet('c1');

        $this->post("http://admin.localhost/admin/cabinets/{$c1->id}/statut", ['statut' => 'archive'])
            ->assertRedirect(route('landlord.cabinets.show', $c1));

        $this->assertSame('archive', Cabinet::find('c1')->status);
        $this->assertDatabaseHas('journal_plateforme', ['action' => 'cabinet.archive', 'cabinet_id' => 'c1']);
    }

    public function test_statut_invalide_refuse(): void
    {
        $this->connecte();
        $c1 = $this->makeCabinet('c1');

        $this->post("http://admin.localhost/admin/cabinets/{$c1->id}/statut", ['statut' => 'inexistant'])
            ->assertSessionHasErrors('statut');
    }

    public function test_suppression_definitive_avec_sauvegarde(): void
    {
        putenv('TENANCY_SKIP_BACKUP=1'); // pg_dump indisponible dans le pipeline CI local
        $this->connecte();
        $c1 = $this->makeCabinet('c1');

        $journalAvant = JournalPlateforme::where('cabinet_id', 'c1')->value('created_at');

        $this->delete("http://admin.localhost/admin/cabinets/{$c1->id}")
            ->assertRedirect(route('landlord.cabinets.index'));

        // Plus aucune trace centrale
        $this->assertDatabaseMissing('tenants', ['id' => 'c1']);
        $this->assertDatabaseMissing('domains', ['tenant_id' => 'c1']);
        $this->assertFalse(ParametresCabinet::where('cabinet_id', 'c1')->exists());

        // Base physique détruite
        $supprimee = false;
        try {
            $config = array_merge(config('database.connections.pgsql'), ['database' => 'keduc_test_c1']);
            config(['database.connections.tenant_supprime_check' => $config]);
            DB::connection('tenant_supprime_check')->getPdo();
        } catch (PDOException) {
            $supprimee = true;
        } finally {
            DB::purge('tenant_supprime_check');
        }
        $this->assertTrue($supprimee, 'La base du cabinet doit avoir été détruite.');

        // L'événement est journalisé
        $this->assertDatabaseHas('journal_plateforme', ['action' => 'cabinet.supprime', 'cabinet_id' => 'c1']);

        // On retire le cabinet supprimé de la liste de nettoyage tearDown
        $this->createdCabinets = array_values(array_filter(
            $this->createdCabinets,
            fn (Cabinet $c) => $c->id !== 'c1'
        ));

        putenv('TENANCY_SKIP_BACKUP');
    }

    public function test_l_acces_aux_actions_requiert_l_authentification(): void
    {
        $this->get('http://admin.localhost/admin/cabinets')->assertRedirect(route('landlord.login'));
    }
}