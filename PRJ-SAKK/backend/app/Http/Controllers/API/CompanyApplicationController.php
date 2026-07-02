<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Services\AdminNotificationService;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile self-service "join as a company" (انضم كشركة) — the third business
 * audience after agents/merchants. Mirrors PartnerApplicationController but for
 * the Company model. Reuses CompanyService::apply() (the same path the web
 * onboarding portal uses) so both surfaces stay in sync.
 */
class CompanyApplicationController extends Controller
{
    public function __construct(private CompanyService $companies = new CompanyService()) {}

    /// Return the current user's company application (if any) + doc types.
    public function application(Request $request): JsonResponse
    {
        $company = Company::where('user_id', $request->user()->id)
            ->with('documents')
            ->first();

        return response()->json([
            'data' => [
                'company' => $company ? $this->transform($company) : null,
                'document_types' => $this->labelMap(Company::TYPES),
            ],
        ]);
    }

    /// Submit a company registration application. One company per user.
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'legal_name' => 'nullable|string|max:160',
            'owner_name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:160',
            'phone' => 'nullable|string|max:30',
            'tax_id' => 'nullable|string|max:60',
            'commercial_register' => 'nullable|string|max:60',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:80',
            'governorate' => 'nullable|string|max:80',
        ]);

        $user = $request->user();

        try {
            $company = $this->companies->apply($user, $validated);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        rescue(fn () => AdminNotificationService::companyApplicationSubmitted($user, $company->name));

        return response()->json([
            'message' => 'تم استلام طلب تسجيل الشركة. أكمل رفع المستندات لمراجعتها.',
            'data' => $this->transform($company->load('documents')),
        ], 201);
    }

    /// Upload a single KYC document for the user's company application.
    public function uploadDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:80',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        $company = Company::where('user_id', $request->user()->id)->firstOrFail();

        $file = $request->file('file');
        // SEC H5: identity docs live on the private disk, served only via SecureFileController.
        $path = $file->store('company-documents', 'private');

        $doc = CompanyDocument::create([
            'company_id' => $company->id,
            'document_type' => $validated['document_type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'document_number' => $validated['document_number'] ?? null,
            'status' => 'pending',
        ]);

        // Resubmitting documents moves the application back into review.
        if (in_array($company->kyc_status, ['documents_required', 'rejected'], true)) {
            $company->update(['kyc_status' => 'pending']);
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

    private function transform(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'legal_name' => $company->legal_name,
            'company_code' => $company->company_code,
            'phone' => $company->phone,
            'city' => $company->city,
            'is_active' => $company->is_active,
            'is_verified' => $company->is_verified,
            'payroll_enabled' => $company->payroll_enabled,
            'kyc_status' => $company->kyc_status,
            'kyc_status_label' => $company->kyc_status_label,
            'kyc_status_color' => $company->kyc_status_color,
            'kyc_rejection_reason' => $company->kyc_rejection_reason,
            'documents' => $company->relationLoaded('documents')
                ? $company->documents->map(fn ($d) => $this->transformDoc($d))->values()
                : [],
        ];
    }

    private function transformDoc(CompanyDocument $doc): array
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
