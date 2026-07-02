<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $tasks = Task::query()
            ->with(['project:id,name', 'creator:id,name'])
            ->when($request->q, fn ($q) => $q->where('title', 'like', "%{$request->q}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.tasks.index', [
            'tasks' => $tasks,
            'q' => $request->q,
            'status' => $request->status,
        ]);
    }
}
