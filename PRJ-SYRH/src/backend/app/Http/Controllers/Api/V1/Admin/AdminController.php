<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\AgencySubscription;
use App\Models\ContactMessage;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyReview;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SakkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $sakk = app(SakkService::class);

        return response()->json(['data' => [
            'total_users'       => User::count(),
            'total_agencies'    => Agency::count(),
            'total_properties'  => Property::count(),
            'total_inquiries'   => \App\Models\Inquiry::count(),
            'active_plans'      => AgencySubscription::where('status', 'active')->count(),
            'pending_agencies'  => Agency::where('status', 'pending')->count(),
            'unread_messages'   => ContactMessage::where('is_read', false)->count(),
            'recent_users'      => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
            'recent_agencies'   => Agency::latest()->take(5)->get(['id', 'name', 'status', 'created_at']),
            'monthly_revenue'   => Payment::where('status', 'completed')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'sakk' => [
                'configured'     => $sakk->isConfigured(),
                'merchant_id'    => Setting::where('key', 'sakk_merchant_id')->value('value'),
                'sandbox'        => Setting::where('key', 'sakk_sandbox')->value('value') === 'true',
                'agencies_linked'=> Agency::whereNotNull('sakk_merchant_id')->count(),
                'total_payments' => Payment::where('gateway', 'sakk')
                    ->where('status', 'completed')->count(),
                'total_revenue'  => Payment::where('gateway', 'sakk')
                    ->where('status', 'completed')->sum('amount'),
            ],
        ]]);
    }

    // -- Users --
    public function users(): JsonResponse
    {
        $users = User::with('roles')->latest()->paginate(20);
        return response()->json(['data' => $users->items(), 'meta' => [
            'total' => $users->total(), 'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(), 'last_page' => $users->lastPage(),
        ]]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'email'  => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone'  => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,suspended',
            'role'   => 'sometimes|string|exists:roles,name',
        ]);

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        $user->update($validated);

        return response()->json(['data' => $user->fresh()->load('roles')]);
    }

    // -- Agencies --
    public function agencies(): JsonResponse
    {
        $agencies = Agency::with('owner', 'subscription.plan')->latest()->paginate(20);
        return response()->json(['data' => $agencies->items(), 'meta' => [
            'total' => $agencies->total(), 'per_page' => $agencies->perPage(),
            'current_page' => $agencies->currentPage(), 'last_page' => $agencies->lastPage(),
        ]]);
    }

    public function updateAgency(Request $request, Agency $agency): JsonResponse
    {
        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'status' => 'sometimes|in:pending,active,suspended',
            'phone'  => 'sometimes|string|max:20',
            'email'  => 'sometimes|email|max:255',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'active' && !$agency->verified_at) {
            $validated['verified_at'] = now();
        }

        $agency->update($validated);

        return response()->json(['data' => $agency->fresh()->load('owner', 'subscription.plan')]);
    }

    // -- Properties (moderation) --
    public function properties(): JsonResponse
    {
        $props = Property::with(['agency', 'type', 'governorate'])->latest()->paginate(20);
        return response()->json(['data' => $props->items(), 'meta' => [
            'total' => $props->total(), 'per_page' => $props->perPage(),
            'current_page' => $props->currentPage(), 'last_page' => $props->lastPage(),
        ]]);
    }

    public function moderateProperty(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'status'      => 'required|in:available,reserved,sold,rented,draft',
            'is_featured' => 'sometimes|boolean',
            'is_hot_deal' => 'sometimes|boolean',
        ]);

        $property->update($validated);

        return response()->json(['data' => $property->fresh()]);
    }

    // -- Subscriptions --
    public function subscriptionPlans(): JsonResponse
    {
        return response()->json(['data' => SubscriptionPlan::orderBy('sort')->get()]);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_ar'       => 'required|string|max:255',
            'name_en'       => 'required|string|max:255',
            'slug'          => 'required|string|max:255|unique:subscription_plans',
            'price'         => 'required|numeric|min:0',
            'currency'      => 'required|in:USD,SYP',
            'duration_days' => 'required|integer|min:1',
            'max_properties' => 'required|integer|min:0',
            'max_agents'    => 'required|integer|min:0',
            'is_featured'   => 'sometimes|boolean',
            'features'      => 'nullable|array',
            'sort'          => 'sometimes|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ]);

        $plan = SubscriptionPlan::create($validated);

        return response()->json(['data' => $plan], 201);
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name_ar'       => 'sometimes|string|max:255',
            'name_en'       => 'sometimes|string|max:255',
            'price'         => 'sometimes|numeric|min:0',
            'duration_days' => 'sometimes|integer|min:1',
            'max_properties' => 'sometimes|integer|min:0',
            'max_agents'    => 'sometimes|integer|min:0',
            'is_featured'   => 'sometimes|boolean',
            'features'      => 'nullable|array',
            'sort'          => 'sometimes|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ]);

        $plan->update($validated);

        return response()->json(['data' => $plan->fresh()]);
    }

    // -- Contact Messages --
    public function contactMessages(): JsonResponse
    {
        $msgs = ContactMessage::latest()->paginate(20);
        return response()->json(['data' => $msgs->items(), 'meta' => [
            'total' => $msgs->total(), 'per_page' => $msgs->perPage(),
            'current_page' => $msgs->currentPage(), 'last_page' => $msgs->lastPage(),
        ]]);
    }

    public function readMessage(ContactMessage $message): JsonResponse
    {
        $message->markAsRead();

        return response()->json(['data' => $message->fresh()]);
    }

    // -- Reviews moderation --
    public function reviews(): JsonResponse
    {
        $reviews = PropertyReview::with('user', 'property')->latest()->paginate(20);
        return response()->json(['data' => $reviews->items(), 'meta' => [
            'total' => $reviews->total(), 'per_page' => $reviews->perPage(),
            'current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(),
        ]]);
    }

    public function approveReview(PropertyReview $review): JsonResponse
    {
        $review->update(['is_approved' => true, 'approved_at' => now()]);

        return response()->json(['data' => $review->fresh()]);
    }

    // -- Settings --
    public function settings(): JsonResponse
    {
        return response()->json(['data' => Setting::get()->groupBy('group')]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key'   => 'required|string',
            'settings.*.value' => 'required|string',
        ]);

        foreach ($validated['settings'] as $s) {
            Setting::updateOrCreate(['key' => $s['key']], ['value' => $s['value']]);
        }

        return response()->json(['message' => 'Settings updated']);
    }

    // -- Area Management --
    public function areas(): JsonResponse
    {
        $areas = Area::with('governorate')
            ->orderBy('governorate_id')
            ->orderBy('name_ar')
            ->paginate(50);
        return response()->json(['data' => $areas->items(), 'meta' => [
            'total' => $areas->total(), 'per_page' => $areas->perPage(),
            'current_page' => $areas->currentPage(), 'last_page' => $areas->lastPage(),
        ]]);
    }

    public function storeArea(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'governorate_id' => 'required|integer|exists:governorates,id',
            'name_ar'        => 'required|string|max:255',
            'name_en'        => 'required|string|max:255',
            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name_en'] ?: $validated['name_ar']);
        $validated['properties_count'] = 0;

        $area = Area::create($validated);

        return response()->json(['data' => $area->load('governorate')], 201);
    }

    public function updateArea(Request $request, Area $area): JsonResponse
    {
        $validated = $request->validate([
            'governorate_id' => 'sometimes|integer|exists:governorates,id',
            'name_ar'        => 'sometimes|string|max:255',
            'name_en'        => 'sometimes|string|max:255',
            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',
        ]);

        if (isset($validated['name_en'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name_en']);
        }

        $area->update($validated);

        return response()->json(['data' => $area->fresh()->load('governorate')]);
    }

    public function deleteArea(Area $area): JsonResponse
    {
        $area->delete();
        return response()->json(['message' => 'Area deleted']);
    }

    public function governorates(): JsonResponse
    {
        $governorates = Governorate::orderBy('name_ar')->get();
        return response()->json(['data' => $governorates]);
    }
}
