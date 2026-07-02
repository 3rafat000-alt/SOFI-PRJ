<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationTemplate extends Model
{
    protected $fillable = [
        'integration_id', 'name', 'name_ar', 'type', 'event',
        'subject', 'subject_ar', 'body', 'body_ar',
        'variables', 'is_active', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'variables' => 'array',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    public function render(array $data = [], string $locale = 'ar'): array
    {
        $subject = $locale === 'ar' ? $this->subject_ar : $this->subject;
        $body = $locale === 'ar' ? $this->body_ar : $this->body;

        foreach ($data as $key => $value) {
            $subject = str_replace("{{{$key}}}", $value, $subject);
            $body = str_replace("{{{$key}}}", $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
