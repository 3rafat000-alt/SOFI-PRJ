<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
    ];

    protected $guarded = [];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Cache only scalar row data — never the Eloquent model itself.
        // Serializing a model into a non-class-aware store (database/file) yields
        // an __PHP_Incomplete_Class on read and throws when its props are accessed.
        $setting = Cache::remember("setting:{$key}", 3600, function () use ($key) {
            $row = static::where('key', $key)->first();

            return $row ? ['value' => $row->value, 'type' => $row->type] : null;
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting['value'], $setting['type']);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value, ?string $type = null): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        
        if ($type) {
            $setting->type = $type;
        }
        
        $setting->value = is_array($value) ? json_encode($value) : (string) $value;
        $setting->save();

        Cache::forget("setting:{$key}");
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->key => self::castValue($s->value, $s->type)])
            ->toArray();
    }

    /**
     * Cast value to the correct type
     */
    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
