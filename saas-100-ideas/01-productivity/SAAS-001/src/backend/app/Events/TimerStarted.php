<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TimeEntry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimerStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TimeEntry $timeEntry;

    public function __construct(TimeEntry $timeEntry)
    {
        $this->timeEntry = $timeEntry;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user.'.$this->timeEntry->user_id);
    }

    public function broadcastAs(): string
    {
        return 'TimerStarted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'time_entry' => $this->timeEntry->toArray(),
        ];
    }
}
