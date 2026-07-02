<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $taskCounts = $this->relationLoaded('tasks')
            ? $this->tasks->groupBy('status')->map->count()
            : collect();

        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
            'task_count' => [
                'total' => (int) $taskCounts->sum() ?? $this->whenCounted('tasks', fn () => $this->tasks_count),
                'todo' => (int) $taskCounts->get('todo', 0),
                'in_progress' => (int) $taskCounts->get('in_progress', 0),
                'done' => (int) $taskCounts->get('done', 0),
            ],
            'member_count' => $this->whenCounted('workspace.members', fn () => $this->workspace->members_count),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
