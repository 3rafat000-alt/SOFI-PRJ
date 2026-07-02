<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Events\MemberJoined;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Events\TimerStarted;
use App\Events\TimerStopped;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogActivity
{
    /**
     * Log task created.
     */
    public function handleTaskCreated(TaskCreated $event): void
    {
        ActivityLog::create([
            'user_id' => $event->task->creator_id,
            'subject_type' => get_class($event->task),
            'subject_id' => $event->task->id,
            'description' => "Created task '{$event->task->title}'",
            'event' => 'task.created',
            'properties' => [
                'project_id' => $event->task->project_id,
                'title' => $event->task->title,
                'priority' => $event->task->priority,
            ],
        ]);
    }

    /**
     * Log task updated.
     */
    public function handleTaskUpdated(TaskUpdated $event): void
    {
        ActivityLog::create([
            'user_id' => Request::user()?->id,
            'subject_type' => get_class($event->task),
            'subject_id' => $event->task->id,
            'description' => "Updated task '{$event->task->title}'",
            'event' => 'task.updated',
            'properties' => [
                'changed' => $event->changed,
                'project_id' => $event->task->project_id,
            ],
        ]);
    }

    /**
     * Log task deleted.
     */
    public function handleTaskDeleted(TaskDeleted $event): void
    {
        ActivityLog::create([
            'user_id' => Request::user()?->id,
            'subject_type' => \App\Models\Task::class,
            'subject_id' => $event->taskId,
            'description' => 'Deleted task',
            'event' => 'task.deleted',
            'properties' => [
                'project_id' => $event->projectId,
            ],
        ]);
    }

    /**
     * Log comment added.
     */
    public function handleCommentAdded(CommentAdded $event): void
    {
        ActivityLog::create([
            'user_id' => $event->comment->user_id,
            'subject_type' => get_class($event->comment),
            'subject_id' => $event->comment->id,
            'description' => 'Added comment on task',
            'event' => 'comment.created',
            'properties' => [
                'task_id' => $event->comment->task_id,
                'body_preview' => mb_substr($event->comment->body, 0, 100),
            ],
        ]);
    }

    /**
     * Log timer started.
     */
    public function handleTimerStarted(TimerStarted $event): void
    {
        ActivityLog::create([
            'user_id' => $event->timeEntry->user_id,
            'subject_type' => get_class($event->timeEntry),
            'subject_id' => $event->timeEntry->id,
            'description' => 'Started timer',
            'event' => 'timer.started',
            'properties' => [
                'task_id' => $event->timeEntry->task_id,
            ],
        ]);
    }

    /**
     * Log timer stopped.
     */
    public function handleTimerStopped(TimerStopped $event): void
    {
        ActivityLog::create([
            'user_id' => $event->timeEntry->user_id,
            'subject_type' => get_class($event->timeEntry),
            'subject_id' => $event->timeEntry->id,
            'description' => 'Stopped timer',
            'event' => 'timer.stopped',
            'properties' => [
                'task_id' => $event->timeEntry->task_id,
                'duration_minutes' => $event->timeEntry->duration_minutes,
            ],
        ]);
    }

    /**
     * Log member joined.
     */
    public function handleMemberJoined(MemberJoined $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'subject_type' => get_class($event->workspace),
            'subject_id' => $event->workspace->id,
            'description' => "Member {$event->user->name} joined with role {$event->role}",
            'event' => 'member.joined',
            'properties' => [
                'role' => $event->role,
            ],
        ]);
    }
}
