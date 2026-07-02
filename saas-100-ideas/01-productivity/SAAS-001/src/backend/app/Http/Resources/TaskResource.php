<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class TaskResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->whenLoaded('project', fn () => $this->project->name),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'position' => $this->position,
            'assignee' => $this->whenLoaded('assignees', fn () => $this->assignees->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'avatar_url' => $a->avatar ? url('storage/'.$a->avatar) : null,
            ])->first()),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'due_date' => $this->due_date?->toIso8601String(),
            'estimated_minutes' => $this->estimated_minutes,
            'logged_minutes' => $this->whenLoaded('timeEntries', fn () => (int) $this->timeEntries->sum('duration_minutes')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
            ])),
            'comments_count' => $this->whenCounted('comments', fn () => $this->comments_count),
            'attachments_count' => $this->whenCounted('attachments', fn () => $this->attachments_count),
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
