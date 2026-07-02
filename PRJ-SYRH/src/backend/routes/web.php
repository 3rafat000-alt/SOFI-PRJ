<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Return the React SPA entry point.
 */
if (!function_exists('serveSpa')) {
function serveSpa(): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
{
    $spaPath = base_path('../frontend/dist/index.html');

    if (file_exists($spaPath)) {
        return response()->file($spaPath);
    }

    // Fallback to Laravel welcome view if SPA not built yet
    return response()->view('welcome');
}
}

// Install wizard — serves SPA for all install paths
Route::get('/install/{path?}', function () {
    return serveSpa();
})->where('path', '.*');

// SAKK mock checkout page (dev only — simulates payment gateway)
Route::get('/sakk/checkout', function (\Illuminate\Http\Request $req) {
    $tid = $req->query('transaction_id', 'SAKK-XXXX');
    $ref = $req->query('reference_id', '');
    $amount = $req->query('amount', '0');
    $currency = $req->query('currency', 'USD');

    if ($req->query('confirm') === '1' && $ref) {
        try {
            \Illuminate\Support\Facades\Http::post(url('/api/v1/sakk/webhook'), [
                'event'          => 'payment_request.paid',
                'uuid'           => $tid,
                'reference_id'   => $ref,
                'status'         => 'paid',
                'amount'         => (float) $amount,
                'currency'       => $currency,
                'note'           => 'Mock subscription payment',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Mock checkout webhook call failed', ['error' => $e->getMessage()]);
        }

        return redirect('/agency/subscription?paid=1');
    }

    return response()->view('sakk-mock-checkout', compact('tid', 'ref', 'amount', 'currency'));
})->name('sakk.mock-checkout');

// Serve storage files through Laravel (fallback when static symlink missing on production)
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        // Generate placeholder SVG for agency logos
        if (preg_match('#^agencies/.+\.svg$#', $path)) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            $displayName = str_replace(['-', '_'], ' ', $name);
            $initial = mb_strtoupper(mb_substr(trim($displayName), 0, 1)) ?: '?';
            return response()->make(
                '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'
                . '<rect width="200" height="200" fill="#1a6b3c" rx="20"/>'
                . '<text x="100" y="120" font-size="80" font-family="sans-serif" fill="white" text-anchor="middle" font-weight="bold">'
                . $initial
                . '</text></svg>',
                200,
                ['Content-Type' => 'image/svg+xml']
            );
        }
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*');

// React SPA catch-all — all non-API routes serve the frontend
Route::get('/{path?}', function () {
    return serveSpa();
})->where('path', '.*');
