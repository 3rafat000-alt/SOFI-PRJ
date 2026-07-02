<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TaskSync Pro API Routes
|--------------------------------------------------------------------------
|
| API version 1. All routes prefixed with /api/v1.
| Rate limiting applied per route group via custom RateLimitMiddleware.
|
*/

Route::prefix('v1')->group(function (): void {

    // ─── Health Check ───────────────────────────────────────────
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // ─── Public Auth Routes ─────────────────────────────────────
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('ratelimit:auth');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('ratelimit:auth');

    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('ratelimit:auth');

    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('ratelimit:auth');

    // ─── Authenticated Routes ───────────────────────────────────
    Route::middleware(['auth:sanctum'])->group(function (): void {

        // Workspace-scoped middleware (optional — reads X-Workspace-Id header)
        Route::middleware(['workspace'])->group(function (): void {

            // ── Auth (authenticated) ────────────────────────────
            Route::post('auth/logout', [AuthController::class, 'logout']);
            Route::get('auth/me', [AuthController::class, 'me']);

            // ── Workspaces ──────────────────────────────────────
            Route::apiResource('workspaces', WorkspaceController::class)
                ->only(['index', 'store', 'show', 'update', 'destroy']);

            Route::get('workspaces/{workspace}/members', [WorkspaceController::class, 'members']);
            Route::post('workspaces/{workspace}/invite', [WorkspaceController::class, 'invite'])
                ->middleware('ratelimit:invites');

            // ── Projects ────────────────────────────────────────
            Route::apiResource('projects', ProjectController::class)
                ->only(['index', 'store', 'show', 'update', 'destroy']);

            Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks']);

            // ── Tasks ───────────────────────────────────────────
            Route::get('tasks', [TaskController::class, 'index']);
            Route::post('tasks', [TaskController::class, 'store']);
            Route::get('tasks/{task}', [TaskController::class, 'show']);
            Route::put('tasks/{task}', [TaskController::class, 'update']);
            Route::delete('tasks/{task}', [TaskController::class, 'destroy']);

            Route::put('tasks/reorder', [TaskController::class, 'reorder']);
            Route::patch('tasks/{task}/status', [TaskController::class, 'status']);

            // ── Comments ────────────────────────────────────────
            Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
            Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
            Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

            // ── Attachments ─────────────────────────────────────
            Route::post('tasks/{task}/attachments', [AttachmentController::class, 'upload'])
                ->middleware('ratelimit:uploads');
            Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
            Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);

            // ── Time Entries ────────────────────────────────────
            Route::get('time-entries', [TimeEntryController::class, 'index']);
            Route::post('time-entries', [TimeEntryController::class, 'store']);
            Route::put('time-entries/{time_entry}', [TimeEntryController::class, 'update']);
            Route::delete('time-entries/{time_entry}', [TimeEntryController::class, 'destroy']);

            Route::post('time-entries/start', [TimeEntryController::class, 'start']);
            Route::post('time-entries/stop', [TimeEntryController::class, 'stop']);

            Route::get('time-entries/report', [TimeEntryController::class, 'report'])
                ->middleware('ratelimit:reports');

            // ── Tags ────────────────────────────────────────────
            Route::get('tags', [TagController::class, 'index']);
            Route::post('tags', [TagController::class, 'store']);
            Route::delete('tags/{tag}', [TagController::class, 'destroy']);

            // ── Notifications ───────────────────────────────────
            Route::get('notifications', [NotificationController::class, 'index']);
            Route::put('notifications/{notification}/read', [NotificationController::class, 'markRead']);
            Route::put('notifications/read-all', [NotificationController::class, 'markAllRead']);

            // ── Dashboard ───────────────────────────────────────
            Route::get('dashboard/stats', [DashboardController::class, 'stats']);
            Route::get('dashboard/activity', [DashboardController::class, 'activity']);

            // ── Webhooks ────────────────────────────────────────
            Route::get('webhooks', [WebhookController::class, 'index']);
            Route::post('webhooks', [WebhookController::class, 'store']);
            Route::delete('webhooks/{webhook}', [WebhookController::class, 'destroy']);
            Route::post('webhooks/{webhook}/test', [WebhookController::class, 'test']);

        }); // end workspace middleware
    }); // end auth:sanctum
}); // end v1 prefix
