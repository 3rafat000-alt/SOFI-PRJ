<?php

namespace App\Providers;

use App\Services\AuditLogService;
use Illuminate\Support\ServiceProvider;

class AuditLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogService::class, function ($app) {
            return new AuditLogService($app->make('request'));
        });
    }

    public function boot(): void
    {
        // Service booted
    }
}
