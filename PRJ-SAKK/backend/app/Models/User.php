<?php

namespace App\Models;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// AmlFlag and ActivityLog are in same namespace — no extra import needed
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'avatar',
        'date_of_birth',
        'gender',
        'country_code',
        'language',
        'timezone',
        // pin_code is fillable so the factory and trusted code can set it;
        // mass-assignment risk is mitigated because controllers never accept
        // it from user input — a dedicated endpoint + PinService handles it.
        'pin_code',
        // 🔒 SEC-003: kyc_level/kyc_status/status/two_factor_enabled/
        // email_verified_at/phone_verified_at/kyc_verified_at intentionally
        // NOT fillable. These security-critical fields must be set explicitly
        // through trusted code paths only (KycService, AuthService, admin).
        'kyc_data',
        'stripe_cardholder_id',
        'stripe_customer_id',
        'referred_by',
        'fcm_token',
        'device_id',
        // 🔒 SEC-002: 'is_admin' intentionally NOT fillable — privilege escalation guard.
        // Set only via forceFill at trusted sites (installer, seeder, admin updateUser).
        'deletion_reason',
        'deleted_requested_at',
    ];

    // 🔒 SEC-002: أُزيل $guarded=[] — قائمة $fillable أعلاه هي المرجع المسموح به

    protected $hidden = [
        'password',
        'remember_token',
        'pin_code',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'telegram_linked_at' => 'datetime',
            'kyc_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_failed_login_at' => 'datetime',
            'deleted_requested_at' => 'datetime',
            'login_attempts' => 'integer',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'kyc_data' => 'array',
            'two_factor_recovery_codes' => 'array',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'kyc_status' => KycStatus::class,
            'status' => UserStatus::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            $user->uuid = Str::uuid();
            $user->referral_code ??= strtoupper(Str::random(8));
        });

        static::created(function (User $user) {
            $user->wallets()->firstOrCreate(
                ['currency' => 'USD'],
                ['is_default' => true]
            );
            $user->wallets()->firstOrCreate(
                ['currency' => 'SYP'],
                ['is_default' => false]
            );
        });

        // Grant the referral reward to the referrer once this user becomes KYC-verified.
        static::updated(function (User $user) {
            if ($user->wasChanged('kyc_status')
                && $user->kyc_status === KycStatus::VERIFIED
                && $user->referred_by) {
                app(\App\Services\ReferralService::class)->grantOnKycVerified($user);
            }
        });
    }

    // ==================== Accessors ====================

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getIsKycVerifiedAttribute(): bool
    {
        return $this->kyc_status === KycStatus::VERIFIED;
    }

    // ==================== Relationships ====================

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function defaultWallet(): HasOne
    {
        return $this->hasOne(Wallet::class)->where('is_default', true);
    }

    public function usdWallet(): HasOne
    {
        return $this->hasOne(Wallet::class)->where('currency', 'USD');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(VirtualCard::class);
    }

    public function activeCards(): HasMany
    {
        return $this->hasMany(VirtualCard::class)->where('status', 'active');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function amlFlags(): HasMany
    {
        return $this->hasMany(AmlFlag::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function defaultBankAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BankAccount::class)->where('is_default', true);
    }

    // ==================== Methods ====================

    public function getWallet(string $currency): ?Wallet
    {
        return $this->wallets()->where('currency', $currency)->first();
    }

    public function getOrCreateWallet(string $currency): Wallet
    {
        return $this->wallets()->firstOrCreate(
            ['currency' => $currency],
            ['is_default' => $this->wallets()->count() === 0]
        );
    }

    /**
     * Whether the account is in a state that permits transacting at all.
     * Note: KYC level gates LIMITS (enforced in TransferService), not access —
     * even unverified users may transact within their (low) level limits.
     */
    public function canMakeTransaction(): bool
    {
        return $this->status === UserStatus::ACTIVE
            && $this->is_active
            && $this->kyc_status !== KycStatus::REJECTED;
    }

    public function isVerified(): bool
    {
        return ($this->kyc_level ?? 0) >= \App\Services\KycService::VERIFIED_LEVEL;
    }

    public function verifyPin(string $pin): bool
    {
        if (!$this->pin_code) {
            return false;
        }
        // Check if already a bcrypt hash (new storage) or plain text (legacy)
        if (str_starts_with($this->pin_code, '$2y$') || str_starts_with($this->pin_code, '$2a$') || str_starts_with($this->pin_code, '$2b$')) {
            return Hash::check($pin, $this->pin_code);
        }
        return $this->pin_code === $pin;
    }
}
