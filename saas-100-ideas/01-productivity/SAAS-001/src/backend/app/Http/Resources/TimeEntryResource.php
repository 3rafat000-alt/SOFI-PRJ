<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimeEntry */
class TimeEntryResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'task_title' => $this->whenLoaded('task', fn () => $this->task->title),
            'project_name' => $this->whenLoaded('task.project', fn () => $this->task->project->name),
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'is_running' => $this->is_running,
            'notes' => $this->notes,
            'is_manual' => $this->is_manual ?? false,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
