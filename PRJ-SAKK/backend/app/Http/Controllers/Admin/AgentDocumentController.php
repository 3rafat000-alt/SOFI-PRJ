<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Services\PartnerApprovalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = AgentDocument::with('agent')
            ->orderBy('created_at', 'desc');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->get('document_type')) {
            $query->where('document_type', $type);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('agent', fn($a) => $a->where('name', 'like', "%{$search}%")
                    ->orWhere('agent_code', 'like', "%{$search}%"))
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $documents = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => AgentDocument::count(),
            'pending' => AgentDocument::where('status', 'pending')->count(),
            'approved' => AgentDocument::where('status', 'approved')->count(),
            'rejected' => AgentDocument::where('status', 'rejected')->count(),
        ];

        return view('admin.agents.documents', compact('documents', 'stats'));
    }

    public function approve(AgentDocument $document): RedirectResponse
    {
        $document->update([
            'status' => 'approved',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $agent = $document->agent;
        $pending = $agent->documents()->where('status', 'pending')->count();
        $rejected = $agent->documents()->where('status', 'rejected')->count();

        if ($pending === 0 && $rejected === 0) {
            // kyc_approved_at + is_verified are guarded on Agent — forceFill or dropped.
            $agent->forceFill([
                'kyc_status' => 'approved',
                'kyc_approved_at' => now(),
                'is_verified' => true,
            ])->save();

            (new PartnerApprovalNotifier())->notifyAgent($agent->fresh());
        }

        return redirect()->back()->with('success', 'تم اعتماد المستند بنجاح');
    }

    public function reject(Request $request, AgentDocument $document): RedirectResponse
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

        // kyc_rejection_reason is guarded on Agent — forceFill or dropped.
        $document->agent->forceFill([
            'kyc_status' => 'documents_required',
            'kyc_rejection_reason' => 'هناك مستندات مرفوضة: ' . $validated['rejection_reason'],
        ])->save();

        return redirect()->back()->with('success', 'تم رفض المستند');
    }

    public function show(Agent $agent): View
    {
        $documents = $agent->documents()->orderBy('created_at', 'desc')->get();
        return view('admin.agents.documents-show', compact('agent', 'documents'));
    }
}
