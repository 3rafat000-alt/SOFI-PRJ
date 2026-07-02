<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    /**
     * Typed read. Caches the RAW scalar/array value (never the Eloquent model —
     * caching models with the database/file driver yields __PHP_Incomplete_Class).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::rememberForever("setting:{$key}", function () use ($key) {
            $row = static::query()->where('key', $key)->first(['value', 'type']);

            return $row ? ['value' => $row->value, 'type' => $row->type] : null;
        });

        if ($cached === null) {
            return $default;
        }

        return static::castValue($cached['value'], $cached['type']);
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $stored = $type === 'json' ? json_encode($value) : (string) $value;

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type, 'group' => $group],
        );

        Cache::forget("setting:{$key}");
    }

    public static function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
