<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    public Workspace $workspace;

    public string $role;

    public function __construct(User $user, Workspace $workspace, string $role)
    {
        $this->user = $user;
        $this->workspace = $workspace;
        $this->role = $role;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('workspace.'.$this->workspace->id);
    }

    public function broadcastAs(): string
    {
        return 'MemberJoined';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar_url' => $this->user->avatar,
            ],
            'role' => $this->role,
        ];
    }
}
