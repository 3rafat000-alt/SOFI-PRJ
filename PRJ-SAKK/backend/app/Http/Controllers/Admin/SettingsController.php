<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Fee;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Transaction;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog
    ) {}

    /**
     * Allow-list of writable system-setting keys → cast type.
     */
    private const WRITABLE = [
        'maintenance_mode'         => 'boolean',
        'registration_open'        => 'boolean',
        'referral_enabled'         => 'boolean',
        'default_currency'         => 'currency',
        'supported_currencies'     => 'currencies',
        'min_deposit'              => 'decimal',
        'max_deposit'              => 'decimal',
        'min_withdrawal'           => 'decimal',
        'max_withdrawal'           => 'decimal',
        'limit_daily_withdrawal'   => 'decimal',
        'limit_monthly_withdrawal' => 'decimal',
        'limit_card_daily'         => 'decimal',
        'limit_card_monthly'       => 'decimal',
        'withdrawal_fee_percent'   => 'decimal',
        'referral_bonus_referrer'  => 'decimal',
        'referral_bonus_referred'  => 'decimal',
    ];

    /** App update config keys. */
    private const APP_UPDATE_FIELDS = [
        'app_update_enabled' => ['boolean', true],
        'app_force_update'   => ['boolean', false],
        'app_min_build'      => ['integer', 1],
        'app_min_version'    => ['string',  '1.0.0'],
        'app_latest_build'   => ['integer', 1],
        'app_latest_version' => ['string',  '1.0.0'],
        'app_download_url'   => ['string',  ''],
        'app_update_title'   => ['string',  'تحديث مطلوب'],
        'app_update_message' => ['string',  'يتوفّر إصدار جديد من تطبيق صكّ. يرجى التحديث للمتابعة.'],
    ];

    /** Required PHP extensions for the application. */
    private const REQUIRED_EXTENSIONS = [
        'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'gd', 'gmp',
        'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'redis',
        'session', 'tokenizer', 'xml', 'zip',
    ];

    private const CURRENCIES = ['USD', 'SYP'];

    /**
     * min_/max_ key pairs that must never invert (min <= max), and the
     * counterpart key to cross-check against on save.
     */
    private const MIN_MAX_PAIRS = [
        'min_deposit'    => 'max_deposit',
        'max_deposit'    => 'min_deposit',
        'min_withdrawal' => 'max_withdrawal',
        'max_withdrawal' => 'min_withdrawal',
    ];

    /**
     * Upper bound (inclusive) for decimal settings that must not be
     * unbounded. Percent-style keys are capped at 100; the remaining
     * limits get a sane operational ceiling. Keys absent here have no
     * extra cap beyond the base `numeric|min:0` rule.
     */
    private const DECIMAL_MAX = [
        'withdrawal_fee_percent'   => 100,
        'referral_bonus_referrer'  => 10000,
        'referral_bonus_referred'  => 10000,
        'min_deposit'              => 1000000,
        'max_deposit'              => 1000000,
        'min_withdrawal'           => 1000000,
        'max_withdrawal'           => 1000000,
        'limit_daily_withdrawal'   => 1000000,
        'limit_monthly_withdrawal' => 10000000,
        'limit_card_daily'         => 1000000,
        'limit_card_monthly'       => 10000000,
    ];

    public function index(Request $request)
    {
        // ── General settings ──
        $settings = [];
        foreach (array_keys(self::WRITABLE) as $key) {
            $settings[$key] = SystemSetting::get($key);
        }

        // ── Health checks ──
        $checks = $this->runHealthChecks();
        $overallOnline = collect($checks)->every(fn ($c) => $c['status'] === 'online');

        // ── Backup info ──
        $backupFiles = $this->getBackupFiles();
        $dbConnection = config('database.default');
        $dbSize = $this->getDatabaseSize();
        $backupTotalSize = $backupFiles->sum('size');
        $backupTotalFormatted = $this->formatBytes($backupTotalSize);

        // ── App update config ──
        $cfg = [];
        foreach (self::APP_UPDATE_FIELDS as $key => [$type, $default]) {
            $cfg[$key] = SystemSetting::get($key, $default);
        }

        // ── Audit logs (simplified: last 50, with basic filter) ──
        $auditQuery = AuditLog::with('user');
        if ($request->filled('audit_search')) {
            $s = $request->audit_search;
            $auditQuery->where(function ($q) use ($s) {
                $q->where('action', 'like', "%{$s}%")
                  ->orWhere('model_type', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%");
            });
        }
        $auditLogs = $auditQuery->latest()->take(50)->get();

        return view('admin.settings.index', [
            'settings'        => $settings,
            'currencies'      => self::CURRENCIES,
            'stats'           => [
                'users'        => User::count(),
                'transactions' => Transaction::count(),
            ],
            'checks'          => $checks,
            'overallOnline'   => $overallOnline,
            'backupFiles'     => $backupFiles,
            'dbConnection'    => $dbConnection,
            'dbSize'          => $dbSize,
            'backupTotalSize' => $backupTotalFormatted,
            'cfg'             => $cfg,
            'auditLogs'       => $auditLogs,
        ]);
    }

    // ── Health checks ──────────────────────────────────────────

    private function runHealthChecks(): array
    {
        return [
            'database'      => $this->checkDatabase(),
            'cache'         => $this->checkCache(),
            'queue'         => $this->checkQueue(),
            'storage'       => $this->checkStorage(),
            'uptime'        => $this->checkUptime(),
            'schedule'      => $this->checkSchedule(),
            'failed_jobs'   => $this->checkFailedJobs(),
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
        } catch (\Throwable $e) { $details = $e->getMessage(); }
        return [
            'name' => 'قاعدة البيانات', 'name_en' => 'Database',
            'status' => $online ? 'online' : 'offline',
            'response_time' => round((microtime(true) - $start) * 1000, 2),
            'details' => $details, 'icon' => 'storage',
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
            if ($value === 'ok') { $online = true; $details = "السائق: {$driver}"; }
        } catch (\Throwable $e) { $details = $e->getMessage(); }
        return [
            'name' => 'الذاكرة المؤقتة', 'name_en' => 'Cache',
            'status' => $online ? 'online' : 'offline',
            'response_time' => round((microtime(true) - $start) * 1000, 2),
            'details' => $details, 'icon' => 'speed',
        ];
    }

    private function checkQueue(): array
    {
        $start = microtime(true);
        $online = true;
        $details = 'غير متاح';
        $connection = config('queue.default');
        try {
            $queueDb = config('queue.connections.database.table') ?? 'jobs';
            if (DB::getSchemaBuilder()->hasTable($queueDb)) {
                $recent = DB::table($queueDb)->where('created_at', '>=', now()->subMinutes(30))->count();
                $total = DB::table($queueDb)->count();
                $details = "{$total} وظيفة في الطابور ({$recent} حديثة) — {$connection}";
            } else {
                $details = "طابور {$connection} (لا توجد وظائف حالياً)";
            }
        } catch (\Throwable $e) { $details = $e->getMessage(); }
        return [
            'name' => 'طابور المهام', 'name_en' => 'Queue',
            'status' => $online ? 'online' : 'offline',
            'response_time' => round((microtime(true) - $start) * 1000, 2),
            'details' => $details, 'icon' => 'queue',
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
            if ($free === false || $total === false) throw new \RuntimeException('تعذر قراءة مساحة القرص');
            $used = $total - $free;
            $percentUsed = $total > 0 ? round(($used / $total) * 100, 1) : 0;
            $freeFormatted = $this->formatBytes($free);
            $totalFormatted = $this->formatBytes($total);
            $details = "{$freeFormatted} متاح من {$totalFormatted} ({$percentUsed}% مستخدم)";
            $online = $percentUsed < 90;
        } catch (\Throwable $e) { $online = false; $details = $e->getMessage(); }
        return [
            'name' => 'مساحة التخزين', 'name_en' => 'Storage',
            'status' => $online ? 'online' : 'offline',
            'response_time' => round((microtime(true) - $start) * 1000, 2),
            'details' => $details, 'icon' => 'folder',
        ];
    }

    private function checkUptime(): array
    {
        $start = microtime(true);
        $online = true;
        $details = 'غير معروف';
        try {
            $appPath = base_path('.env');
            if (file_exists($appPath)) {
                $modified = filemtime($appPath);
                $lastDeploy = \Carbon\Carbon::createFromTimestamp($modified);
                $diff = $lastDeploy->diffForHumans();
                $details = "آخر تحديث: {$lastDeploy->format('Y-m-d H:i')} (منذ {$diff})";
            }
            if (file_exists(storage_path('framework/down'))) { $online = false; $details = '⚠️ وضع الصيانة نشط'; }
        } catch (\Throwable $e) { $details = $e->getMessage(); }
        return [
            'name' => 'وقت التشغيل', 'name_en' => 'Uptime',
            'status' => $online ? 'online' : 'offline',
            'response_time' => round((microtime(true) - $start) * 1000, 2),
            'details' => $details, 'icon' => 'schedule',
        ];
    }

    private function checkSchedule(): array
    {
        $start = microtime(true);
        $online = true;
        $details = 'غير معروف';
        try {
            $lastRun = Cache::get('laravel_scheduler_last_run');
            if ($lastRun) {
                $lastRunTime = \Carbon\Carbon::parse($lastRun);
                $diffInMinutes = $lastRunTime->diffInMinutes(now());
                $online = $diffInMinutes <= 5;
                $details = "آخر تشغيل: {$lastRunTime->format('Y-m-d H:i')} (منذ {$diffInMinutes} دقيقة)";
            } else {
                $details = 'ملف Artisan موجود (قد لا يكون جدول المهام مفعلاً)';
            }
        } catch (\Throwable $e) { $details = $e->getMessage(); }
        return [
            'name' => 'جدول المهام', 'name_en' => 'Schedule',
            'status' => $online ? 'online' : 'offline',
            'response_time' => round((microtime(true) - $start) * 1000, 2),
            'details' => $details, 'icon' => 'event_repeat',
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
                } else { $details = '0 — لا توجد وظائف فاشلة'; }
                if ($count > 10) { $online = false; }
            } else { $details = '0 — لا يوجد جدول للوظائف الفاشلة'; }
        } catch (\Throwable $e) { $details = $e->getMessage(); }
        return [
            'name' => 'الوظائف الفاشلة', 'name_en' => 'Failed Jobs',
            'status' => $online ? 'online' : 'offline',
            'response_time' => round((microtime(true) - $start) * 1000, 2),
            'details' => $details, 'icon' => 'error_outline',
        ];
    }

    private function checkPhpExtensions(): array
    {
        $start = microtime(true);
        $details = '';
        try {
            $missing = [];
            $installed = 0;
            foreach (self::REQUIRED_EXTENSIONS as $ext) {
                extension_loaded($ext) ? $installed++ : ($missing[] = $ext);
            }
            $total = count(self::REQUIRED_EXTENSIONS);
            $online = empty($missing);
            $details = "{$installed}/{$total} مثبتة";
            if (!empty($missing)) { $details .= ' — المفقودة: ' . implode('، ', $missing); }
        } catch (\Throwable $e) { $online = false; $details = $e->getMessage(); }
        return [
            'name' => 'إضافات PHP', 'name_en' => 'PHP Extensions',
            'status' => $online ? 'online' : 'offline',
            'response_time' => round((microtime(true) - $start) * 1000, 2),
            'details' => $details, 'icon' => 'extension',
        ];
    }

    // ── Backup helpers ─────────────────────────────────────────

    private function getBackupFiles(): \Illuminate\Support\Collection
    {
        $backupDisk = 'local';
        $backupDir = 'backups';
        return collect(Storage::disk($backupDisk)->files($backupDir))
            ->filter(fn ($f) => str_ends_with($f, '.sql') || str_ends_with($f, '.sqlite') || str_ends_with($f, '.gz'))
            ->map(fn ($f) => [
                'filename' => basename($f),
                'path' => $f,
                'size' => Storage::disk($backupDisk)->size($f),
                'size_formatted' => $this->formatBytes(Storage::disk($backupDisk)->size($f)),
                'date' => Storage::disk($backupDisk)->lastModified($f),
                'date_formatted' => date('Y-m-d H:i:s', Storage::disk($backupDisk)->lastModified($f)),
            ])
            ->sortByDesc('date')
            ->values();
    }

    private function getDatabaseSize(): string
    {
        $dbConnection = config('database.default');
        if ($dbConnection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (file_exists($dbPath)) { return $this->formatBytes(filesize($dbPath)); }
        } elseif ($dbConnection === 'mysql') {
            try {
                $db = config('database.connections.mysql.database');
                $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = ?", [$db]);
                if (!empty($result)) { return $result[0]->size_mb . ' MB'; }
            } catch (\Exception $e) { return 'غير معروف'; }
        }
        return 'غير معروف';
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

    // ── Live-save endpoints ────────────────────────────────────

    /**
     * Generic autosave for a single allow-listed system setting.
     */
    public function updateSetting(Request $request)
    {
        $key = (string) $request->input('key');

        if (!array_key_exists($key, self::WRITABLE)) {
            return response()->json(['ok' => false, 'message' => 'إعداد غير معروف'], 422);
        }

        $type = self::WRITABLE[$key];
        $oldValue = SystemSetting::get($key);

        switch ($type) {
            case 'boolean':
                $value = $request->boolean('value') ? '1' : '0';
                SystemSetting::set($key, $value, 'boolean');
                break;

            case 'decimal':
                $max = self::DECIMAL_MAX[$key] ?? null;
                $rawValue = $request->input('value');

                if ($rawValue === null || $rawValue === '') {
                    return response()->json(['ok' => false, 'message' => 'القيمة مطلوبة.'], 422);
                }
                if (!is_numeric($rawValue)) {
                    return response()->json(['ok' => false, 'message' => 'القيمة يجب أن تكون رقماً.'], 422);
                }

                $newValue = (float) $rawValue;

                if ($newValue < 0) {
                    return response()->json(['ok' => false, 'message' => 'القيمة يجب أن تكون 0 على الأقل.'], 422);
                }
                if ($max !== null && $newValue > $max) {
                    return response()->json([
                        'ok' => false,
                        'message' => "القيمة يجب أن لا تتجاوز {$max}.",
                    ], 422);
                }

                // Cross-field guard: a min_/max_ pair must never invert
                // (e.g. min_withdrawal > max_withdrawal would lock users out).
                if (array_key_exists($key, self::MIN_MAX_PAIRS)) {
                    $counterpartKey = self::MIN_MAX_PAIRS[$key];
                    $counterpartValue = (float) SystemSetting::get($counterpartKey, 0);
                    $isMinKey = str_starts_with($key, 'min_');

                    $invalid = $isMinKey
                        ? $newValue > $counterpartValue
                        : $newValue < $counterpartValue;

                    if ($invalid) {
                        return response()->json([
                            'ok' => false,
                            'message' => $isMinKey
                                ? 'القيمة الدنيا يجب أن لا تتجاوز القيمة القصوى.'
                                : 'القيمة القصوى يجب أن لا تقل عن القيمة الدنيا.',
                        ], 422);
                    }
                }

                SystemSetting::set($key, (string) $newValue, 'decimal');
                break;

            case 'currency':
                $value = (string) $request->input('value');
                if (!in_array($value, self::CURRENCIES, true)) {
                    return response()->json(['ok' => false, 'message' => 'عملة غير مدعومة'], 422);
                }
                SystemSetting::set($key, $value, 'string');
                break;

            case 'currencies':
                $value = (array) $request->input('value', []);
                $value = array_values(array_intersect(self::CURRENCIES, $value));
                if (empty($value)) {
                    return response()->json(['ok' => false, 'message' => 'اختر عملة واحدة على الأقل'], 422);
                }
                SystemSetting::set($key, $value, 'json');
                break;

            default:
                return response()->json(['ok' => false, 'message' => 'نوع غير مدعوم'], 422);
        }

        $newValue = SystemSetting::get($key);

        $this->auditLog->log(
            action: 'settings.update',
            modelType: 'SystemSetting',
            modelId: $key,
            changes: ['before' => [$key => $oldValue], 'after' => [$key => $newValue]],
        );

        return response()->json([
            'ok' => true,
            'message' => 'تم الحفظ',
            'value' => $newValue,
        ]);
    }

    // ── Cache control ──────────────────────────────────────────

    public function clearCache()
    {
        Artisan::call('cache:clear');

        $this->auditLog->log(
            action: 'cache.clear',
            modelType: 'SystemSetting',
            modelId: 0,
        );

        return back()->with('success', 'تم مسح التخزين المؤقت');
    }

    public function optimizeCache()
    {
        Artisan::call('optimize');

        $this->auditLog->log(
            action: 'cache.optimize',
            modelType: 'SystemSetting',
            modelId: 0,
        );

        return back()->with('success', 'تم تحسين التخزين المؤقت');
    }
}
