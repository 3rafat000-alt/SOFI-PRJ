<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
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
    public function it_creates_tag(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/tags', [
                'name' => 'عاجل',
                'color' => '#FF0000',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'color', 'usage_count', 'created_at'],
                'meta',
            ]);

        $this->assertEquals('عاجل', $response->json('data.name'));
        $this->assertEquals('#FF0000', $response->json('data.color'));
    }

    /** @test */
    public function it_fails_create_duplicate_tag(): void
    {
        $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/tags', ['name' => 'عاجل', 'color' => '#FF0000']);

        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/tags', ['name' => 'عاجل', 'color' => '#00FF00']);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_lists_tags(): void
    {
        Tag::factory()->count(5)->create([
            'workspace_id' => $this->user->workspaces()->first()->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/tags');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'color', 'usage_count']],
                'meta',
            ]);
    }

    /** @test */
    public function it_attaches_tags_to_task(): void
    {
        $tags = Tag::factory()->count(3)->create([
            'workspace_id' => $this->user->workspaces()->first()->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/tags", [
                'tag_ids' => $tags->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attached_count', 3);

        $this->assertCount(3, $this->task->fresh()->tags);
    }

    /** @test */
    public function it_removes_tag_from_task(): void
    {
        $tag = Tag::factory()->create([
            'workspace_id' => $this->user->workspaces()->first()->id,
        ]);

        $this->task->tags()->attach($tag->id);

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/tasks/{$this->task->id}/tags/{$tag->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Tag removed from task.');

        $this->assertCount(0, $this->task->fresh()->tags);
    }

    /** @test */
    public function it_deletes_tag(): void
    {
        $tag = Tag::factory()->create([
            'workspace_id' => $this->user->workspaces()->first()->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Tag deleted.');

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function it_fails_delete_tag_used_in_other_workspace(): void
    {
        $otherUser = User::factory()->create();
        $otherWorkspace = $otherUser->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $tag = Tag::factory()->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_fails_create_without_name(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/tags', ['color' => '#000000']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
