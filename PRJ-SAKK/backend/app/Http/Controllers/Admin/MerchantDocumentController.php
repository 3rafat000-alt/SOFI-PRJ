<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantDocument;
use App\Services\PartnerApprovalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MerchantDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = MerchantDocument::with('merchant')
            ->orderBy('created_at', 'desc');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->get('document_type')) {
            $query->where('document_type', $type);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('merchant', fn($m) => $m->where('store_name', 'like', "%{$search}%")
                    ->orWhere('merchant_code', 'like', "%{$search}%"))
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $documents = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => MerchantDocument::count(),
            'pending' => MerchantDocument::where('status', 'pending')->count(),
            'approved' => MerchantDocument::where('status', 'approved')->count(),
            'rejected' => MerchantDocument::where('status', 'rejected')->count(),
        ];

        return view('admin.merchants.documents', compact('documents', 'stats'));
    }

    public function approve(MerchantDocument $document): RedirectResponse
    {
        $document->update([
            'status' => 'approved',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $merchant = $document->merchant;
        $pending = $merchant->documents()->where('status', 'pending')->count();
        $rejected = $merchant->documents()->where('status', 'rejected')->count();

        if ($pending === 0 && $rejected === 0) {
            // kyc_approved_at + verified_at are guarded on Merchant — forceFill or dropped.
            $merchant->forceFill([
                'kyc_status' => 'approved',
                'kyc_approved_at' => now(),
                'is_verified' => true,
                'verified_at' => now(),
            ])->save();

            (new PartnerApprovalNotifier())->notifyMerchant($merchant->fresh());
        }

        return redirect()->back()->with('success', 'تم اعتماد المستند بنجاح');
    }

    public function reject(Request $request, MerchantDocument $document): RedirectResponse
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

        // kyc_rejection_reason is guarded on Merchant — forceFill or dropped.
        $document->merchant->forceFill([
            'kyc_status' => 'documents_required',
            'kyc_rejection_reason' => 'هناك مستندات مرفوضة: ' . $validated['rejection_reason'],
        ])->save();

        return redirect()->back()->with('success', 'تم رفض المستند');
    }

    public function show(Merchant $merchant): View
    {
        $documents = $merchant->documents()->orderBy('created_at', 'desc')->get();
        return view('admin.merchants.documents-show', compact('merchant', 'documents'));
    }
}
