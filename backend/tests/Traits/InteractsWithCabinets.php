<?php

namespace Tests\Traits;

use App\Models\Cabinet;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;

trait InteractsWithCabinets
{
    /**
     * Cabinets créés par le test ; leur base physique (`keduc_test_<slug>`)
     * est supprimée en tearDown pour ne pas polluer l'environnement de test.
     */
    protected array $createdCabinets = [];

    /**
     * Crée un cabinet : enregistrement Landlord + domaine + base physique
     * (`keduc_test_<slug>`) + migrations tenant (pipeline synchrone).
     */
    protected function makeCabinet(string $slug, array $attributes = []): Cabinet
    {
        $cabinet = Cabinet::create(array_merge([
            'id' => $slug,
            'nom' => 'Cabinet ' . $slug,
        ], $attributes));

        $cabinet->domains()->create(['domain' => $slug . '.localhost']);

        $this->createdCabinets[] = $cabinet;

        return $cabinet;
    }

    protected function initCabinet(Cabinet $cabinet): void
    {
        tenancy()->initialize($cabinet);
    }

    protected function ensureCabinetsDropped(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $manager = new PostgreSQLDatabaseManager();
        $manager->setConnection('pgsql');

        foreach ($this->createdCabinets as $cabinet) {
            try {
                $manager->deleteDatabase($cabinet);
            } catch (\Throwable $e) {
                fwrite(STDERR, 'DROP ' . $cabinet->id . ' : ' . $e->getMessage() . PHP_EOL);
            }
        }
    }
}