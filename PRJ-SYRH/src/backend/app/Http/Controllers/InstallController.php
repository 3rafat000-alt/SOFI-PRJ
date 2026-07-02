<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Web-based 5-step installer wizard.
 *
 * Steps:
 *   1. Requirements   — PHP version, extensions, directory permissions
 *   2. Database       — choose SQLite/MySQL/PostgreSQL, write .env, run migrations
 *   3. Admin account  — create the first super-admin user
 *   4. Settings       — app name, URL, currency; write .env; create storage/installed
 *   5. Complete       — summary of installation
 */
class InstallController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Step 1 — Requirements
    // ─────────────────────────────────────────────────────────────────────────

    public function requirements(): JsonResponse
    {
        $checks = [];

        // PHP version
        $phpVersion = phpversion();
        $checks[] = [
            'name'   => 'PHP >= 8.2',
            'passed' => version_compare($phpVersion, '8.2.0', '>='),
            'value'  => $phpVersion,
        ];

        // Required PHP extensions
        $extensions = ['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'pdo_sqlite', 'tokenizer', 'xml', 'curl', 'gd', 'intl', 'zip'];
        foreach ($extensions as $ext) {
            $checks[] = [
                'name'   => "Extension: {$ext}",
                'passed' => extension_loaded($ext),
            ];
        }

        // Directory permissions
        $dirs = [
            storage_path()              => 'storage/',
            storage_path('app')         => 'storage/app',
            storage_path('framework')   => 'storage/framework',
            storage_path('logs')        => 'storage/logs',
            base_path('bootstrap/cache') => 'bootstrap/cache',
            database_path()             => 'database/',
        ];
        foreach ($dirs as $dir => $label) {
            $checks[] = [
                'name'   => "Directory writable: {$label}",
                'passed' => is_dir($dir) && is_writable($dir),
            ];
        }

        $allPassed = collect($checks)->every(fn ($c) => $c['passed']);

        return response()->json([
            'data' => [
                'checks'    => $checks,
                'allPassed' => $allPassed,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 2 — Database
    // ─────────────────────────────────────────────────────────────────────────

    public function database(): JsonResponse
    {
        return response()->json(['data' => [
            'current' => Config::get('database.default'),
            'drivers' => ['sqlite', 'mysql', 'pgsql'],
        ]]);
    }

    public function databaseStore(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'driver' => 'required|in:sqlite,mysql,pgsql',
            'host'   => 'required_if:driver,mysql,pgsql|string|max:255',
            'port'   => 'required_if:driver,mysql,pgsql|string|max:10',
            'name'   => 'required_if:driver,mysql,pgsql|string|max:255',
            'user'   => 'required_if:driver,mysql,pgsql|string|max:255',
            'password' => 'nullable|string|max:255',
        ])->validate();

        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return response()->json(['success' => false, 'message' => 'ملف .env غير موجود'], 500);
        }

        $driver = $validated['driver'];

        try {
            if ($driver === 'sqlite') {
                // Create SQLite database file
                $dbPath = database_path('database.sqlite');
                if (!file_exists($dbPath)) {
                    touch($dbPath);
                }
                $this->setEnvValue('DB_CONNECTION', 'sqlite');
                $this->setEnvValue('DB_HOST', '');
                $this->setEnvValue('DB_PORT', '');
                $this->setEnvValue('DB_DATABASE', '');
                $this->setEnvValue('DB_USERNAME', '');
                $this->setEnvValue('DB_PASSWORD', '');
            } else {
                $this->setEnvValue('DB_CONNECTION', $driver);
                $this->setEnvValue('DB_HOST', $validated['host']);
                $this->setEnvValue('DB_PORT', $validated['port']);
                $this->setEnvValue('DB_DATABASE', $validated['name']);
                $this->setEnvValue('DB_USERNAME', $validated['user']);
                $this->setEnvValue('DB_PASSWORD', $validated['password'] ?? '');
            }

            // Reboot config
            Artisan::call('config:clear');

            // Verify connection
            DB::connection()->getPdo();

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إعداد قاعدة البيانات بنجاح.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 3 — Admin Account
    // ─────────────────────────────────────────────────────────────────────────

    public function admin(): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email',
            'phone'                 => 'nullable|string|max:20',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ])->validate();

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'] ?? null,
            'password'          => Hash::make($validated['password']),
            'locale'            => 'ar',
            'email_verified_at' => now(),
        ]);

        // Assign admin role
        $user->assignRole('admin');

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء حساب المدير بنجاح.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 4 — Settings & Finalize
    // ─────────────────────────────────────────────────────────────────────────

    public function settings(): JsonResponse
    {
        return response()->json(['data' => [
            'defaults' => [
                'app_name' => Config::get('app.name', 'سوريا هومز'),
                'app_url'  => Config::get('app.url', 'http://localhost'),
                'currency' => Config::get('app.currency', 'USD'),
            ],
        ]]);
    }

    public function settingsStore(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_url'  => 'required|url|max:255',
            'currency' => 'required|string|size:3',
        ])->validate();

        try {
            $this->setEnvValue('APP_NAME', str_replace('"', '', $validated['app_name']));
            $this->setEnvValue('APP_URL', $validated['app_url']);
            $this->setEnvValue('APP_ENV', 'production');
            $this->setEnvValue('APP_DEBUG', 'false');

            // Set currency as custom config
            $this->setEnvValue('APP_CURRENCY', $validated['currency']);

            Artisan::call('config:clear');

            // Seed default data
            Artisan::call('db:seed', ['--force' => true]);

            // Mark installation complete
            File::put(storage_path('installed'), date('Y-m-d H:i:s'));
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل حفظ الإعدادات: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تثبيت المنصة بنجاح!',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 5 — Complete
    // ─────────────────────────────────────────────────────────────────────────

    public function complete(): JsonResponse
    {
        $admin = User::role('admin')->first();

        return response()->json(['data' => [
            'installed_at' => File::exists(storage_path('installed'))
                ? File::get(storage_path('installed'))
                : null,
            'admin_email' => $admin?->email,
            'app_name'    => Config::get('app.name'),
            'app_url'     => Config::get('app.url'),
        ]]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Set (or add) a key=value pair in the .env file.
     */
    private function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        $key = strtoupper($key);
        $escaped = str_replace(['"', '\\'], ['\"', '\\\\'], $value);
        // If value has spaces, wrap in quotes
        if (preg_match('/\s/', $escaped)) {
            $escaped = '"' . $escaped . '"';
        }

        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $content);
        } else {
            $content .= "\n{$key}={$escaped}";
        }

        file_put_contents($envPath, $content);
    }
}
