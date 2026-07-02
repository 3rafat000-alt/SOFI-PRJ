<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyTypeResource;
use App\Models\PropertyType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles property type listing for the landing page filter UI.
 */
class PropertyTypeController extends Controller
{
    /**
     * Return all property types ordered by sort column.
     *
     * GET /api/v1/property-types
     *
     * No pagination — the full list is small and needed upfront by filter UI.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $types = PropertyType::orderBy('sort')->get();

        return PropertyTypeResource::collection($types);
    }
}
