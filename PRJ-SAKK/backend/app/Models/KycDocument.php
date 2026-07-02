<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KycDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'document_type',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'document_number',
        'issuing_country',
        'issue_date',
        'expiry_date',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'ocr_data',
        'extracted_data',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
            'ocr_data' => 'array',
            'extracted_data' => 'array',
            'status' => VerificationStatus::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (KycDocument $document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
