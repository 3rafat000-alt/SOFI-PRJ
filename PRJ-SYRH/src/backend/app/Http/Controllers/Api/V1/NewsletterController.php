<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Handles newsletter subscription.
 *
 * Security controls:
 * - Email is validated as RFC-compliant before insert.
 * - Uses updateOrInsert (upsert) to prevent duplicate rows without exposing
 *   whether the email already exists (no user enumeration).
 * - Throttle (5/min per IP) enforced at route level.
 * - No mass-assignment: only whitelisted columns written to DB.
 *
 * Note: this stores directly via DB::table() because a full Newsletter
 * Eloquent model is out-of-scope for this ticket; the data-schema-engineer
 * will provide the newsletters table migration. The column assumed is:
 * newsletters(id, email, subscribed_at, created_at, updated_at).
 */
class NewsletterController extends Controller
{
    /**
     * Subscribe an email address to the newsletter.
     *
     * POST /api/v1/newsletter
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:254'],
        ]);

        DB::table('newsletters')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'subscribed_at' => now(),
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        return response()->json([
            'message' => 'شكراً! تم اشتراكك في النشرة البريدية بنجاح.',
        ], 201);
    }
}
