<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\VerifiesTransactionAuth;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QrAuthController extends Controller
{
    use VerifiesTransactionAuth;

    private const TTL = 300;

    public function generate(): JsonResponse
    {
        $token = Str::uuid()->toString();

        Cache::put("qr_auth:{$token}", [
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
        ], self::TTL);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'expires_in' => self::TTL,
                'qr_data' => $token,
            ],
        ]);
    }

    public function poll(string $token): JsonResponse
    {
        $data = Cache::get("qr_auth:{$token}");

        if (!$data) {
            return response()->json([
                'success' => true,
                'data' => ['status' => 'expired'],
            ]);
        }

        if ($data['status'] === 'approved') {
            Cache::forget("qr_auth:{$token}");

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                    'token' => $data['auth_token'],
                    'token_type' => 'Bearer',
                    'user' => $data['user'],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => ['status' => $data['status']],
        ]);
    }

    public function approve(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'pin' => 'required_without:biometric_token|string',
        ], [
            'token.required' => 'الرمز مطلوب.',
        ]);

        $token = $request->token;
        $data = Cache::get("qr_auth:{$token}");

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'رمز QR منتهي الصلاحية أو غير صالح',
            ], 422);
        }

        if ($data['status'] !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'رمز QR مستخدم مسبقاً',
            ], 422);
        }

        $user = $request->user();

        // SEC M6: approving a QR mints a live session token (a sensitive grant).
        // Require a second factor so a phished or attacker-generated QR can't be
        // authorised by a single careless tap.
        if (!$this->verifyTransactionFactor($request, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق الأمني — رمز PIN أو البصمة مطلوب للموافقة.',
                'code' => 'transaction_auth_required',
            ], 422);
        }
        $authToken = $user->createToken('qr_auth', [
            'payment-request:accept', 'payment-request:read',
        ])->plainTextToken;

        Cache::put("qr_auth:{$token}", [
            'status' => 'approved',
            'auth_token' => $authToken,
            'user' => new UserResource($user->load(['wallets', 'cards'])),
            'approved_at' => now()->toIso8601String(),
        ], self::TTL);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على تسجيل الدخول',
        ]);
    }
}
