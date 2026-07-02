<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
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

        $this->project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'creator_id' => $this->user->id,
        ]);
    }

    private function withAuth(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function createTask(array $overrides = []): Task
    {
        return Task::factory()->create(array_merge([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
        ], $overrides));
    }

    /** @test */
    public function it_creates_task(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/tasks', [
                'project_id' => $this->project->id,
                'title' => 'تصميم الصفحة الرئيسية',
                'description' => 'تصميم واجهة المستخدم',
                'priority' => 'high',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'project_id', 'title', 'status', 'priority', 'position', 'assignee', 'created_at'],
                'meta',
            ]);

        $this->assertEquals('todo', $response->json('data.status'));
        $this->assertEquals('تصميم الصفحة الرئيسية', $response->json('data.title'));
    }

    /** @test */
    public function it_fails_create_without_title(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/tasks', [
                'project_id' => $this->project->id,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function it_lists_tasks(): void
    {
        $this->createTask(['title' => 'Task A']);
        $this->createTask(['title' => 'Task B']);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/tasks?limit=50');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'priority', 'assignee', 'creator']],
                'meta' => ['next_cursor', 'has_more'],
            ]);
    }

    /** @test */
    public function it_filters_tasks_by_status(): void
    {
        $this->createTask(['title' => 'Todo task', 'status' => 'todo']);
        $this->createTask(['title' => 'Done task', 'status' => 'done']);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/tasks?status=todo&limit=50');

        $response->assertStatus(200);
        foreach ($response->json('data') as $task) {
            $this->assertEquals('todo', $task['status']);
        }
    }

    /** @test */
    public function it_filters_tasks_by_priority(): void
    {
        $this->createTask(['title' => 'High', 'priority' => 'high']);
        $this->createTask(['title' => 'Low', 'priority' => 'low']);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/tasks?priority=high&limit=50');

        $response->assertStatus(200);
        foreach ($response->json('data') as $task) {
            $this->assertEquals('high', $task['priority']);
        }
    }

    /** @test */
    public function it_searches_tasks(): void
    {
        $this->createTask(['title' => 'تصميم الصفحة']);
        $this->createTask(['title' => 'Other thing']);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/tasks?search=تصميم&limit=50');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /** @test */
    public function it_shows_task(): void
    {
        $task = $this->createTask();

        $response = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);
    }

    /** @test */
    public function it_fails_show_nonexistent_task(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/tasks/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_updates_task(): void
    {
        $task = $this->createTask();

        $response = $this->withHeaders($this->withAuth())
            ->putJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Updated title',
                'status' => 'in_progress',
                'priority' => 'urgent',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.priority', 'urgent');
    }

    /** @test */
    public function it_deletes_task(): void
    {
        $task = $this->createTask();

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Task deleted.');

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function it_fails_delete_by_member(): void
    {
        $member = User::factory()->create();
        $workspace = $this->user->workspaces()->first();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $memberToken = $member->createToken('auth-token')->plainTextToken;

        $task = $this->createTask();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
        ])->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_reorders_tasks(): void
    {
        $task1 = $this->createTask(['position' => 1]);
        $task2 = $this->createTask(['position' => 2]);

        $response = $this->withHeaders($this->withAuth())
            ->putJson('/api/v1/tasks/reorder', [
                'project_id' => $this->project->id,
                'orders' => [
                    ['id' => $task1->id, 'status' => 'todo', 'position' => 2],
                    ['id' => $task2->id, 'status' => 'in_progress', 'position' => 1],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.reordered_count', 2);

        $this->assertDatabaseHas('tasks', ['id' => $task1->id, 'status' => 'todo', 'position' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'status' => 'in_progress', 'position' => 1]);
    }

    /** @test */
    public function it_fails_reorder_with_invalid_orders(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->putJson('/api/v1/tasks/reorder', [
                'project_id' => $this->project->id,
                'orders' => [['id' => 'bad', 'status' => 'invalid', 'position' => -1]],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_quickly_changes_status(): void
    {
        $task = $this->createTask(['status' => 'todo']);

        $response = $this->withHeaders($this->withAuth())
            ->patchJson("/api/v1/tasks/{$task->id}/status", [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');
    }

    /** @test */
    public function it_fails_status_change_invalid_status(): void
    {
        $task = $this->createTask();

        $response = $this->withHeaders($this->withAuth())
            ->patchJson("/api/v1/tasks/{$task->id}/status", [
                'status' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_requires_auth_for_all_task_endpoints(): void
    {
        $this->getJson('/api/v1/tasks')->assertStatus(401);
        $this->postJson('/api/v1/tasks', [])->assertStatus(401);
        $this->putJson('/api/v1/tasks/reorder', [])->assertStatus(401);
    }
}
