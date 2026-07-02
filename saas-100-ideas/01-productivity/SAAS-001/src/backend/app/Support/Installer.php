<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Tiny helper around the install lock file. The presence of the file marks the
 * application as installed; its JSON content records when/what installed it.
 */
class Installer
{
    public static function lockPath(): string
    {
        return storage_path('app/installed.lock');
    }

    public static function isInstalled(): bool
    {
        return file_exists(self::lockPath());
    }

    public static function markInstalled(array $meta = []): void
    {
        file_put_contents(
            self::lockPath(),
            json_encode(array_merge([
                'app' => 'TaskSync Pro (SAAS-001)',
                'installed_at' => now()->toIso8601String(),
            ], $meta), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }
}
