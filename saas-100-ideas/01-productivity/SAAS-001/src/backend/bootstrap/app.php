<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\RedirectIfInstalled;
use App\Http\Middleware\WorkspaceMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Custom middleware aliases used by routes/api.php + routes/web.php
        $middleware->alias([
            'ratelimit' => RateLimitMiddleware::class,
            'workspace' => WorkspaceMiddleware::class,
            'installed' => EnsureInstalled::class,
            'not_installed' => RedirectIfInstalled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
