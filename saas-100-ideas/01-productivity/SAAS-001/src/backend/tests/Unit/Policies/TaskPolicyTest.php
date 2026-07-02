<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TaskPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = app(TaskPolicy::class);
    }

    private function createTask(User $user, array $overrides = []): Task
    {
        $workspace = $user->workspaces()->first() ?? throw new \RuntimeException('no workspace');
        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'creator_id' => $user->id,
        ]);

        return Task::factory()->create(array_merge([
            'project_id' => $project->id,
            'creator_id' => $user->id,
        ], $overrides));
    }

    /** @test */
    public function member_can_view_task(): void
    {
        $owner = User::factory()->create();
        $task = $this->createTask($owner);
        $workspace = $owner->workspaces()->first();

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($this->policy->view($member, $task));
    }

    /** @test */
    public function non_member_cannot_view_task(): void
    {
        $owner = User::factory()->create();
        $task = $this->createTask($owner);
        $stranger = User::factory()->create();

        $this->assertFalse($this->policy->view($stranger, $task));
    }

    /** @test */
    public function any_workspace_member_can_create(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($this->policy->create($member, $workspace));
    }

    /** @test */
    public function viewer_cannot_create_task(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $viewer = User::factory()->create();
        $workspace->members()->attach($viewer->id, ['role' => 'viewer', 'joined_at' => now()]);

        $this->assertFalse($this->policy->create($viewer, $workspace));
    }

    /** @test */
    public function member_can_update_task(): void
    {
        $owner = User::factory()->create();
        $task = $this->createTask($owner);
        $workspace = $owner->workspaces()->first();

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($this->policy->update($member, $task));
    }

    /** @test */
    public function viewer_cannot_update_task(): void
    {
        $owner = User::factory()->create();
        $task = $this->createTask($owner);
        $workspace = $owner->workspaces()->first();

        $viewer = User::factory()->create();
        $workspace->members()->attach($viewer->id, ['role' => 'viewer', 'joined_at' => now()]);

        $this->assertFalse($this->policy->update($viewer, $task));
    }

    /** @test */
    public function only_owner_or_admin_can_delete_task(): void
    {
        $owner = User::factory()->create();
        $task = $this->createTask($owner);
        $workspace = $owner->workspaces()->first();

        $admin = User::factory()->create();
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($this->policy->delete($owner, $task));
        $this->assertTrue($this->policy->delete($admin, $task));
        $this->assertFalse($this->policy->delete($member, $task));
    }

    /** @test */
    public function creator_can_delete_own_task(): void
    {
        $creator = User::factory()->create();
        $task = $this->createTask($creator);

        $this->assertTrue($this->policy->delete($creator, $task));
    }
}
