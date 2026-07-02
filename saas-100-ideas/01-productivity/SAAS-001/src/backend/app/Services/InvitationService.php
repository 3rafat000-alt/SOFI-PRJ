<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\MemberJoined;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationService
{
    /**
     * Invite a member to a workspace.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function invite(Workspace $workspace, array $data): array
    {
        $email = $data['email'];
        $role = $data['role'] ?? 'member';

        // Check if user already exists and is a member
        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $workspace->members()->where('user_id', $existingUser->id)->exists()) {
            return [
                'message' => 'User is already a member of this workspace.',
                'invitation' => null,
            ];
        }

        $token = Str::random(32);
        $expiresAt = Carbon::now()->addDays(7);

        // Store invitation in activity log as pending invite
        // In production, you'd have an invitations table
        DB::table('activity_logs')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => null,
            'subject_type' => Workspace::class,
            'subject_id' => $workspace->id,
            'description' => "Invitation sent to {$email} with role {$role}",
            'event' => 'invitation.sent',
            'properties' => json_encode([
                'email' => $email,
                'role' => $role,
                'token' => $token,
                'expires_at' => $expiresAt->toIso8601String(),
                'channel' => $data['channel'] ?? 'email',
                'message' => $data['message'] ?? null,
            ]),
            'created_at' => now(),
        ]);

        // If user exists, auto-accept and add to workspace
        if ($existingUser) {
            $workspace->members()->attach($existingUser->id, [
                'role' => $role,
                'joined_at' => now(),
            ]);

            broadcast(new MemberJoined($existingUser, $workspace, $role))->toOthers();

            NotificationService::createNotification(
                userId: $existingUser->id,
                type: 'invite.accepted',
                data: [
                    'workspace_id' => $workspace->id,
                    'workspace_name' => $workspace->name,
                    'role' => $role,
                ],
            );
        }

        return [
            'invitation' => [
                'email' => $email,
                'role' => $role,
                'status' => $existingUser ? 'accepted' : 'pending',
                'expires_at' => $expiresAt->toIso8601String(),
                'channel' => $data['channel'] ?? 'email',
            ],
            'message' => 'Invitation sent successfully.',
        ];
    }

    /**
     * Create a notification record (static helper).
     *
     * @param  array<string, mixed>  $data
     */
    public static function createNotification(string $userId, string $type, array $data): void
    {
        \App\Models\Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'read_at' => null,
        ]);
    }
}
