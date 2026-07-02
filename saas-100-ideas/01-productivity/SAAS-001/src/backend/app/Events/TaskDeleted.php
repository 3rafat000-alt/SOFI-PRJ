<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $taskId;

    public string $projectId;

    public function __construct(string $taskId, string $projectId)
    {
        $this->taskId = $taskId;
        $this->projectId = $projectId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('project.'.$this->projectId);
    }

    public function broadcastAs(): string
    {
        return 'TaskDeleted';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->taskId,
            'project_id' => $this->projectId,
        ];
    }
}
