<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    protected $fillable = [
        'admin_id',
        'title',
        'body',
        'type',
        'user_ids',
        'status',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'sent_at',
    ];

    protected $guarded = [];

    protected $casts = [
        'user_ids' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get target users based on type
     */
    public function getTargetUsers()
    {
        return match ($this->type) {
            'all' => User::where('is_active', true)->whereNotNull('fcm_token'),
            'kyc_verified' => User::where('kyc_status', 'verified')->whereNotNull('fcm_token'),
            'active' => User::where('is_active', true)->whereNotNull('fcm_token'),
            'inactive' => User::where('is_active', false)->whereNotNull('fcm_token'),
            'specific' => User::whereIn('id', $this->user_ids ?? [])->whereNotNull('fcm_token'),
            default => User::query()->whereRaw('1=0'), // empty
        };
    }
}
