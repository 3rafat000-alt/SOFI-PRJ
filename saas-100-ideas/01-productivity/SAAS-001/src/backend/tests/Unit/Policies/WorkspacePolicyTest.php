<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspacePolicyTest extends TestCase
{
    use RefreshDatabase;

    private WorkspacePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = app(WorkspacePolicy::class);
    }

    /** @test */
    public function owner_can_view(): void
    {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $this->assertTrue($this->policy->view($user, $workspace));
    }

    /** @test */
    public function member_can_view(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($this->policy->view($member, $workspace));
    }

    /** @test */
    public function non_member_cannot_view(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');
        $stranger = User::factory()->create();

        $this->assertFalse($this->policy->view($stranger, $workspace));
    }

    /** @test */
    public function owner_can_update(): void
    {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $this->assertTrue($this->policy->update($user, $workspace));
    }

    /** @test */
    public function admin_can_update(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');
        $admin = User::factory()->create();
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        $this->assertTrue($this->policy->update($admin, $workspace));
    }

    /** @test */
    public function member_cannot_update(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertFalse($this->policy->update($member, $workspace));
    }

    /** @test */
    public function owner_can_delete(): void
    {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $this->assertTrue($this->policy->delete($user, $workspace));
    }

    /** @test */
    public function non_owner_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');
        $admin = User::factory()->create();
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

        $this->assertFalse($this->policy->delete($admin, $workspace));
    }

    /** @test */
    public function only_owner_and_admin_can_invite(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $admin = User::factory()->create();
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($this->policy->invite($owner, $workspace));
        $this->assertTrue($this->policy->invite($admin, $workspace));
        $this->assertFalse($this->policy->invite($member, $workspace));
    }

    /** @test */
    public function viewer_cannot_perform_actions(): void
    {
        $owner = User::factory()->create();
        $workspace = $owner->workspaces()->first() ?? throw new \RuntimeException('no workspace');
        $viewer = User::factory()->create();
        $workspace->members()->attach($viewer->id, ['role' => 'viewer', 'joined_at' => now()]);

        $this->assertFalse($this->policy->update($viewer, $workspace));
        $this->assertFalse($this->policy->delete($viewer, $workspace));
        $this->assertTrue($this->policy->view($viewer, $workspace));
    }
}
