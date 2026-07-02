<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyImageResource;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Deal;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\SubscriptionPlan;
use App\Models\AgencySubscription;
use App\Services\SakkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    private function getAgency(Request $request): Agency
    {
        $user = $request->user();
        $agency = $user->agency;

        if (!$agency && $user->isAgencyOwner()) {
            $agency = $user->agent?->agency;
        }

        abort_if(!$agency, 403, 'No agency found');

        return $agency;
    }

    public function stats(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);
        $dealsQuery = Deal::where('agency_id', $agency->id);

        return response()->json(['data' => [
            'total_properties'   => Property::where('agency_id', $agency->id)->count(),
            'active_listings'    => Property::where('agency_id', $agency->id)->where('status', 'available')->count(),
            'total_agents'       => Agent::where('agency_id', $agency->id)->count(),
            'total_inquiries'    => Inquiry::whereIn('property_id', Property::where('agency_id', $agency->id)->select('id'))->count(),
            'pending_inquiries'  => Inquiry::whereIn('property_id', Property::where('agency_id', $agency->id)->select('id'))->where('status', 'new')->count(),
            'monthly_views'      => DB::table('property_views')
                ->join('properties', 'property_views.property_id', '=', 'properties.id')
                ->where('properties.agency_id', $agency->id)
                ->where('viewed_at', '>=', now()->startOfMonth())
                ->count(),
            'total_deals'        => (clone $dealsQuery)->count(),
            'confirmed_deals'    => (clone $dealsQuery)->where('status', 'confirmed')->count(),
            'total_commission'   => (clone $dealsQuery)->where('status', 'confirmed')->sum('commission_amount'),
            'monthly_commission' => (clone $dealsQuery)->where('status', 'confirmed')
                ->where('deal_date', '>=', now()->startOfMonth())->sum('commission_amount'),
        ]]);
    }

    public function properties(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $properties = Property::where('agency_id', $agency->id)
            ->with(['type', 'governorate', 'area', 'images', 'agent'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['data' => $properties->items(), 'meta' => [
            'total'         => $properties->total(),
            'per_page'      => $properties->perPage(),
            'current_page'  => $properties->currentPage(),
            'last_page'     => $properties->lastPage(),
        ]]);
    }

    public function storeProperty(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        // Check plan limit
        if (!$agency->canAddProperty()) {
            return response()->json([
                'error' => $agency->propertyLimitMessage(),
            ], 403);
        }

        $validated = $request->validate([
            'property_type_id' => 'required|exists:property_types,id',
            'agent_id'         => 'nullable|exists:agents,id',
            'governorate_id'   => 'required|exists:governorates,id',
            'area_id'          => 'nullable|exists:areas,id',
            'purpose'          => 'required|in:sale,rent',
            'title_ar'         => 'required|string|max:255',
            'title_en'         => 'required|string|max:255',
            'description_ar'   => 'required|string',
            'description_en'   => 'required|string',
            'price'            => 'required|numeric|min:0',
            'currency'         => 'required|in:USD,SYP',
            'area_sqm'         => 'required|integer|min:1',
            'bedrooms'         => 'nullable|integer|min:0',
            'bathrooms'        => 'nullable|integer|min:0',
            'status'           => 'sometimes|in:draft,available',
        ]);

        $validated['agency_id'] = $agency->id;
        $validated['slug'] = Str::slug($validated['title_en'] . '-' . uniqid());
        $validated['ref_code'] = 'SYRH-' . str_pad((string)((Property::max('id') ?: 0) + 1), 4, '0', STR_PAD_LEFT);
        $validated['status'] ??= 'draft';

        $property = Property::create($validated);

        return response()->json(['data' => $property->load(['type', 'governorate', 'area'])], 201);
    }

    public function showProperty(Request $request, Property $property): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($property->agency_id !== $agency->id, 403);

        return response()->json(['data' => $property->load([
            'type', 'governorate', 'area', 'images', 'agent', 'amenities',
        ])]);
    }

    public function updateProperty(Request $request, Property $property): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($property->agency_id !== $agency->id, 403);

        $validated = $request->validate([
            'property_type_id' => 'sometimes|exists:property_types,id',
            'agent_id'         => 'nullable|exists:agents,id',
            'governorate_id'   => 'sometimes|exists:governorates,id',
            'area_id'          => 'nullable|exists:areas,id',
            'purpose'          => 'sometimes|in:sale,rent',
            'title_ar'         => 'sometimes|string|max:255',
            'title_en'         => 'sometimes|string|max:255',
            'description_ar'   => 'sometimes|string',
            'description_en'   => 'sometimes|string',
            'price'            => 'sometimes|numeric|min:0',
            'currency'         => 'sometimes|in:USD,SYP',
            'status'           => 'sometimes|in:draft,available,reserved,sold,rented',
        ]);

        $property->update($validated);

        return response()->json(['data' => $property->fresh()->load(['type', 'governorate', 'area'])]);
    }

    public function agents(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        return response()->json(['data' => Agent::where('agency_id', $agency->id)->get()]);
    }

    public function storeAgent(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        // Check plan limit
        if (!$agency->canAddAgent()) {
            return response()->json([
                'error' => $agency->agentLimitMessage(),
            ], 403);
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'required|string|max:20',
            'whatsapp'     => 'nullable|string|max:20',
            'bio_ar'       => 'nullable|string',
            'bio_en'       => 'nullable|string',
        ]);

        $validated['agency_id'] = $agency->id;
        $agent = Agent::create($validated);

        return response()->json(['data' => $agent], 201);
    }

    public function inquiries(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $inquiries = Inquiry::whereIn('property_id', Property::where('agency_id', $agency->id)->select('id'))
            ->with(['property:id,slug,title_ar,title_en', 'agent:id,display_name'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['data' => $inquiries->items(), 'meta' => [
            'total'         => $inquiries->total(),
            'per_page'      => $inquiries->perPage(),
            'current_page'  => $inquiries->currentPage(),
            'last_page'     => $inquiries->lastPage(),
        ]]);
    }

    public function updateInquiry(Request $request, Inquiry $inquiry): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($inquiry->property->agency_id !== $agency->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:new,contacted,closed',
        ]);

        $inquiry->update($validated);

        return response()->json(['data' => $inquiry->fresh()]);
    }

    public function subscription(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        return response()->json([
            'data' => [
                'current_subscription' => $agency->subscription?->load('plan'),
                'available_plans'      => SubscriptionPlan::where('is_active', true)->orderBy('sort')->get(),
                'usage'                => [
                    'properties' => [
                        'current' => $agency->properties()->count(),
                        'max'     => $agency->maxPropertiesAllowed(),
                    ],
                    'agents' => [
                        'current' => $agency->agents()->count(),
                        'max'     => $agency->maxAgentsAllowed(),
                    ],
                ],
            ],
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);
        $user = $request->user();

        abort_if(!$user->isAgencyOwner(), 403);

        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Cancel existing subscription if any
        $agency->subscriptions()->whereIn('status', ['trial', 'active'])->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        if ($plan->price <= 0) {
            // Free plan — activate immediately
            $subscription = AgencySubscription::create([
                'agency_id'    => $agency->id,
                'plan_id'      => $plan->id,
                'start_at'     => now(),
                'end_at'       => now()->addDays($plan->duration_days),
                'status'       => 'active',
                'trial_ends_at' => null,
            ]);

            return response()->json(['data' => $subscription->load('plan')], 201);
        }

        // Paid plan — create payment record first; subscription activates on webhook
        $payment = Payment::create([
            'agency_subscription_id' => null,
            'agency_id'              => $agency->id,
            'amount'                 => $plan->price,
            'currency'               => $plan->currency ?? 'USD',
            'payment_method'         => 'sakk',
            'gateway'                => 'sakk',
            'status'                 => 'pending',
            'notes'                  => json_encode([
                'type'     => 'subscription',
                'plan_id'  => $plan->id,
                'agency_id'=> $agency->id,
            ]),
        ]);

        // Call SAKK to get payment URL
        $sakk = app(SakkService::class);
        $result = $sakk->createPayment([
            'amount'       => (float) $plan->price,
            'currency'     => $plan->currency ?? 'USD',
            'description'  => 'Subscription: ' . ($plan->name_en ?: 'Plan') . ' — Agency: ' . $agency->name,
            'callback_url' => route('sakk.webhook'),
            'reference_id' => (string) $payment->id,
            'customer'     => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);

        if (!$result['success']) {
            $payment->delete();
            return response()->json([
                'error' => $result['error'] ?? 'Payment initiation failed',
            ], 502);
        }

        // Store transaction ID from SAKK
        if (isset($result['transaction_id'])) {
            $payment->update(['transaction_id' => $result['transaction_id']]);
        }

        return response()->json([
            'data' => [
                'payment_url'   => $result['payment_url'],
                'transaction_id'=> $result['transaction_id'] ?? null,
                'is_mock'       => $result['_mock'] ?? false,
            ],
        ], 201);
    }

    // -----------------------------------------------------------------------
    // SAKK account linking (agencies link their own SAKK merchant)
    // -----------------------------------------------------------------------

    public function sakkAccount(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        return response()->json(['data' => [
            'sakk_merchant_id' => $agency->sakk_merchant_id,
            'sakk_verified'    => $agency->sakk_verified,
            'sakk_verified_at' => $agency->sakk_verified_at,
        ]]);
    }

    public function updateSakkAccount(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $validated = $request->validate([
            'sakk_merchant_id' => 'required|string|max:100',
            'sakk_api_key'     => 'required|string|max:500',
        ]);

        $agency->update([
            'sakk_merchant_id'    => $validated['sakk_merchant_id'],
            'sakk_api_key_encrypted' => Crypt::encryptString($validated['sakk_api_key']),
        ]);

        return response()->json(['message' => 'SAKK account linked', 'data' => [
            'sakk_merchant_id' => $agency->sakk_merchant_id,
        ]]);
    }

    public function removeSakkAccount(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $agency->update([
            'sakk_merchant_id'    => null,
            'sakk_api_key_encrypted' => null,
            'sakk_verified'       => false,
            'sakk_verified_at'    => null,
        ]);

        return response()->json(['message' => 'SAKK account removed']);
    }

    public function profile(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request)->load(['governorate', 'area']);
        return response()->json(['data' => $agency]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $validated = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'phone'         => 'sometimes|string|max:20',
            'whatsapp'      => 'nullable|string|max:20',
            'email'         => 'sometimes|email|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'address'       => 'nullable|string|max:255',
            'logo_path'     => 'nullable|string',
            'cover_path'    => 'nullable|string',
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'area_id'       => 'nullable|integer|exists:areas,id',
            'lat'           => 'nullable|numeric|between:-90,90',
            'lng'           => 'nullable|numeric|between:-180,180',
        ]);

        $agency->update($validated);

        return response()->json(['data' => $agency->fresh()->load(['governorate', 'area'])]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,webp,svg|max:2048',
        ]);

        $file = $request->file('logo');
        $path = $file->store('agency-logos', 'public');

        if (!$path) {
            return response()->json(['message' => 'Failed to upload logo'], 500);
        }

        $url = '/storage/' . $path;
        $agency->update(['logo_path' => $url]);

        return response()->json(['data' => [
            'logo_url' => $url,
        ]]);
    }

    public function uploadCover(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $request->validate([
            'cover' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        $file = $request->file('cover');
        $path = $file->store('agency-covers', 'public');

        if (!$path) {
            return response()->json(['message' => 'Failed to upload cover'], 500);
        }

        $url = '/storage/' . $path;
        $agency->update(['cover_path' => $url]);

        return response()->json(['data' => [
            'cover_url' => $url,
        ]]);
    }

    // -----------------------------------------------------------------------
    // Deals (commission tracking)
    // -----------------------------------------------------------------------

    public function payments(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $payments = Payment::where('agency_id', $agency->id)
            ->with('agencySubscription.plan')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['data' => $payments->items(), 'meta' => [
            'total'         => $payments->total(),
            'per_page'      => $payments->perPage(),
            'current_page'  => $payments->currentPage(),
            'last_page'     => $payments->lastPage(),
        ]]);
    }

    public function deals(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $deals = Deal::where('agency_id', $agency->id)
            ->with(['property:id,slug,title_ar,title_en', 'agent:id,display_name'])
            ->orderByDesc('deal_date')
            ->paginate(20);

        return response()->json(['data' => $deals->items(), 'meta' => [
            'total'         => $deals->total(),
            'per_page'      => $deals->perPage(),
            'current_page'  => $deals->currentPage(),
            'last_page'     => $deals->lastPage(),
        ]]);
    }

    public function storeDeal(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $validated = $request->validate([
            'property_id'  => 'required|exists:properties,id',
            'agent_id'     => 'nullable|exists:agents,id',
            'type'         => 'required|in:sale,rent',
            'price'        => 'required|numeric|min:0',
            'currency'     => 'required|in:USD,SYP',
            'deal_date'    => 'required|date',
            'client_name'  => 'required|string|max:255',
            'client_phone' => 'nullable|string|max:20',
            'notes'        => 'nullable|string',
        ]);

        abort_if(Property::find($validated['property_id'])->agency_id !== $agency->id, 403);

        $validated['agency_id'] = $agency->id;
        $validated['commission_rate'] = $agency->commission_rate ?? 0.50;
        $validated['commission_amount'] = $validated['price'] * ($validated['commission_rate'] / 100);

        $deal = Deal::create($validated);

        return response()->json(['data' => $deal->load('property', 'agent')], 201);
    }

    public function updateDeal(Request $request, Deal $deal): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($deal->agency_id !== $agency->id, 403);

        $validated = $request->validate([
            'type'         => 'sometimes|in:sale,rent',
            'price'        => 'sometimes|numeric|min:0',
            'currency'     => 'sometimes|in:USD,SYP',
            'status'       => 'sometimes|in:pending,confirmed,cancelled',
            'deal_date'    => 'sometimes|date',
            'client_name'  => 'sometimes|string|max:255',
            'client_phone' => 'nullable|string|max:20',
            'notes'        => 'nullable|string',
        ]);

        if (isset($validated['price'])) {
            $validated['commission_amount'] = $validated['price'] * (($deal->commission_rate ?: $agency->commission_rate ?? 0.50) / 100);
        }

        $deal->update($validated);

        return response()->json(['data' => $deal->fresh()->load('property', 'agent')]);
    }

    public function commissionReport(Request $request): JsonResponse
    {
        $agency = $this->getAgency($request);

        $year = $request->integer('year', now()->year);

        $monthly = Deal::where('agency_id', $agency->id)
            ->where('status', 'confirmed')
            ->whereYear('deal_date', $year)
            ->selectRaw("EXTRACT(MONTH FROM deal_date) as month")
            ->selectRaw("COUNT(*) as deal_count")
            ->selectRaw("SUM(price) as total_volume")
            ->selectRaw("SUM(commission_amount) as total_commission")
            ->groupByRaw("EXTRACT(MONTH FROM deal_date)")
            ->orderByRaw("EXTRACT(MONTH FROM deal_date)")
            ->get();

        $totals = Deal::where('agency_id', $agency->id)
            ->where('status', 'confirmed')
            ->whereYear('deal_date', $year)
            ->selectRaw("COUNT(*) as total_deals")
            ->selectRaw("SUM(price) as total_volume")
            ->selectRaw("SUM(commission_amount) as total_commission")
            ->first();

        return response()->json(['data' => [
            'year'        => $year,
            'monthly'     => $monthly,
            'totals'      => $totals,
            'rate'        => (float) ($agency->commission_rate ?? 0.50),
        ]]);
    }

    // -----------------------------------------------------------------------
    // Property images
    // -----------------------------------------------------------------------

    /**
     * Upload one or more images for a property
     *
     * POST /agency/properties/{property}/images
     * Body: multipart/form-data with 'images[]' (up to 10 files)
     */
    public function uploadImages(Request $request, Property $property): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($property->agency_id !== $agency->id, 403);

        $request->validate([
            'images'   => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        $uploaded = [];
        $currentMax = $property->images()->max('sort') ?? 0;

        foreach ($request->file('images') as $i => $file) {
            /** @var UploadedFile $file */
            $path = $file->store('property-images', 'public');

            if (!$path) {
                continue;
            }

            $image = $property->images()->create([
                'path'    => $path,
                'sort'    => $currentMax + $i + 1,
                'is_cover'=> $property->images()->count() === 0 && $i === 0,
            ]);

            $uploaded[] = $image;
        }

        return response()->json([
            'data' => PropertyImageResource::collection(collect($uploaded)),
        ], 201);
    }

    /**
     * Delete a property image
     *
     * DELETE /agency/properties/{property}/images/{image}
     */
    public function deleteImage(Request $request, Property $property, PropertyImage $image): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($property->agency_id !== $agency->id, 403);
        abort_if($image->property_id !== $property->id, 404);

        // Delete file from storage
        Storage::disk('public')->delete($image->path);

        $image->delete();

        // If deleted image was cover, assign cover to next available
        if ($image->is_cover) {
            $first = $property->images()->orderBy('sort')->first();
            if ($first) {
                $first->update(['is_cover' => true]);
            }
        }

        return response()->json(['message' => 'Image deleted']);
    }

    /**
     * Set image as cover
     *
     * POST /agency/properties/{property}/images/{image}/cover
     */
    public function setCoverImage(Request $request, Property $property, PropertyImage $image): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($property->agency_id !== $agency->id, 403);
        abort_if($image->property_id !== $property->id, 404);

        // Unset all cover images for this property
        $property->images()->update(['is_cover' => false]);

        // Set the selected one
        $image->update(['is_cover' => true]);

        return response()->json(['data' => new PropertyImageResource($image->fresh())]);
    }

    // -----------------------------------------------------------------------
    // Property deletion
    // -----------------------------------------------------------------------

    /**
     * Delete a property (with images cleanup)
     *
     * DELETE /agency/properties/{property}
     */
    public function destroyProperty(Request $request, Property $property): JsonResponse
    {
        $agency = $this->getAgency($request);
        abort_if($property->agency_id !== $agency->id, 403);

        // Delete associated image files from storage
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $property->delete(); // cascades to images, inquiries, deals via FK

        return response()->json(['message' => 'Property deleted']);
    }
}
