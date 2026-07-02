<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * SAFETY RAIL (2026-07-02 incident): refuse to run the suite against anything
     * but an in-memory SQLite DB. On 2026-07-02 a cached config
     * (bootstrap/cache/config.php) overrode phpunit.xml's DB_DATABASE=:memory:,
     * so RefreshDatabase's migrate:fresh wiped the REAL database/database.sqlite.
     * This guard makes that impossible: if the resolved DB is not :memory:, abort
     * before any migration can touch a real file.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Check the ACTUAL live connection, not config() (config can report the
        // real file path while phpunit's <env> still binds the connection to
        // :memory:). getDatabaseName() reflects what PDO is really attached to.
        $database = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();

        if ($database !== ':memory:') {
            $this->fail(
                "REFUSING TO RUN TESTS against a non-:memory: database (database={$database}). "
                . "Cached config is likely overriding phpunit.xml — run `php artisan config:clear` first. "
                . "See the 2026-07-02 DB-wipe incident."
            );
        }
    }
}
