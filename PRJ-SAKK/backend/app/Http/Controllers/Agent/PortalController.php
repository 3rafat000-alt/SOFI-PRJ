<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentDocument;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $agent = $request->attributes->get('agent');

        return view('agent.dashboard', compact('agent'));
    }

    public function profile(Request $request)
    {
        $agent = $request->attributes->get('agent');

        return view('agent.profile', compact('agent'));
    }

    public function documents(Request $request)
    {
        $agent = $request->attributes->get('agent');
        $documents = $agent->documents()->latest()->get();

        return view('agent.documents', [
            'agent' => $agent,
            'documents' => $documents,
            'documentTypes' => AgentDocument::TYPES,
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $agent = $request->attributes->get('agent');

        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:80',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        // SEC H5: identity docs live on the private disk, served only via SecureFileController.
        $path = $request->file('file')->store('agent-documents', 'private');
        $file = $request->file('file');

        AgentDocument::create([
            'agent_id' => $agent->id,
            'document_type' => $validated['document_type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'document_number' => $validated['document_number'] ?? null,
            'status' => 'pending',
        ]);

        if (in_array($agent->kyc_status, ['documents_required', 'rejected'], true)) {
            $agent->update(['kyc_status' => 'pending']);
        }

        return back()->with('success', 'تم رفع المستند بنجاح.');
    }
}
