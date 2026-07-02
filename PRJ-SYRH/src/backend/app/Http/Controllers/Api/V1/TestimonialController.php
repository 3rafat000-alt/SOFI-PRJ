<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Exposes featured testimonials for the landing page social-proof section.
 */
class TestimonialController extends Controller
{
    /**
     * Return all featured testimonials ordered by sort.
     *
     * GET /api/v1/testimonials
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $testimonials = Testimonial::where('is_featured', true)
            ->orderBy('sort')
            ->get();

        return TestimonialResource::collection($testimonials);
    }
}
