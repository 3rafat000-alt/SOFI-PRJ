<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Touch sentinel so InstallerGuard lets requests through
        $installed = storage_path('installed');
        if (!file_exists($installed)) {
            touch($installed);
        }
    }

    protected function tearDown(): void
    {
        $installed = storage_path('installed');
        if (file_exists($installed) && !str_contains(__FILE__, 'InstallTest')) {
            // Leave file alone — other tests may need it
        }
        parent::tearDown();
    }
}
