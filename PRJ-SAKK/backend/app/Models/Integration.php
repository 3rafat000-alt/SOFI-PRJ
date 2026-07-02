<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'key', 'name', 'name_ar', 'description', 'description_ar',
        'icon', 'category', 'is_active', 'is_visible',
        'config', 'credentials', 'settings',
        'webhook_url', 'environment',
        'last_synced_at', 'last_error_at', 'last_error_message',
        'error_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'config' => 'encrypted:array',
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'last_synced_at' => 'datetime',
            'last_error_at' => 'datetime',
        ];
    }

    public function docs(): HasMany
    {
        return $this->hasMany(IntegrationDoc::class)->orderBy('order')->orderBy('id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(IntegrationTemplate::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class)->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->config ?? [], $key, $default);
    }

    public function setConfigValue(string $key, $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
    }

    public function getCredential(string $key, $default = null)
    {
        return data_get($this->credentials ?? [], $key, $default);
    }

    public function log(string $level, string $action, ?string $message = null, ?array $payload = null, ?array $response = null, ?string $statusCode = null): IntegrationLog
    {
        $log = $this->logs()->create([
            'level' => $level,
            'action' => $action,
            'message' => $message,
            'payload' => $payload,
            'response' => $response,
            'status_code' => $statusCode,
            'ip_address' => request()->ip(),
            'user_id' => auth()->id(),
        ]);

        if ($level === 'error') {
            $this->increment('error_count');
            $this->update(['last_error_at' => now(), 'last_error_message' => $message]);
        }

        return $log;
    }
}
