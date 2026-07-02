<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Handles inquiry submission for a specific property.
 *
 * Security controls:
 * - Mass-assignment: only validated() fields are passed to Inquiry::create();
 *   property_id and agent_id are set explicitly, not from user input,
 *   preventing IDOR via crafted agent_id substitution.
 * - Throttle (10/min per IP) is enforced at the route level.
 * - StoreInquiryRequest validates and sanitizes all user input before it
 *   reaches this controller.
 */
class InquiryController extends Controller
{
    /**
     * Store an inquiry for the given property.
     *
     * POST /api/v1/properties/{property}/inquiries
     *
     * @param  StoreInquiryRequest  $request
     * @param  Property             $property  Route-model bound by slug.
     * @return JsonResponse
     */
    public function store(StoreInquiryRequest $request, Property $property): JsonResponse
    {
        // Guard: only accept inquiries on published, non-draft properties.
        abort_if($property->status === 'draft', 404);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        // Attempt to detect authenticated user from bearer token (route is public)
        $userId = null;
        if ($token = $request->bearerToken()) {
            $tokenModel = PersonalAccessToken::findToken($token);
            if ($tokenModel) {
                $userId = $tokenModel->tokenable_id;
            }
        }

        // Explicitly set system-derived fields — not from user input.
        $inquiry = Inquiry::create([
            'property_id'  => $property->id,
            'agent_id'     => $property->agent_id,
            'user_id'      => $userId,
            'name'         => $validated['name'],
            'phone'        => $validated['phone'],
            'email'        => $validated['email'] ?? null,
            'message'      => $validated['message'],
            'type'         => $validated['type'],
            'preferred_at' => $validated['preferred_at'] ?? null,
            'offer_amount' => $validated['offer_amount'] ?? null,
            'status'       => 'new',
        ]);

        return response()->json([
            'data'    => ['id' => $inquiry->id],
            'message' => 'تم إرسال طلبك بنجاح. سيتواصل معك الوكيل قريباً.',
        ], 201);
    }
}
