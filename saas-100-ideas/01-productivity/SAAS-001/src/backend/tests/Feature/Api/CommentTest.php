<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
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
    public function it_creates_comment(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'body' => 'تم الانتهاء من التصميم',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'task_id', 'user', 'body', 'created_at', 'can_delete'],
                'meta',
            ]);

        $this->assertTrue($response->json('data.can_delete'));
        $this->assertEquals('تم الانتهاء من التصميم', $response->json('data.body'));
    }

    /** @test */
    public function it_fails_create_without_body(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    /** @test */
    public function it_lists_comments(): void
    {
        Comment::factory()->count(3)->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/tasks/{$this->task->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'task_id', 'user', 'body', 'created_at', 'can_delete']],
                'meta' => ['total', 'per_page'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_deletes_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Comment deleted.');

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function it_fails_delete_others_comment(): void
    {
        $otherUser = User::factory()->create();
        $workspace = $this->user->workspaces()->first();
        $workspace->members()->attach($otherUser->id, ['role' => 'member', 'joined_at' => now()]);

        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $otherToken = $otherUser->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$otherToken,
        ])->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_allows_admin_to_delete_any_comment(): void
    {
        $admin = User::factory()->create();
        $workspace = $this->user->workspaces()->first();
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $adminToken = $admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$adminToken,
        ])->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_fails_list_comments_for_nonexistent_task(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/tasks/00000000-0000-0000-0000-000000000000/comments');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_shows_can_delete_true_for_own_comment(): void
    {
        $createResponse = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'body' => 'My comment',
            ]);

        $commentId = $createResponse->json('data.id');

        $listResponse = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/tasks/{$this->task->id}/comments");

        $myComment = collect($listResponse->json('data'))->firstWhere('id', $commentId);
        $this->assertTrue($myComment['can_delete']);
    }
}
