<?php

namespace App\Providers;

use App\Models\Property;
use App\Observers\PropertyObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Registers the PropertyObserver so that governorates.properties_count,
     * areas.properties_count, and property_types.listings_count stay in sync
     * without N+1 queries. The observer uses targeted increment/decrement calls
     * rather than full recounts to keep writes atomic and cheap.
     */
    public function boot(): void
    {
        Property::observe(PropertyObserver::class);
    }
}
