<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private $workspace;

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
        $this->workspace = $this->user->workspaces()->first();
    }

    private function withAuth(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    /** @test */
    public function it_creates_webhook(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/webhooks', [
                'name' => 'Slack Notifier',
                'url' => 'https://hooks.slack.com/services/T00/B00/xxx',
                'events' => ['task.created', 'task.updated'],
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'url', 'events', 'is_active', 'secret', 'last_triggered_at', 'created_at'],
                'meta',
            ]);

        $this->assertEquals('Slack Notifier', $response->json('data.name'));
        $this->assertNotNull($response->json('data.secret'));
    }

    /** @test */
    public function it_fails_create_without_url(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/webhooks', [
                'name' => 'Broken',
                'events' => ['task.created'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function it_fails_create_invalid_event(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/webhooks', [
                'name' => 'Bad Webhook',
                'url' => 'https://example.com/hook',
                'events' => ['invalid.event.name'],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_lists_webhooks(): void
    {
        Webhook::factory()->count(3)->create(['workspace_id' => $this->workspace->id, 'creator_id' => $this->user->id]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/webhooks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'url', 'events', 'is_active', 'last_triggered_at']],
                'meta',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_updates_webhook(): void
    {
        $webhook = Webhook::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'name' => 'Updated Webhook',
                'events' => ['task.completed'],
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Webhook')
            ->assertJsonPath('data.is_active', false);
    }

    /** @test */
    public function it_deletes_webhook(): void
    {
        $webhook = Webhook::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    /** @test */
    public function it_tests_webhook(): void
    {
        $webhook = Webhook::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['status_code', 'success', 'triggered_at'],
                'meta',
            ]);
    }

    /** @test */
    public function it_fails_update_other_workspace_webhook(): void
    {
        $otherUser = User::factory()->create();
        $otherWorkspace = $otherUser->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $webhook = Webhook::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'creator_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->putJson("/api/v1/webhooks/{$webhook->id}", [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_fails_create_duplicate_url(): void
    {
        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/webhooks', [
                'name' => 'First',
                'url' => 'https://example.com/hook',
                'events' => ['task.created'],
            ]);

        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/webhooks', [
                'name' => 'Duplicate',
                'url' => 'https://example.com/hook',
                'events' => ['task.created'],
            ]);

        $response->assertStatus(422);
    }
}
