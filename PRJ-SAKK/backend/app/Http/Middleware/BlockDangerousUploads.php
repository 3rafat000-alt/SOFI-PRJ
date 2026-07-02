<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockDangerousUploads
{
    private const BLOCKED_MIMES = [
        'image/svg+xml',
        'text/html',
        'application/x-php',
    ];

    public function handle(Request $request, Closure $next)
    {
        foreach ($request->allFiles() as $field => $file) {
            $files = is_array($file) ? $file : [$file];
            foreach ($files as $f) {
                if (in_array($f->getMimeType(), self::BLOCKED_MIMES)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'نوع الملف غير مسموح به',
                    ], 400);
                }
            }
        }

        return $next($request);
    }
}
