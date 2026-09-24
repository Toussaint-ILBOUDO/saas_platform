<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

/**
 * Base des Feature Tests multi-tenant (D-003/D-023) :
 * PostgreSQL interdit CREATE DATABASE dans un bloc de transaction → la base
 * centrale de test est migrée (migrate:fresh) mais non transactée ; les bases
 * tenant (`keduc_test_<slug>`) sont supprimées en tearDown via
 * InteractsWithCabinets::ensureCabinetsDropped().
 */
abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected function connectionsToTransact(): array
    {
        return [];
    }

    protected function refreshTestDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
        $this->artisan('migrate:fresh');
        $this->beginDatabaseTransaction();
    }

    protected function tearDown(): void
    {
        if (method_exists($this, 'ensureCabinetsDropped')) {
            $this->ensureCabinetsDropped();
        }

        parent::tearDown();
    }
}