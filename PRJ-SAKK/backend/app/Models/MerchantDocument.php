<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'document_type',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'document_number',
        'issue_date',
        'expiry_date',
        'issuing_authority',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public const TYPES = [
        'commercial_record' => 'سجل تجاري',
        'tax_card' => 'بطاقة ضريبية',
        'bank_account' => 'حساب بنكي',
        'license' => 'رخصة مهنية',
        'id_card' => 'هوية المالك',
        'contract' => 'عقد تأسيس',
        'ownership' => 'إثبات ملكية',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->document_type] ?? $this->document_type;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };
    }
}
