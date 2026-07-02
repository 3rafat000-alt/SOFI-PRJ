<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Task $task;
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
        $workspace = $this->user->workspaces()->first();

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $this->task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
        ]);
    }

    private function withAuth(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    /** @test */
    public function it_starts_timer(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/start', [
                'task_id' => $this->task->id,
                'note' => 'بدء العمل',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'task_id', 'user_id', 'started_at', 'is_running', 'notes'],
                'meta',
            ]);

        $this->assertTrue($response->json('data.is_running'));
        $this->assertNull($response->json('data.ended_at'));
    }

    /** @test */
    public function it_fails_start_timer_already_running(): void
    {
        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/start', [
                'task_id' => $this->task->id,
            ]);

        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/start', [
                'task_id' => $this->task->id,
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    }

    /** @test */
    public function it_stops_timer(): void
    {
        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/start', [
                'task_id' => $this->task->id,
            ]);

        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/stop', [
                'note' => 'انتهيت',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'task_id', 'started_at', 'ended_at', 'duration_minutes', 'is_running', 'notes'],
                'meta',
            ]);

        $this->assertFalse($response->json('data.is_running'));
        $this->assertNotNull($response->json('data.ended_at'));
        $this->assertNotNull($response->json('data.duration_minutes'));
    }

    /** @test */
    public function it_fails_stop_without_running_timer(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/stop');

        $response->assertStatus(409);
    }

    /** @test */
    public function it_creates_manual_time_entry(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries', [
                'task_id' => $this->task->id,
                'started_at' => '2026-07-05T09:00:00Z',
                'ended_at' => '2026-07-05T11:30:00Z',
                'notes' => 'عمل يدوي',
                'is_manual' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'task_id', 'user_id', 'started_at', 'ended_at', 'duration_minutes', 'is_manual'],
                'meta',
            ]);

        $this->assertEquals(150, $response->json('data.duration_minutes'));
    }

    /** @test */
    public function it_lists_time_entries(): void
    {
        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/start', ['task_id' => $this->task->id]);

        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/stop');

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/time-entries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'task_id', 'user_id', 'started_at', 'duration_minutes', 'is_manual']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    /** @test */
    public function it_filters_time_entries_by_date_range(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/time-entries?from=2026-07-01&to=2026-07-31');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_updates_time_entry(): void
    {
        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/start', ['task_id' => $this->task->id]);

        $stopped = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/stop');

        $entryId = $stopped->json('data.id');

        $response = $this->withHeaders($this->withAuth())
            ->putJson("/api/v1/time-entries/{$entryId}", [
                'notes' => 'ملاحظة محدثة',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'ملاحظة محدثة');
    }

    /** @test */
    public function it_fails_update_others_time_entry(): void
    {
        $otherUser = User::factory()->create();
        $workspace = $this->user->workspaces()->first();
        $workspace->members()->attach($otherUser->id, ['role' => 'member', 'joined_at' => now()]);
        $otherToken = $otherUser->createToken('auth-token')->plainTextToken;

        // Start + stop as main user
        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/start', ['task_id' => $this->task->id]);

        $stopped = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/stop');

        $entryId = $stopped->json('data.id');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$otherToken,
        ])->putJson("/api/v1/time-entries/{$entryId}", [
            'notes' => 'hacked',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_time_entry(): void
    {
        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/start', ['task_id' => $this->task->id]);

        $stopped = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/time-entries/stop');

        $entryId = $stopped->json('data.id');

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/time-entries/{$entryId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Time entry deleted.');

        $this->assertDatabaseMissing('time_entries', ['id' => $entryId]);
    }

    /** @test */
    public function it_generates_time_report(): void
    {
        $workspace = $this->user->workspaces()->first();

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/time-entries/report', [
                'workspace_id' => $workspace->id,
                'from' => '2026-07-01',
                'to' => '2026-07-31',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['summary' => ['total_minutes', 'total_hours', 'period'], 'entries'],
                'meta',
            ]);
    }

    /** @test */
    public function it_fails_report_without_workspace(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/time-entries/report');

        $response->assertStatus(422);
    }
}
