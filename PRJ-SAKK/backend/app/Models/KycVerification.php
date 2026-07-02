<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycVerification extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'level',
        'verification_type',
        // 🔒 SEC-003: 'status', 'reviewed_by', 'reviewed_at' intentionally NOT fillable.
        // These are set explicitly by KycService::reviewVerification() only.
        'document_path',
        'document_type',
        'extracted_data',
        'rejection_reason',
        'expires_at',
    ];

    // 🔒 SEC-003: Intentionally no $guarded — $fillable above is the explicit allowlist.
    // Status, reviewed_by, and reviewed_at are NOT in $fillable to prevent
    // self-approval through mass assignment. They must be set explicitly
    // through KycService::reviewVerification().

    protected function casts(): array
    {
        return [
            'extracted_data' => 'array',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
