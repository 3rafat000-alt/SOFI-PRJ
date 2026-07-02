<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\AgencyRegisterRequest;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['password'] = Hash::make($validated['password']);
        $validated['locale'] ??= 'ar';

        $user = User::create($validated);
        $user->syncRoles('visitor');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user'  => $user->load('roles'),
                'token' => $token,
            ],
        ], 201);
    }

    public function agencyRegister(AgencyRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Create owner user
        $user = User::create([
            'name'     => $validated['owner_name'],
            'email'    => $validated['owner_email'],
            'phone'    => $validated['owner_phone'],
            'password' => Hash::make($validated['password']),
            'locale'   => $request->input('locale', 'ar'),
        ]);
        $user->syncRoles('agency');

        // Create agency
        $slug = Str::slug($validated['agency_name']) . '-' . Str::random(6);
        $agency = Agency::create([
            'name'        => $validated['agency_name'],
            'slug'        => $slug,
            'license_no'  => $validated['license_no'] ?? null,
            'email'       => $validated['agency_email'] ?? null,
            'phone'       => $validated['agency_phone'] ?? null,
            'whatsapp'    => $validated['whatsapp'] ?? null,
            'address'     => $validated['address'] ?? null,
            'owner_id'    => $user->id,
            'status'      => 'pending',
        ]);

        // Link user → agency
        $user->agency_id = $agency->id;
        $user->save();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user'   => $user->load('roles'),
                'agency' => $agency,
                'token'  => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => trans('auth.failed'),
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user'  => $user->load('roles'),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => trans('auth.logout_success')]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load(['roles', 'agent', 'agency', 'favorites', 'savedSearches']),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $user->fresh(),
            'message' => trans('auth.profile_updated'),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Delete old avatar file if it's a local storage path
        $oldPath = $user->avatar_url;
        if ($oldPath && str_starts_with($oldPath, '/storage/avatars/')) {
            $relative = str_replace('/storage/', '', $oldPath);
            if (Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            }
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $url = '/storage/' . $path;

        $user->update(['avatar_url' => $url]);

        return response()->json([
            'success' => true,
            'data'    => $user->fresh()->load('roles'),
            'message' => trans('auth.avatar_uploaded'),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['success' => true, 'message' => trans('auth.password_changed')]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            $message = $status === Password::RESET_THROTTLED
                ? trans('passwords.throttled')
                : trans('passwords.user');

            throw ValidationException::withMessages([
                'email' => [$message],
            ]);
        }

        return response()->json(['success' => true, 'message' => trans('auth.password_reset_sent')]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $status = Password::reset(
            [
                'email'                 => $validated['email'],
                'password'              => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
                'token'                 => $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])
                    ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $message = $status === Password::INVALID_TOKEN
                ? trans('passwords.token')
                : trans('passwords.user');

            throw ValidationException::withMessages([
                'email' => [$message],
            ]);
        }

        return response()->json(['success' => true, 'message' => trans('auth.password_reset_done')]);
    }
}
