<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    /**
     * Handle: auth first, role second.
     *
     * Usage: ->middleware('check.user:admin')
     *        ->middleware('check.user:admin,agent')
     *
     * Maps type strings to User model attributes:
     *   'admin' → is_admin
     *   Otherwise uses value literally as attribute name.
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 401);
        }

        $user = auth()->user();

        foreach ($types as $type) {
            $attribute = match ($type) {
                'admin' => 'is_admin',
                default => $type,
            };

            if ($user->{$attribute} ?? false) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'ممنوع',
        ], 403);
    }
}
