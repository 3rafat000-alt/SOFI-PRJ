<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed demo data for "الفريق الذهبي" workspace.
     *
     * Run: php artisan db:seed --class=DatabaseSeeder
     */
    public function run(): void
    {
        // ─── Users ───────────────────────────────────
        // Created before the workspace: workspaces.owner_id has a NOT NULL FK
        // to users.id, while users.current_workspace_id is nullable — so users
        // come first, then the workspace, then we backfill current_workspace_id.

        $sara = User::create([
            'name' => 'سارة أحمد',
            'email' => 'sara@example.com',
            'password' => Hash::make('password'),
            'locale' => 'ar',
            'timezone' => 'Asia/Riyadh',
        ]);

        $ahmed = User::create([
            'name' => 'أحمد علي',
            'email' => 'ahmed@example.com',
            'password' => Hash::make('password'),
            'locale' => 'ar',
            'timezone' => 'Asia/Riyadh',
        ]);

        $layla = User::create([
            'name' => 'ليلى محمد',
            'email' => 'layla@example.com',
            'password' => Hash::make('password'),
            'locale' => 'ar',
            'timezone' => 'Asia/Riyadh',
        ]);

        // ─── Workspace ───────────────────────────────

        $workspace = Workspace::create([
            'name' => 'الفريق الذهبي',
            'slug' => 'al-fareeq-al-dhahabi',
            'owner_id' => $sara->id,
            'max_members' => 10,
            'plan' => 'pro',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Backfill each user's active workspace
        $sara->update(['current_workspace_id' => $workspace->id]);
        $ahmed->update(['current_workspace_id' => $workspace->id]);
        $layla->update(['current_workspace_id' => $workspace->id]);

        // ─── Workspace Membership ────────────────────

        $workspace->members()->attach($sara->id, [
            'role' => 'owner',
            'joined_at' => now()->subDays(30),
        ]);

        $workspace->members()->attach($ahmed->id, [
            'role' => 'member',
            'joined_at' => now()->subDays(20),
        ]);

        $workspace->members()->attach($layla->id, [
            'role' => 'admin',
            'joined_at' => now()->subDays(15),
        ]);

        // ─── Projects ────────────────────────────────

        $websiteProject = Project::create([
            'workspace_id' => $workspace->id,
            'creator_id' => $sara->id,
            'name' => 'تصميم الموقع الإلكتروني',
            'description' => 'مشروع تصميم وتطوير الموقع الجديد للشركة',
            'color' => '#6366F1',
            'status' => 'active',
            'start_date' => now()->subDays(14),
            'end_date' => now()->addDays(30),
        ]);

        $campaignProject = Project::create([
            'workspace_id' => $workspace->id,
            'creator_id' => $layla->id,
            'name' => 'الحملة التسويقية',
            'description' => 'حملة إطلاق المنتج الربعية',
            'color' => '#10B981',
            'status' => 'active',
            'start_date' => now()->subDays(7),
            'end_date' => now()->addDays(60),
        ]);

        // ─── Tags ────────────────────────────────────

        $urgent = Tag::create([
            'workspace_id' => $workspace->id,
            'name' => 'عاجل',
            'color' => '#EF4444',
        ]);

        $design = Tag::create([
            'workspace_id' => $workspace->id,
            'name' => 'تصميم',
            'color' => '#8B5CF6',
        ]);

        $dev = Tag::create([
            'workspace_id' => $workspace->id,
            'name' => 'تطوير',
            'color' => '#3B82F6',
        ]);

        $marketing = Tag::create([
            'workspace_id' => $workspace->id,
            'name' => 'تسويق',
            'color' => '#F59E0B',
        ]);

        // ─── Tasks ───────────────────────────────────

        // Task 1: In progress, high priority
        $task1 = Task::create([
            'project_id' => $websiteProject->id,
            'creator_id' => $sara->id,
            'title' => 'تصميم الصفحة الرئيسية',
            'description' => 'تصميم واجهة المستخدم للصفحة الرئيسية مع مراعاة تجربة المستخدم',
            'priority' => 'high',
            'status' => 'in_progress',
            'position' => 0,
            'due_date' => now()->addDays(5),
            'estimated_minutes' => 480,
        ]);
        $task1->assignees()->attach($sara->id);
        $task1->tags()->attach([$design->id, $urgent->id]);

        // Task 2: Todo, high priority
        $task2 = Task::create([
            'project_id' => $websiteProject->id,
            'creator_id' => $sara->id,
            'title' => 'تطوير واجهة API',
            'description' => 'تطوير واجهة برمجة التطبيقات للموقع الإلكتروني',
            'priority' => 'high',
            'status' => 'todo',
            'position' => 1,
            'due_date' => now()->addDays(10),
            'estimated_minutes' => 960,
        ]);
        $task2->assignees()->attach($ahmed->id);
        $task2->tags()->attach($dev->id);

        // Task 3: Done
        $task3 = Task::create([
            'project_id' => $campaignProject->id,
            'creator_id' => $layla->id,
            'title' => 'كتابة المحتوى التسويقي',
            'description' => 'كتابة محتوى إعلاني للحملة التسويقية للمنتج الجديد',
            'priority' => 'medium',
            'status' => 'done',
            'position' => 0,
            'due_date' => now()->subDays(2),
            'estimated_minutes' => 240,
        ]);
        $task3->assignees()->attach($layla->id);
        $task3->tags()->attach($marketing->id);

        // Task 4: Todo, medium priority
        $task4 = Task::create([
            'project_id' => $campaignProject->id,
            'creator_id' => $layla->id,
            'title' => 'إعداد إعلانات السوشيال ميديا',
            'description' => 'تصميم وإعداد إعلانات فيسبوك وإنستغرام للحملة',
            'priority' => 'medium',
            'status' => 'todo',
            'position' => 1,
            'due_date' => now()->addDays(7),
            'estimated_minutes' => 360,
        ]);
        $task4->assignees()->attach($ahmed->id);
        $task4->tags()->attach([$marketing->id, $design->id]);

        // Task 5: Todo, low priority
        $task5 = Task::create([
            'project_id' => $websiteProject->id,
            'creator_id' => $sara->id,
            'title' => 'مراجعة التصميم النهائي',
            'description' => 'مراجعة التصميم النهائي مع الفريق قبل الإطلاق',
            'priority' => 'low',
            'status' => 'todo',
            'position' => 2,
            'due_date' => now()->addDays(14),
            'estimated_minutes' => 120,
        ]);
        $task5->tags()->attach($design->id);

        // Task 6: Done
        $task6 = Task::create([
            'project_id' => $campaignProject->id,
            'creator_id' => $layla->id,
            'title' => 'تحليل أداء الحملة السابقة',
            'description' => 'تحليل أداء الحملة التسويقية السابقة واستخلاص الدروس',
            'priority' => 'medium',
            'status' => 'done',
            'position' => 2,
            'estimated_minutes' => 180,
        ]);
        $task6->assignees()->attach($layla->id);
        $task6->tags()->attach($marketing->id);

        // ─── Time Entries ────────────────────────────

        TimeEntry::create([
            'task_id' => $task1->id,
            'user_id' => $sara->id,
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHours(1),
            'duration_minutes' => 120,
            'notes' => 'العمل على تصميم الهيدر والفوتر',
        ]);

        TimeEntry::create([
            'task_id' => $task2->id,
            'user_id' => $ahmed->id,
            'started_at' => now()->subDay()->subHours(2),
            'ended_at' => now()->subDay(),
            'duration_minutes' => 120,
            'notes' => 'إعداد هيكل API الأساسي',
        ]);

        TimeEntry::create([
            'task_id' => $task3->id,
            'user_id' => $layla->id,
            'started_at' => now()->subDays(3),
            'ended_at' => now()->subDays(3)->addHours(4),
            'duration_minutes' => 240,
            'notes' => 'كتابة المحتوى الإعلاني',
        ]);

        // Running timer (active)
        TimeEntry::create([
            'task_id' => $task1->id,
            'user_id' => $sara->id,
            'started_at' => now()->subMinutes(45),
            'ended_at' => null,
            'duration_minutes' => null,
            'notes' => 'تجربة ألوان الواجهة',
        ]);

        // ─── Comments ────────────────────────────────

        Comment::create([
            'task_id' => $task1->id,
            'user_id' => $sara->id,
            'body' => 'بدأت العمل على التصميم الرئيسي، سأرفع النموذج الأولي قريباً',
        ]);

        Comment::create([
            'task_id' => $task3->id,
            'user_id' => $layla->id,
            'body' => 'تم الانتهاء من المحتوى التسويقي، جاهز للمراجعة',
        ]);

        Comment::create([
            'task_id' => $task2->id,
            'user_id' => $ahmed->id,
            'body' => 'سأبدأ في تطوير API بعد الانتهاء من المهمة الحالية',
        ]);

        // ─── Notifications ───────────────────────────

        Notification::create([
            'user_id' => $sara->id,
            'type' => 'App\Notifications\TaskAssigned',
            'data' => [
                'task_id' => $task1->id,
                'task_title' => 'تصميم الصفحة الرئيسية',
                'assigned_by' => 'النظام',
                'message' => 'تم تعيينك في مهمة: تصميم الصفحة الرئيسية',
            ],
            'read_at' => now()->subHours(2),
        ]);

        Notification::create([
            'user_id' => $ahmed->id,
            'type' => 'App\Notifications\TaskAssigned',
            'data' => [
                'task_id' => $task2->id,
                'task_title' => 'تطوير واجهة API',
                'assigned_by' => 'سارة أحمد',
                'message' => 'تم تعيينك في مهمة: تطوير واجهة API',
            ],
            'read_at' => now()->subDay(),
        ]);

        Notification::create([
            'user_id' => $layla->id,
            'type' => 'App\Notifications\TaskDueSoon',
            'data' => [
                'task_id' => $task3->id,
                'task_title' => 'كتابة المحتوى التسويقي',
                'due_date' => now()->subDays(2)->toIso8601String(),
                'message' => 'مهمتك تقترب من الموعد النهائي',
            ],
            'read_at' => null,
        ]);

        // ─── Activity Logs ───────────────────────────

        ActivityLog::create([
            'user_id' => $sara->id,
            'subject_type' => Task::class,
            'subject_id' => $task1->id,
            'description' => 'قامت سارة بإنشاء مهمة "تصميم الصفحة الرئيسية"',
            'event' => 'task_created',
            'properties' => ['task_title' => 'تصميم الصفحة الرئيسية', 'project' => 'تصميم الموقع الإلكتروني'],
        ]);

        ActivityLog::create([
            'user_id' => $layla->id,
            'subject_type' => Task::class,
            'subject_id' => $task3->id,
            'description' => 'قامت ليلى بتحديث حالة مهمة "كتابة المحتوى التسويقي" إلى منجز',
            'event' => 'task_status_updated',
            'properties' => ['from' => 'in_progress', 'to' => 'done'],
        ]);

        ActivityLog::create([
            'user_id' => $ahmed->id,
            'subject_type' => Comment::class,
            'subject_id' => 'seed-' . Str::uuid(),
            'description' => 'أضاف أحمد تعليقاً على مهمة "تطوير واجهة API"',
            'event' => 'comment_added',
            'properties' => ['task_id' => $task2->id],
        ]);
    }
}
