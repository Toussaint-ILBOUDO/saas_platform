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

    /**
     * Vide le schéma de la base centrale de test avant `migrate:fresh`.
     *
     * `migrate:fresh` s'appuie sur `db:wipe`, qui sur PostgreSQL ne supprime que
     * les **tables** (`dropAllTables`). Une exécution interrompue peut laisser
     * une séquence orpheline — typiquement `migrations_id_seq` : la table a été
     * droppée, sa séquence pas. Le `migrate:fresh` suivant échoue alors sur
     *
     *     pg_class_relname_nsp_index : (relname, relnamespace)=(migrations_id_seq)
     *
     * et plus aucun test ne démarre : la base de test reste bloquée jusqu'à
     * intervention manuelle. Ce nettoyage rend la suite auto-réparatrice.
     *
     * Limité à l'environnement `testing` : la base de développement ne doit
     * jamais passer par ici.
     */
    protected function purgeSchemaCentralTest(): void
    {
        if (app()->environment('testing') !== true) {
            return;
        }

        $schema = DB::getDatabaseName();

        // Vues d'abord : elles peuvent dépendre des tables.
        foreach (DB::select('
            SELECT viewname FROM pg_views WHERE schemaname = current_schema()
        ') as $vue) {
            DB::unprepared('DROP VIEW IF EXISTS "' . $schema . '"."' . $vue->viewname . '" CASCADE');
        }

        // Types énumérés : `db:wipe` ne les droppe que sur option.
        foreach (DB::select("
            SELECT t.typname
            FROM pg_type t
            JOIN pg_namespace n ON n.oid = t.typnamespace
            WHERE n.nspname = current_schema()
              AND t.typtype = 'e'
        ") as $type) {
            DB::unprepared('DROP TYPE IF EXISTS "' . $schema . '"."' . $type->typname . '" CASCADE');
        }

        // Tables, puis séquences : l'ordre compte, une séquence `serial` peut
        // être rattachée à une colonne encore existante.
        foreach (DB::select('
            SELECT tablename FROM pg_tables WHERE schemaname = current_schema()
        ') as $table) {
            DB::unprepared('DROP TABLE IF EXISTS "' . $schema . '"."' . $table->tablename . '" CASCADE');
        }

        foreach (DB::select('
            SELECT c.relname
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE c.relkind = \'S\' AND n.nspname = current_schema()
        ') as $sequence) {
            DB::unprepared('DROP SEQUENCE IF EXISTS "' . $schema . '"."' . $sequence->relname . '" CASCADE');
        }
    }
}