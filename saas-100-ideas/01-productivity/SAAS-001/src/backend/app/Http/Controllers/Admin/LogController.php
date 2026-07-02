<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->when($request->event, fn ($q) => $q->where('event', $request->event))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'events' => ActivityLog::query()->distinct()->pluck('event')->filter()->values(),
            'event' => $request->event,
        ]);
    }
}
