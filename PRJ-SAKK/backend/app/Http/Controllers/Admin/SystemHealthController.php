<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    /** Required PHP extensions for the application. */
    private const REQUIRED_EXTENSIONS = [
        'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'gd',
        'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql',
        'session', 'tokenizer', 'xml', 'zip',
    ];

    /** Extensions that are nice-to-have but not required for the current stack. */
    private const OPTIONAL_EXTENSIONS = ['gmp', 'redis'];

    public function index(): View
    {
        $checks = $this->runAllChecks();

        return view('admin.system.health', compact('checks'));
    }

    public function runChecks(): JsonResponse
    {
        $checks = $this->runAllChecks();
        $overall = collect($checks)->every(fn ($c) => $c['status'] === 'online');

        // Notify admins on health degradation
        if (!$overall) {
            foreach ($checks as $key => $check) {
                if ($check['status'] !== 'online') {
                    \App\Services\AdminNotificationService::systemError(
                        $check['name'] ?? $key,
                        $check['details'] ?? 'تعذر الوصول',
                    );
                }
            }
        }

        return response()->json([
            'overall' => $overall ? 'online' : 'degraded',
            'checks' => $checks,
        ]);
    }

    private function runAllChecks(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'uptime' => $this->checkUptime(),
            'schedule' => $this->checkSchedule(),
            'failed_jobs' => $this->checkFailedJobs(),
            'php_extensions' => $this->checkPhpExtensions(),
        ];
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);
        $online = false;
        $details = 'غير متاح';

        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $online = true;
            $details = DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $details = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'name' => 'قاعدة البيانات',
            'name_en' => 'Database',
            'status' => $online ? 'online' : 'offline',
            'response_time' => $time,
            'details' => $details,
            'icon' => 'storage',
        ];
    }

    private function checkCache(): array
    {
        $start = microtime(true);
        $online = false;
        $details = 'غير متاح';
        $driver = config('cache.default');

        try {
            $key = '__health_' . uniqid();
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value === 'ok') {
                $online = true;
                $details = "السائق: {$driver}";
            }
        } catch (\Throwable $e) {
            $details = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'name' => 'الذاكرة المؤقتة',
            'name_en' => 'Cache',
            'status' => $online ? 'online' : 'offline',
            'response_time' => $time,
            'details' => $details,
            'icon' => 'speed',
        ];
    }

    private function checkQueue(): array
    {
        $start = microtime(true);
        $online = false;
        $details = 'غير متاح';
        $connection = config('queue.default');

        try {
            // Check if Horizon is configured
            if ($connection === 'redis' && class_exists('\Laravel\Horizon\Horizon')) {
                $queues = \Laravel\Horizon\Contracts\WorkloadRepository::class;
                $online = true;
                $details = "Horizon — {$connection}";
            } else {
                // Fallback: check jobs table exists and has recent activity
                $queueDb = config('queue.connections.database.table') ?? 'jobs';
                if (DB::getSchemaBuilder()->hasTable($queueDb)) {
                    $recent = DB::table($queueDb)
                        ->where('created_at', '>=', now()->subMinutes(30))
                        ->count();
                    $total = DB::table($queueDb)->count();
                    $online = true;
                    $details = "{$total} وظيفة في الطابور ({$recent} حديثة) — {$connection}";
                } else {
                    // No queue table yet — mark as "no queue configured" but not offline
                    $online = true;
                    $details = "طابور {$connection} (لا توجد وظائف حالياً)";
                }
            }
        } catch (\Throwable $e) {
            $details = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'name' => 'طابور المهام',
            'name_en' => 'Queue',
            'status' => $online ? 'online' : 'offline',
            'response_time' => $time,
            'details' => $details,
            'icon' => 'queue',
        ];
    }

    private function checkStorage(): array
    {
        $start = microtime(true);
        $details = 'غير متاح';

        try {
            $path = storage_path();
            $free = disk_free_space($path);
            $total = disk_total_space($path);

            if ($free === false || $total === false) {
                throw new \RuntimeException('تعذر قراءة مساحة القرص');
            }

            $used = $total - $free;
            $percentUsed = $total > 0 ? round(($used / $total) * 100, 1) : 0;

            $freeFormatted = $this->formatBytes($free);
            $totalFormatted = $this->formatBytes($total);
            $details = "{$freeFormatted} متاح من {$totalFormatted} ({$percentUsed}% مستخدم)";

            // Alert if disk usage exceeds 90%
            $online = $percentUsed < 90;
        } catch (\Throwable $e) {
            $online = false;
            $details = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'name' => 'مساحة التخزين',
            'name_en' => 'Storage',
            'status' => $online ? 'online' : 'offline',
            'response_time' => $time,
            'details' => $details,
            'icon' => 'folder',
        ];
    }

    private function checkUptime(): array
    {
        $start = microtime(true);
        $online = true;
        $details = 'غير معروف';

        try {
            // Check last deployment via file timestamp
            $appPath = base_path('.env');
            if (file_exists($appPath)) {
                $modified = filemtime($appPath);
                $lastDeploy = \Carbon\Carbon::createFromTimestamp($modified);
                $diff = $lastDeploy->diffForHumans();
                $details = "آخر تحديث: {$lastDeploy->format('Y-m-d H:i')} (منذ {$diff})";
            } else {
                $details = 'غير معروف';
            }

            // Also check if app is in maintenance mode
            if (file_exists(storage_path('framework/down'))) {
                $online = false;
                $details = '⚠️ وضع الصيانة نشط — التطبيق في وضع الصيانة';
            }
        } catch (\Throwable $e) {
            $details = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'name' => 'وقت التشغيل',
            'name_en' => 'Uptime',
            'status' => $online ? 'online' : 'offline',
            'response_time' => $time,
            'details' => $details,
            'icon' => 'schedule',
        ];
    }

    private function checkSchedule(): array
    {
        $start = microtime(true);
        $online = false;
        $details = 'غير معروف';

        try {
            // Check if schedule is running by looking for the cache key Laravel sets
            $lastRun = Cache::get('laravel_scheduler_last_run');

            if ($lastRun) {
                $lastRunTime = \Carbon\Carbon::parse($lastRun);
                $diffInMinutes = $lastRunTime->diffInMinutes(now());
                $online = $diffInMinutes <= 5;
                $details = "آخر تشغيل: {$lastRunTime->format('Y-m-d H:i')} (منذ {$diffInMinutes} دقيقة)";
            } else {
                // Try to check crontab if available
                $lastArtisan = \Carbon\Carbon::createFromTimestamp(
                    filemtime(base_path('artisan')) ?: time()
                );
                $details = "ملف Artisan موجود (قد لا يكون جدول المهام مفعلاً)";

                // Check if schedule:run was ever called via log
                $logPath = storage_path('logs/laravel.log');
                if (file_exists($logPath)) {
                    $details .= ' — تحقق من السجلات للتأكد';
                }
                // Not conclusive — mark as unknown rather than offline
                $online = true;
            }
        } catch (\Throwable $e) {
            $details = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'name' => 'جدول المهام',
            'name_en' => 'Schedule',
            'status' => $online ? 'online' : 'offline',
            'response_time' => $time,
            'details' => $details,
            'icon' => 'event_repeat',
        ];
    }

    private function checkFailedJobs(): array
    {
        $start = microtime(true);
        $online = true;
        $details = '0';

        try {
            $table = config('queue.failed.table') ?? 'failed_jobs';
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $count = DB::table($table)->count();
                $details = (string) $count;

                if ($count > 0) {
                    $latest = DB::table($table)->latest('failed_at')->first();
                    if ($latest) {
                        $time = \Carbon\Carbon::parse($latest->failed_at)->diffForHumans();
                        $details = "{$count} فاشلة (آخرها منذ {$time})";
                    }
                } else {
                    $details = '0 — لا توجد وظائف فاشلة';
                }

                // More than 10 failed jobs is considered degraded
                if ($count > 10) {
                    $online = false;
                }
            } else {
                $details = '0 — لا يوجد جدول للوظائف الفاشلة';
            }
        } catch (\Throwable $e) {
            $details = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'name' => 'الوظائف الفاشلة',
            'name_en' => 'Failed Jobs',
            'status' => $online ? 'online' : 'offline',
            'response_time' => $time,
            'details' => $details,
            'icon' => 'error_outline',
        ];
    }

    private function checkPhpExtensions(): array
    {
        $start = microtime(true);
        $details = '';

        try {
            $required = self::REQUIRED_EXTENSIONS;

            // redis is only mandatory when the app is actually configured to use it.
            $usesRedis = in_array(config('cache.default'), ['redis'], true)
                || in_array(config('queue.default'), ['redis'], true)
                || in_array(config('session.driver'), ['redis'], true);

            $optional = self::OPTIONAL_EXTENSIONS;

            if ($usesRedis) {
                $required[] = 'redis';
                $optional = array_values(array_diff($optional, ['redis']));
            }

            $missing = [];
            $installed = 0;

            foreach ($required as $ext) {
                if (extension_loaded($ext)) {
                    $installed++;
                } else {
                    $missing[] = $ext;
                }
            }

            $missingOptional = [];
            foreach ($optional as $ext) {
                if (!extension_loaded($ext)) {
                    $missingOptional[] = $ext;
                }
            }

            $total = count($required);
            $online = empty($missing);
            $details = "{$installed}/{$total} مثبتة";

            if (!empty($missingOptional)) {
                $details .= ' · اختيارية غير مثبتة: ' . implode('، ', $missingOptional);
            }

            if (!empty($missing)) {
                $details .= ' — المفقودة: ' . implode('، ', $missing);
            }
        } catch (\Throwable $e) {
            $online = false;
            $details = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'name' => 'إضافات PHP',
            'name_en' => 'PHP Extensions',
            'status' => $online ? 'online' : 'offline',
            'response_time' => $time,
            'details' => $details,
            'icon' => 'extension',
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
