<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
        'price', 'currency', 'duration_days', 'max_properties', 'max_agents',
        'is_featured', 'features', 'sort', 'is_active',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
        'features'    => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgencySubscription::class, 'plan_id');
    }
}
