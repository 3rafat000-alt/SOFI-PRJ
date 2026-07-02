<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Task $task;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

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
    public function it_uploads_attachment(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024);

        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
                'file' => $file,
                'note' => 'تقارير الأداء',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'task_id', 'file_name', 'file_size', 'mime_type', 'url', 'uploader', 'created_at'],
                'meta',
            ]);

        $this->assertEquals('report.pdf', $response->json('data.file_name'));
        Storage::disk('s3')->assertExists($response->json('data.url'));
    }

    /** @test */
    public function it_fails_upload_without_file(): void
    {
        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/attachments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_fails_upload_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('huge.zip', 25600); // 25MB

        $response = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_lists_attachments(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 512);

        $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
                'file' => $file,
            ]);

        $response = $this->withHeaders($this->withAuth())
            ->getJson("/api/v1/tasks/{$this->task->id}/attachments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'file_name', 'file_size', 'mime_type', 'uploader', 'created_at']],
                'meta',
            ]);
    }

    /** @test */
    public function it_deletes_attachment(): void
    {
        $file = UploadedFile::fake()->create('delete_me.txt', 128);

        $uploadResponse = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
                'file' => $file,
            ]);

        $attachmentId = $uploadResponse->json('data.id');

        $response = $this->withHeaders($this->withAuth())
            ->deleteJson("/api/v1/attachments/{$attachmentId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Attachment deleted.');
    }

    /** @test */
    public function it_fails_delete_others_attachment(): void
    {
        $otherUser = User::factory()->create();
        $workspace = $this->user->workspaces()->first();
        $workspace->members()->attach($otherUser->id, ['role' => 'member', 'joined_at' => now()]);

        $file = UploadedFile::fake()->create('mine.pdf', 256);
        $uploadResponse = $this->withHeaders($this->withAuth())
            ->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
                'file' => $file,
            ]);

        $attachmentId = $uploadResponse->json('data.id');
        $otherToken = $otherUser->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$otherToken,
        ])->deleteJson("/api/v1/attachments/{$attachmentId}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/tasks/'.fake()->uuid.'/attachments');

        $response->assertStatus(401);
    }
}
