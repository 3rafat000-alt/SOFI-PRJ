<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\Installer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InstallController extends Controller
{
    /** Step 1 — environment requirements. */
    public function index(): View
    {
        return view('install.requirements', [
            'step' => 1,
            'checks' => $this->requirementChecks(),
            'canProceed' => collect($this->requirementChecks())->every(fn ($c) => $c['passed']),
        ]);
    }

    /** Step 2 — database setup form. */
    public function database(): View
    {
        return view('install.database', [
            'step' => 2,
            'current' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => config('database.connections.mysql.database'),
                'username' => config('database.connections.mysql.username'),
            ],
        ]);
    }

    /** Step 2 (submit) — run migrations + seed against the configured database. */
    public function runDatabase(Request $request): RedirectResponse
    {
        $request->validate([
            'connection' => ['required', 'in:sqlite,mysql,pgsql'],
            'seed' => ['nullable', 'boolean'],
        ]);

        try {
            if ($request->connection === 'sqlite') {
                $path = database_path('database.sqlite');
                if (! file_exists($path)) {
                    touch($path);
                }
            }

            Artisan::call('migrate', ['--force' => true]);

            if ($request->boolean('seed')) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['database' => 'فشل تهيئة قاعدة البيانات: '.$e->getMessage()]);
        }

        return redirect()->route('install.admin');
    }

    /** Step 3 — create the super-admin. */
    public function admin(): View
    {
        return view('install.admin', ['step' => 3]);
    }

    /** Step 3 (submit) — persist admin, write lock file, finish. */
    public function createAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        Admin::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'is_super' => true,
            ],
        );

        Installer::markInstalled([
            'admin_email' => $data['email'],
            'connection' => config('database.default'),
        ]);

        return redirect()->route('install.complete');
    }

    /** Step 4 — done. */
    public function complete(): View
    {
        return view('install.complete', ['step' => 4]);
    }

    /**
     * @return array<int, array{label: string, passed: bool, hint: string}>
     */
    private function requirementChecks(): array
    {
        return [
            [
                'label' => 'PHP >= 8.3 (الحالي '.PHP_VERSION.')',
                'passed' => version_compare(PHP_VERSION, '8.3.0', '>='),
                'hint' => 'حدّث إصدار PHP',
            ],
            ['label' => 'إضافة PDO', 'passed' => extension_loaded('pdo'), 'hint' => 'فعّل pdo'],
            ['label' => 'إضافة Mbstring', 'passed' => extension_loaded('mbstring'), 'hint' => 'فعّل mbstring'],
            ['label' => 'إضافة OpenSSL', 'passed' => extension_loaded('openssl'), 'hint' => 'فعّل openssl'],
            ['label' => 'إضافة JSON', 'passed' => extension_loaded('json'), 'hint' => 'فعّل json'],
            [
                'label' => 'مجلد storage قابل للكتابة',
                'passed' => is_writable(storage_path()),
                'hint' => 'chmod -R 775 storage',
            ],
            [
                'label' => 'مجلد bootstrap/cache قابل للكتابة',
                'passed' => is_writable(base_path('bootstrap/cache')),
                'hint' => 'chmod -R 775 bootstrap/cache',
            ],
            [
                'label' => 'مفتاح التطبيق APP_KEY مضبوط',
                'passed' => ! empty(config('app.key')),
                'hint' => 'php artisan key:generate',
            ],
        ];
    }
}
