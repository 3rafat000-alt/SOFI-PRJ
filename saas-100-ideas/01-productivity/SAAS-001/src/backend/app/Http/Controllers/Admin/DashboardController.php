<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'workspaces' => Workspace::count(),
                'projects' => Project::count(),
                'tasks' => Task::count(),
                'done_tasks' => Task::where('status', 'done')->count(),
            ],
            'recentUsers' => User::latest()->limit(5)->get(),
            'recentActivity' => ActivityLog::with('user:id,name')->latest()->limit(8)->get(),
        ]);
    }
}
