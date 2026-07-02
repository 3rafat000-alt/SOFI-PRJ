<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Match a user's phone contacts against registered SAKK users so they can
 * transfer to people they know. Privacy-preserving: only returns matches,
 * never reveals which of the submitted numbers exist beyond the matched set.
 */
class ContactController extends Controller
{
    /** How many trailing digits to compare (ignores country-code differences). */
    private const MATCH_DIGITS = 9;

    public function match(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phones' => 'required|array|max:1000',
            'phones.*' => 'string|max:32',
        ], [
            'phones.required' => 'قائمة الأرقام مطلوبة.',
        ]);

        $me = $request->user();

        // Normalize submitted phones -> suffix (last N digits) => original input.
        $suffixToInput = [];
        foreach ($validated['phones'] as $raw) {
            $digits = preg_replace('/\D+/', '', $raw);
            if ($digits === '' || strlen($digits) < 6) {
                continue;
            }
            $suffix = substr($digits, -self::MATCH_DIGITS);
            $suffixToInput[$suffix] = $raw;
        }

        if (empty($suffixToInput)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Pull candidate users that have a phone, then match by suffix in PHP
        // (portable across SQLite/MySQL without RIGHT() functions).
        $matches = [];
        User::whereNotNull('phone')
            ->where('id', '!=', $me->id)
            ->select('id', 'first_name', 'last_name', 'phone')
            ->chunk(500, function ($users) use (&$matches, $suffixToInput) {
                foreach ($users as $user) {
                    $digits = preg_replace('/\D+/', '', (string) $user->phone);
                    if ($digits === '') {
                        continue;
                    }
                    $suffix = substr($digits, -self::MATCH_DIGITS);
                    if (isset($suffixToInput[$suffix])) {
                        $matches[] = [
                            'phone' => $suffixToInput[$suffix], // echo back the contact's original input
                            'name' => trim("{$user->first_name} {$user->last_name}"),
                            'initials' => mb_strtoupper(mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1)),
                            'account_number' => 'SK' . str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
                        ];
                    }
                }
            });

        return response()->json(['success' => true, 'data' => $matches]);
    }
}
