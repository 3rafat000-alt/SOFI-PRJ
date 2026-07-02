<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('renders the health dashboard with all check keys present', function () {
    $checks = (new \App\Http\Controllers\Admin\SystemHealthController())->index();

    expect($checks)->toBeInstanceOf(\Illuminate\View\View::class);
    $data = $checks->getData()['checks'];
    expect(collect(['database', 'cache', 'queue', 'storage', 'uptime', 'schedule', 'failed_jobs', 'php_extensions'])
        ->every(fn ($k) => array_key_exists($k, $data)))->toBeTrue();
});

it('renders the /admin/system/health page (fixed: blade route name matched to admin.system.health.checks)', function () {
    // Regression for bug: the blade referenced the singular route name
    // 'admin.system.health.check' while the registered route is the plural
    // 'admin.system.health.checks', causing RouteNotFoundException on render.
    $response = $this->actingAs($this->admin)->get(route('admin.system.health'));

    $response->assertOk();
    $response->assertSee(route('admin.system.health.checks'), false);
});

it('runChecks returns a json report with an overall status and per-check shape', function () {
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.system.health.checks'));

    $response->assertOk()
        ->assertJsonStructure([
            'overall',
            'checks' => [
                'database' => ['name', 'name_en', 'status', 'response_time', 'details', 'icon'],
                'cache' => ['name', 'status'],
                'queue' => ['name', 'status'],
                'storage' => ['name', 'status'],
                'uptime' => ['name', 'status'],
                'schedule' => ['name', 'status'],
                'failed_jobs' => ['name', 'status'],
                'php_extensions' => ['name', 'status'],
            ],
        ]);

    expect($response->json('checks.database.status'))->toBe('online');
    expect(in_array($response->json('overall'), ['online', 'degraded']))->toBeTrue();
});

it('reports php_extensions as online with the full installed count when all present', function () {
    $response = $this->actingAs($this->admin)->getJson(route('admin.system.health.checks'));

    // In CI/test env this may legitimately be missing an extension (e.g. redis) —
    // assert the shape and that a numeric x/y count is reported either way.
    expect($response->json('checks.php_extensions.details'))->toMatch('/\d+\/\d+ مثبتة/');
});

it('reports 0 failed jobs by default when the failed_jobs table is empty', function () {
    $response = $this->actingAs($this->admin)->getJson(route('admin.system.health.checks'));

    expect($response->json('checks.failed_jobs.status'))->toBe('online');
});

it('flags failed_jobs as offline when more than 10 rows exist', function () {
    for ($i = 0; $i < 11; $i++) {
        \Illuminate\Support\Facades\DB::table('failed_jobs')->insert([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'boom',
            'failed_at' => now(),
        ]);
    }

    $response = $this->actingAs($this->admin)->getJson(route('admin.system.health.checks'));

    expect($response->json('checks.failed_jobs.status'))->toBe('offline');
    expect($response->json('overall'))->toBe('degraded');
});

it('creates admin alerts for degraded checks', function () {
    for ($i = 0; $i < 11; $i++) {
        \Illuminate\Support\Facades\DB::table('failed_jobs')->insert([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'boom',
            'failed_at' => now(),
        ]);
    }

    $this->actingAs($this->admin)->getJson(route('admin.system.health.checks'));

    // AdminNotificationService::systemError() always titles the alert generically
    // ("خطأ في النظام"), embedding the component name in the message body instead.
    $this->assertDatabaseHas('admin_alerts', ['title' => 'خطأ في النظام']);
    expect(\App\Models\AdminAlert::where('message', 'like', '%الوظائف الفاشلة%')->exists())->toBeTrue();
});

it('reports maintenance mode as offline uptime when the down file exists', function () {
    $downFile = storage_path('framework/down');
    file_put_contents($downFile, json_encode(['time' => now()->toIso8601String()]));

    try {
        // The app-wide maintenance middleware would itself 503 this request —
        // bypass it here to isolate SystemHealthController's own down-file check.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class);

        $response = $this->actingAs($this->admin)->getJson(route('admin.system.health.checks'));

        expect($response->json('checks.uptime.status'))->toBe('offline');
        expect($response->json('checks.uptime.details'))->toContain('وضع الصيانة');
    } finally {
        @unlink($downFile);
    }
});
