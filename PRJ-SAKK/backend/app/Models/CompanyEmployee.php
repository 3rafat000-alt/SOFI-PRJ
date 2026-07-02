<?php

namespace App\Models;

use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CompanyEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'employee_user_id',
        'phone',
        'name',
        'national_id',
        'job_title',
        'default_amount',
        'default_currency',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CompanyEmployee $employee) {
            $employee->uuid ??= (string) Str::uuid();
            // Always store the phone canonicalized so roster + payroll + release
            // all key off the same form.
            if (!empty($employee->phone)) {
                $employee->phone = PhoneNormalizer::canonical($employee->phone);
            }
        });

        static::updating(function (CompanyEmployee $employee) {
            if ($employee->isDirty('phone') && !empty($employee->phone)) {
                $employee->phone = PhoneNormalizer::canonical($employee->phone);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
