<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    /** KYC document types a company may submit. */
    public const TYPES = [
        'commercial_register'    => 'السجل التجاري',
        'tax_card'               => 'البطاقة الضريبية',
        'license'                => 'رخصة مزاولة',
        'id_card'                => 'هوية المالك',
        'contract'               => 'عقد التأسيس',
        'bank_account'           => 'حساب بنكي',
        'payroll_authorization'  => 'تفويض توزيع رواتب',
    ];

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'legal_name',
        'owner_name',
        'email',
        'phone',
        'tax_id',
        'commercial_register',
        'description',
        'logo',
        'address',
        'city',
        'governorate',
        // 🔒 SEC-003: payroll_enabled, is_verified, kyc_approved_at,
        // kyc_rejection_reason intentionally NOT fillable — flipped only by the
        // admin approval flow via forceFill/update(). is_active, kyc_status,
        // kyc_submitted_at are set by trusted controllers, never user input.
        'is_active',
        'kyc_status',
        'kyc_submitted_at',
        'settings',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payroll_enabled'  => 'boolean',
            'is_active'        => 'boolean',
            'is_verified'      => 'boolean',
            'settings'         => 'array',
            'kyc_submitted_at' => 'datetime',
            'kyc_approved_at'  => 'datetime',
            'approval_notified_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Company $company) {
            $company->uuid ??= (string) Str::uuid();
            if (empty($company->company_code)) {
                $company->company_code = 'CO-' . str_pad((string) random_int(1000, 999999), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(BankAccount::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function approvedDocuments(): HasMany
    {
        return $this->hasMany(CompanyDocument::class)->where('status', 'approved');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(CompanyEmployee::class);
    }

    public function payrollBatches(): HasMany
    {
        return $this->hasMany(PayrollBatch::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    // ==================== Wallet helper ====================

    /**
     * The company's wallet for a currency, provisioned on demand. This is the
     * single source of truth for company funds (no inline `balance` column).
     * Call inside a DB transaction + lockForUpdate when moving money.
     */
    public function companyWallet(string $currency): Wallet
    {
        $currency = strtoupper($currency);

        return $this->wallets()->firstOrCreate(
            ['currency' => $currency],
            ['is_default' => $currency === 'USD'],
        );
    }

    // ==================== Scopes ====================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePayrollReady(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('is_verified', true)
            ->where('payroll_enabled', true);
    }

    /** Gate: may this company run payroll right now? */
    public function canRunPayroll(): bool
    {
        return $this->is_active && $this->is_verified && $this->payroll_enabled
            && $this->kyc_status === 'approved';
    }

    // ==================== Accessors ====================

    public function getKycStatusLabelAttribute(): string
    {
        return match ($this->kyc_status) {
            'pending' => 'قيد المراجعة',
            'documents_required' => 'مستندات ناقصة',
            'approved' => 'مفعّلة',
            'rejected' => 'مرفوضة',
            'suspended' => 'موقوفة',
            default => $this->kyc_status,
        };
    }

    public function getKycStatusColorAttribute(): string
    {
        return match ($this->kyc_status) {
            'pending', 'documents_required' => 'warning',
            'approved' => 'success',
            'rejected', 'suspended' => 'danger',
            default => 'secondary',
        };
    }
}
