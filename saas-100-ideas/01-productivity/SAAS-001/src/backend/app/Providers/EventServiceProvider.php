<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CommentAdded;
use App\Events\MemberJoined;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Events\TimerStarted;
use App\Events\TimerStopped;
use App\Listeners\BroadcastToWebhooks;
use App\Listeners\LogActivity;
use App\Listeners\SendTaskAssignmentNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        TaskCreated::class => [
            SendTaskAssignmentNotification::class.'@handleTaskCreated',
            LogActivity::class.'@handleTaskCreated',
            BroadcastToWebhooks::class,
        ],
        TaskUpdated::class => [
            SendTaskAssignmentNotification::class.'@handleTaskUpdated',
            LogActivity::class.'@handleTaskUpdated',
            BroadcastToWebhooks::class,
        ],
        TaskDeleted::class => [
            LogActivity::class.'@handleTaskDeleted',
            BroadcastToWebhooks::class,
        ],
        CommentAdded::class => [
            LogActivity::class.'@handleCommentAdded',
            BroadcastToWebhooks::class,
        ],
        TimerStarted::class => [
            LogActivity::class.'@handleTimerStarted',
        ],
        TimerStopped::class => [
            LogActivity::class.'@handleTimerStopped',
        ],
        MemberJoined::class => [
            LogActivity::class.'@handleMemberJoined',
            BroadcastToWebhooks::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
