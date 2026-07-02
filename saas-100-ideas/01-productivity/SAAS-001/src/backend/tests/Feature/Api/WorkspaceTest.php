<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
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
    public function it_lists_workspaces(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson('/api/v1/workspaces');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'role', 'plan', 'created_at']],
                'meta',
            ]);
    }

    /** @test */
    public function it_creates_workspace(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/workspaces', [
                'name' => 'فريق تقني',
                'description' => 'فريق تطوير البرمجيات',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'role', 'member_count', 'plan'],
                'meta',
            ]);

        $this->assertEquals('owner', $response->json('data.role'));
        $this->assertEquals(1, $response->json('data.member_count'));
    }

    /** @test */
    public function it_fails_create_workspace_without_name(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson('/api/v1/workspaces', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_shows_workspace(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/workspaces/{$this->workspace->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->workspace->id);
    }

    /** @test */
    public function it_fails_show_workspace_not_member(): void
    {
        $otherUser = User::factory()->create();
        $otherWs = Workspace::factory()->create(['owner_id' => $otherUser->id]);
        $otherWs->members()->attach($otherUser->id, ['role' => 'owner', 'joined_at' => now()]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/workspaces/{$otherWs->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_updates_workspace(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->putJson("/api/v1/workspaces/{$this->workspace->id}", [
                'name' => 'فريق التسويق الرقمي',
                'description' => 'فريق محدث',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'فريق التسويق الرقمي');
    }

    /** @test */
    public function it_fails_update_by_member(): void
    {
        $member = User::factory()->create();
        $this->workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $memberToken = $member->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
        ])->putJson("/api/v1/workspaces/{$this->workspace->id}", [
            'name' => 'غير مسموح',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_workspace(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/workspaces/{$this->workspace->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Workspace deleted successfully.');

        $this->assertSoftDeleted('workspaces', ['id' => $this->workspace->id]);
    }

    /** @test */
    public function it_lists_members(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/workspaces/{$this->workspace->id}/members");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'role', 'joined_at', 'task_count']],
                'meta',
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function it_invites_existing_user(): void
    {
        $newUser = User::factory()->create(['email' => 'ahmed@example.com']);

        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/workspaces/{$this->workspace->id}/invite", [
                'email' => 'ahmed@example.com',
                'role' => 'member',
                'channel' => 'email',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.invitation.status', 'accepted');

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $newUser->id,
            'role' => 'member',
        ]);
    }

    /** @test */
    public function it_invites_new_user_and_pends(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/workspaces/{$this->workspace->id}/invite", [
                'email' => 'new@example.com',
                'role' => 'member',
                'channel' => 'email',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.invitation.status', 'pending');
    }

    /** @test */
    public function it_fails_invite_already_member(): void
    {
        $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/workspaces/{$this->workspace->id}/invite", [
                'email' => 'sara@example.com',
                'role' => 'member',
            ]);

        // Owner is already a member — invite will check and say already member
        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/workspaces/{$this->workspace->id}/invite", [
                'email' => 'sara@example.com',
                'role' => 'member',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.invitation', null);
    }

    /** @test */
    public function it_fails_invite_without_permission(): void
    {
        $member = User::factory()->create();
        $this->workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $memberToken = $member->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$memberToken,
        ])->postJson("/api/v1/workspaces/{$this->workspace->id}/invite", [
            'email' => 'some@example.com',
            'role' => 'member',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_auth(): void
    {
        $this->getJson('/api/v1/workspaces')->assertStatus(401);
        $this->postJson('/api/v1/workspaces', [])->assertStatus(401);
        $this->getJson("/api/v1/workspaces/{$this->workspace->id}")->assertStatus(401);
        $this->putJson("/api/v1/workspaces/{$this->workspace->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}")->assertStatus(401);
    }
}
