<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\VerifiesTransactionAuth;
use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class BiometricController extends Controller
{
    use VerifiesTransactionAuth;


    /**
     * Enroll a biometric public key on an ALREADY-TRUSTED device.
     *
     * SEC C3: this endpoint previously did `updateOrCreate(..., ['is_trusted' => true])`,
     * which silently created a brand-new device and marked it trusted — bypassing the
     * entire device-approval flow (pending state + 48h transaction hold in
     * DeviceController::register/approve). An attacker with a session token could
     * register their own device + public key and transact immediately.
     *
     * Biometric enrollment now requires a device that has already passed the trust
     * flow (first-device auto-approve, or owner approval from a trusted device). We
     * only attach the public key here — we NEVER establish trust.
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:ios,android',
            'public_key' => 'required|string',
        ], [
            'device_id.required' => 'معرف الجهاز مطلوب.',
            'device_name.required' => 'اسم الجهاز مطلوب.',
            'device_name.max' => 'اسم الجهاز يجب أن لا يتجاوز 255 حرف.',
            'device_type.required' => 'نوع الجهاز مطلوب.',
            'device_type.in' => 'نوع الجهاز يجب أن يكون ios أو android.',
            'public_key.required' => 'المفتاح العام مطلوب.',
        ]);

        $user = $request->user();

        $device = $user->devices()->where('device_id', $request->device_id)->first();

        // Device must already exist (registered via DeviceController::register).
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الجهاز أولاً قبل تفعيل البصمة.',
                'code' => 'device_not_registered',
            ], 422);
        }

        // ...and must already be trusted/approved. We do not grant trust here.
        $isApproved = $device->is_trusted === true
            && (!isset($device->status) || $device->status === Device::STATUS_APPROVED);

        if (!$isApproved) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الجهاز غير موثوق بعد. وافق عليه من جهاز موثوق ثم فعّل البصمة.',
                'code' => 'device_not_trusted',
            ], 403);
        }

        // Attach only the biometric public key + metadata; trust is untouched.
        $device->update([
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'public_key' => $request->public_key,
            'last_used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل البصمة على الجهاز بنجاح',
            'data' => [
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
            ],
        ]);
    }

    public function getDevices(Request $request)
    {
        $devices = $request->user()->devices()
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'device_type' => $device->device_type,
                    'is_trusted' => $device->is_trusted,
                    'last_used_at' => $device->last_used_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    public function removeDevice(Request $request, $deviceId)
    {
        $deleted = $request->user()->devices()
            ->where('id', $deviceId)
            ->delete();

        return response()->json([
            'success' => $deleted ? true : false,
            'message' => $deleted ? 'تم حذف الجهاز' : 'الجهاز غير موجود',
        ]);
    }

    public function challenge(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
        ], [
            'device_id.required' => 'معرف الجهاز مطلوب.',
        ]);

        $device = $request->user()->devices()
            ->where('device_id', $request->device_id)
            ->where('is_trusted', true)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'الجهاز غير معروف',
            ], 404);
        }

        $challenge = bin2hex(random_bytes(32));

        cache()->put('biometric_challenge:' . $request->user()->id, $challenge, now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'data' => [
                'challenge' => $challenge,
            ],
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'signature' => 'required|string',
        ], [
            'device_id.required' => 'معرف الجهاز مطلوب.',
            'signature.required' => 'التوقيع مطلوب.',
        ]);

        $user = $request->user();

        // A fresh, server-issued challenge must exist (issued by challenge(), 5-min TTL).
        // Reject early when it is absent/expired — do NOT consume it here.
        $cached = cache()->get('biometric_challenge:' . $user->id);

        if (!$cached) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الطلب',
            ], 400);
        }

        // Cryptographically verify the signature against the registered device public
        // key over the cached challenge. Fails closed: a forged/invalid signature, an
        // unknown/untrusted device, or a signature over the wrong challenge all return
        // false WITHOUT consuming the challenge or minting a token. On success the trait
        // consumes the challenge (single-use → no replay) and touches the device.
        $verified = $this->verifyBiometricToken(
            $user,
            (string) $request->input('signature'),
            (string) $request->input('device_id'),
        );

        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من البصمة',
            ], 401);
        }

        // Mint a SHORT-LIVED token. An explicit expiresAt is required because
        // config/sanctum.php 'expiration' is null (global tokens never expire);
        // passing it here keeps the biometric token short-lived regardless of config.
        $token = $user->createToken('biometric-auth', ['gold:buy', 'gold:sell'], now()->addMinutes(15))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق بنجاح',
            'data' => [
                'token' => $token,
            ],
        ]);
    }
}
