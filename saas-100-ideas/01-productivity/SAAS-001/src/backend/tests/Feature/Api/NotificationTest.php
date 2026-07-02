<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'سارة أحمد',
            'email' => 'sara@example.com',
            'password' => 'SecureP@ss123',
            'password_confirmation' => 'SecureP@ss123',
            'workspace_name' => 'فريق التسويق',
        ]);

        $this->token = $registerResponse->json('data.token');
        $this->user = User::where('email', 'sara@example.com')->first();
    }

    private function withAuth(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function createNotifications(int $count, array $overrides = []): void
    {
        Notification::factory()->count($count)->create(array_merge([
            'user_id' => $this->user->id,
        ], $overrides));
    }

    /** @test */
    public function it_lists_notifications(): void
    {
        $this->createNotifications(5);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'type', 'title', 'body', 'is_read', 'reference_type', 'created_at']],
                'meta' => ['total', 'unread_count'],
            ]);

        $this->assertEquals(5, $response->json('meta.total'));
        $this->assertEquals(5, $response->json('meta.unread_count'));
    }

    /** @test */
    public function it_filters_read_notifications(): void
    {
        $this->createNotifications(3);
        $this->createNotifications(2, ['is_read' => true, 'read_at' => now()]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/notifications?filter=read');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('meta.total'));
        foreach ($response->json('data') as $n) {
            $this->assertTrue($n['is_read']);
        }
    }

    /** @test */
    public function it_filters_unread_notifications(): void
    {
        $this->createNotifications(4);
        $this->createNotifications(1, ['is_read' => true, 'read_at' => now()]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/notifications?filter=unread');

        $response->assertStatus(200);
        $this->assertEquals(4, $response->json('meta.total'));
        foreach ($response->json('data') as $n) {
            $this->assertFalse($n['is_read']);
        }
    }

    /** @test */
    public function it_marks_single_notification_as_read(): void
    {
        $this->createNotifications(1);
        $notification = Notification::where('user_id', $this->user->id)->first();

        $response = $this->withHeaders($this->withAuth())
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    /** @test */
    public function it_marks_all_notifications_as_read(): void
    {
        $this->createNotifications(3);

        $response = $this->withHeaders($this->withAuth())
            ->patchJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJsonPath('data.marked_read_count', 3);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count());
    }

    /** @test */
    public function it_updates_notification_preferences(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->putJson('/api/v1/notifications/preferences', [
                'email_task_assigned' => true,
                'email_comment_added' => false,
                'push_mention' => true,
                'in_app_all' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['email_task_assigned', 'email_comment_added', 'push_mention', 'in_app_all'],
                'meta',
            ]);

        $this->assertTrue($response->json('data.push_mention'));
    }

    /** @test */
    public function it_fails_mark_read_nonexistent_notification(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->patchJson('/api/v1/notifications/00000000-0000-0000-0000-000000000000/read');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_shows_zero_unread_when_empty(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.unread_count', 0);
    }
}
