<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Events\MemberJoined;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;

class BroadcastToWebhooks
{
    /**
     * Handle and dispatch to matching webhooks.
     *
     * @param  object  $event
     */
    public function handle($event): void
    {
        $mapping = $this->getEventMapping($event);

        if (! $mapping) {
            return;
        }

        [$webhookEventName, $workspaceId, $payload] = $mapping;

        /** @var \Illuminate\Database\Eloquent\Collection<int, Webhook> $webhooks */
        $webhooks = Webhook::active()
            ->byWorkspace($workspaceId)
            ->byEvent($webhookEventName)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $webhookEventName, $payload);
        }
    }

    /**
     * Map event to webhook payload.
     *
     * @return array{string, string, array<string, mixed>}|null
     */
    private function getEventMapping(object $event): ?array
    {
        return match (true) {
            $event instanceof TaskCreated => [
                'task.created',
                $event->task->project->workspace_id,
                [
                    'id' => $event->task->id,
                    'project_id' => $event->task->project_id,
                    'title' => $event->task->title,
                    'status' => $event->task->status,
                    'priority' => $event->task->priority,
                    'assignee' => $event->task->assignees->map(fn ($a) => ['id' => $a->id, 'email' => $a->email]),
                    'due_date' => $event->task->due_date?->toIso8601String(),
                ],
            ],
            $event instanceof TaskUpdated => [
                'task.updated',
                $event->task->project->workspace_id,
                [
                    'id' => $event->task->id,
                    'changes' => $event->changed,
                    'current' => $event->task->toArray(),
                ],
            ],
            $event instanceof TaskDeleted => [
                'task.deleted',
                \App\Models\Project::find($event->projectId)?->workspace_id ?? '',
                [
                    'id' => $event->taskId,
                    'project_id' => $event->projectId,
                ],
            ],
            $event instanceof CommentAdded => [
                'comment.created',
                $event->comment->task->project->workspace_id,
                [
                    'id' => $event->comment->id,
                    'task_id' => $event->comment->task_id,
                    'user_id' => $event->comment->user_id,
                    'body' => $event->comment->body,
                ],
            ],
            $event instanceof MemberJoined => [
                'member.joined',
                $event->workspace->id,
                [
                    'user_id' => $event->user->id,
                    'name' => $event->user->name,
                    'email' => $event->user->email,
                    'role' => $event->role,
                ],
            ],
            default => null,
        };
    }

    /**
     * Send webhook payload to URL.
     *
     * @param  array<string, mixed>  $payload
     */
    private function sendWebhook(Webhook $webhook, string $eventName, array $payload): void
    {
        $timestamp = now()->toIso8601String();
        $data = [
            'event' => $eventName,
            'workspace_id' => $webhook->workspace_id,
            'timestamp' => $timestamp,
            'data' => $payload,
        ];

        if ($webhook->secret) {
            $signature = hash_hmac('sha256', $eventName.$timestamp.json_encode($payload), $webhook->secret);
            $data['signature'] = 'sha256='.$signature;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhook->url, $data);

            $webhook->update([
                'last_sent_at' => now(),
                'last_status_code' => $response->status(),
                'last_response' => mb_substr($response->body(), 0, 1000),
            ]);
        } catch (\Exception $e) {
            $webhook->update([
                'last_sent_at' => now(),
                'last_status_code' => 0,
                'last_response' => $e->getMessage(),
            ]);
        }
    }
}
