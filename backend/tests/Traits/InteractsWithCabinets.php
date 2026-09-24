<?php

namespace Tests\Traits;

use App\Models\Cabinet;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;

trait InteractsWithCabinets
{
    /**
     * Cabinets créés par le test ; leur base physique (`keduc_test_<slug>`)
     * est supprimée en tearDown pour ne pas polluer l'environnement de test.
     */
    protected array $createdCabinets = [];

    /**
     * Termine les sessions actives sur la base donnée (DROP DATABASE sinon
     * interdit tant qu'une connexion reste ouverte) puis exécute le callback.
     */
    protected function termineSessionsEt(string $base, callable $callback): void
    {
        DB::unprepared(
            "SELECT pg_terminate_backend(pid) FROM pg_stat_activity "
            . "WHERE datname = '" . $base . "' AND pid <> pg_backend_pid();"
        );
        $callback();
    }

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
            $base = config('tenancy.database.prefix') . $cabinet->id;

            try {
                $this->termineSessionsEt($base, fn () => $manager->deleteDatabase($cabinet));
            } catch (\Throwable $e) {
                fwrite(STDERR, 'DROP ' . $cabinet->id . ' : ' . $e->getMessage() . PHP_EOL);
            }
        }
    }

    /**
     * Nettoyage de l'environnement de test : supprime toute base `keduc_test_%`
     * laissée par un run précédent interrompu, avant migrate:fresh.
     */
    protected function purgeBasesTenantResiduelles(): void
    {
        $prefix = config('tenancy.database.prefix');

        $bases = DB::select("SELECT datname FROM pg_database WHERE datname LIKE '" . $prefix . "%'");

        foreach ($bases as $ligne) {
            $base = $ligne->datname;

            try {
                $this->termineSessionsEt($base, fn () => DB::statement('DROP DATABASE IF EXISTS "' . $base . '"'));
            } catch (\Throwable $e) {
                fwrite(STDERR, 'PURGE résiduelle échouée pour ' . $base . ' : ' . $e->getMessage() . PHP_EOL);
            }
        }
    }
}