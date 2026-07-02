<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::query()
            ->with(['workspace:id,name', 'creator:id,name'])
            ->withCount('tasks')
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.projects.index', ['projects' => $projects, 'q' => $request->q]);
    }
}
