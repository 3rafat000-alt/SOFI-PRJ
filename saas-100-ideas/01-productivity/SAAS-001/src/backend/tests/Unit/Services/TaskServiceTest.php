<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $taskService;
    private User $user;
    private Project $project;
    private $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskService = app(TaskService::class);

        $this->user = User::factory()->create();
        $this->workspace = $this->user->workspaces()->first() ?? throw new \RuntimeException('no workspace');
        $this->project = Project::factory()->create([
            'workspace_id' => $this->workspace->id,
            'creator_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_creates_task_with_next_position(): void
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'position' => 5,
        ]);

        $task = $this->taskService->createTask($this->project, [
            'title' => 'New Task',
        ], $this->user);

        $this->assertEquals('New Task', $task->title);
        $this->assertEquals(6, $task->position);
        $this->assertEquals('todo', $task->status);
        $this->assertEquals($this->user->id, $task->creator_id);
    }

    /** @test */
    public function it_creates_task_with_default_status(): void
    {
        $task = $this->taskService->createTask($this->project, [
            'title' => 'Default status',
        ], $this->user);

        $this->assertEquals('todo', $task->status);
    }

    /** @test */
    public function it_creates_task_with_tags(): void
    {
        $tags = Tag::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $task = $this->taskService->createTask($this->project, [
            'title' => 'Tagged task',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ], $this->user);

        $this->assertCount(2, $task->fresh()->tags);
    }

    /** @test */
    public function it_reorders_tasks(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'position' => 1,
            'status' => 'todo',
        ]);
        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'position' => 2,
            'status' => 'in_progress',
        ]);

        $count = $this->taskService->reorderTasks($this->project->id, [
            ['id' => $task1->id, 'status' => 'done', 'position' => 2],
            ['id' => $task2->id, 'status' => 'todo', 'position' => 1],
        ]);

        $this->assertEquals(2, $count);

        $this->assertDatabaseHas('tasks', ['id' => $task1->id, 'status' => 'done', 'position' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'status' => 'todo', 'position' => 1]);
    }

    /** @test */
    public function it_filters_tasks_by_status(): void
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'status' => 'todo',
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'status' => 'done',
        ]);

        $tasks = $this->taskService->getTasks($this->user, ['status' => 'todo']);

        $this->assertCount(1, $tasks);
        $this->assertEquals('todo', $tasks->first()->status);
    }

    /** @test */
    public function it_filters_tasks_by_priority(): void
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'priority' => 'high',
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'priority' => 'low',
        ]);

        $tasks = $this->taskService->getTasks($this->user, ['priority' => 'high']);

        $this->assertCount(1, $tasks);
    }

    /** @test */
    public function it_searches_tasks_by_title(): void
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'title' => 'Fix login bug',
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'title' => 'Update docs',
        ]);

        $tasks = $this->taskService->getTasks($this->user, ['search' => 'login']);

        $this->assertCount(1, $tasks);
        $this->assertStringContainsString('login', $tasks->first()->title);
    }

    /** @test */
    public function it_updates_task_status(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $updated = $this->taskService->updateTaskStatus($task, 'in_progress');

        $this->assertEquals('in_progress', $updated->status);
    }

    /** @test */
    public function it_assigns_task_to_user(): void
    {
        $assignee = User::factory()->create();
        $this->workspace->members()->attach($assignee->id, ['role' => 'member', 'joined_at' => now()]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
        ]);

        $updated = $this->taskService->assignTask($task, $assignee->id);

        $this->assertEquals($assignee->id, $updated->assignee_id);
    }

    /** @test */
    public function it_soft_deletes_task(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
        ]);

        $this->taskService->deleteTask($task);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function it_returns_empty_for_no_tasks(): void
    {
        $tasks = $this->taskService->getTasks($this->user, []);

        $this->assertCount(0, $tasks);
    }

    /** @test */
    public function it_calculates_position_when_no_tasks_exist(): void
    {
        $task = $this->taskService->createTask($this->project, [
            'title' => 'First task',
        ], $this->user);

        $this->assertEquals(1, $task->position);
    }
}
