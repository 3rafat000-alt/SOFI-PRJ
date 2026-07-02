<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Inquiry;
use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function favorites(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()->with('property.images', 'property.governorate', 'property.area')->latest()->get();

        return response()->json(['data' => $favorites]);
    }

    public function toggleFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate(['property_id' => 'required|exists:properties,id']);
        $user = $request->user();

        $existing = Favorite::where('user_id', $user->id)
            ->where('property_id', $validated['property_id'])
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['data' => ['favorited' => false]]);
        }

        Favorite::create([
            'user_id'     => $user->id,
            'property_id' => $validated['property_id'],
        ]);

        return response()->json(['data' => ['favorited' => true]], 201);
    }

    public function savedSearches(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->savedSearches()->latest()->get()]);
    }

    public function saveSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'filters' => 'required|json',
        ]);

        $search = $request->user()->savedSearches()->create($validated);

        return response()->json(['data' => $search], 201);
    }

    public function deleteSearch(Request $request, SavedSearch $search): JsonResponse
    {
        abort_if($search->user_id !== $request->user()->id, 403);
        $search->delete();

        return response()->json(['message' => 'deleted']);
    }

    /**
     * User dashboard stats.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'favorites_count'  => Favorite::where('user_id', $user->id)->count(),
            'inquiries_count'  => Inquiry::where('user_id', $user->id)->count(),
            'searches_count'   => SavedSearch::where('user_id', $user->id)->count(),
            'recent_inquiries' => Inquiry::where('user_id', $user->id)
                ->with('property:id,title_ar,title_en,slug,price,currency,cover_image')
                ->latest()->take(5)->get(),
            'recent_favorites' => $user->favorites()
                ->with('property:id,title_ar,title_en,slug,price,currency,cover_image')
                ->latest()->take(5)->get(),
        ]]);
    }

    /**
     * User's inquiries.
     */
    public function inquiries(Request $request): JsonResponse
    {
        $inquiries = Inquiry::where('user_id', $request->user()->id)
            ->with('property:id,title_ar,title_en,slug,price,currency,cover_image')
            ->latest()
            ->paginate(20);

        return response()->json($inquiries);
    }
}
