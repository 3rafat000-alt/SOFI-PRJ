<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Services\PartnerApprovalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = CompanyDocument::with('company')->orderBy('created_at', 'desc');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->get('document_type')) {
            $query->where('document_type', $type);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('company', fn ($c) => $c->where('name', 'like', "%{$search}%")
                    ->orWhere('company_code', 'like', "%{$search}%"))
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $documents = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => CompanyDocument::count(),
            'pending' => CompanyDocument::where('status', 'pending')->count(),
            'approved' => CompanyDocument::where('status', 'approved')->count(),
            'rejected' => CompanyDocument::where('status', 'rejected')->count(),
        ];

        return view('admin.companies.documents', compact('documents', 'stats'));
    }

    public function approve(CompanyDocument $document): RedirectResponse
    {
        $document->update([
            'status' => 'approved',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $company = $document->company;
        $pending = $company->documents()->where('status', 'pending')->count();
        $rejected = $company->documents()->where('status', 'rejected')->count();

        // All docs cleared → activate the company AND unlock payroll.
        if ($pending === 0 && $rejected === 0) {
            $company->forceFill([
                'kyc_status' => 'approved',
                'kyc_approved_at' => now(),
                'is_verified' => true,
                'payroll_enabled' => true,
            ])->save();

            (new PartnerApprovalNotifier())->notifyCompany($company->fresh());
        }

        return redirect()->back()->with('success', 'تم اعتماد المستند بنجاح');
    }

    public function reject(Request $request, CompanyDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $document->company->forceFill([
            'kyc_status' => 'documents_required',
            'kyc_rejection_reason' => 'هناك مستندات مرفوضة: ' . $validated['rejection_reason'],
            // Safety: a rejection re-locks payroll until re-approved.
            'payroll_enabled' => false,
        ])->save();

        return redirect()->back()->with('success', 'تم رفض المستند');
    }

    public function show(Company $company): View
    {
        $documents = $company->documents()->orderBy('created_at', 'desc')->get();

        return view('admin.companies.documents-show', compact('company', 'documents'));
    }
}
