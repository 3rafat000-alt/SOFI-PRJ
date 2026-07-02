<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-controlled matrix: for a given (event, recipient) which channels fire.
 */
class NotificationChannel extends Model
{
    protected $fillable = [
        'event_key', 'event_label', 'event_label_ar', 'recipient',
        'via_email', 'via_sms', 'via_push', 'via_in_app', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'via_email' => 'boolean',
            'via_sms' => 'boolean',
            'via_push' => 'boolean',
            'via_in_app' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Which channels are enabled for an event+recipient (used by the dispatcher). */
    public static function channelsFor(string $eventKey, string $recipient): array
    {
        // Cache raw attributes, never the model — a serialized model in a
        // class-unaware store (database/file) deserializes to __PHP_Incomplete_Class.
        $attrs = Cache::remember(
            "notif_channel:{$eventKey}:{$recipient}",
            600,
            fn () => static::where('event_key', $eventKey)->where('recipient', $recipient)->first()?->getAttributes()
        );

        $row = $attrs ? (new static)->setRawAttributes($attrs, true) : null;

        if (!$row || !$row->is_active) {
            return [];
        }

        return array_keys(array_filter([
            'email' => $row->via_email,
            'sms' => $row->via_sms,
            'push' => $row->via_push,
            'in_app' => $row->via_in_app,
        ]));
    }

    protected static function booted(): void
    {
        static::saved(fn (self $c) => Cache::forget("notif_channel:{$c->event_key}:{$c->recipient}"));
    }
}
