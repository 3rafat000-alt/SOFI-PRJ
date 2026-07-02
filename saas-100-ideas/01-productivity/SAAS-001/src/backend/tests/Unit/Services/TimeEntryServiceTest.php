<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\TimerAlreadyRunningException;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimeEntryService $timeEntryService;
    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeEntryService = app(TimeEntryService::class);
        $this->user = User::factory()->create();
        $workspace = $this->user->workspaces()->first() ?? throw new \RuntimeException('no workspace');

        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'creator_id' => $this->user->id,
        ]);

        $this->task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_starts_timer(): void
    {
        $entry = $this->timeEntryService->startTimer($this->user, $this->task->id, 'بدء العمل');

        $this->assertTrue($entry->is_running);
        $this->assertNotNull($entry->started_at);
        $this->assertNull($entry->ended_at);
        $this->assertEquals($this->user->id, $entry->user_id);
        $this->assertEquals($this->task->id, $entry->task_id);
    }

    /** @test */
    public function it_throws_exception_on_concurrent_start(): void
    {
        $this->timeEntryService->startTimer($this->user, $this->task->id);

        $this->expectException(TimerAlreadyRunningException::class);

        $this->timeEntryService->startTimer($this->user, $this->task->id);
    }

    /** @test */
    public function it_stops_running_timer(): void
    {
        $started = $this->timeEntryService->startTimer($this->user, $this->task->id);

        $stopped = $this->timeEntryService->stopTimer($this->user, 'انتهيت');

        $this->assertFalse($stopped->is_running);
        $this->assertNotNull($stopped->ended_at);
        $this->assertNotNull($stopped->duration_minutes);
        $this->assertGreaterThan(0, $stopped->duration_minutes);
        $this->assertEquals($started->id, $stopped->id);
    }

    /** @test */
    public function it_throws_exception_stop_without_running_timer(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->timeEntryService->stopTimer($this->user);
    }

    /** @test */
    public function it_creates_manual_entry(): void
    {
        $entry = $this->timeEntryService->createManualEntry($this->user, [
            'task_id' => $this->task->id,
            'started_at' => '2026-07-05T09:00:00Z',
            'ended_at' => '2026-07-05T11:30:00Z',
            'notes' => 'عمل يدوي',
        ]);

        $this->assertFalse($entry->is_running);
        $this->assertTrue($entry->is_manual);
        $this->assertEquals(150, $entry->duration_minutes);
        $this->assertNotNull($entry->started_at);
        $this->assertNotNull($entry->ended_at);
    }

    /** @test */
    public function it_gets_active_timer(): void
    {
        $this->timeEntryService->startTimer($this->user, $this->task->id);

        $active = $this->timeEntryService->getActiveTimer($this->user);

        $this->assertNotNull($active);
        $this->assertTrue($active->is_running);
    }

    /** @test */
    public function it_returns_null_when_no_active_timer(): void
    {
        $active = $this->timeEntryService->getActiveTimer($this->user);

        $this->assertNull($active);
    }

    /** @test */
    public function it_returns_user_entries(): void
    {
        TimeEntry::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
        ]);

        $entries = $this->timeEntryService->getUserEntries($this->user);

        $this->assertCount(3, $entries);
    }

    /** @test */
    public function it_updates_entry(): void
    {
        $entry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
        ]);

        $updated = $this->timeEntryService->updateEntry($entry, ['notes' => 'Updated note']);

        $this->assertEquals('Updated note', $updated->notes);
    }

    /** @test */
    public function it_deletes_entry(): void
    {
        $entry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
        ]);

        $this->timeEntryService->deleteEntry($entry);

        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    /** @test */
    public function it_filters_entries_by_date_range(): void
    {
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => '2026-07-10 10:00:00',
        ]);
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'started_at' => '2026-08-15 09:00:00',
            'ended_at' => '2026-08-15 10:00:00',
        ]);

        $entries = $this->timeEntryService->getUserEntries($this->user, [
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ]);

        $this->assertCount(1, $entries);
    }
}
