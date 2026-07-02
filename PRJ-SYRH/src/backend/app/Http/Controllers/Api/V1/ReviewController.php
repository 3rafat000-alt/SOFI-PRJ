<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PropertyReview;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reviews = PropertyReview::with('user')
            ->where('is_approved', true)
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'rating'      => 'required|integer|min:1|max:5',
            'title'       => 'nullable|string|max:255',
            'body'        => 'nullable|string',
        ]);

        // Prevent duplicate
        $existing = PropertyReview::where('user_id', $request->user()->id)
            ->where('property_id', $validated['property_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You have already reviewed this property'], 409);
        }

        $review = PropertyReview::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $review->load('user')], 201);
    }
}
