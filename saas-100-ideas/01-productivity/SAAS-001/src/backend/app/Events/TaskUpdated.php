<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Task $task;

    /** @var array<int, string> */
    public array $changed;

    /**
     * @param  array<int, string>  $changed
     */
    public function __construct(Task $task, array $changed = [])
    {
        $this->task = $task;
        $this->changed = $changed;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('project.'.$this->task->project_id);
    }

    public function broadcastAs(): string
    {
        return 'TaskUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task' => $this->task->toArray(),
            'changed' => $this->changed,
        ];
    }
}
