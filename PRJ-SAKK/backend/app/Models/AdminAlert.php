<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAlert extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'type',
        'read_at',
        'link',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForAdmin($query, ?int $adminId = null)
    {
        return $query->where(function ($q) use ($adminId) {
            $q->whereNull('admin_id')
              ->orWhere('admin_id', $adminId ?? auth()->id());
        });
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
