<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withTrashed()
            ->withCount(['createdTasks', 'workspaces'])
            ->when($request->q, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->q}%")
                ->orWhere('email', 'like', "%{$request->q}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'q' => $request->q]);
    }

    public function destroy(string $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('status', "تم تعطيل المستخدم: {$user->name}");
    }

    public function restore(string $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('status', "تمت إعادة تفعيل المستخدم: {$user->name}");
    }
}
