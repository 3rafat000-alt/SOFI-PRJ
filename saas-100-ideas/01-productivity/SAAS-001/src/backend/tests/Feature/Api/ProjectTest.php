<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Workspace $workspace;
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
        $this->workspace = $this->user->workspaces()->first();
    }

    private function withAuth(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    /** @test */
    public function it_lists_projects(): void
    {
        Project::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/projects?workspace_id='.$this->workspace->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'workspace_id', 'name', 'status', 'task_count', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_fails_list_without_workspace_id(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/projects');

        $response->assertStatus(422);
    }

    /** @test */
    public function it_creates_project(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/projects', [
                'workspace_id' => $this->workspace->id,
                'name' => 'حملة إطلاق المنتج',
                'description' => 'مهام إطلاق المنتج الجديد',
                'color' => '#4F46E5',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'workspace_id', 'name', 'color', 'status', 'task_count', 'created_at'],
                'meta',
            ]);

        $this->assertEquals('active', $response->json('data.status'));
    }

    /** @test */
    public function it_fails_create_with_invalid_workspace(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/projects', [
                'workspace_id' => '00000000-0000-0000-0000-000000000000',
                'name' => 'Test',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_shows_project(): void
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $project->id);
    }

    /** @test */
    public function it_fails_show_nonexistent_project(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/projects/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_updates_project(): void
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->putJson("/api/v1/projects/{$project->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    /** @test */
    public function it_fails_update_by_member(): void
    {
        $member = User::factory()->create();
        $this->workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $memberToken = $member->createToken('auth-token')->plainTextToken;

        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
        ])->putJson("/api/v1/projects/{$project->id}", ['name' => 'Hack']);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_project(): void
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Project deleted successfully.');

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    /** @test */
    public function it_filters_projects_by_status(): void
    {
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
            'status' => 'active',
        ]);
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
            'status' => 'archived',
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/projects?workspace_id='.$this->workspace->id.'&status=archived');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('archived', $response->json('data.0.status'));
    }

    /** @test */
    public function it_searches_projects(): void
    {
        Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
            'name' => 'مشروع التسويق',
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/projects?workspace_id='.$this->workspace->id.'&search=تسويق');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /** @test */
    public function it_lists_tasks_in_project(): void
    {
        $project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $task = $project->tasks()->create([
            'creator_id' => $this->user->id,
            'title' => 'Test task',
            'status' => 'todo',
            'position' => 1,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/projects/{$project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'position', 'assignee', 'creator']],
                'meta',
            ]);
    }
}
