<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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
    public function it_returns_dashboard_stats(): void
    {
        Project::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $project = Project::where('workspace_id', $this->workspace->id)->first();

        Task::factory()->count(5)->create([
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
        ]);

        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
            'status' => 'done',
        ]);

        TimeEntry::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'task_id' => Task::first()->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_projects',
                    'total_tasks',
                    'completed_tasks',
                    'overdue_tasks',
                    'active_timers',
                    'total_time_hours',
                    'tasks_by_status',
                    'completion_rate',
                ],
                'meta',
            ]);

        $this->assertEquals(2, $response->json('data.total_projects'));
        $this->assertGreaterThan(0, $response->json('data.total_tasks'));
        $this->assertGreaterThan(0, $response->json('data.completion_rate'));
    }

    /** @test */
    public function it_returns_recent_activity(): void
    {
        Comment::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'task_id' => Task::factory()->create([
                'project_id' => Project::factory()->create([
                    'workspace_id' => $this->workspace->id,
                    'creator_id' => $this->user->id,
                ]),
                'creator_id' => $this->user->id,
            ]),
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/dashboard/activity');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['type', 'description', 'user_name', 'created_at', 'reference_type', 'reference_id']],
                'meta',
            ]);
    }

    /** @test */
    public function it_returns_my_tasks(): void
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
            'assignee_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/dashboard/my-tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'priority', 'project_name', 'due_date', 'tags']],
                'meta',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_upcoming_tasks(): void
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
            'assignee_id' => $this->user->id,
            'due_date' => now()->addDays(3),
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/dashboard/upcoming');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'due_date', 'project_name', 'priority']],
                'meta' => ['total'],
            ]);
    }

    /** @test */
    public function it_returns_team_workload(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/dashboard/team-workload');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['user', 'active_tasks', 'completed_today', 'total_time_hours']],
                'meta',
            ]);
    }

    /** @test */
    public function it_fails_without_auth(): void
    {
        $this->getJson('/api/v1/dashboard/stats')->assertStatus(401);
    }
}
