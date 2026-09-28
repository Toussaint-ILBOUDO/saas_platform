<?php

namespace Tests\Feature;

use App\Jobs\CreerAdminCabinetEtNotifier;
use App\Mail\CabinetIdentifiants;
use App\Models\Cabinet;
use App\Models\JournalPlateforme;
use App\Models\ParametresCabinet;
use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDOException;
use RuntimeException;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Pipeline de création d'un cabinet (T2.4) : admin + identifiants + journal,
 * rollback total en cas d'échec.
 */
class LandlordPipelineTest extends TenantTestCase
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

    public function test_la_creation_cree_l_admin_notifie_et_journalise(): void
    {
        Mail::fake();
        $this->connecte();

        $this->post('http://admin.localhost/admin/cabinets', [
            'id' => 'cabinet-pipe',
            'nom' => 'Cabinet Pipeline',
            'sous_domaine' => 'pipe',
            'email' => 'directeur@pipe.example',
            'telephone' => '',
        ])->assertRedirect(route('landlord.cabinets.show', 'cabinet-pipe'));

        $cabinet = Cabinet::findOrFail('cabinet-pipe');
        $this->createdCabinets[] = $cabinet;

        // Admin créé dans la base tenant avec le rôle admin_cabinet
        $config = array_merge(config('database.connections.pgsql'), ['database' => 'keduc_test_cabinet-pipe']);
        config(['database.connections.tenant_admin_check' => $config]);
        $user = DB::connection('tenant_admin_check')
            ->table('users')
            ->where('email', 'directeur@pipe.example')
            ->first();
        $this->assertNotNull($user, 'L\'admin du cabinet doit exister dans la base tenant.');

        $role = DB::connection('tenant_admin_check')
            ->table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->value('roles.name');
        $this->assertSame('admin_cabinet', $role);
        DB::purge('tenant_admin_check');

        // Email des identifiants
        Mail::assertSent(CabinetIdentifiants::class, function (CabinetIdentifiants $mail) {
            return $mail->hasTo('directeur@pipe.example');
        });

        // Journal
        $this->assertDatabaseHas('journal_plateforme', [
            'action' => 'admin_cabinet.cree',
            'level' => 'info',
            'cabinet_id' => 'cabinet-pipe',
        ]);
    }

    public function test_creation_injecte_la_fiche_cabinet_dans_le_site(): void
    {
        Mail::fake();
        $this->connecte();

        $this->post('http://admin.localhost/admin/cabinets', [
            'id' => 'cabinet-magis',
            'nom' => 'Magis Plus Center',
            'sous_domaine' => 'magis-plus-center',
            'email' => 'contact@magis.example',
            'telephone' => '+22670000001',
            'slogan' => 'L\'excellence pour tous',
            'directeur' => 'M. K. Yao',
            'telephone_2' => '+22670000002',
            'orange_money' => '55000001',
            'wave' => '55000002',
            'cash' => '1',
            'pays' => 'Burkina Faso',
            'devise' => 'FCFA',
            'localites' => "Ouagadougou\nBobo-Dioulasso",
            'horaires' => 'Lun-Ven 8h-18h',
        ])->assertRedirect(route('landlord.cabinets.show', 'cabinet-magis'));

        $cabinet = Cabinet::findOrFail('cabinet-magis');
        $this->createdCabinets[] = $cabinet;

        // Fiche stockée sur le tenant (virtual column → tenants.data.fiche).
        $this->assertSame('L\'excellence pour tous', $cabinet->fiche['identite']['slogan']);

        // Fiche injectée dans parametres_publics de la base tenant (D-044).
        $config = array_merge(config('database.connections.pgsql'), ['database' => 'keduc_test_cabinet-magis']);
        config(['database.connections.tenant_fiche_check' => $config]);
        $donnees = DB::connection('tenant_fiche_check')->table('parametres_publics')->value('data');
        $fiche = json_decode((string) $donnees, true)['fiche'];
        DB::purge('tenant_fiche_check');

        $this->assertSame('L\'excellence pour tous', $fiche['identite']['slogan']);
        $this->assertSame('M. K. Yao', $fiche['identite']['directeur']);
        $this->assertSame('+22670000001', $fiche['contact']['telephone']);
        $this->assertSame('Lun-Ven 8h-18h', $fiche['contact']['horaires']);
        $this->assertSame('55000001', $fiche['paiements']['orange_money']);
        $this->assertSame('55000002', $fiche['paiements']['wave']);
        $this->assertTrue($fiche['paiements']['cash']);
        $this->assertSame('FCFA', $fiche['zones']['devise']);
        $this->assertSame(['Ouagadougou', 'Bobo-Dioulasso'], $fiche['zones']['localites']);
    }

    public function test_un_echec_du_pipeline_rollback_tout(): void
    {
        putenv('TENANCY_SIMULATE_ECHEC=1');
        $this->connecte();

        try {
            $this->post('http://admin.localhost/admin/cabinets', [
                'id' => 'cabinet-fragile',
                'nom' => 'Cabinet Fragile',
                'sous_domaine' => 'fragile',
                'email' => 'chef@fragile.example',
            ]);

            $this->fail('Le pipeline devait lever une exception simulée.');
        } catch (RuntimeException) {
            // attendu : le job rolleback puis relance
        } finally {
            putenv('TENANCY_SIMULATE_ECHEC');
        }

        // Aucune trace centrale
        $this->assertDatabaseMissing('tenants', ['id' => 'cabinet-fragile']);
        $this->assertDatabaseMissing('domains', ['tenant_id' => 'cabinet-fragile']);
        $this->assertFalse(ParametresCabinet::where('cabinet_id', 'cabinet-fragile')->exists());

        // Base physique supprimée
        $dropped = false;
        try {
            $config = array_merge(config('database.connections.pgsql'), ['database' => 'keduc_test_cabinet-fragile']);
            config(['database.connections.tenant_gone_check' => $config]);
            DB::connection('tenant_gone_check')->getPdo();
        } catch (PDOException) {
            $dropped = true;
        } finally {
            DB::purge('tenant_gone_check');
        }
        $this->assertTrue($dropped, 'La base du cabinet doit avoir été supprimée par le rollback.');

        // L'échec est journalisé
        $this->assertDatabaseHas('journal_plateforme', [
            'action' => 'cabinet.echec',
            'level' => 'error',
            'cabinet_id' => 'cabinet-fragile',
        ]);
    }
}