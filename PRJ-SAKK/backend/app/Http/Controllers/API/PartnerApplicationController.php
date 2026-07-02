<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\Merchant;
use App\Models\MerchantDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerApplicationController extends Controller
{
    /// Return the current user's agent + merchant applications (if any).
    public function application(Request $request): JsonResponse
    {
        $user = $request->user();

        $agent = Agent::where('user_id', $user->id)->with('documents')->first();
        $merchant = Merchant::where('user_id', $user->id)->with('documents')->first();

        return response()->json([
            'data' => [
                'agent' => $agent ? $this->transformAgent($agent) : null,
                'merchant' => $merchant ? $this->transformMerchant($merchant) : null,
                'document_types' => [
                    'agent' => $this->labelMap(AgentDocument::TYPES),
                    'merchant' => $this->labelMap(MerchantDocument::TYPES),
                ],
            ],
        ]);
    }

    /// Submit an application to become an agent or a merchant.
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:agent,merchant',
        ]);

        $user = $request->user();

        if ($validated['type'] === 'agent') {
            return $this->applyAgent($request, $user);
        }

        return $this->applyMerchant($request, $user);
    }

    private function applyAgent(Request $request, $user): JsonResponse
    {
        if (Agent::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'لديك طلب وكيل مسجّل بالفعل'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'owner_name' => 'nullable|string|max:120',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:80',
            'governorate' => 'nullable|string|max:80',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'services' => 'nullable|array',
            'services.*' => 'in:cash_in,cash_out',
            'working_hours' => 'nullable|string|max:120',
        ]);

        $agent = Agent::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'owner_name' => $validated['owner_name'] ?? trim("{$user->first_name} {$user->last_name}"),
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'governorate' => $validated['governorate'] ?? null,
            'latitude' => $validated['latitude'] ?? 0,
            'longitude' => $validated['longitude'] ?? 0,
            'services' => $validated['services'] ?? ['cash_in', 'cash_out'],
            'working_hours' => $validated['working_hours'] ?? null,
            'is_active' => false,
            'is_verified' => false,
            'kyc_status' => 'pending',
            'kyc_submitted_at' => now(),
        ]);

        rescue(fn () => \App\Services\AdminNotificationService::partnerApplicationSubmitted($user, 'agent'));

        return response()->json([
            'message' => 'تم استلام طلبك كوكيل. أكمل رفع المستندات لمراجعتها.',
            'data' => $this->transformAgent($agent),
        ], 201);
    }

    private function applyMerchant(Request $request, $user): JsonResponse
    {
        if (Merchant::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'لديك طلب تاجر مسجّل بالفعل'], 422);
        }

        $validated = $request->validate([
            'store_name' => 'required|string|max:120',
            'store_type' => 'required|in:physical,ecommerce,both',
            'owner_name' => 'nullable|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:120',
            'description' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:80',
            'governorate' => 'nullable|string|max:80',
            'website_url' => 'nullable|url|max:255',
        ]);

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'store_name' => $validated['store_name'],
            'type' => $validated['store_type'],
            'owner_name' => $validated['owner_name'] ?? trim("{$user->first_name} {$user->last_name}"),
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? $user->email,
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'governorate' => $validated['governorate'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'has_api_access' => false,
            'is_active' => false,
            'is_verified' => false,
            'kyc_status' => 'pending',
            'kyc_submitted_at' => now(),
        ]);

        rescue(fn () => \App\Services\AdminNotificationService::partnerApplicationSubmitted($user, 'merchant'));

        return response()->json([
            'message' => 'تم استلام طلبك كتاجر. أكمل رفع المستندات لمراجعتها.',
            'data' => $this->transformMerchant($merchant),
        ], 201);
    }

    /// Upload a single document for the user's agent/merchant application.
    public function uploadDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:agent,merchant',
            'document_type' => 'required|string|max:40',
            'document_number' => 'nullable|string|max:80',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        $user = $request->user();

        // SEC H5: identity docs live on the private disk, served only via SecureFileController.
        $path = $request->file('file')->store('partner-documents', 'private');
        $file = $request->file('file');

        if ($validated['type'] === 'agent') {
            $agent = Agent::where('user_id', $user->id)->firstOrFail();
            $doc = AgentDocument::create([
                'agent_id' => $agent->id,
                'document_type' => $validated['document_type'],
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'document_number' => $validated['document_number'] ?? null,
                'status' => 'pending',
            ]);
            // Resubmitting documents moves the application back into review.
            if (in_array($agent->kyc_status, ['documents_required', 'rejected'])) {
                $agent->update(['kyc_status' => 'pending']);
            }
        } else {
            $merchant = Merchant::where('user_id', $user->id)->firstOrFail();
            $doc = MerchantDocument::create([
                'merchant_id' => $merchant->id,
                'document_type' => $validated['document_type'],
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'document_number' => $validated['document_number'] ?? null,
                'status' => 'pending',
            ]);
            if (in_array($merchant->kyc_status, ['documents_required', 'rejected'])) {
                $merchant->update(['kyc_status' => 'pending']);
            }
        }

        return response()->json([
            'message' => 'تم رفع المستند بنجاح',
            'data' => [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'type_label' => $doc->type_label,
                'status' => $doc->status,
            ],
        ], 201);
    }

    // ==================== Transformers ====================

    private function transformAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'type' => 'agent',
            'name' => $agent->name,
            'agent_code' => $agent->agent_code,
            'phone' => $agent->phone,
            'city' => $agent->city,
            'is_active' => $agent->is_active,
            'kyc_status' => $agent->kyc_status,
            'kyc_status_label' => $agent->kyc_status_label,
            'kyc_status_color' => $agent->kyc_status_color,
            'kyc_rejection_reason' => $agent->kyc_rejection_reason,
            'documents' => $agent->relationLoaded('documents')
                ? $agent->documents->map(fn ($d) => $this->transformDoc($d))->values()
                : [],
        ];
    }

    private function transformMerchant(Merchant $merchant): array
    {
        return [
            'id' => $merchant->id,
            'type' => 'merchant',
            'store_name' => $merchant->store_name,
            'merchant_code' => $merchant->merchant_code,
            'merchant_type' => $merchant->type,
            'phone' => $merchant->phone,
            'city' => $merchant->city,
            'is_active' => $merchant->is_active,
            'kyc_status' => $merchant->kyc_status,
            'kyc_status_label' => $merchant->kyc_status_label,
            'kyc_status_color' => $merchant->kyc_status_color,
            'kyc_rejection_reason' => $merchant->kyc_rejection_reason,
            'documents' => $merchant->relationLoaded('documents')
                ? $merchant->documents->map(fn ($d) => $this->transformDoc($d))->values()
                : [],
        ];
    }

    private function transformDoc($doc): array
    {
        return [
            'id' => $doc->id,
            'document_type' => $doc->document_type,
            'type_label' => $doc->type_label,
            'status' => $doc->status,
            'status_color' => $doc->status_color,
            'rejection_reason' => $doc->rejection_reason,
            'uploaded_at' => $doc->created_at->toIso8601String(),
        ];
    }

    private function labelMap(array $types): array
    {
        return collect($types)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
        ])->values()->all();
    }
}
