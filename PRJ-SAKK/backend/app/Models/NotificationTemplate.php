<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reusable notification message template with dynamic %variables%.
 */
class NotificationTemplate extends Model
{
    protected $fillable = [
        'code', 'event_key', 'recipient', 'name', 'channel',
        'subject', 'subject_ar', 'body', 'body_ar', 'variables', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Render the body, replacing %placeholders% with values.
     * Unknown placeholders are left intact so issues are visible.
     */
    public function render(array $data, bool $arabic = true): array
    {
        $subject = $arabic ? ($this->subject_ar ?: $this->subject) : $this->subject;
        $body = $arabic ? ($this->body_ar ?: $this->body) : $this->body;

        foreach ($data as $key => $value) {
            $token = '%' . trim($key, '%') . '%';
            $subject = str_replace($token, (string) $value, (string) $subject);
            $body = str_replace($token, (string) $value, (string) $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }
}
