<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $workspaces = Workspace::query()
            ->with('owner:id,name,email')
            ->withCount(['members', 'projects'])
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.workspaces.index', ['workspaces' => $workspaces, 'q' => $request->q]);
    }

    public function show(string $id): View
    {
        $workspace = Workspace::with(['owner:id,name,email', 'members:id,name,email'])
            ->withCount(['members', 'projects'])
            ->findOrFail($id);

        return view('admin.workspaces.show', ['workspace' => $workspace]);
    }
}
