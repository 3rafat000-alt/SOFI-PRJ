<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gated streaming of sensitive KYC / partner identity documents.
 *
 * Identity documents (national ID, passport, driver's licence, selfies, proof of
 * address) live on the PRIVATE 'local' disk (storage/app/private) and are NEVER
 * exposed through the public storage symlink. This controller is the single
 * authorised egress: admin views build links with
 *   route('admin.secure-file', ['path' => encrypt($relativeStoragePath)])
 * and this action decrypts, validates and streams the file.
 *
 * The route is already registered behind ['auth','admin'] (routes/web.php). We
 * re-assert the admin ability in-controller (defence in depth — a future route
 * change must not silently de-gate identity PII) and constrain the served path to
 * the known document directories so this endpoint can never be coerced into an
 * arbitrary-file reader for anything else under storage/app/private.
 */
class SecureFileController extends Controller
{
    /**
     * Relative-path prefixes this endpoint is permitted to serve.
     *
     * Mirrors every place identity documents are written:
     *  - KycService            -> "kyc/{userId}/{id|selfie|address}/..."
     *  - Admin\KycController    -> "kyc-documents/..."
     *  - API\PartnerApplication -> "partner-documents/..."
     */
    private const ALLOWED_PREFIXES = [
        'kyc/',
        'kyc-documents/',
        'partner-documents/',
        'merchant-documents/',
        'agent-documents/',
        'company-documents/',
    ];

    public function show(Request $request): Response
    {
        // Defence in depth: hard-require the admin ability even though the route
        // already carries the 'admin' middleware. abort(403) on failure.
        AdminMiddleware::authorize('secure-file.view');

        $encrypted = $request->query('path');
        if (!is_string($encrypted) || $encrypted === '') {
            abort(404);
        }

        // The link is built with encrypt($relativePath); a missing/tampered payload
        // is treated as a 403 (and logged) -- not silently ignored.
        try {
            $path = decrypt($encrypted);
        } catch (DecryptException $e) {
            Log::warning('SecureFile: undecryptable path payload', [
                'admin_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);
            abort(403);
        }

        if (!is_string($path) || !$this->isSafeRelativePath($path)) {
            Log::warning('SecureFile: rejected unsafe document path', [
                'admin_id' => auth()->id(),
                'ip' => $request->ip(),
                'path' => is_string($path) ? $path : gettype($path),
            ]);
            abort(403);
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($path)) {
            abort(404);
        }

        // Serve inline from the private disk. Identity documents are small
        // (ID/passport scans, selfies, short PDFs), so an in-memory Illuminate
        // response is fine and exposes the full response API to callers. nosniff
        // guards against MIME confusion on the served bytes.
        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * A path is safe only when it is a clean relative key under one of the known
     * document directories: no traversal (..), no null byte, no backslash, not
     * absolute, and prefix-allowlisted.
     */
    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '\\')) {
            return false;
        }

        // Reject absolute paths (leading '/') and stream/scheme wrappers.
        if (str_starts_with($path, '/') || preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $path) === 1) {
            return false;
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
