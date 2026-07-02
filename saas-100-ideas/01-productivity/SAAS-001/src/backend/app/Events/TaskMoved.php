<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Task $task;
    public string $oldStatus;
    public string $newStatus;
    public int $oldPosition;
    public int $newPosition;

    public function __construct(Task $task, string $oldStatus, string $newStatus, int $oldPosition, int $newPosition)
    {
        $this->task = $task;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->oldPosition = $oldPosition;
        $this->newPosition = $newPosition;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('project.'.$this->task->project_id);
    }

    public function broadcastAs(): string
    {
        return 'TaskMoved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task' => $this->task->toArray(),
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'old_position' => $this->oldPosition,
            'new_position' => $this->newPosition,
        ];
    }
}
