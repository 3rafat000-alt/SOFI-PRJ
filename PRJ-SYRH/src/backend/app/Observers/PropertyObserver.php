<?php

namespace App\Observers;

use App\Models\Area;
use App\Models\Governorate;
use App\Models\Property;
use App\Models\PropertyType;

/**
 * Keeps governorates.properties_count, areas.properties_count, and
 * property_types.listings_count accurate without N+1 queries.
 *
 * "Published" means: status != 'draft' (published_at is managed separately
 * but a row in draft state never contributes to the count even if published_at
 * is set — business rule: draft = invisible).
 */
class PropertyObserver
{
    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function isPublished(Property $property): bool
    {
        return $property->status !== 'draft';
    }

    /**
     * Increment the three denormalized counters for a property that just
     * became published or was newly created in a published state.
     */
    private function increment(Property $property): void
    {
        Governorate::where('id', $property->governorate_id)
            ->increment('properties_count');

        if ($property->area_id) {
            Area::where('id', $property->area_id)
                ->increment('properties_count');
        }

        PropertyType::where('id', $property->property_type_id)
            ->increment('listings_count');
    }

    /**
     * Decrement the three denormalized counters for a property that just
     * became unpublished / deleted. Guards against going below 0 (floor at 0).
     */
    private function decrement(Property $property): void
    {
        Governorate::where('id', $property->governorate_id)
            ->where('properties_count', '>', 0)
            ->decrement('properties_count');

        if ($property->area_id) {
            Area::where('id', $property->area_id)
                ->where('properties_count', '>', 0)
                ->decrement('properties_count');
        }

        PropertyType::where('id', $property->property_type_id)
            ->where('listings_count', '>', 0)
            ->decrement('listings_count');
    }

    // ---------------------------------------------------------------------------
    // Observer hooks
    // ---------------------------------------------------------------------------

    public function created(Property $property): void
    {
        if ($this->isPublished($property)) {
            $this->increment($property);
        }
    }

    public function updated(Property $property): void
    {
        $statusChanged     = $property->wasChanged('status');
        $governorateChanged = $property->wasChanged('governorate_id');
        $areaChanged       = $property->wasChanged('area_id');
        $typeChanged       = $property->wasChanged('property_type_id');

        $wasDraft    = $property->getOriginal('status') === 'draft';
        $isDraft     = $property->status === 'draft';
        $wasPublished = ! $wasDraft;
        $isPublished  = ! $isDraft;

        // Case 1: just published (draft → non-draft)
        if ($statusChanged && $wasDraft && $isPublished) {
            $this->increment($property);
            return;
        }

        // Case 2: just unpublished (non-draft → draft)
        if ($statusChanged && $wasPublished && $isDraft) {
            // Decrement using old location/type so we hit the right rows
            $this->decrementFor(
                $property->getOriginal('governorate_id'),
                $property->getOriginal('area_id'),
                $property->getOriginal('property_type_id')
            );
            return;
        }

        // Case 3: published property moved between governorate / area / type
        if ($isPublished && ($governorateChanged || $areaChanged || $typeChanged)) {
            // Remove from old buckets
            $this->decrementFor(
                $property->getOriginal('governorate_id'),
                $property->getOriginal('area_id'),
                $property->getOriginal('property_type_id')
            );
            // Add to new buckets
            $this->increment($property);
        }
    }

    public function deleted(Property $property): void
    {
        // Soft-delete: remove from counts while row stays in DB
        if ($this->isPublished($property)) {
            $this->decrement($property);
        }
    }

    public function restored(Property $property): void
    {
        // Soft-restore: add back to counts if it is still published
        if ($this->isPublished($property)) {
            $this->increment($property);
        }
    }

    public function forceDeleted(Property $property): void
    {
        // Permanent delete — identical handling to soft-delete
        if ($this->isPublished($property)) {
            $this->decrement($property);
        }
    }

    // ---------------------------------------------------------------------------
    // Private targeted decrement (used in move-case)
    // ---------------------------------------------------------------------------

    /**
     * Decrement counters for the given (old) foreign-key values without
     * touching the property model's current state.
     */
    private function decrementFor(int $governorateId, ?int $areaId, int $propertyTypeId): void
    {
        Governorate::where('id', $governorateId)
            ->where('properties_count', '>', 0)
            ->decrement('properties_count');

        if ($areaId) {
            Area::where('id', $areaId)
                ->where('properties_count', '>', 0)
                ->decrement('properties_count');
        }

        PropertyType::where('id', $propertyTypeId)
            ->where('listings_count', '>', 0)
            ->decrement('listings_count');
    }
}
