<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Unified, encrypted store for 3rd-party & security service configs:
 * SMS gateway, Mail, Firebase OTP, reCAPTCHA.
 */
class ServiceConfig extends Model
{
    protected $fillable = [
        'key', 'name', 'name_ar', 'group', 'icon', 'driver',
        'credentials', 'settings', 'is_active', 'last_tested_at', 'last_test_ok',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array', // secrets never stored in plaintext
            'settings' => 'array',
            'is_active' => 'boolean',
            'last_test_ok' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function getCredential(string $key, $default = null)
    {
        return data_get($this->credentials ?? [], $key, $default);
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    /** Cached lookup by key (configs are read often, written rarely). */
    public static function forKey(string $key): ?self
    {
        // Cache raw attributes, never the model — a serialized model in a
        // class-unaware store (database/file) deserializes to __PHP_Incomplete_Class.
        // Raw attrs keep encrypted/array casts intact (applied on access after rehydrate).
        $attrs = Cache::remember("service_config:{$key}", 600, fn () => static::where('key', $key)->first()?->getAttributes());

        return $attrs ? (new static)->setRawAttributes($attrs, true) : null;
    }

    protected static function booted(): void
    {
        static::saved(fn (self $c) => Cache::forget("service_config:{$c->key}"));
        static::deleted(fn (self $c) => Cache::forget("service_config:{$c->key}"));
    }
}
