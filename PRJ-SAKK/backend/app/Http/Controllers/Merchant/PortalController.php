<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantDocument;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $merchant = $request->attributes->get('merchant');

        return view('merchant.dashboard', compact('merchant'));
    }

    public function profile(Request $request)
    {
        $merchant = $request->attributes->get('merchant');

        return view('merchant.profile', compact('merchant'));
    }

    public function regenerateKeys(Request $request)
    {
        $merchant = $request->attributes->get('merchant');
        $merchant->regenerateApiKey(); // model method (forceFills new key/secret)

        return back()->with('success', 'تم تجديد مفاتيح الـAPI.');
    }

    public function documents(Request $request)
    {
        $merchant = $request->attributes->get('merchant');
        $documents = $merchant->documents()->latest()->get();

        return view('merchant.documents', [
            'merchant' => $merchant,
            'documents' => $documents,
            'documentTypes' => MerchantDocument::TYPES,
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $merchant = $request->attributes->get('merchant');

        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:80',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        // SEC H5: identity docs live on the private disk, served only via SecureFileController.
        $path = $request->file('file')->store('merchant-documents', 'private');
        $file = $request->file('file');

        MerchantDocument::create([
            'merchant_id' => $merchant->id,
            'document_type' => $validated['document_type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'document_number' => $validated['document_number'] ?? null,
            'status' => 'pending',
        ]);

        if (in_array($merchant->kyc_status, ['documents_required', 'rejected'], true)) {
            $merchant->update(['kyc_status' => 'pending']);
        }

        return back()->with('success', 'تم رفع المستند بنجاح.');
    }
}
