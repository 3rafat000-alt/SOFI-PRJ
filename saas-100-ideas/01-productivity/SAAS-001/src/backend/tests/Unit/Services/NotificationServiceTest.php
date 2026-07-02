<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(NotificationService::class);
        $this->user = User::factory()->create();
    }

    public function test_it_creates_notification(): void
    {
        $notification = $this->service->create([
            'user_id' => $this->user->id,
            'type' => 'task_assigned',
            'data' => ['task_id' => 'test-uuid', 'title' => 'Test'],
        ]);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals('task_assigned', $notification->type);
    }

    public function test_it_marks_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $this->service->markAsRead($notification);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_it_marks_all_as_read(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $count = $this->service->markAllAsRead($this->user);

        $this->assertEquals(3, $count);
        $this->assertEquals(0, Notification::byUser($this->user->id)->unread()->count());
    }

    public function test_it_counts_unread(): void
    {
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        $count = $this->service->unreadCount($this->user);

        $this->assertEquals(2, $count);
    }

    public function test_it_lists_with_filters(): void
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'type' => 'task_assigned',
        ]);
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'mention',
        ]);

        $result = $this->service->list($this->user, ['type' => 'mention', 'per_page' => 10]);

        $this->assertEquals(3, $result->total());
    }
}
