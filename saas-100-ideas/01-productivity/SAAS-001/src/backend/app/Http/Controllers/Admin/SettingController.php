<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => [
                'app_name' => SystemSetting::get('app_name', 'TaskSync Pro'),
                'support_email' => SystemSetting::get('support_email', 'support@tasksyncpro.com'),
                'free_plan_max_members' => SystemSetting::get('free_plan_max_members', 5),
                'registration_open' => SystemSetting::get('registration_open', true),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'support_email' => ['required', 'email'],
            'free_plan_max_members' => ['required', 'integer', 'min:1', 'max:100'],
            'registration_open' => ['nullable', 'boolean'],
        ]);

        SystemSetting::set('app_name', $data['app_name'], 'string', 'general');
        SystemSetting::set('support_email', $data['support_email'], 'string', 'general');
        SystemSetting::set('free_plan_max_members', $data['free_plan_max_members'], 'int', 'plans');
        SystemSetting::set('registration_open', $request->boolean('registration_open'), 'bool', 'general');

        return back()->with('status', 'تم حفظ الإعدادات بنجاح.');
    }
}
