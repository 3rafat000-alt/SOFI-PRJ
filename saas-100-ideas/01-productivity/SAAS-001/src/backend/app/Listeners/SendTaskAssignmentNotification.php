<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Models\Notification;

class SendTaskAssignmentNotification
{
    /**
     * Handle task created event.
     */
    public function handleTaskCreated(TaskCreated $event): void
    {
        $task = $event->task;

        foreach ($task->assignees as $assignee) {
            if ($assignee->id !== $task->creator_id) {
                Notification::create([
                    'user_id' => $assignee->id,
                    'type' => 'task_assigned',
                    'data' => [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'project_id' => $task->project_id,
                        'project_name' => $task->project->name ?? null,
                        'assigned_by' => $task->creator->name ?? null,
                    ],
                    'read_at' => null,
                ]);
            }
        }
    }

    /**
     * Handle task updated event.
     */
    public function handleTaskUpdated(TaskUpdated $event): void
    {
        if (! in_array('assignees', $event->changed)) {
            return;
        }

        $task = $event->task;

        foreach ($task->assignees as $assignee) {
            Notification::create([
                'user_id' => $assignee->id,
                'type' => 'task_assigned',
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'project_id' => $task->project_id,
                    'project_name' => $task->project->name ?? null,
                    'assigned_by' => $task->creator->name ?? null,
                ],
                'read_at' => null,
            ]);
        }
    }
}
