<?php

namespace Tests\Feature;

use App\Models\JournalPlateforme;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Commande cabinet:migrer-tous (T2.8) : enveloppe de tenants:migrate,
 * sauvegarde préalable facultative, rapport et journal par cabinet.
 */
class CommanMigrationTousTest extends TenantTestCase
{
    use InteractsWithCabinets;

    public function test_migre_tous_les_cabinets(): void
    {
        $c1 = $this->makeCabinet('c1');

        $this->artisan('cabinet:migrer-tous', ['--force-nosauvegarde' => true])
            ->expectsOutputToContain('Migre « Cabinet c1 »')
            ->expectsOutputToContain('OK')
            ->assertExitCode(0);

        $this->assertDatabaseHas('journal_plateforme', ['action' => 'cabinet.migre', 'cabinet_id' => 'c1']);
    }

    public function test_option_tenant_filtre_sur_un_cabinet(): void
    {
        $this->makeCabinet('c1');
        $this->makeCabinet('c2');

        $this->artisan('cabinet:migrer-tous', [
            '--tenant' => 'c1',
            '--force-nosauvegarde' => true,
        ])
            ->expectsOutputToContain('Migre « Cabinet c1 »')
            ->assertExitCode(0);

        $this->assertDatabaseHas('journal_plateforme', ['action' => 'cabinet.migre', 'cabinet_id' => 'c1']);
        $this->assertDatabaseMissing('journal_plateforme', ['action' => 'cabinet.migre', 'cabinet_id' => 'c2']);
    }

    public function test_erreur_de_migration_journalisee(): void
    {
        $c1 = $this->makeCabinet('c1');

        // Simule une base manquante/n’migrée nulle part
        $manager = new PostgreSQLDatabaseManager();
        $manager->setConnection('pgsql');
        $this->termineSessionsEt('keduc_test_c1', fn () => $manager->deleteDatabase($c1));

        $this->artisan('cabinet:migrer-tous', [
            '--tenant' => 'c1',
            '--force-nosauvegarde' => true,
        ])
            ->expectsOutputToContain('ERREUR')
            ->assertExitCode(0);

        $this->assertDatabaseHas('journal_plateforme', ['action' => 'cabinet.migre.erreur', 'cabinet_id' => 'c1'], 'pgsql');
        tenancy()->end();
    }

    public function test_aucun_cabinet(): void
    {
        $this->artisan('cabinet:migrer-tous')
            ->expectsOutputToContain('Aucun cabinet à migrer.')
            ->assertExitCode(0);
    }
}